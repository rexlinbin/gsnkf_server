<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityLogic.class.php 243174 2016-05-17 09:01:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivityLogic.class.php $
 * @author $Author: MingTian $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-17 09:01:30 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243174 $
 * @brief “开服7天乐”逻辑类
 *  
 **/
class NewServerActivityLogic
{
	public static function getInfo($uid, $fight = 0)
	{
		// $ret返回3个信息
		// 1.返回给前端当天能看到的任务的info
		// 2.返回给前端抢购商品的信息数组
		// 3.返回给前端任务更新截止时间戳 和 “开服7天乐”关闭时间戳
		$ret = array();
		$taskInfo = array();
		// 超过展示时间段,直接返回空数组
		$canDisplay = NewServerActivityUtil::canDisplay();
		if (!$canDisplay)
		{
			return $ret;
		}
	
		/** 1.返回给前端当天能看到的任务的info **/
		// 只返回给前端今天能显示的任务id
		$arrTaskId = NewServerActivityUtil::getArrOpenTaskIdConf();
		$curDate = NewServerActivityUtil::getActivityCurDay();
		$manager = NewServerActivityManager::getInstance($uid);
	
		$canUpdateTask = NewServerActivityUtil::canUpdateTask();
		// 用来控制拉取开服7天内的充值金币总和的次数
		$sumGold = -1;
		$fightFlag = true;
		foreach ($arrTaskId as $taskId)
		{
			$openDate = NewServerActivityUtil::getTaskDisplayDate($taskId);
			if ($curDate >= $openDate)
			{
				$finishNum = $manager->getTaskFinishNum($taskId);
				if($canUpdateTask)
				{
					$confReqNum = NewServerActivityUtil::getTaskRqrConf($taskId);
					if($finishNum < $confReqNum)
					{
						$taskType = NewServerActivityUtil::getType($taskId);
						switch ($taskType)
						{
							// 如果是充值类型,特殊判断,因为充值不提供En接口,所以在这触发记录dataModify
							case NewServerActivityDef::RECHARGE_GOLD:
								if ($sumGold < 0)
								{
									$sumGold = NewServerActivityUtil::getDuringRechargeGoldSum();
									$manager->updateTask($taskType, $sumGold);
									$finishNum = $manager->getTaskFinishNum($taskId);
								}
								break;
								// 战斗力因为必须打副本后端才会更新,所以做特殊处理,如果战斗力达到完成条件要求,则主动调用战斗接口计算战斗力,以保持前后端一致
							case NewServerActivityDef::FIGHT_FORCE:
								if($fightFlag && $fight > $finishNum)
								{
									// 更新战斗力任务状态
									$user = Enuser::getUserObj();
									// 清理战斗缓存
									$user->modifyBattleData();
									/* 然后拉取一次战斗信息,此时由于menCache里没有记录,会重新算一次战斗力,
									 * 此时会调用OtherUserObj里的setFightForce函数,这时候会触发我模块的
									* EnNewServerActivity::updateFightForce,这时候就会更新战斗力相关任务的进度
									*/
									$user->getBattleFormation();
									$user->update();
									// 如果更新过战斗力,则重新获取战斗力任务完成进度
									$finishNum = $manager->getTaskFinishNum($taskId);
									$fightFlag = false;
								}
								break;
						}
					}
				}
				
				$status = $manager->getTaskStatus($taskId);
				if ((NewServerActivityDef::WAIT == $status) && (0 == $finishNum))
				{
					continue;
				}
				$taskInfo[$taskId][NewServerActivityDef::STATUS] = $status;
				$taskInfo[$taskId][NewServerActivityDef::FINISHNUM] = $finishNum;
			}
		}
		$ret[NewServerActivityDef::TASK_INFO] = $taskInfo;
		
		/** 2.返回给前端抢购商品的信息数组 **/
		//只有前7天才能抢购商品
		$curDate = ($curDate > 7) ? 7: $curDate;
		for ($day = 1; $day <= $curDate; ++$day)
		{
			$remainNum = NewServerActivityGoodsManager::getInstance($day)->getGoodsRemainNum();
			$ret[NewServerActivityDef::PURCHASE][$day][NewServerActivityDef::REMAIN] = $remainNum;
			$buyFlag = $manager->isHadBuy($day);
			$ret[NewServerActivityDef::PURCHASE][$day][NewServerActivityDef::BUYFLAG] = $buyFlag ? 1: 0;
		}
	
		/** 3.返回给前端任务更新截止时间 和 “开服狂欢”关闭时间 **/
		$openServerTime = strtotime(GameConf::SERVER_OPEN_YMD);
		$ret[NewServerActivityCsvDef::DEADLINE] = $openServerTime + NewServerActivityUtil::getTaskDeadLine() * SECONDS_OF_DAY;
		$ret[NewServerActivityCsvDef::CLOSEDAY] = $openServerTime + NewServerActivityUtil::getCloseDate() * SECONDS_OF_DAY;
		
		return $ret;
	}
	
	public static function obtainReward($uid, $taskId)
	{
		// 1.检查传入的任务id是否在配置中
		$taskIdArr = NewServerActivityUtil::getArrTaskIdConf();
		if (!in_array($taskId, $taskIdArr))
		{
			throw new FakeException('taskId:%d not belongs to conf', $taskId);
		}
		
		// 2.是否在功能消失前
		$canDisplay = NewServerActivityUtil::canDisplay();
		if (!$canDisplay)
		{
			throw new FakeException("NewServerActivity activity is over, date is error");
		}
	
		// 3.任务是否达成
		$manager = NewServerActivityManager::getInstance($uid);
		$status = $manager->getTaskStatus($taskId);
		if (NewServerActivityDef::COMPLETE != $status)
		{
			throw new FakeException("current task's status:%s is not equal to status:complete, error. uid:%d, taskId:%d.", $status, $uid, $taskId);
		}
	
		// 4.任务是否今天可见
		$isTaskDisplay = NewServerActivityUtil::isTaskDisplay($taskId);
		if (!$isTaskDisplay)
		{
			throw new FakeException("taskId:%d cant display today", $taskId);
		}
		
		$manager->setTaskStatus($taskId, NewServerActivityDef::REWARDED);
	
		$reward = NewServerActivityUtil::getTaskRewardConf($taskId);
		$ret = RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_NEWSERVERACTIVITY_GET, false, false);
		$manager->save();
		RewardUtil::updateReward($uid, $ret);
		Logger::info("get NewServerActivity reward, uid:%d, taskId:%d", $uid, $taskId);
	
		return 'ok';
	}
	
	public static function buy($uid, $day)
	{
		/** 一堆check **/
		$ret = array();
		$goodsConf = NewServerActivityUtil::getGoodsData($day);
		// 1.检查配置是否存在
		if (empty($goodsConf))
		{
			throw new FakeException('goods of the day:%d from client is not in conf', $day);
		}
	
		// 2.1只能在活动任务进行时才能购买商品
		$canUpdateTask = NewServerActivityUtil::canUpdateTask();
		if(!$canUpdateTask)
		{
			$curDay = NewServerActivityUtil::getActivityCurDay();
			$deadLine = NewServerActivityUtil::getTaskDeadLine();
			throw new FakeException('curDay:%d beyond the buy period, the buy deadline is %d', $curDay, $deadLine);
		}
			
		// 2.2只可以购买当天以及之前天数的商品,这个判断在数据管理类中检查
		$goodsDataManager = NewServerActivityGoodsManager::getInstance($day);
		
		// 3.检查改商品id的购买数量是否超全服上限或者自己是否已经购买过了
		// 3.1全服购买是否超限
		$isExceedLimit = $goodsDataManager->isExceedLimit();
		if (NewServerActivityDef::LIMIT == $isExceedLimit)
		{
			// 全服受限商品超过购买次数时返回的结果
			$ret[NewServerActivityDef::RET] = NewServerActivityDef::LIMIT;
			$ret[NewServerActivityDef::REMAIN] = $goodsDataManager->getGoodsRemainNum();
			return $ret;
		}
		// 3.2自己是否重复购买
		$taskDataManager = NewServerActivityManager::getInstance($uid);
		$ifHadBuy = $taskDataManager->isHadBuy($day);
		if ($ifHadBuy)
		{
			throw new FakeException('goods of the day:%d had bought', $day);
		}
		
		// 4.检查扣金币
		$user = EnUser::getUserObj($uid);
		$curGold = $user->getGold();
		$confReqGold = $goodsConf[NewServerActivityCsvDef::PRICE];
		if ($curGold < $confReqGold)
		{
			throw new FakeException('curGold:%d less than conf require gold:%d, cannt buy goods of the day:%d', $curGold, $confReqGold, $day);
		}
		$subGold = $user->subGold($confReqGold, StatisticsDef::ST_FUNCKEY_NEWSERVERACTIVITY_COST);
		if (!$subGold)
		{
			throw new FakeException('sub gold:%d is failed', $confReqGold);
		}
	
		$arrItem = NewServerActivityUtil::getGoodsItem($day);
		Logger::info('confReqGold:%d curGold:%d goods of the day:%d itemArr:%s', $confReqGold, $curGold, $day, $arrItem);
		
		/** 记录奖励并发奖 **/
		// 5.记录购买,发物品
		$goodsDataManager->buy();
		$taskDataManager->buy($day);
		$rewardRet = RewardUtil::reward3DArr($uid, $arrItem, StatisticsDef::ST_FUNCKEY_NEWSERVERACTIVITY_GET, false, false);
		$goodsDataManager->update();
		$taskDataManager->save();
		RewardUtil::updateReward($uid, $rewardRet);
		$user->update();
	
		// 6.正常购买时返回的结果
		$ret[NewServerActivityDef::RET] = 'ok';
		$ret[NewServerActivityDef::REMAIN] = $goodsDataManager->getGoodsRemainNum();
	
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */