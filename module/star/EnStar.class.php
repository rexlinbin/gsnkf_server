<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnStar.class.php 139450 2014-11-11 03:45:21Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/EnStar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-11 03:45:21 +0000 (Tue, 11 Nov 2014) $
 * @version $Revision: 139450 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : EnStar
 * Description : 名将系统内部接口类
 * Inherit     :
 **********************************************************************************************************************/
class EnStar
{
	/**
	 * 给用户增加新的名将
	 * 加名将必须有相应的武将，由武将触发，不检测
	 * 
	 * @param int $uid								用户id
	 * @param int $stid								名将模板id
	 * @throws Exception
	 * @return array $sid => $stid					名将id =>名将模板id, 如果是重复名将就返回空array
	 */
	public static function addNewStar($uid, $stid)
	{
		// 参数检查
		if(empty($uid) || empty($stid))
		{
			throw new FakeException('Err para, uid:%d stid:%d!', $uid, $stid);
		}
		
		// 获取当前用户线程，非用户线程，不能加名将 
		if ($uid != RPCContext::getInstance()->getUid()) 
		{
			throw new FakeException('User:%d is not in the current session!', $uid);
		}
		
		//检查激活名将所需武将
		if (!isset(btstore_get()->STAR[$stid])) 
		{
			throw new ConfigException('star template id:%d is not exist!', $stid);
		}
		
		// 获取当前用户的所有名将模板id
		$myStar = MyStar::getInstance($uid);
		$allStid = $myStar->getAllStarTid();
		
		if (empty($allStid)) 
		{
			// 数据库还没有用户数据，需要进行初始化
			$myStar->initInfo();
		}
		else
		{
			// 否则，判断用户是否已有这个名将了
			if (in_array($stid, $allStid)) 
			{
				// 不能重复添加同一种名将
				return array();
			}
		}
		// 增加新的名将
		$sid = $myStar->addNewStar($stid);
		
		return array($sid => $stid);
	}
	
	/**
	 * 获取名将对武将的属性加成
	 * 
	 * @param int $uid								用户id
	 * @param int $htid								武将模板id
	 * @return array mixed
	 */
	public static function getStarAttr($uid, $htid)
	{
		// 参数检查：用户id和武将模板id不为空
		if(empty($uid) || empty($htid))
		{
			throw new FakeException('Err para, uid:%d htid:%d!', $uid, $htid);
		}
		
		// 获得用户的所有名将信息
		$myStar = MyStar::getInstance($uid);
		$allStarInfo = $myStar->getAllInfo();
		
		// 用户没有名将
		if (empty($allStarInfo)) 
		{
 			// 名将表里没有用户的数据
 			Logger::trace('User has no star!');	
 			return array();
		}
		//获得武将对应的名将模板id
		$stid = Creature::getCreatureConf($htid, CreatureAttr::STAR_ID);
		if (empty($stid))
		{
			return array();
		}
		$level = 0;
		foreach ($allStarInfo[StarDef::STAR_LIST] as $sid => $starInfo)
		{
			if ($starInfo[StarDef::STAR_TID] == $stid)
			{
				$level = $starInfo[StarDef::STAR_LEVEL];
				break;
			}
		}
		$attrInfo = array();
		for ($i = 1; $i <= $level; $i++)
		{
			$abilityConf = StarLogic::getAbilityConf($stid, $i);
			if (!empty($abilityConf))
			{
				$attrInfo[] = $abilityConf[StarDef::STAR_ABILITY_ATTR];
			}
		}
	
		// 统计每个属性的总加成值
		$attrInfo = Util::arrayAdd2V($attrInfo);
		// 将属性id转成属性名称
		$attrInfo = HeroUtil::adaptAttr($attrInfo);
		return $attrInfo;	
	}
	
	/**
	 * 获取用户所有名将的好感度等级总数
	 *
	 * @return int $totalFavor						好感度等级总数
	 */
	public static function getAllStarFavor($uid = 0)
	{
		if (empty($uid)) 
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		
		// 获得用户的所有名将信息
		$myStar = MyStar::getInstance($uid);
		$allStarInfo = $myStar->getAllInfo();
		
		$totalFavor = 0;
		// 用户没有名将
		if (empty($allStarInfo))
		{
			// 名将表里没有用户的数据
			Logger::trace('User has no star!');
			return $totalFavor;
		}
		
		foreach ($allStarInfo[StarDef::STAR_LIST] as $star)
		{
			$totalFavor += $star[StarDef::STAR_LEVEL];
		}		
		return $totalFavor;
	}
	
	public static function getUserStarLevel($uid, $stid)
	{
		// 获得用户的所有名将信息
		$myStar = MyStar::getInstance($uid);
		$allStarInfo = $myStar->getAllInfo();
		
		$level = 0;
		// 用户没有名将
		if (empty($allStarInfo))
		{
			// 名将表里没有用户的数据
			Logger::trace('User has no star!');
			return $level;
		}
		
		foreach ($allStarInfo[StarDef::STAR_LIST] as $star)
		{
			if ($star[StarDef::STAR_TID] == $stid) 
			{
				$level = $star[StarDef::STAR_LEVEL];
				break;
			}
		}

		return $level;
	}
	
	/**
	 * 获得主角装备的其他武将的技能
	 * 
	 * @param int $uid
	 * @return array $skills
	 * {
	 * 		attackSkill:普通技能
	 * 		rageSkill:怒气技能
	 * }
	 */
	public static function getMasterSkill($uid)
	{
		$skills = array();
		
		$myStar = MyStar::getInstance($uid);
		$sid = $myStar->getEquipSkill();
		if (!empty($sid)) 
		{
			$stid = $myStar->getStarStid($sid);
			$skills[PropertyKey::ATTACK_SKILL] = StarLogic::getNormalSkill($uid, $stid);
			$skills[PropertyKey::RAGE_SKILL] = $myStar->getStarFeelSkill($sid);
		}
		
		return $skills;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */