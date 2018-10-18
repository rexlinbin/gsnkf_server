<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGiftLogic.class.php 208550 2015-11-10 11:23:12Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/RechargeGiftLogic.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-10 11:23:12 +0000 (Tue, 10 Nov 2015) $
 * @version $Revision: 208550 $
 * @brief 
 *  
 **/
class RechargeGiftLogic
{
	public static function getInfo($uid)
	{
		$ret = array();
		$ret[RechargeGiftDef::ACC_GOLD]= self::getAccGold($uid);
		$ret[RechargeGiftDef::HAD_REWARD] = RechargeGiftManager::getInstance($uid)->getRewardArr();
		return $ret;
	}
	/**
	 * 获得 从活动开始时间当天的0点到现在的累计充值金币数,至于为什么是0点这是策划后来口头要求的,其实还是希望他写在策划案里……
	 * @return int 累计充值金币数
	 */
	public static function getAccGold($uid)
	{
		$startTime = RechargeGiftUtil::getConfStartTime();
		$beginDayTime = strtotime( date( 'Ymd', $startTime).'000000' );
		$accRechargeGold = EnUser::getRechargeGoldByTime($beginDayTime , Util::getTime(), $uid);
	
		return $accRechargeGold;
	}
	
	public static function obtainReward($uid, $rewardId, $select = 0)
	{
		/** 一堆check **/
		
		$dataConf = RechargeGiftUtil::getConfData();
		// 1.检查配置是否存在
		if (empty($dataConf[$rewardId]))
		{
			throw new FakeException('rewardId:%d from client is not in conf', $rewardId);
		}
		
		// 2.检查奖励id是否领取过
		$manager = RechargeGiftManager::getInstance($uid);
		if ($manager->isobtainReward($rewardId))
		{
			throw new FakeException('rewardId:%d had obtain', $rewardId);
		}
		
		// 3.检查充值金币数是否达到配置要求
		$accGold = self::getAccGold($uid);
		$confReqGold = RechargeGiftUtil::getReqGold($rewardId);
		if ($accGold < $confReqGold)
		{
			throw new FakeException('recharge %d golds less than conf require gold:%d', $accGold, $confReqGold);
		}
		
		// 4.检查 奖励数组内奖励物品的编号$select 与 奖励类型 是否相符   PS:这部分判断写得有点丑……
		$rewardType = RechargeGiftUtil::getRewardType($rewardId);
		$allRewardArr = RechargeGiftUtil::getConfRewardArr($rewardId);
		if (empty($select))
		{
			// 4.1 既要检查奖励档位类型与$select一致
			if (RechargeGiftDef::UNSELECT_TYPE != $rewardType)
			{
				throw new FakeException('rewardId:%d is selectType, so need $select', $rewardId);
			}
		}
		else 
		{
			// 4.1 既要检查奖励档位类型与$select一致
			if (RechargeGiftDef::SELECT_TYPE != $rewardType)
			{
				throw new FakeException('rewardId:%d is unSelectType, dont need $select', $rewardId);
			}
			// 4.2 又要检查奖励选项$select范围是否配置中第$rewardId档奖励里能找到
			if (empty($allRewardArr[$select]))
			{
				throw new FakeException('rewardId:%d is selectType, but no select:%d in conf', $rewardId, $select);
			}
		}
		
		// 5.检查背包满
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isFull())
		{
			throw new FakeException('bag is full, cant obtain recharge reward, rewardId:%d', $rewardId);
		}
		
		/** 记录奖励并发奖 **/
		
		// 6.记录奖励id数组并获得发奖数组
		$rewardArr = array();
		$manager->recordReward($rewardId, $select);
		switch ($rewardType)
		{
			case RechargeGiftDef::UNSELECT_TYPE:
				// 非选择类型发完奖励数组的奖励
				$rewardArr = $allRewardArr;
				break;
			case RechargeGiftDef::SELECT_TYPE:
				// 选择类型只发对应$select的奖励,[0]是为了补齐三元组格式,当然因为目前需求是单选才可以这样补齐
				$rewardArr[0] = $allRewardArr[$select];
				break;
		}	
		Logger::info('confReqGold:%d accRechargeGold:%d rewardId:%d rewardType:%d rewardArr:%s', $confReqGold, $accGold, $rewardId, $rewardType, $rewardArr);
		
		// 7.发奖
		$ret = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_RECHARGE_GIFT, false, false);
		$manager->update();
		RewardUtil::updateReward($uid, $ret);
		return 'ok';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */