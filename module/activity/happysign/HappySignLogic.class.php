<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySignLogic.class.php 232026 2016-03-10 08:34:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/HappySignLogic.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-10 08:34:26 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232026 $
 * @brief 
 *  
 **/
class HappySignLogic
{
	public static function getSignInfo($uid)
	{
		$ret = array();
		$manager = HappySignManager::getInstance($uid);
		$ret[HappySignDef::LOGIN_DAYS] = $manager->getSignNum();
		$ret[HappySignDef::HAD_REWARD] = $manager->getRewardInfo();
		$ret[HappySignDef::TODAY] = HappySignUtil::getDateNumOfToday();
		return $ret;
	}
	
	public static function gainSignReward($uid, $rewardId, $select = 0)
	{
		/** 一堆check **/
		
		$dataConf = HappySignUtil::getConfData($rewardId);
		// 1.检查配置是否存在
		if (empty($dataConf))
		{
			throw new FakeException('rewardId:%d from client is not in conf', $rewardId);
		}
		
		// 2.检查是否已经签到领奖过了
		$manager = HappySignManager::getInstance($uid);
		if ($manager->isObtainReward($rewardId))
		{
			throw new FakeException('rewardId:%d had signed', $rewardId);
		}
		
		// 3.判断是否是补签领奖,如果是,则需要扣金币
		// $ifRemedy为0表示非补签,为1表示补签
		$ifRemedy = 0;
		$reqCost = 0;
		$today = HappySignUtil::getDateNumOfToday();
		if ($today > $rewardId)
		{
			$ifRemedy = 1;
			$reqCost = $dataConf[HappySignDef::COST];
		}
		else if ($today < $rewardId)
		{
			throw new FakeException('sign day:%d is not opened, cant gain', $rewardId);
		}
		
		// 4.检查 奖励数组内奖励物品的编号$select 与 奖励类型 是否相符
		$rewardType = HappySignUtil::getRewardType($rewardId);
		$allRewardArr = HappySignUtil::getConfRewardArr($rewardId);
		if (empty($select))
		{
			// 4.1 既要检查奖励档位类型与$select一致
			if (HappySignDef::UNSELECT_TYPE != $rewardType)
			{
				throw new FakeException('rewardId:%d is selectType, so need $select', $rewardId);
			}
		}
		else
		{
			// 4.1 既要检查奖励档位类型与$select一致
			if (HappySignDef::SELECT_TYPE != $rewardType)
			{
				throw new FakeException('rewardId:%d is unSelectType, dont need $select', $rewardId);
			}
			// 4.2 又要检查奖励选项$select范围是否配置中第$rewardId档奖励里能找到
			if (empty($allRewardArr[$select]))
			{
				throw new FakeException('rewardId:%d is selectType, but no select:%d in conf', $rewardId, $select);
			}
		}
		
		// 5.背包满的判断
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isFull())
		{
			throw new FakeException('bag is full, cant get sign reward, rewardId:%d', $rewardId);
		}
		
		// 6.如果是补签,需要判断扣金币时金币数是否足够
		if (1 == $ifRemedy)
		{
			$user = EnUser::getUserObj($uid);
			$curGold = $user->getGold();
			if (false == $user->subGold($reqCost, StatisticsDef::ST_FUNCKEY_HAPPY_SIGN_COST))
			{
				throw new FakeException('too less gold:%d to remedy sign, at least gold:%d', $curGold, $reqCost);
			}
		}
		
		/** 记录奖励并发奖 **/
		
		// 7.记录奖励id数组并获得发奖数组
		$rewardArr = array();
		$manager->recordReward($rewardId, $select);
		switch ($rewardType)
		{
			case HappySignDef::UNSELECT_TYPE:
				// 非选择类型发完奖励数组的奖励
				$rewardArr = $allRewardArr;
				break;
			case HappySignDef::SELECT_TYPE:
				// 选择类型只发对应$select的奖励,[0]是为了补齐三元组格式
				$rewardArr[0] = $allRewardArr[$select];
				break;
		}
		Logger::info('rewardId:%d ifRemedy:%d reqCost:%d rewardType:%d rewardArr:%s', $rewardId, $ifRemedy, $reqCost, $rewardType, $rewardArr);
		
		// 8.发奖
		$ret = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_HAPPY_SIGN, false, false);
		$manager->update();
		if (1 == $ifRemedy)
		{
			$user->update();
		}
		RewardUtil::updateReward($uid, $ret);
		
		return 'ok';
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */