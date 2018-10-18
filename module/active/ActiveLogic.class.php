<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActiveLogic.class.php 224162 2016-01-20 08:31:37Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/ActiveLogic.class.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2016-01-20 08:31:37 +0000 (Wed, 20 Jan 2016) $
 * @version $Revision: 224162 $
 * @brief 
 *  
 **/
class ActiveLogic
{
	public static function getActiveInfo($uid)
	{
		Logger::trace('ActiveLogic::getHuntInfo Start.');
		
		$info = MyActive::getInstance($uid)->getInfo();
		unset($info[ActiveDef::UID]);
		unset($info[ActiveDef::UPDATE_TIME]);
		
		Logger::trace('ActiveLogic::getHuntInfo End.');
		return $info;
	}

	public static function getTaskPrize($uid, $taskId)
	{
		Logger::trace('ActiveLogic::getTaskPrize Start.');
		
		if (empty(btstore_get()->ACTIVE[$taskId][ActiveDef::ACTIVE_REWARD]))
		{
			throw new FakeException('reward of taskId:%d is not exist', $taskId);
		}
		
		$conf = btstore_get()->ACTIVE[$taskId];
		$confReqNum = intval($conf[ActiveDef::ACTIVE_NUM]);
		$myActive = MyActive::getInstance($uid);
		$hadFinishTaskArr = $myActive->getTask();
		if (empty($hadFinishTaskArr[$taskId]) || $hadFinishTaskArr[$taskId] < $confReqNum)
		{
			throw new FakeException('task:%d is not finished', $taskId);
		}
		
		$hadgainRewardArr = $myActive->getTaskIdOFHadGainReward();
		if (in_array($taskId, $hadgainRewardArr))
		{
			throw new FakeException('user:%d has got taskId:%d already', $uid, $taskId);
		}
		
		$rewardArr = $conf[ActiveDef::ACTIVE_REWARD]->toArray();
		$rewardRet = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_ACTIVE_TASK_REWARD);
		$myActive->gainTaskReward($taskId);
		$myActive->save();
		RewardUtil::updateReward($uid, $rewardRet);
		
		Logger::trace('ActiveLogic::getTaskPrize End.');
		
		return 'ok';
	}
	
	public static function getPrize($uid, $prizeId)
	{
		Logger::trace('ActiveLogic::getPrize Start.');
		
		if (empty(btstore_get()->ACTIVE_PRIZE[$prizeId])) 
		{
			throw new FakeException('prizeId:%d is not exist', $prizeId);
		}
		$conf = btstore_get()->ACTIVE_PRIZE[$prizeId];
		
		$myActive = MyActive::getInstance($uid);
		$step = $myActive->getStep();
		$arrPrizeId = btstore_get()->ACTIVE_OPEN[$step][ActiveDef::ACTIVE_PRIZE]->toArray();
		if (!in_array($prizeId, $arrPrizeId)) 
		{
			throw new FakeException('prizeId:%d is invalid', $prizeId);
		}
		$point = $myActive->getPoint();
		if ($conf[ActiveDef::ACTIVE_POINT] > $point) 
		{
			throw new FakeException('point:%d is not enough', $point);
		}
		$prize = $myActive->getPrize();
		if (in_array($prizeId, $prize)) 
		{
			Logger::trace('user:%d has got prizeId:%d already', $uid, $prizeId);
			return 'err';
		}
		
		RewardUtil::reward3DArr($uid, $conf[ActiveDef::ACTIVE_PRIZE], StatisticsDef::ST_FUNCKEY_ACTIVE_PRIZE);
		
		$myActive->addPrize($prizeId);
		$myActive->save();
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		Logger::trace('ActiveLogic::getPrize End.');

		return 'ok';
	}
	
	public static function upgrade($uid)
	{
		Logger::trace('ActiveLogic::upgrade Start.');
		
		$myActive = MyActive::getInstance($uid);
		$reward = $myActive->getReward();
		if (!empty($reward)) 
		{
// 			throw new FakeException('user has reward to get');
			return 'remainingReward';
		}
		
		$step = $myActive->getStep();
		if (!isset(btstore_get()->ACTIVE_OPEN[$step + 1])) 
		{
			throw new FakeException('user can not upgrade');
		}
		
		$user = EnUser::getUserObj($uid);
		$conf = btstore_get()->ACTIVE_OPEN[$step + 1];
		if ($conf[ActiveDef::ACTIVE_LEVEL] > $user->getLevel()) 
		{
			throw new FakeException('user level is not enough to upgrade');
		}
		
		$myActive->upgrade();
		$myActive->save();
		
		Logger::trace('ActiveLogic::upgrade End.');
		
		return 'ok';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */