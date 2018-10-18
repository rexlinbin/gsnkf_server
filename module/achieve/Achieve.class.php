<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Achieve.class.php 112376 2014-06-03 13:27:03Z QiangHuang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/Achieve.class.php $
 * @author $Author: QiangHuang $(wuqilin@babeltime.com)
 * @date $Date: 2014-06-03 13:27:03 +0000 (Tue, 03 Jun 2014) $
 * @version $Revision: 112376 $
 * @brief 
 *  
 **/



class Achieve implements IAchieve
{
	
	
	
	public function getStarAchieve()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$arrId = AchieveLogic::getArrAchieveId($uid);
		
		return $arrId;
	}
	
	public function getInfo()
	{
		$obj = AchieveObj::getObj(RPCContext::getInstance()->getUid());
		return $obj->getInfos();;
	}
	
	public function updateTypeByOther($uid, $type, $finish_type, $finish_num)
	{
		$obj = AchieveObj::getObj(intval($uid));
		$obj->updateType(intval($type), intval($finish_type), intval($finish_num), true);
	}
	
	public function updateTypeArrBySystem($type, $infos)
	{
		Logger::debug("updateTypeArrBySystem. type:%d infos:%s", $type, $infos);
		$type = intval($type);
		$isRankType = in_array($type, AchieveDef::$DESC_TYPES);
		foreach($infos as $uid => $finish_num) {
			$finish_num = intval($finish_num);
			if($isRankType) {
				$finish_num = AchieveDef::MAX_BOSS_RANK - $finish_num;
			}
			$obj = AchieveObj::getObj(intval($uid));
			$obj->updateType($type, 0, $finish_num);
		}
	}
	
	public function obtainReward($achieveId)
	{
		$obj = AchieveObj::getObj(RPCContext::getInstance()->getUid());
		return $obj->obtainReward(intval($achieveId));
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
