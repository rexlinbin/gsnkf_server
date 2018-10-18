<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnOneRecharge.class.php 251598 2016-07-14 10:10:41Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/EnOneRecharge.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-07-14 10:10:41 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251598 $
 * @brief 单充回馈配置解析脚本
 *
 **/
class EnOneRecharge
{
	public static function readOneRechargeCSV($arrData, $version, $startTime, $endTime)
	{
		/**策划案的要求是补发奖励时补发奖励时间是活动结束前1小时,review代码后吴哥提到如果玩家在在这个临界时间点击“领取”,
		 * OneRechargeLogic::gainReward对应的拦截中的$nowTime = Util::getTime()得到的时间可能是临界点前的,
		 * 那样玩家就能领奖了,但是发奖励到奖励中心的timer已经执行了,如果在计算到改玩家剩余奖励前玩家领完奖,这时候数据还算正常,如果是在
		 * 领奖之前就计算那么就想当于给玩家多发了奖励,所以额外空出2分钟再执行timer
		 */
		$timerTime = $endTime - 3600 + 60*2;

		// 虽然这个活动不会在跨服机器上运行,但是刷活动也会刷到跨服上,而跨服数据库上没有t_timer表,为了能正常刷上,要把插入timer的操作去掉
		if(!Util::isInCross())
		{
			self::checkDoOneRechargeRewardTimer($startTime, $timerTime);
		}

		$index = 0;
		$arrConfKey = array(
				OneRechargeDef::REQ => ++$index,
				OneRechargeDef::REWARD => ++$index,
				OneRechargeDef::DAY_NUM => ($index += 2),
		        OneRechargeDef::ONE_FROM_N => ++$index
		);

		$confList = array();

		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}

			$conf = array();

			foreach ($arrConfKey as $key => $index)
			{
				switch ($key)
				{
					case OneRechargeDef::REWARD:
						$conf[$key] = array();
						$temp = Util::str2Array($data[$index], ',');
						foreach ($temp as $k => $v)
						{
							$conf[$key][] = Util::array2Int(Util::str2Array($v, '|'));
						}
						break;
					case OneRechargeDef::ONE_FROM_N:
						if (isset($data[$index]))
							$conf[$key] = intval($data[$index]);
						else 
							$conf[$key] = OneRechargeDef::ONE_FROM_N_NO;
						break;
					default:
						$conf[$key] = intval($data[$index]);
				}
			}

			$goodsId = $data[0];
			$confList[$goodsId] = $conf;
		}
		return $confList;
	}

	/**
	 * 用于“活动结束前一小时,针对那些还有奖励剩余没领取的玩家,帮他们发送到奖励中心”
	 * NOTICE:非常需要注意的是,若策划因为某些原因重传活动配置,而且又改变了活动结束时间,这时候应该会有未执行的发奖timer,此时必须检查timer表
	 * @param $endTime 活动结束时间
	 */
	private static function addOneRechargeTimerTaskForReward($timerTime)
    {
        $taskName = OneRechargeDef::ONE_RECHARGE_REWARD_CENTER_TASK_NAME;
        $timerId = TimerTask::addTask(
            SPECIAL_UID::ONE_RECHARGE_TIMER_UID, $timerTime, $taskName, array());
        Logger::info('add oneRecharge rewardToCenter timer:%d, time:%s', $timerId, $timerTime);
    }

	private static function checkDoOneRechargeRewardTimer($startTime, $timerTime)
    {
        Logger::trace('OneRecharge checkDoOneRechargeRewardTimer');
        $taskName = OneRechargeDef::ONE_RECHARGE_REWARD_CENTER_TASK_NAME;
        $ret = EnTimer::getArrTaskByName($taskName, array(TimerStatus::RETRY, TimerStatus::UNDO), $startTime);
        $findValid = FALSE;
        foreach($ret as $index => $timer)
        {
            if($timer['status'] == TimerStatus::RETRY)
            {
                Logger::fatal('the timer %d is retry.but the oneRecharge activity not end.',$timer['tid']);
                TimerTask::cancelTask($timer['tid']);
                continue;
            }
            if($timer['status'] == TimerStatus::UNDO)
            {
            	// NOTICE 之前没看懂逻辑
                if($timer['execute_time'] != $timerTime)
                {
                    Logger::fatal('invalid timer %d.execute_time %d',$timer['tid'],$timer['execute_time']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else if($findValid)
                {
                    Logger::fatal('one more valid timer.timer %d.',$timer['tid']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else
                {
                    Logger::trace('checkRewardTimer findvalid');
                    $findValid = TRUE;
                }
            }
        }
        if($findValid == FALSE)
        {
            Logger::fatal('no valid timer.addTask for onerecharge.rewardToCenter.');
            self::addOneRechargeTimerTaskForReward($timerTime);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */