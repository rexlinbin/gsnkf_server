<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeRaffleLogic.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/ChargeRaffleLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class ChargeRaffleLogic 
{
    
    public static function getInfo($uid)
    {
        if(EnActivity::isOpen(ActivityName::CHARGERAFFLE) == FALSE)
        {
            throw new FakeException('activity is not open');
        }
        $inst = MyChargeRaffle::getInstance($uid);
        if($inst == NULL)
        {
            return array(
                    ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM1 => 0,
                    ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM2 => 0,
                    ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM3 => 0,
                    ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS => ChargeRaffleDef::REWARDSTATUS_NOREWARD,
                    );
        }
        $ret = $inst->getRaffleInfo();
        $inst->save();
        return $ret;
    }
    
    public static function raffle($index,$uid)
    {
        if(EnActivity::isOpen(ActivityName::CHARGERAFFLE) == FALSE)
        {
            throw new FakeException('activity is not open');
        }
        //是否能抽奖 1.是否充值了  2.是否有抽奖次数
        $inst = MyChargeRaffle::getInstance($uid);
        if($inst == NULL)
        {
            throw new FakeException('user %d charge no gold.',$uid);
        }
        if($inst->getCanRaffleNum($index) < 1)
        {
            throw new FakeException('user %d has no raffle num',$uid);
        }
        if($inst->raffle($index) == FALSE)
        {
            throw new FakeException('charge raffle failed.');
        }
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        //掉落东西
        $arrFreeDrop =  $conf['data']['arrDrop'.$index];
        $arrSpecailDrop = $conf['data']['specailDrop'.$index];
        $allRaffleNum = $inst->getAllRaffleNum($index);
        $dropId = 0;
        if(isset($arrSpecailDrop[$allRaffleNum]))
        {
            $dropId = $arrSpecailDrop[$allRaffleNum];
            Logger::info('chargeraffle.raffle allrafflenum %d user special dropid %d',
                    $allRaffleNum,$dropId);
        }
        else
        {
            $arrDrop = Util::noBackSample($arrFreeDrop, 1);
            if(count($arrDrop) != 1)
            {
                throw new FakeException('drop failed');
            }
            $dropId = $arrDrop[0];
        }
        $dropInfo = EnUser::drop($uid, array($dropId));
        if(count($dropInfo) != 1)
        {
            throw new FakeException('drop multi item.drop get is %s',$arrDrop);
        }
        $ret = array();
        foreach($dropInfo as $type => $dropInfoOnce)
        {
            if($type != DropDef::DROP_TYPE_STR_ITEM && ($type != DropDef::DROP_TYPE_STR_TREASFRAG))
            {
                throw new ConfigException('empty config.chargeraffle drop other type except item or treasurefrag');
            }
            switch($type)
            {
                case DropDef::DROP_TYPE_STR_ITEM:
                    $ret[RewardConfType::ITEM_MULTI] = $dropInfoOnce;
                    break;
                case DropDef::DROP_TYPE_STR_TREASFRAG:
                    $ret[RewardConfType::TREASURE_FRAG_MULTI] = $dropInfoOnce;
                    break;
            }
        }
        $inst->save();
        BagManager::getInstance()->getBag($uid)->update();
        return $ret;
    }
    
    
    public static function getConfReward()
    {
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $reward = $conf['data']['reward'];
        return $reward;
    }
    /**
     * 每日首冲大礼包
     * @param int $uid
     * @throws FakeException
     */
    public static function fetchReward($uid)
    {
        if(EnActivity::isOpen(ActivityName::CHARGERAFFLE) == FALSE)
        {
            throw new FakeException('activity is not open');
        }
        //是否能领奖 1.是否充值了  2.是否能领取首冲奖励
        $inst = MyChargeRaffle::getInstance($uid);
        if($inst == NULL)
        {
            throw new FakeException('user %d charge no gold.',$uid);
        }
        if($inst->getRewardStatus() != ChargeRaffleDef::REWARDSTATUS_HASREWARD)
        {
            throw new FakeException('can not get reward.reward status is %d',$inst->getRewardStatus());
        }
        if($inst->getReward() == FALSE)
        {
            throw new FakeException('fetch reward failed.');
        }
        $inst->save();
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $reward = $conf['data']['originalReward'];
        RewardUtil::reward($uid, $reward, StatisticsDef::ST_FUNCKEY_CHARGERAFFLE_GIFT);
        EnUser::getUserObj($uid)->update();
        BagManager::getInstance()->getBag($uid)->update();
        return 'ok';
    }
    
    public static function sendRewardOnce($uid)
    {
        $rewardConf = self::getConfReward();
        $userObj = EnUser::getUserObj($uid);
        $userLv = $userObj->getLevel();
        $reward = array();
        foreach($rewardConf as $confType => $rewardInfo)
        {
            switch($confType)
            {
                case RewardConfType::SILVER_MUL_LEVEL:
                case RewardConfType::SOUL_MUL_LEVEL:
                case RewardConfType::EXP_MUL_LEVEL:
                    $rewardInfo = intval($rewardInfo) * $userLv;
                    break;
            }
            $type = RewardConfType::$rewardUtil2Center[$confType];
            $reward[$type] = $rewardInfo;
        }
        EnReward::sendReward($uid, RewardSource::CHARGE_RAFFLE, $reward);
    }
    
    public static function getChargeNum($startTime,$endTime,$uid)
    {
        return Enuser::getRechargeGoldByTime($startTime, $endTime, $uid);
    }
    /**
     * 获取凌晨时间戳
     * @param int $time
     */
    public static function getDayBreak($time)
    {
        return strtotime(date("Y-m-d", $time));
    }
    
    public static function getActStartTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $start = $conf['start_time'];
        return $start;
    }
    
    public static function getActEndTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $start = $conf['end_time'];
        return $start;
    }
    
    public static function isUserChargeDuringAct($uid)
    {
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $start = $conf['start_time'];
        $end = $conf['end_time'];
        $num = self::getChargeNum($start, $end, $uid);
        if($num > 0)
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function getRaffleNumByCharge($chargeGold)
    {
        $conf = EnActivity::getConfByName(ActivityName::CHARGERAFFLE);
        $actConf = $conf['data'];
        $needGold = $actConf['needCharge'];
        $raffleNumLimit = $actConf['raffleMaxNum'];
        if(count($needGold) != 3 || (count($raffleNumLimit) != 3))
        {
            throw new ConfigException('config error.needgold or rafflenumlimit count is not 3');
        }
        $maxIndexGold = $needGold[ChargeRaffleDef::MAX_RAFFLE_CLASS];
        $minIndexGold = $needGold[ChargeRaffleDef::MIN_RAFFLE_CLASS];
        $allAddNum = intval($chargeGold/$maxIndexGold);
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $raffleNum[$i] = $allAddNum;
        }
        $extraGold = $chargeGold%$maxIndexGold;
        $arrIndex = array();
        for($i=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i>=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i--)
        {
            if($extraGold < $needGold[$i])
            {
                continue;
            }
            $raffleNum[$i]++;
        }
        foreach($raffleNum as $index => $num)
        {
            if($num > $raffleNumLimit[$index])
            {
                $raffleNum[$index] = $raffleNumLimit[$index];
            }
        }
        return $raffleNum;
    }
    
    public static function addRewardTimer()
    {
        Logger::trace('addRewardTimer');
        if(EnActivity::isOpen(ActivityName::CHARGERAFFLE) == FALSE)
        {
            throw new FakeException('ChargeRaffleLogic.addRewardTimer act chargeRaffle is not open');
        }
        $taskName = 'chargeraffle.rewardUserOnActEnd';
        $endTime = self::getActEndTime() + ChargeRaffleDef::REWARDUSER_ACTEND_DELAY;
        $ret = EnTimer::getArrTaskByName($taskName,array(TimerStatus::RETRY,TimerStatus::UNDO), 0);
        $findValid = FALSE;
        foreach($ret as $index => $timer)
        {
            if($timer['status'] == TimerStatus::RETRY)
            {
                Logger::fatal('the timer %d is retry.but the activity not end.',$timer['tid']);
                TimerTask::cancelTask($timer['tid']);
                continue;
            }
            if($timer['status'] == TimerStatus::UNDO)
            {
                if($timer['execute_time'] != $endTime)
                {
                    Logger::fatal('addRewardTimer.invalid timer %d.execute_time %d',$timer['tid'],$timer['execute_time']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else if($findValid)
                {
                    Logger::fatal('addRewardTimer.one more valid timer.timer %d.',$timer['tid']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else
                {
                    Logger::trace('addRewardTimer findvalid');
                    $findValid = TRUE;
                }
            }
        }
        if($findValid == FALSE)
        {
            Logger::fatal('no valid timer.addTask for chargeraffle.rewardUserOnActEnd.');
            TimerTask::addTask(SPECIAL_UID::CHARGERAFFLE_REWARDTIMER,
                    $endTime, $taskName, array());
        }
    }
    
    /**
     * 给所有在活动期间内 
     */
    public static function rewardUserOnActEnd()
    {
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        /**
         * @var array
         * [
         *     uid:array
         *     [
         *         date=>charge_num
         *     ]
         * ]
         */
        $arrUserCharge = array();
        //获取活动期间内的所有充值订单
        while($startTime < $endTime)
        {
            $dayStartTime = $startTime;
            $dayEndTime = self::getDayBreak($startTime) + SECONDS_OF_DAY - 1;
            if($dayEndTime > $endTime)
            {
                $dayEndTime = $endTime;
            }
            //拉取某一天的充值信息
            $offset = 0;
            $limit = CData::MAX_FETCH_SIZE;
            while(TRUE)
            {
                $arrOrder = User4BBpayDao::getArrOrder(array('uid','gold_num'),
                         $dayStartTime, $dayEndTime, $offset, $limit, OrderType::NORMAL_ORDER);
                foreach($arrOrder as $orderInfo)
                {
                    $uid = $orderInfo['uid'];
                    $chargeGold = $orderInfo['gold_num'];
                    if(!isset($arrUserCharge[$uid][$dayStartTime]))
                    {
                        $arrUserCharge[$uid][$dayStartTime] = 0;
                    }
                    $arrUserCharge[$uid][$dayStartTime] += $chargeGold;
                }
                $offset += $limit;
                if(count($arrOrder) < $limit)
                {
                    break;
                }
            }
            $startTime = $startTime + SECONDS_OF_DAY;
        }
        
        //拉取所有参与此活动的玩家 last_rfr_time > start_time
        //如果fetch_reward_time>=活动结束当天的凌晨   不需要处理了   否则补发每日充值奖励
        $arrUserRewardNum = array();
        $offset = 0;
        $actEndDayBreak = self::getDayBreak(self::getActEndTime());
        while(TRUE)
        {
            $data = new CData();
            $ret = $data->select(array(ChargeRaffleDef::TBLFIELD_REWARDTIME,ChargeRaffleDef::TBLFIELD_UID))
                        ->from(ChargeRaffleDef::TBLNAME)
                        ->where(array(ChargeRaffleDef::TBLFIELD_LASTRFRTIME,'>',self::getActStartTime()))
                        ->orderBy(ChargeRaffleDef::TBLFIELD_UID, TRUE)
                        ->limit($offset, $limit)
                        ->query();
            foreach($ret as $userActInfo)
            {
                $uid = $userActInfo[ChargeRaffleDef::TBLFIELD_UID];
                $rewardTime = $userActInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME];
                if($rewardTime >= $actEndDayBreak)
                {
                    unset($arrUserCharge[$uid]);
                    Logger::info('rewardUserOnActEnd user %d has fetch all reward',$uid);
                    continue;
                }
                $rewardNum = 0;
                foreach($arrUserCharge[$uid] as $dayTime => $chargeGold)
                {
                    $dayBreak = self::getDayBreak($dayTime);
                    if($rewardTime < $dayBreak)//没有领取奖励
                    {
                        $rewardNum++;
                    }
                }
                for($i=0;$i<$rewardNum;$i++)
                {
                    self::sendRewardOnce($uid);
                }
                Logger::info('rewardUserOnActEnd user %d has reward num %d send center.',$uid,$rewardNum);
                unset($arrUserCharge[$uid]);
            }
            $offset += $limit;
            if(count($ret) < $limit)
            {
                break;
            }
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */