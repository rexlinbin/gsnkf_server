<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalLogic.class.php 175207 2015-05-28 02:39:18Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/festival/FestivalLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-28 10:39:18 +0800 (星期四, 28 五月 2015) $
 * @version $Revision: 175207 $
 * @brief
 *
 **/
class FestivalActLogic
{
	public static function getInfo($uid)
	{
	    // 返回的值
	    $ret = array();
	    // 对Va做下处理再返回给其前端 - 完整数据和红点信息
		$ret = self::dealInfoToFore($uid);
		// 得到当前的季度和活动进行的天数
		$confObj = FestivalActConf::getInstance();
		$ret['period'] = $confObj->getCurPeriod();
		$ret['day'] = $confObj->getCurDay();
        // 返回
		return $ret;
	}

	/**
	 * 任务直接领奖励接口
	 */
	public static function taskReward($uid, $misID)
	{
	    // 得到数据对象
	    $festivalActObj = FestivalActManager::getInstance($uid);
	    // 查看奖励所在季度
	    $confObj = FestivalActConf::getInstance();
	    $periodID = $confObj->getMisIDOfPeriod($misID);
	    if ($periodID == 0)
	    {
	        throw new FakeException('taskReward err misID:%s periodID is 0.', $misID);
	    }
	    // 查看任务是否完成
	    $big_type = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::BIGTYPE);
	    $ret = $festivalActObj->canRewardByTask($periodID, $big_type, $misID, false);
	    if (!$ret)
	    {
	        throw new FakeException('FestivalAct task periodID:%s, misID:%s is not completed.', $periodID, $misID);
	    }
	    // 没问题就领奖
	    $get = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::GET);
	    $rewardInfo = RewardUtil::reward3DArr($uid, $get, StatisticsDef::ST_FUNCKEY_FESTIVALACT_TASKREWARD, FALSE, FALSE);
	    // 好 都OK
	    $festivalActObj->taskReward($big_type, $periodID, $misID);
	    // 没问题的话就更新吧
	    $festivalActObj->update();
	    RewardUtil::updateReward($uid, $rewardInfo);
	    return 'ok';

	}

	/**
	 * 补签 - 扣金币 - 领取奖励
	 */
	public static function signReward($uid, $misID)
	{
	    // 查找要补签任务的季度
        $confObj = FestivalActConf::getInstance();
	    $periodID = $confObj->getMisIDOfPeriod($misID);
	    if ($periodID == 0)
	    {
	        throw new FakeException('signReward misID:%s not exist.', $misID);
	    }
	    // 看看有没有领过奖励
	    $festivalActObj = FestivalActManager::getInstance($uid);
	    $ret = $festivalActObj->canRewardByTask($periodID, FestivalActDef::ACT_TYPE_TASK, $misID, true);
	    if (!$ret)
	    {
	        throw new FakeException('signReward signtask periodID:%s, misID:%s is rewarded.', $periodID, $misID);
	    }
	    // 补签是直接扣金币20 - 策划让写死在程序里
	    $need = intval(FestivalActDef::SIGN_FILL_IN_NEED);
	    $need = array(array(RewardConfType::GOLD, 0, intval($need)));
	    $statistics = StatisticsDef::ST_FUNCKEY_FESTIVALACT_SIGN;
	    $subUpdate = RewardUtil::delMaterial($uid, $need, $statistics, 1, array(), false);
	    // 看看给点啥
	    $get = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::GET);
	    $addUpdate = RewardUtil::reward3DArr($uid, $get, $statistics, FALSE, FALSE);
	    // 好 都OK
	    $festivalActObj->signReward($periodID, $misID);
	    // 没问题的话就更新吧
	    $festivalActObj->update();
	    $update = self::mergeUpdate($subUpdate, $addUpdate);
	    RewardUtil::updateReward($uid, $update);
	    return 'ok';
	}


	/**
	 * 限时购买
	 */
	public static function buy($uid, $misID, $num)
	{
	    // 买东西的话，看看还在不在折扣时间
	    $bigType = FestivalActDef::ACT_TYPE_DISCOUNT;
	    $confObj = FestivalActConf::getInstance();
	    $periodID = $confObj->checkMisPeriod($bigType, $misID);
	    // 看看已经进行了几次
	    $dataObj = FestivalActManager::getInstance($uid);
	    $beforeNum = $dataObj->getPeriodBigTypeMisIDNum($periodID, $bigType, $misID);
	    // 得到要买的ID的配置数量
	    $canNum = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::NUM);
	    // 看还能不能兑换
	    if ($beforeNum + $num > $canNum)
	    {
	        throw new FakeException('FestivalAct misID:%s beforeNum:%s + num:%s > canNum:%s.', $misID, $beforeNum, $num, $canNum);
	    }
	    // 看看需要的够不够
	    $need = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::NEED);
	    $need = array(array(RewardConfType::GOLD, 0, intval($need)));
	    $statistics = StatisticsDef::ST_FUNCKEY_FESTIVALACT_BUY;
	    $subUpdate = RewardUtil::delMaterial($uid, $need, $statistics, $num, array(), false);
	    // 扣完给东西
	    $get = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::GET);
	    // 函数里没有倍数参数，所以自己搞一下
	    $get = self::dealRewardAmount($get, $num);
	    // 给东西吧
	    $addUpdate = RewardUtil::reward3DArr($uid, $get, $statistics, FALSE, FALSE);
	    // 记录下次数
	    $dataObj->buy($periodID, $misID, $num);
	    // 没问题的话就更新吧
	    $dataObj->update();
	    // 把加和减的一起更新
	    $update = self::mergeUpdate($subUpdate, $addUpdate);
	    RewardUtil::updateReward($uid, $update);
	    return 'ok';
	}


    /**
     * 限时兑换
     */
	public static function exchange($uid, $misID, $num)
	{
	    // 看看已经进行了几次
	    $dataObj = FestivalActManager::getInstance($uid);
	    $beforeNum = $dataObj->getExchangeMisIDNum($misID);
	    // 得到要买的ID的配置数量
	    $confObj = FestivalActConf::getInstance();
	    $canNum = $confObj->getExchangeMisKey($misID, FestivalActDef::NUM);
	    // 看还能不能兑换
	    if ($beforeNum + $num > $canNum)
	    {
	        throw new FakeException('FestivalAct misID:%s beforeNum:%s + num:%s > canNum:%s.', $misID, $beforeNum, $num, $canNum);
	    }
	    // 看看需要的够不够
        $need = $confObj->getExchangeMisKey($misID, FestivalActDef::NEED);
        $statistics = StatisticsDef::ST_FUNCKEY_FESTIVALACT_EXCHANGE;
        $subUpdate = RewardUtil::delMaterial($uid, $need, $statistics, $num, array(), false);
        // 扣完给东西
        $get = $confObj->getExchangeMisKey($misID, FestivalActDef::GET);
        // 函数里没有倍数参数，所以自己搞一下
        $get = self::dealRewardAmount($get, $num);
        // 给东西吧
        $addUpdate = RewardUtil::reward3DArr($uid, $get, $statistics, FALSE, FALSE);
        // 记录下次数
        $dataObj->exchange($misID, $num);
        // 没问题的话就更新吧
        $dataObj->update();
        // 把加和减的一起更新
        $update = self::mergeUpdate($subUpdate, $addUpdate);
        RewardUtil::updateReward($uid, $update);
        return 'ok';
	}

	/**
	 * 把更新的加和减合并到一起
	 */
	protected static function mergeUpdate($subUpdate, $addUpdate)
	{
		Logger::debug('mergeUpdate subUpdate:%s, addUpdate:%s.', $subUpdate, $addUpdate);
	    if (empty($subUpdate))
	    {
	        return $addUpdate;
	    }
	    foreach ($subUpdate as $key => $value)
	    {
	        // 没有 或者为 false
	        if (!isset($addUpdate[$key]) || !$addUpdate[$key])
	        {
	            $addUpdate[$key] = $subUpdate[$key];
	        }
	    }
	    return $addUpdate;
	}

	/**
	 * 奖励的东西有倍数，这里将奖励数量修正下
	 */
	public static function dealRewardAmount($reward3DArr, $num)
	{
	    Logger::debug('dealRewardAmount reward3DArr:%s, num:%s.', $reward3DArr, $num);
	    if ($num == 1)
	    {
	        return $reward3DArr;
	    }
	    $ret = array();
	    foreach ($reward3DArr as $info)
	    {
	        $info[2] = intval($info[2]) * intval($num);
	        $ret[] = $info;
	    }
	    return $ret;
	}

	/**
	 * 检查今天登陆的任务是否
	 * 只要当天没领登陆奖，之后就需要花钱补签，所以不用记录当前是否登陆过
	 * $data[$periodID][$bigType][$missionID] = array($num, $status);
	 * $redtip[$missionID] = $status;
	 */
	protected static function loginCheck($uid, &$data, &$redtip)
	{
	    // 目标就是找到今天的任务ID，然后没有领奖的时候标记可领取
	    // 先取配置，遍历当前季度的登陆任务，找到今天的登陆任务
	    $misID = FestivalActConf::getInstance()->getCurDayLoginTaskID();
	    // 没有就说明这天没有登陆奖励或者今天已经是延长出来的最后一天啦
	    if (empty($misID))
	    {
	        return ;
	    }
	    // 有的话检查下今天的配置有没有领奖
	    else
	    {
	        $curPeriod = FestivalActConf::getInstance()->getCurPeriod();
	        $bigType = FestivalActDef::ACT_TYPE_TASK;
	        $status = FestivalActManager::getInstance($uid)->getPeriodBigTypeMisIDStatus($curPeriod, $bigType, $misID);
	        if ($status == 2)
	        {
	            // 已经领奖就直接返回吧
	            return ;
	        }
	        else
	        {
	            $data[$curPeriod][FestivalActDef::ACT_TYPE_TASK][$misID][1] = 1;
	            $redtip[$misID] = 1;
	            return ;
	        }
	    }
	}


	/**
	 * 通知活动任务的完成情况
	 */
	public static function __notify($uid, $type_id, $num)
	{
	    Logger::debug('FestivalActLogic::__notify uid:%s, type:%s, num:%s.', $uid, $type_id, $num);
	    $dataObj = FestivalActManager::getInstance($uid);
	    switch ($type_id)
	    {
	        // 以下任务是靠记录累加的
	        case FestivalActDef::TASK_COPY_ANY_NUM:
	        case FestivalActDef::TASK_COPY_ELITE_NUM:
	        case FestivalActDef::TASK_FRAG_NUM:
	        case FestivalActDef::TASK_BLACK_SHOP_EXCHARGE_NUM:
	        case FestivalActDef::TASK_ARENA_NUM:
    	        // 直接记录数量
    	        $dataObj->notifyTask($type_id, $num);
    	        break;
    	    // 以下任务是将总次数发过来的
	        case FestivalActDef::TASK_SCORE_WHEEL_POINT:
	        case FestivalActDef::TASK_COMPETE_NUM:
	        case FestivalActDef::TASK_TRAVEL_SHOP_BUY_NUM:
	        case FestivalActDef::TASK_WORLD_GROUPON_POINT:
	            // 替换当前记录
	            $dataObj->notifyTask($type_id, $num, true);
	            break;
    	    default:
    	        throw new FakeException('err Type:%s.', $type_id);
	    }
	    // 最后再更新
	    $dataObj->update();
	}

	/**
	 * 对后端的数据处理下再返回给前端
	 * $data[$periodID][$bigType][$missionID] = array($num, $status);
	 * $redtip[$missionID] = $status;
	 */
	public static function dealInfoToFore($uid)
	{
	    Logger::debug('dealInfoToFore start');
	    // 先取下配置和数据
	    $confObj = FestivalActConf::getInstance();
	    $dataObj = FestivalActManager::getInstance($uid);
	    // 返回给前端的data数据
	    $data = array();
	    // 返回给前端的exchange数据
	    $exchange = array();
	    // 前端需要的红点数据
	    $redtip = array();
	    // 兑换的
	    $exchangeInfo = $confObj->getMisArrByExchange();
	    foreach ($exchangeInfo as $misID)
	    {
	        $num = $dataObj->getExchangeMisIDNum($misID);
	        $status = $dataObj->getExchangeMisIDStatus($misID);
	        // 限时兑换的红点状态只有0和2, 1由前端自己判断
	        $redtip[$misID] = $status;
	        // 一开始脑子不好使写成这样 后来前端又不改，就有了这种情况。。。。一个类的status表示的含义不同。。。。
	        $status = ($status == 0) ? 0 : 1;
	        $exchange[$misID] = array($num, $status);
	    }
	    // 充值的话比较特殊，需要实时拉取下用户在活动期间内的充值记录，所以统一处理下
	    $toReward = self::dealChargeInfo($uid);
	    $typeInfo = $confObj->getMisArrPartByPeriodBigType();
	    foreach ($typeInfo as $periodID => $periodInfo)
	    {
	        foreach ($periodInfo as $bigType => $misArr)
	        {
	            foreach ($misArr as $misID)
	            {
	                switch ($bigType)
	                {
	                    // 任务的数据格式是[今天前的计数，今天的计数，完成情况0未完成|1完成未领奖|2领过奖, 上一次操作的时间]
	                    case FestivalActDef::ACT_TYPE_TASK:
	                        $num = $dataObj->getPeriodBigTypeMisIDNum($periodID, $bigType, $misID);
	                        $status = $dataObj->getPeriodBigTypeMisIDStatus($periodID, $bigType, $misID);
	                        $data[$periodID][$bigType][$misID] = array($num, $status);
	                        $redtip[$misID] = $status;
	                        break;
	                    // 折扣数据格式是[使用的次数，上一次操作时间]
	                    case FestivalActDef::ACT_TYPE_DISCOUNT:
	                        $num = $dataObj->getPeriodBigTypeMisIDNum($periodID, $bigType, $misID);
	                        $status = $dataObj->getPeriodBigTypeMisIDStatus($periodID, $bigType, $misID);
	                        // 限时折扣的红点状态只有0和2, 1由前端自己判断
	                        $redtip[$misID] = $status;
	                        // 一开始脑子不好使写成这样 后来前端又不改，就有了这种情况。。。。一个类的status表示的含义不同。。。。
	                        $status = ($status == 0) ? 0 : 1;
	                        $data[$periodID][$bigType][$misID] = array($num, $status);
	                        break;
	                    // 充值兑换的数据格式[使用的次数，上一次操作时间]
	                    case FestivalActDef::ACT_TYPE_CHARGE:
	                        $num = $dataObj->getPeriodBigTypeMisIDNum($periodID, $bigType, $misID);
	                        $confNum = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::NUM);
	                        $canReward = 0;
	                        if (isset($toReward[$periodID][$misID]))
	                        {
	                            $canReward = $toReward[$periodID][$misID];
	                        }
	                        $data[$periodID][$bigType][$misID] = array($num, $canReward);
	                        $redtip[$misID] = 0;
	                        if ($canReward > 0)
	                        {
	                            $redtip[$misID] = 1;
	                        }
	                        if ($num >= $confNum)
	                        {
	                            $redtip[$misID] = 2;
	                        }
	                        break;
	                    default:
	                        throw new FakeException('err bigType:%s.', $bigType);
	                }
	            }
	        }
	    }
	    // 登陆任务是需要特殊处理的 因为没有单独的通知
	    self::loginCheck($uid, $data, $redtip);
	    // 然后把推送发出去 - 下一个模块在做的时候不要这么干了吧。。。。
	    RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::FESTIVALACT_NEW_FINISH, $redtip);
	    return array(
	        'exchange' => $exchange,
	        'data' => $data,
	        );
	}

	public static function dealChargeInfo($uid)
	{
	    Logger::debug('dealChargeInfo start uid:%s.', $uid);
	    // 得到数据对象
	    $dataObj = FestivalActManager::getInstance($uid);
	    // 配置里 季度->(金额=>可以领奖次数)
	    $confGoldNum = array();
	    // 配置里 季度->(金额->任务ID)
	    $confGoldMisID = array();
	    // 充值后领奖的数组 季度->(金额=>领奖次数)
	    $hadReward = array();
	    // 得到按季度大类划分的配置模式
	    $confObj = FestivalActConf::getInstance();
	    $typeInfo = $confObj->getMisArrPartByPeriodBigType();
	    // 标记下还有没有奖励的可能
	    $hasReward = false;
	    // 通过数据库里面的大类直接取充值ID
	    foreach ($typeInfo as $periodID => $periodInfo)
	    {
	        // 要考虑没有充值活动的情况
	        if (!isset($periodInfo[FestivalActDef::ACT_TYPE_CHARGE]))
	        {
	            continue;
	        }
	        foreach ($periodInfo[FestivalActDef::ACT_TYPE_CHARGE] as $misID)
	        {
	            $gold = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::NEED);
	            $confGoldNum[$periodID][$gold] = $confObj->getPeriodMisKey($periodID, $misID, FestivalActDef::NUM);
	            $confGoldMisID[$periodID][$gold] = $misID;
	            $num = $dataObj->getPeriodBigTypeMisIDNum($periodID, FestivalActDef::ACT_TYPE_CHARGE, $misID);
	            $hadReward[$periodID][$gold] = $num;
	            if ($hadReward[$periodID][$gold] < $confGoldNum[$periodID][$gold])
	            {
	                $hasReward = true;
	            }
	       }
	    }
	    // 若是每个季度都没有充值活动的话
	    if (empty($confGoldNum))
	    {
	        return array();
	    }
	    // 要是奖励都已经领完啦也返回吧
	    if (!$hasReward)
	    {
	        Logger::debug('dealChargeInfo has not reward.');
	        return array();
	    }

	    // 将实际充值按季度分开，季度->(充值数=>充值次数)
	    $rechargeRecord = array();
        // 通过查询和遍历配置得到季度对应的充值记录
	    $start_time = $confObj->getActStartTime();
	    $rechargeArr = EnUser::getChargeOrderByTime($start_time, Util::getTime(), $uid);
	    Logger::debug('dealChargeInfo rechargeArr:%s.', $rechargeArr);
	    // $rechargeRecord遍历完存的是每个季度每种实际充值对应的充值次数
	    foreach ($rechargeArr as $orderInfo)
	    {
	        // 充值金额
	        $gold = $orderInfo['gold_num'];
            // 充值时间所在阶段
	        $periodID = $confObj->getTimeInPeriod($orderInfo['mtime']);
	        // 正好不在有充值奖励的季度
	        if ($periodID == -1)
	        {
	            continue;
	        }
	        // 还没记录过就初始化下数据
	        if (!isset($rechargeRecord[$periodID][$gold]))
	        {
	            $rechargeRecord[$periodID][$gold] = 0;
	        }
	        $rechargeRecord[$periodID][$gold]++;
	   }
	   Logger::debug('dealChargeInfo rechargeRecord:%s.', $rechargeRecord);
       // 要是没有充值的记录也可以直接返回啦
       if (empty($rechargeRecord))
       {
           return array();
       }

	   // 从配置和实际充值算一下可以领奖的数组
	   $canReward = array();
	   // 根据配置看看一共可以领哪些奖
       foreach ($rechargeRecord as $periodID => $periodInfo)
       {
           // 先将充值和次数的数组按降序排列
           krsort($confGoldNum[$periodID]);
           // 然后遍历 - 把每一笔充值对应到奖励配置里去
           foreach ($periodInfo as $gold => $rewardNum)
           {
               while ($rewardNum > 0)
               {
                   foreach ($confGoldNum[$periodID] as $confGold => $num)
                   {
                       if ($confGold <= $gold && $num >= 1)
                       {
                           $confGoldNum[$periodID][$confGold]--;
                           if (!isset($canReward[$periodID][$confGold]))
                           {
                               $canReward[$periodID][$confGold] = 0;
                           }
                           $canReward[$periodID][$confGold]++;
                           Logger::debug('dealChargeInfo confGoldNum:%s, canReward:%s.', $confGoldNum[$periodID], $canReward[$periodID]);
                           break;
                       }
                   }
                   $rewardNum--;
               }
               // 已经都领完啦就不用再遍历啦
               if(empty($confGoldNum[$periodID]))
               {
                   break;
               }
           }
       }

       // 还可以领奖的数组 季度->(ID->次数)
       $toReward = array();
       foreach ($canReward as $periodID => $periodInfo)
       {
           foreach ($periodInfo as $confGold => $canNum)
           {
               if (!isset($hadReward[$periodID][$confGold]))
               {
                   $hadReward[$periodID][$confGold] = 0;
               }
               if ($canNum > $hadReward[$periodID][$confGold])
               {
                   $misID = $confGoldMisID[$periodID][$confGold];
                   $toReward[$periodID][$misID] = $canNum - $hadReward[$periodID][$confGold];
               }
           }
       }
       Logger::debug('dealChargeInfo ret toReward:%s.', $toReward);
       return $toReward;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */