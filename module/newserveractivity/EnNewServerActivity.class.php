<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnNewServerActivity.class.php 243720 2016-05-21 09:40:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/EnNewServerActivity.class.php $
 * @author $Author: wuqilin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-21 09:40:51 +0000 (Sat, 21 May 2016) $
 * @version $Revision: 243720 $
 * @brief “开服7天乐”En接口
 *  
 **/
class EnNewServerActivity
{
	/**
	 * @param $uid
	 * @param int $userLv 主将等级
	 * @desc 任务类型：101.主角等级达到某数值，
	 */
	public static function updateUserLevel($uid, $userLv)
	{
		try 
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::USER_LEVEL);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::USER_LEVEL, $userLv);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateUserLevel fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $hadRecruitNum 神将抽将的累计次数
	 * @desc 任务类型：102.神将抽将N次
	 */
	public static function updateGoldRecruit($uid, $hadRecruitNum)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::GOLD_RECRUIT);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::GOLD_RECRUIT, $hadRecruitNum);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateGoldRecruit fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $fightForce 战斗力最新值
	 * @desc 任务类型：103.战斗力达到N万
	 */
	public static function updateFightForce($uid, $fightForce)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::FIGHT_FORCE);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::FIGHT_FORCE, $fightForce);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateFightForce fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $baseId 据点id
	 * @desc 任务类型：104.主线副本
	 */
	public static function updatePassCopy($uid, $baseId)
	{	
		try 
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::PASS_COPY);
			if ($isInConf)
			{
				// 副本id $copyId 当做是 updateTask中的$finishNum
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::PASS_COPY, $baseId, NewServerActivityDef::COPYTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updatePassCopy fail, Exception:%s', $e);
		}
	}
	
	/**
	 * $num个上阵武将的装备全部强化到 $level等级
	 * @param $uid
	 * @param $num int num个上阵武将
	 * @param $level int 这num个上阵武将身上穿戴的装备的最小等级
	 * 
	 * 目前暂时只有任务类型：105.6个上阵武将身上穿戴的装备强化都达到level级
	 */
	public static function updateFriendEquipStrong($uid, $num, $level)
	{
		try
		{
			for($i = 1; $i <= $num; $i++)
			{
				$type = NewServerActivityDef::EQUIP_STRONG_PREFIX . $num;
				if(defined('NewServerActivityDef::'.$type) == false)
				{
					continue;
				}
				$taskType = NewServerActivityDef::$ALL_SPECIAL_TYPES[$type];
				$isInConf = NewServerActivityUtil::isInConf($uid, $taskType);
				if ($isInConf)
				{
					NewServerActivityManager::getInstance($uid)->updateTask($taskType, $level);
				}	
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateFightForce fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $strongLv 阵上6个武将的12件宝物中最小的强化等级
	 * @desc 任务类型：106.阵上6个武将的12件宝物(包括兵书和战马)强化都达到$strongLv级
	 */
	public static function updateTwelveTreasureOnFormation($uid, $strongLv)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::TREASURE_STRONG_12);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::TREASURE_STRONG_12, $strongLv);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateTwelveTreasureOnFormation fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $num 每次获得的紫色宝物数量
	 * @desc 任务类型：107.获得N个蓝色宝物
	 */
	public static function updateBlueTreasure($uid, $num)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::BLUE_TREASURE);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::BLUE_TREASURE, $num, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateBlueTreasure fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $num 每次获得的紫色宝物数量
	 * @desc 任务类型：108.获得N个紫色宝物
	 */
	public static function updatePurpleTreasure($uid, $num)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::PURPLE_TREASURE);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::PURPLE_TREASURE, $num, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updatePurpleTreasure fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $magicLevel 阵上6个武将的12件宝物中最小的精炼等级
	 * @desc 任务类型：109.阵上6个武将的12件宝物(包括兵书和战马)精炼均达到N级
	 */
	public static function updateTwelveMagicTreasure($uid, $magicLevel)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::TWELVE_TREASURE_MAGIC_LEVEL);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::TWELVE_TREASURE_MAGIC_LEVEL, $magicLevel);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('update12MagicCreasure fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $magicLevel 精炼等级
	 * @desc 任务类型：110.任意（安装在阵上与背包里的都算）宝物精炼最高等级达到N级
	 */
	public static function updateAnyMagicTreasure($uid, $magicLevel)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::ANY_TREASURE_MAGIC_LEVEL);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::ANY_TREASURE_MAGIC_LEVEL, $magicLevel);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateAnyMagicCreasure fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $rank 竞技场排名
	 * @desc 任务类型：111.竞技场排名达到N名
	 */
	public static function updateArenaRank($uid, $rank)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::ARENA_RANK);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::ARENA_RANK, $rank, NewServerActivityDef::RANKTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateArenaRank fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $addCost 消耗声望的增量
	 * @desc 任务类型：112.消耗N点竞技场声望
	 */
	public static function updatePrestige($uid, $addCost)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::COST_PRESTIGE);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::COST_PRESTIGE, $addCost, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updatePrestige fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $addCost 消耗魂玉的增量
	 * @desc 任务类型：113.消耗N个魂玉
	 */
	public static function updateJewel($uid, $addCost)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::COST_JEWEL);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::COST_JEWEL, $addCost, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateJewel fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $cost 在道具商店里消耗的金币
	 * @desc 任务类型：114.道具商店消耗N个金币
	 */
	public static function updateProShopCostGold($uid, $cost)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::COST_GOLD_IN_PROPERTY_SHOP);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::COST_GOLD_IN_PROPERTY_SHOP, $cost, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateProShopCostGold fail, Exception:%s', $e);
		}
	}
	
	/**
	 * $num个紫色武将好感达到$level级
	 * @param $uid
	 * @param int $num 紫色武将个数
	 * @param int $level 这num个紫色武将好感的最小等级
	 *
	 * 目前暂时只有任务类型：
	 * 115.1个紫色武将好感达到N级
	 * 126.5个紫色武将好感达到N级
	 */
	public static function updateAddPurpleFavor($uid, $num, $level)
	{
		try
		{
			for($i = 1; $i <= $num; $i++)
			{
				$type = NewServerActivityDef::ADD_PURPLE_FAVOR_PREFIX . $num;
				if(defined('NewServerActivityDef::'.$type) == false)
				{
					continue;
				}
				$taskType = NewServerActivityDef::$ALL_SPECIAL_TYPES[$type];
				$isInConf = NewServerActivityUtil::isInConf($uid, $taskType);
				if ($isInConf)
				{
					NewServerActivityManager::getInstance($uid)->updateTask($taskType, $level);
				}
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateAddPurpleFavor fail, Exception:%s', $e);
		}	
	}
	
	/**
	 * @param $uid
	 * @param int $towerLv 通关的塔层
	 * @desc 任务类型：116.通关到N层试练塔
	 */
	public static function updatePassTower($uid, $towerLv)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::PASS_TOWER);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::PASS_TOWER, $towerLv);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updatePassTower fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $resetNum 重置试炼塔的次数
	 * @desc 任务类型：117.重置N次试练塔
	 */
	public static function updateResetTower($uid, $resetNum)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::RESET_TOWER);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::RESET_TOWER, $resetNum, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateResetTower fail, Exception:%s', $e);
		}
	}
	
	/**
	 * 任意$num个紫色战魂强化到$strongLv级
	 * @param $uid
	 * @param int $num 紫色战魂个数
	 * @param int $strongLv 这num个紫色战魂强化的最小等级
	 * 
	 * 目前暂时只有任务类型：
	 * 118.任意1个紫色战魂强化到N级
	 * 127.任意6个紫色战魂强化到N级
	 */
	public static function updateStrongPurpleFightsoul($uid, $num, $strongLv)
	{
		try
		{
			for($i = 1; $i <= $num; $i++)
			{
				$type = NewServerActivityDef::STRONG_PURPLE_FIGHTSOUL_PREFIX . $num;
				if(defined('NewServerActivityDef::'.$type) == false)
				{
					continue;
				}
				$taskType = NewServerActivityDef::$ALL_SPECIAL_TYPES[$type];
				$isInConf = NewServerActivityUtil::isInConf($uid, $taskType);
				if ($isInConf)
				{
					NewServerActivityManager::getInstance($uid)->updateTask($taskType, $strongLv);
				}	
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateStrongPurpleFightsoul fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $damage 单次攻打boss的伤害
	 * @desc 任务类型：119.单次攻打boss伤害达到N万
	 */
	public static function updateAttackBoss($uid, $damage)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::ATTACK_BOSS);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::ATTACK_BOSS, $damage);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateAttackBoss fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $num 军团特级捐献次数
	 * @desc 任务类型：120.军团特级捐献N次(V2)
	 */
	public static function updateSpecialDonation($uid, $num)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::SPECIAL_DONATION);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::SPECIAL_DONATION , $num, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateSpecialDonation fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @param int $num 军团究级捐献次数
	 * @desc 任务类型：121.军团究极捐献N次(V5)
	 */
	public static function updateUltimateDonation($uid, $num)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::ULTIMATE_DONATION);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::ULTIMATE_DONATION, $num, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateUltimateDonation fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @desc 任务类型：122.加入或创建军团
	 */
	public static function updateGuild($uid)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::GUILD);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::GUILD, 1);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateGuild fail, Exception:%s', $e);
		}
	}
	
	/**
	 * @param $uid
	 * @desc 任务类型：123.占领N次资源矿 
	 */
	public static function updateMineral($uid)
	{
		try
		{
			$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::MINERAL);
			if ($isInConf)
			{
				NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::MINERAL, 1, NewServerActivityDef::ACCUMTYPE);
			}
		}
		catch (Exception $e)
		{
			Logger::warning('updateMineral fail, Exception:%s', $e);
		}
	}
	
	/**
     * @desc 任务类型：124.开服第N天(累计登陆)
     * 在User.cfg.php里$LOGIN_FUNC_LIST定义，登陆即可拉取
     */
    public static function updateAccSign()
    {
    	try
    	{
    		$uid = RPCContext::getInstance()->getUid();
	    	$isInConf = NewServerActivityUtil::isInConf($uid, NewServerActivityDef::ACCSIGN);
	    	if ($isInConf)
	    	{
	    		$lastLoginTime = EnUser::getUserObj()->getLastLoginTime();
	    		if (!Util::isSameDay($lastLoginTime))
	    		{
	    			$day = NewServerActivityUtil::getCurDay();
	    			NewServerActivityManager::getInstance($uid)->updateTask(NewServerActivityDef::ACCSIGN, $day);
	    		}
	    	}
    	}
    	catch (Exception $e)
    	{
    		Logger::warning('updateAccSign fail, Exception:%s', $e);
    	}
    }
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
