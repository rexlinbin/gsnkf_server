<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackLogic.class.php 260660 2016-09-05 10:31:44Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/WelcomebackLogic.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-09-05 10:31:44 +0000 (Mon, 05 Sep 2016) $
 * @version $Revision: 260660 $
 * @brief 
 *  
 **/
class WelcomebackLogic
{
	
	public static function getOpen($uid)
	{
		$ret = array();
		$user = EnUser::getUserObj($uid);
		$conf = WelcomebackUtil::getWelcomebackConf();
		
		RPCContext::getInstance()->setSession(WelcomebackDef::SESSION_OPEN_STATUS, WelcomebackDef::SESSION_CLOSE);
		
		//等级限制
		if ($user->getLevel() < $conf[WelcomebackDef::LEVEL_LIMIT])
		{
			Logger::debug('level:[%d] is less than %d', $user->getLevel(), $conf[WelcomebackDef::LEVEL_LIMIT]);
			$ret['isOpen'] = 0;
			$ret['endTime'] = 0;
			return $ret;
		}


		//30天内的新服不开启
		$serverOpenTime = strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME);//开服时间	
		$time = Util::getTime();
		$serverOpenDay = WelcomebackUtil::getOfflineDay($serverOpenTime, $time) + 1;//获取开服的第几天
		
		if ($serverOpenDay <= $conf[WelcomebackDef::SERVER_LIMIT])
		{
			Logger::debug('serverOpenDay:[%d] is less than %d days', $serverOpenDay, $conf[WelcomebackDef::SERVER_LIMIT]);
			$ret['isOpen'] = 0;
			$ret['endTime'] = 0;
			return $ret;
		}
				
		//可能有上次活动过期时有未领的奖，发到奖励中心
		$welcomebackObj = WelcomebackObj::getInstance($uid);//先不初始化，就可以看到以前有没有数据
			
		if (!$welcomebackObj->isOpen() && $welcomebackObj->getNeedBufa())
		{
			$rewardIdNotGain = $welcomebackObj->getRewardNotGained();
			if (!empty($rewardIdNotGain))
			{
				$itemArrGroup = array();
				foreach ($rewardIdNotGain as $id)
				{
					$allItemArr = self::getReward($id);
					if (!empty($allItemArr))
					{
						$itemArr = array();
						$rewardConf = WelcomebackUtil::getRewardConf();
						if (1 == $rewardConf[$id][WelcomebackDef::ONE_FROM_N])
							$itemArr[] = $allItemArr[0];//多选1的，默认给玩家选第一个物品
						else
							$itemArr = $allItemArr;
						
						$itemArrGroup[] = $itemArr;					
					}
				}
				Logger::info('welcomeback bufa reward, idList:[%s], reward:[%s]', $rewardIdNotGain, $itemArrGroup);
				
				if(!empty($itemArrGroup))
				{
					try
					{
						RewardUtil::reward3DtoCenter($uid, $itemArrGroup, RewardSource::WELCOMEBACK_BUFA_REWARD);
					}
					catch (Exception $e)
					{
						Logger::fatal('welcomeback cant send reward to center, idList:[%s], reward:[%s]', $rewardIdNotGain['idList'], $itemArrGroup);
					}
				}	
			}
			$welcomebackObj->setNeedBufa(0);
			$welcomebackObj->update();
		}

		//当前没开的情况下，离线1个自然天才开启
		if (!$welcomebackObj->isOpen() && WelcomebackUtil::getOfflineDay($user->getLastLogoffTime(), Util::getTime()) < 1)
		{
			Logger::debug('logofftime:[%d] is less than 1 day', $user->getLastLogoffTime());
			$ret['isOpen'] = 0;
			$ret['endTime'] = 0;
			return $ret;
		}
		
		//老玩家回归活动开启，新活动会把离线时间、回归时间、结束时间保存数据库，正在进行的老活动不用管
		if (!$welcomebackObj->isOpen())
		{
			$welcomebackObj->initData();
			Logger::debug('welcomeback activity is open now');
		}
		
		$welcomebackObj->update();
		RPCContext::getInstance()->setSession(WelcomebackDef::SESSION_OPEN_STATUS, WelcomebackDef::SESSION_OPEN);
		$ret['isOpen'] = 1;
		$ret['endTime'] = $welcomebackObj->getEndTime();
		
		return $ret;
	}
	
	/**
	 * array
	 * [
	 * 		'endTime' => time,				活动结束时间，秒数
	   		
	   		'gift' => array(
				id => gainGift				1:未领取，2：已经领取
			), 
			
			'task' => array(
				id => array(
						finishedTimes, 		目前执行次数
						status				0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
				)
			), 
			
			'recharge' => array(
				id => array(
						hadRewardTimes,		已领奖次数
						toRewardTimes		待领奖次数
				)
			),
			
			'shop' => array(
				id => buyTimes				已购买次数
			)
	 * ]
	 * @param int $uid
	 */
	public static function getInfo($uid)
	{
		$welcomebackObj = WelcomebackObj::getInstance($uid);
		if (!$welcomebackObj->isOpen())
			throw new FakeException('welcomeback activity is close');
		
		$welcomebackObj->updateRecharge();//活动开启的情况下更新充值信息
		
		$ret = array();
		$ret['endTime'] = $welcomebackObj->getEndTime();
		$ret['day'] = self::getOfflineGiftDay();
		$ret['gift'] = $welcomebackObj->getGiftInfo();
		$ret['task'] = $welcomebackObj->getTaskInfo();
		$ret['recharge'] = $welcomebackObj->getRechargeInfo();
		$ret['shop'] = $welcomebackObj->getShopInfo();
		
		/*
		 * 把单充信息精简。原来的：
		 * 'task' => array(
				id => array(
						0 => finishedTimes, 	目前执行次数
						1 => status,			0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
						2 => select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
				)
			), 
		 * 'recharge' => array(
				id => array(
						0 => gold,					需要充值金币
						1 => rechargeTimes,			总的可充值次数
						2 => hadRewardTimes,		已领奖次数
						3 => toRewardTimes			待领奖次数
						4 => array(
								select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
							 )	
				)
			)
		 */
		foreach ($ret['task'] as $key => $value)
		{
			$ret['task'][$key]['finishedTimes'] = $value[0];
			$ret['task'][$key]['status'] = $value[1];
			unset($ret['task'][$key][0]);
			unset($ret['task'][$key][1]);
			unset($ret['task'][$key][2]);
		}
		
		foreach ($ret['recharge'] as $key => $value)
		{
			$ret['recharge'][$key]['hadRewardTimes'] = $ret['recharge'][$key][2];
			$ret['recharge'][$key]['toRewardTimes'] = $ret['recharge'][$key][3];
			unset($ret['recharge'][$key][0]);
			unset($ret['recharge'][$key][1]);
			unset($ret['recharge'][$key][2]);
			unset($ret['recharge'][$key][3]);
			unset($ret['recharge'][$key][4]);
		}
		
		return $ret;
	}
	
	public static function gainReward($uid, $taskId, $selectId)
	{
		$welcombackObj = WelcomebackObj::getInstance($uid);
		
		if (!$welcombackObj->isOpen())
			throw new FakeException('welcomeback activity is not open, cant gain reward');
		
		$conf = WelcomebackUtil::getRewardConf();
		
		if (isset($conf[$taskId]))
		{
			//背包
			$bag = BagManager::getInstance()->getBag($uid);
			if($bag->isFull())
				throw new FakeException("bag is full");
			
			//获取奖励
			$allRewardArr = self::getReward($taskId);
			$rewardArr = array();
			
			//合法性检查	
			if ($selectId < 0)
				throw new FakeException('select:[%d] is < 0, taskId:[%d]', $selectId, $taskId);		
				
			if ($selectId > 0)
			{
				if (self::rewardIsOneFromN($taskId))
				{
					if (!isset($allRewardArr[$selectId - 1]))
						throw new FakeException('reward of select:[%d] is null, taskId:[%d]', $selectId, $taskId);
					$rewardArr[] = $allRewardArr[$selectId - 1];
				}
				else 
					throw new FakeException('reward id:[%d] is not 1 from N, but select is:[%d]',$taskId, $selectId);
			}
			else
				$rewardArr = $allRewardArr;	
			
			//更改obj。下面三个分支分别是领白送的礼包、领任务奖励、领单充奖励
			if ($conf[$taskId][WelcomebackDef::TYPE] == WelcomebackDef::TYPE_GIFT) 
			{
				$source = StatisticsDef::ST_FUNCKEY_WELCOMEBACK_GIFT_GET;		
				if (1 == $welcombackObj->getGiftGained($taskId))//未领取		
				{
					$welcombackObj->setGiftGained($taskId);//奖励设置为已领取
					
					//礼包  = 基数 * 离线天数(不能超过配置的天数)
					$offlineRate = self::getOfflineGiftDay();
					foreach ($rewardArr as $index => $reward)
					{
						$rewardArr[$index][2] = $reward[2] * $offlineRate;
					}
				}
				else
					throw new FakeException('gift id:[%d] already gained', $taskId);
			}
			else if ($conf[$taskId][WelcomebackDef::TYPE] == WelcomebackDef::TYPE_TASK)
			{
				$source = StatisticsDef::ST_FUNCKEY_WELCOMEBACK_TASK_GET;
				if (1 == $welcombackObj->getTaskStatus($taskId))//任务完成但没领取
				{
					$welcombackObj->setTaskStatus($taskId, 2);//设置为已领取
					$welcombackObj->setTaskSelect($taskId, $selectId);//设置选择的奖励物品
				}
				else 
					throw new FakeException('task id:[%d] not finished or already gained', $taskId);
			}
			else if ($conf[$taskId][WelcomebackDef::TYPE] == WelcomebackDef::TYPE_RECHARGE)
			{
				$source = StatisticsDef::ST_FUNCKEY_WELCOMEBACK_RECHARGE_GET;
				$toRewardTimes = $welcombackObj->getRechargeToReward($taskId);
				if ($toRewardTimes > 0)//待领取次数>0
				{
					$hadReward = $welcombackObj->getRechargeHadReward($taskId) + 1;
					$welcombackObj->setRechargeHadReward($taskId, $hadReward, $selectId);//设置已领取次数和选择物品
					$welcombackObj->setRechargeToReward($taskId, $toRewardTimes -1);//设置待领取次数
				}
				else 
					throw new FakeException('recharge id:[%d] has 0 toRewardTimes', $taskId);
			}
			else 
				throw new FakeException('cant gain reward, id:[%d], type:[%d]', $taskId, $conf[$taskId][WelcomebackDef::TYPE]);

			Logger::info('welcomeback gainReward, id:[%d], $selectId:[%d], $rewardArr:[%s]', $taskId, $selectId, $rewardArr);
			
			//发奖
			$welcombackObj->update();
			$ret = RewardUtil::reward3DArr($uid, $rewardArr, $source, false, false);
			RewardUtil::updateReward($uid, $ret);
		}
		else 
			throw new FakeException('id:[%d] not exists in return_reward.csv', $taskId);
		
		return 'ok';
	}
	
	public static function buy($uid, $taskId, $num)
	{
		$welcomebackObj = WelcomebackObj::getInstance($uid);
		
		if (!$welcomebackObj->isOpen())
			throw new FakeException('welcomeback activity is not open, cant buy');
		
		$conf = WelcomebackUtil::getRewardConf();
		
		if ($conf[$taskId][WelcomebackDef::TYPE] == WelcomebackDef::TYPE_SHOP) 
		{
			//背包
			$bag = BagManager::getInstance()->getBag($uid);
			if($bag->isFull())
				throw new FakeException("bag is full");
			
			//更改obj
			$hadBuy = $welcomebackObj->getShopBuyTimes($taskId);
			if ($hadBuy + $num <= $conf[$taskId][WelcomebackDef::BUY_TIMES]) //没超过购买上限
			{
				$welcomebackObj->addShopBuyTimes($taskId, $num);
			}
			else 
				throw new FakeException('cant buy too much times, id:[%d], num:[%d], hadBuy:[%d], limit:[%d]', $taskId, $num, $hadBuy, $conf[$taskId][WelcomebackDef::BUY_TIMES]);
			
			//扣金币
			$costGold = $num * intval($conf[$taskId][WelcomebackDef::COST][1]);
			if (empty($costGold))
				throw new FakeException('cost is 0 in return_reward.csv, $id:[$d]', $taskId);
			
			$user = EnUser::getUserObj($uid);
			if (!$user->subGold($costGold, StatisticsDef::ST_FUNCKEY_WELCOMEBACK_SHOP_COST)) 
				throw new FakeException('welcomeback shop buy: subGold failed, cost:[%d], taskId:[$d], num:[$d]', $costGold, $taskId, $num);
			$user->update();
			$welcomebackObj->update();
			
			//发货
			$itemArr = $conf[$taskId][WelcomebackDef::DISCOUNT_ITEM];
			foreach ($itemArr as $index => $item)
			{
				$itemArr[$index][2] = $num * $item[2];
			}
			Logger::info('welcomeback buy, id:[%d], num:[$d], cost:[$d], items:[%s]', $taskId, $num, $costGold, $itemArr);
			$ret = RewardUtil::reward3DArr($uid, $itemArr, StatisticsDef::ST_FUNCKEY_WELCOMEBACK_SHOP_GET, false, false);
			RewardUtil::updateReward($uid, $ret);
		}
		else 
			throw new FakeException('taskId:[%d] is not a shop type', $taskId);
		
		return 'ok';
	}
	
	private static function getReward($id)
	{
		$conf = WelcomebackUtil::getRewardConf();
		if (!isset($conf[$id][WelcomebackDef::REWARD]))
		{
			Logger::fatal('cant find id:[%d] in reward conf', $id);
			return array();
		}
		return $conf[$id][WelcomebackDef::REWARD];
	}
	
	private static function rewardIsOneFromN($id)
	{
		$conf = WelcomebackUtil::getRewardConf();
		return 1 == $conf[$id][WelcomebackDef::ONE_FROM_N];
	}
	
	private static function getOfflineGiftDay()
	{
		$welcombackObj = WelcomebackObj::getInstance();
		$offlineDay = WelcomebackUtil::getOfflineDay($welcombackObj->getOfflineTime(), $welcombackObj->getBackTime());
		$ruleConf = WelcomebackUtil::getWelcomebackConf();
		return min($offlineDay, $ruleConf[WelcomebackDef::OFFLINE_LIMIT]);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */