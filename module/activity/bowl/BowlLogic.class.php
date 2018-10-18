<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BowlLogic.class.php 153786 2015-01-20 08:59:19Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/BowlLogic.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-20 08:59:19 +0000 (Tue, 20 Jan 2015) $
 * @version $Revision: 153786 $
 * @brief 
 *  
 **/
 
class BowlLogic
{
	public static function getBowlInfo($uid)
	{
		Logger::trace('BowlLogic::getBowlInfo param[uid:%d] begin...', $uid);
		// 活动是否开启
		if (!EnActivity::isOpen(ActivityName::BOWL))
		{
			throw new FakeException('bowl activity is not open');
		}

		// 得到聚宝盆信息
		$ret['charge'] = self::getChargeDuringBowl($uid);
		
		$bowlObj = BowlObj::getInstance($uid);
		$ret['type'] = $bowlObj->getBowlInfo();
		
		$bowlObj->update();
		
		Logger::trace('BowlLogic::getBowlInfo param[uid:%d] ret[%s]end...', $uid, $ret);
		return $ret;
	}
	
	public static function buy($uid, $type)
	{
		Logger::trace('BowlLogic::buy param[uid:%d,type:%d] begin...', $uid, $type);
		
		// 当前活动是否开启
		if (!EnActivity::isOpen(ActivityName::BOWL))
		{
			throw new FakeException('BowlLogic::buy failed, bowl activity is not open');
		}
		
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$buyDays = intval($conf['data'][$type][BowlDef::BOWL_BUY_DAYS]);
		$currDay = EnActivity::getActivityDay(ActivityName::BOWL) + 1;
		
		// 是否在聚宝时间
		if ($currDay > $buyDays) 
		{
			throw new FakeException('BowlLogic::buy failed, not buy day, currDay[%d] buyDays[%d].', $currDay, $buyDays);
		}
		
		// 是否充值足够
		$recharge = self::getChargeDuringBowl($uid, $type);
		$need = intval($conf['data'][$type][BowlDef::BOWL_BUY_NEED]);
		if ($recharge < $need) 
		{
			throw new FakeException('BowlLogic::buy failed, not enough recharge, recharge[%d] need[%d]', $recharge, $need);
		}
		
		// 是否已经买过
		$bowlObj = BowlObj::getInstance($uid);
		if ($bowlObj->hasBuy($type))
		{
			throw new FakeException('BowlLogic::buy failed, has buy bowl type[%d], can not buy again', $type);
		}
		
		// 是否买的起
		$cost = intval($conf['data'][$type][BowlDef::BOWL_BUY_COST]);
		$userObj = EnUser::getUserObj($uid);
		if (FALSE == $userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_BOWL_COST)) 
		{
			throw new FakeException('BowlLogic::buy failed, gold is not enough, need[%d]', $cost); 
		}
		$userObj->update();
		
		$bowlObj->buy($type);
		$bowlObj->update();
		
		Logger::trace('BowlLogic::buy param[uid:%d,type:%d] end...', $uid, $type);
		return 'ok';
	}
	
	public static function receive($uid, $type, $day)
	{
		Logger::trace('BowlLogic::receive param[uid:%d,type:%d,day:%d] begin...', $uid, $type, $day);
		
		// 当前活动是否开启
		if (!EnActivity::isOpen(ActivityName::BOWL))
		{
			throw new FakeException('BowlLogic::receive failed, bowl activity is not open');
		}
		
		// 是否买过这个类型的聚宝盆
		$bowlObj = BowlObj::getInstance($uid);
		if (!$bowlObj->hasBuy($type))
		{
			throw new FakeException('BowlLogic::receive failed, not buy bowl type[%d], can not receive', $type);
		}
		
		// 这天的奖是否可以领
		if (FALSE == $bowlObj->canReceive($type, $day))
		{
			throw new FakeException('BowlLogic::receive failed, type[%d] day[%d].', $type, $day);
		}
		
		// 更新状态
		$bowlObj->receive($type, $day);
		
		// 发奖励(坑： 此处有担心发奖时出现对方背包满而出现错误的情况，所以先塞东西再进行bowlObj的update，可能会出现用户多了宝物碎片或者钱的情况，不过在Reward里是先对物品等进行操作，可以做一层保证)
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$rewardArr = $conf['data'][$type][BowlDef::BOWL_BUY_REWARD][$day];
		
		$rewardInfo = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_BOWL_REWARD,FALSE,FALSE);
		unset($rewardInfo['rewardInfo']);
		
		$bowlObj->update();
		
		RewardUtil::updateReward($uid, $rewardInfo);
		
		Logger::trace('BowlLogic::receive param[uid:%d,type:%d,day:%d] ret[reward:%s] end...', $uid, $type, $day, $rewardArr);
		return 'ok';
	}
	
	public static function getActStartTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$start = $conf['start_time'];
		return $start;
	}
	
	public static function getActEndTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$start = $conf['end_time'];
		return $start;
	}
	
	//获得聚宝期间的充值数，聚宝期策划确定配成一样的（如果以后更改需求，要求不一样，就将充值数写成一个由end_time作key的map）
	public static function getChargeDuringBowl($uid = 0, $type = 1)
	{
		if ( empty($uid) )
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$buyDays = intval($conf['data'][$type][BowlDef::BOWL_BUY_DAYS]);
		
		// 是否充值足够
		$startTime = $conf['start_time'];
		$startTime = intval(strtotime(date('Y-m-d', $startTime)));
		$endTime = $startTime + $buyDays * SECONDS_OF_DAY;
		
		$recharge = EnUser::getRechargeGoldByTime($startTime, $endTime, $uid);
		
		return $recharge;
	}
	
	public static function getRewardDayNum($type)
	{
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$reward = $conf['data'][$type]['reward'];
		$num = count($reward);
		return $num;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */