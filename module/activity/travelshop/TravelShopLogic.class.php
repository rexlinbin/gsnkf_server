<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShopLogic.class.php 246313 2016-06-14 08:03:40Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/TravelShopLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-06-14 08:03:40 +0000 (Tue, 14 Jun 2016) $
 * @version $Revision: 246313 $
 * @brief 
 *  
 **/
class TravelShopLogic
{
	public static function getInfo($uid)
	{
		Logger::trace('TravelShopLogic::getInfo Start.');

		$ts = TravelShopObj::getInstance();
		$tsUser = TravelShopUserObj::getInstance($uid);
		$info = array(
				TravelShopDef::FIELD_SUM => $ts->getSum(),
				TravelShopDef::FIELD_SCORE => $tsUser->getScore(),
				TravelShopDef::FIELD_FINISH_TIME => $tsUser->getFinishTime(),
				TravelShopDef::BUY => $tsUser->getBuy(),
				TravelShopDef::PAYBACK => $tsUser->getPayback(),
				TravelShopDef::REWARD => $tsUser->getReward(),
				'topup' => $tsUser->getTopup(),
		);
		
		//用户有充值返利可以领取的时候,加活动结束的timer
		$id = $tsUser->checkPayback();
		if (!empty($id))
		{
			//是否已有timer
			$timer = self::getTimer();
			if (empty($timer)) 
			{
				RPCContext::getInstance()->executeTask(SPECIAL_UID::TRAVEL_SHOP_UID, 'travelshop.addTimer', array());
			}
		}
		
		Logger::trace('TravelShopLogic::getInfo End.');
		return $info;
	}
	
	public static function buy($uid, $goodsId, $num)
	{
		Logger::trace('TravelShopLogic::buy Start. goodsId:%d, num:%d', $goodsId, $num);
		
		//最后一小时不让购买
		$rewardTime = self::getRewardTime();
		if (Util::getTime() >= $rewardTime) 
		{
			throw new FakeException('can not buy goods after reward time %d', $rewardTime);
		}
		
		//今天能否购买商品id
		$day = EnActivity::getActivityDay(ActivityName::TRAVELSHOP) + 1;
		$goodsConf = self::getGoodsConf($goodsId);
		if (!in_array($day, $goodsConf[TravelShopDef::DAYS])) 
		{
			throw new FakeException('user:%d can not buy goodsId:%d today', $uid, $goodsId);
		}
		
		//检查商品的购买上限
		$tsUser = TravelShopUserObj::getInstance($uid);
		if ($tsUser->getBuyNum($goodsId) + $num > $goodsConf[TravelShopDef::LIMIT])
		{
			throw new FakeException('goodsId:%d buy num reach limit', $goodsId);
		}
		
		//先加商品，目前只支持银币，物品
		//再扣消耗，目前只支持银币，金币，声望，荣誉
		$arrAdd = array();
		$arrSub = array();
		for ($i = 0; $i < $num; $i++)
		{
			$arrAdd = array_merge($arrAdd, $goodsConf[TravelShopDef::GOODS]);
			$arrSub = array_merge($arrSub, $goodsConf[TravelShopDef::COST]);
		}
		RewardUtil::reward3DArr($uid, $arrAdd, 0);
		RewardUtil::delMaterial($uid, $arrSub, -1, 1, array(), false);
		//RewardUtil::delMaterial($uid, $arrSub, StatisticsDef::ST_FUNCKEY_TRAVEL_SHOP_BUY, 1, array(), false);
		
		//更新用户数据,加购买次数,加积分
		$tsUser->addBuyNum($goodsId, $num);
		$tsUser->addScore($num * $goodsConf[TravelShopDef::SCORE]);
		$tsUser->update();
		
		//user和bag更新
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		//更新公共数据
		TravelShopObj::addSum($num);
		
		$subGoldNum = 0;
		foreach ($arrSub as $sub)
		{
			if ($sub[0] == RewardConfType::GOLD) 
			{
				$subGoldNum += $sub[2];
			}
		}
		if ($subGoldNum > 0)
		{
			Statistics::gold4Item(StatisticsDef::ST_FUNCKEY_TRAVEL_SHOP_BUY_ID, -$subGoldNum, $goodsId, $num, EnUser::getUserObj($uid)->getGold());
		}
		Logger::info('travel shop exchangeId:%d num:%d subGoldNum:%d ', $goodsId, $num, $subGoldNum);
		
		// 云游商人的购买次数统计 - $num传的是当前购买的总次数，这个次数是每天重置的
		EnFestivalAct::notify($uid, FestivalActDef::TASK_TRAVEL_SHOP_BUY_NUM, $tsUser->getBuySum());
		
		return $tsUser->getFinishTime();
	}
	
	public static function getPayback($uid, $id)
	{
		Logger::trace('TravelShopLogic::getPayback Start. id:%d', $id);
		
		//判断是否可以领取返利
		$tsUser = TravelShopUserObj::getInstance($uid);
		if (!$tsUser->canGainPayback($id)) 
		{
			throw new FakeException('user:%d can not get payback id:%d', $uid, $id);
		}
		
		//获得充值返利
		list($pay, $back) = self::getPaybackConf($id);
		$user = EnUser::getUserObj($uid);
		$user->addGold($back, StatisticsDef::ST_FUNCKEY_TRAVEL_SHOP_PAYBACK);
		
		//更新用户数据，设置领取状态
		$tsUser->gainPayback($id);
		$tsUser->update();
		
		//user更新
		$user->update();
		
		return 'ok';
	}
	
	public static function getReward($uid, $id)
	{
		Logger::trace('TravelShopLogic::getReward Start. id:%d', $id);
		
		//检查人数是否达到
		$ts = TravelShopObj::getInstance();
		if ($ts->getSum() < $id) 
		{
			throw new FakeException('total count is not reach %d', $id);
		}
	
		//判断是否可以领取返利
		$tsUser = TravelShopUserObj::getInstance($uid);
		if (!$tsUser->canGainReward($id))
		{
			throw new FakeException('user:%d can not get reward id:%d', $uid, $id);
		}
	
		//获得普天奖励
		$reward = self::getRewardConf($id);
		if (empty($reward)) 
		{
			throw new FakeException('reward:%d is not exist', $id);
		}
		Logger::trace('get reward:%s', $reward);
		RewardUtil::reward3DArr($uid, $reward, 0);
	
		//更新用户数据，设置领取状态
		$tsUser->gainReward($id);
		$tsUser->update();
	
		//user和bag更新
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
	
		return 'ok';
	}
	
	public static function addTimer()
	{
		$findValid = false;
		$ret = self::getTimer();
		$rewardTime = self::getRewardTime();
		foreach ($ret as $index => $timer)
		{
			if ($timer['status'] == TimerStatus::RETRY)
			{
				Logger::fatal('the timer %d is retry.but the activity not end.', $timer['tid']);
				TimerTask::cancelTask($timer['tid']);
				continue;
			}
			if ($timer['status'] == TimerStatus::UNDO)
			{
				if($timer['execute_time'] != $rewardTime)
				{
					Logger::fatal('invalid timer %d.execute_time %d', $timer['tid'], $timer['execute_time']);
					TimerTask::cancelTask($timer['tid']);
				}
				else if($findValid)
				{
					Logger::fatal('one more valid timer.timer %d.', $timer['tid']);
					TimerTask::cancelTask($timer['tid']);
				}
				else
				{
					Logger::trace('a findvalid');
					$findValid = true;
				}
			}
		}
		if($findValid == false)
		{
			Logger::info('no valid timer.addTask for travelshop.reward.');
			TimerTask::addTask(SPECIAL_UID::TRAVEL_SHOP_UID, $rewardTime, TravelShopDef::TASK_NAME, array());
		}
	}
	
	public static function rewardUser()
	{
		Logger::info('travel shop reward user is start');
		
		$i = 0; 
		$count = CData::MAX_FETCH_SIZE;
		list($startTime, $endTime) = self::getActTime();
		
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$arrInfo = TravelShopDao::getArrUser($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE, $startTime);
			$count = count($arrInfo);
			++$i;
			$sleepCount = 0;
			foreach ($arrInfo as $uid => $info)
			{
				try
				{
					$tsUser = new TravelShopUserObj($uid, $info);
					$payback = $tsUser->getPayback();
					$id = count($payback);
					if ($tsUser->canGainPayback($id)) 
					{
						list($pay, $back) = self::getPaybackConf($id);
						$reward = array(array(array(RewardConfType::GOLD, 0, $back)));
						Logger::info('reward for uid:%d, reward:%s', $uid, $reward);
						$tsUser->gainPayback($id);
						$tsUser->update();
						RewardUtil::reward3DtoCenter($uid, $reward, RewardSource::TRAVEL_SHOP_PAY_BACK_GOLD);
					}
						
					if (++$sleepCount == 10)
					{
						usleep(50);
						$sleepCount = 0;
					}
				}
				catch (Exception $e )
				{
					Logger::fatal('failed to reward for uid:%d, $reward:%s', $uid, $reward);
				}
			}
		}
		Logger::info('travel shop reward user is end');
	}
	
	public static function getTimer()
	{
		$rewardTime = self::getRewardTime();
		return EnTimer::getArrTaskByName(TravelShopDef::TASK_NAME, array(TimerStatus::RETRY, TimerStatus::UNDO), $rewardTime);
	}
	
	public static function getActTime()
	{
		$actConf = EnActivity::getConfByName(ActivityName::TRAVELSHOP);
		return array($actConf['start_time'], $actConf['end_time']);
	}
	
	public static function getRewardTime()
	{
		list($startTime, $endTime) = self::getActTime();
		return $endTime - TravelShopDef::REWARD_TIME + TravelShopDef::DELAY;
	}
	
	public static function isInCurRound($time)
	{
		list($startTime, $endTime) = self::getActTime();
		if ($time >= $startTime && $time <= $endTime)
		{
			return true;
		}
	
		return false;
	}
	
	public static function getConf()
	{
		$conf = EnActivity::getConfByName(ActivityName::TRAVELSHOP);
		return $conf['data'];
	}
	
	public static function getGoodsConf($goodsId)
	{
		$conf = self::getConf();
		return $conf[TravelShopDef::ALL][$goodsId];
	}
	
	public static function getPaybackConf($id)
	{
		$payback = array();
		$conf = self::getConf();
		foreach ($conf[TravelShopDef::PAYBACK] as $key => $value)
		{
			if ($id >= $key) 
			{
				$payback = $value;
			}
			else 
			{
				break;
			}
		}
		
		return $payback;
	}
	
	public static function getRewardConf($id)
	{
		$conf = self::getConf();
		return isset($conf[TravelShopDef::REWARD][$id]) ? $conf[TravelShopDef::REWARD][$id] : array();
	}
	
	public static function getDeadlineConf()
	{
		$conf = self::getConf();
		return $conf[TravelShopDef::DEADLINE];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
