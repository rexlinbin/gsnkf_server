<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyChargeRaffle.class.php 125646 2014-08-08 03:04:09Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/MyChargeRaffle.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-08 03:04:09 +0000 (Fri, 08 Aug 2014) $
 * @version $Revision: 125646 $
 * @brief 
 *  
 **/
class MyChargeRaffle
{
    //从数据库拉取的信息
    private $dbInfo = NULL;
    private $buffer = NULL;
    private $uid = NULL;
    private $rewardNum = 0;
    //比较容易使用的信息
    private $raffleInfo = NULL;
    /**
     * 
     * @var MyChargeRaffle
     */
    private static $_instance = NULL;
    
    
    public static function getInstance($uid)
    {
        if(self::$_instance == NULL || (self::$_instance->uid != $uid))
        {
            self::$_instance = new self($uid);
        }
        $raffleInfo = self::$_instance->getRaffleInfo();
        if(empty($raffleInfo))
        {
            return NULL;
        }
        return self::$_instance;
    }
    
    public static function release()
    {
        self::$_instance = NULL;
    }
    
    private function __construct($uid)
    {
        if(empty($uid))
        {
            throw new FakeException('empty uid.can not get raffle info');
        }
        $info = ChargeRaffleDao::getRaffleInfo($uid);
        $this->uid = $uid;
        $this->dbInfo = $info;
        $this->buffer = $info;
        $this->rfrInfoAndDbInfoToRaffleInfo();
    }
    
    private function rfrInfoAndDbInfoToRaffleInfo()
    {
        Logger::trace('dbinfo %s',$this->dbInfo);
        if(empty($this->dbInfo))
        {
            //如果活动期间内充值了 init
            if(ChargeRaffleLogic::isUserChargeDuringAct($this->uid))
            {
                $this->init();
            }
            else
            {
                $this->raffleInfo = array();
                return;
            }
        }
        else
        {
            //如果lastRfrTime小于活动开始时间  init
            if($this->getLastRfrTime() < ChargeRaffleLogic::getActStartTime())
            {
                $this->init();
            }
        }
        Logger::trace('dbinfo %s',$this->dbInfo);
        //先刷新信息 刷新信息：1.昨天没有使用的次数进行累积    2.没有领取每次首冲奖励的数目
        $lastRfrTime = $this->getLastRfrTime();
        while(Util::isSameDay($lastRfrTime) == FALSE && $lastRfrTime < Util::getTime())
        {
            $startTime = ChargeRaffleLogic::getDayBreak($lastRfrTime);
            $endTime = $startTime + SECONDS_OF_DAY - 1;
            $chargeGold = ChargeRaffleLogic::getChargeNum($startTime, $endTime, $this->uid);            
            $arrRaffleNum = ChargeRaffleLogic::getRaffleNumByCharge($chargeGold);
            foreach($arrRaffleNum as $index => $num)
            {
                $raffleNum = $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                        [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$index];
                $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                    [ChargeRaffleDef::TBLFIELD_ACCUMNUM][$index] += ($num - $raffleNum);
            }     
            if($chargeGold > 0 && ($this->dbInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] < $startTime))
            {
                Logger::trace('starttime %d endtime %d.not get reward',$startTime,$endTime);
                $this->rewardNum ++;
            }       
            $this->resetInfoOnDayBreak($lastRfrTime + SECONDS_OF_DAY, $lastRfrTime);
            $lastRfrTime = $lastRfrTime + SECONDS_OF_DAY;
        }
        //根据今天的充值情况 刷新可抽奖次数和首冲奖励
        $startTime = ChargeRaffleLogic::getDayBreak(Util::getTime());
        $endTime = $startTime + SECONDS_OF_DAY;
        $todayCharge = ChargeRaffleLogic::getChargeNum($startTime, $endTime, $this->uid);
        $arrRaffleNum = ChargeRaffleLogic::getRaffleNumByCharge($todayCharge);
        Logger::trace('dbinfo is %s',$this->dbInfo);
        foreach($arrRaffleNum as $index => $num)
        {
            $canRaffleField = constant('ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM'.$index);
            $raffleNum = $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$index];
            $accumNum = $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                [ChargeRaffleDef::TBLFIELD_ACCUMNUM][$index];
            if($raffleNum > $num + $accumNum)
            {
                $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                    [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$index] = $num + $accumNum;
                $raffleNum = $num + $accumNum;
                Logger::fatal("time %d index is %d rafflenum is %d today_accum_num is %d accum_num before today is  %d",
                        $startTime,$index,$raffleNum,$num,$accumNum);
            }
            $this->raffleInfo[$canRaffleField] = $accumNum + ($num - $raffleNum);
        }
        if($todayCharge > 0)
        {
            if($this->dbInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] < $startTime)
            {
                $this->raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS] = ChargeRaffleDef::REWARDSTATUS_HASREWARD;
            }
            else
            {
                $this->raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS] = ChargeRaffleDef::REWARDSTATUS_GETREWARD;
            }
        }
        else
        {
            $this->raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS] = ChargeRaffleDef::REWARDSTATUS_NOREWARD;
        }
    }
    
    public function init()
    {
        //初始化时设置上次刷新时为活动开始时间   是为了下面进行抽奖次数累积和每日首冲奖励累积
        $actStartTime = ChargeRaffleLogic::getActStartTime();
        $vaInfo = array();
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $vaInfo[ChargeRaffleDef::TBLFIELD_RAFFLENUM][$i] = 0;
            $vaInfo[ChargeRaffleDef::TBLFIELD_ACCUMNUM][$i] = 0;
            $vaInfo[ChargeRaffleDef::TBLFIELD_ALLRAFFLENUM][$i] = 0;
        }
        $info = array(
                ChargeRaffleDef::TBLFIELD_UID => $this->uid,
                ChargeRaffleDef::TBLFIELD_LASTRFRTIME => $actStartTime,
                ChargeRaffleDef::TBLFIELD_REWARDTIME => 0,
                ChargeRaffleDef::TBLFIELD_VA_INFO => $vaInfo,
        );
        $this->dbInfo = $info;
    }
    
    private function resetInfoOnDayBreak($rfrTime,$rewardTime)
    {
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$i] = 0;
        }
        $this->dbInfo[ChargeRaffleDef::TBLFIELD_LASTRFRTIME] = $rfrTime;
        $this->dbInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] = $rewardTime;
    }
        
    private function getLastRfrTime()
    {
        return $this->dbInfo[ChargeRaffleDef::TBLFIELD_LASTRFRTIME];
    }
    
    public function getUid()
    {
        return $this->uid;
    }
    
    public function getRaffleInfo()
    {
        return $this->raffleInfo;
    }
    
    public function getCanRaffleNum($index)
    {
        return $this->raffleInfo[constant('ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM'.$index)];
    }
    
    public function raffle($index)
    {
        $canRaffle = $this->raffleInfo[constant('ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM'.$index)];
        if($canRaffle < 1)
        {
            return FALSE;
        }
        $this->raffleInfo[constant('ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM'.$index)]--;
        $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$index]++;
        $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                [ChargeRaffleDef::TBLFIELD_ALLRAFFLENUM][$index]++;
        return TRUE;
    }
    
    public function getRewardStatus()
    {
        return $this->raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS];
    }
    
    public function setRewardTime($time)
    {
        $this->dbInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] = $time;
    }
    
    public function getAllRaffleNum($index)
    {
        return $this->dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
            [ChargeRaffleDef::TBLFIELD_ALLRAFFLENUM][$index];
    }
    
    public function getReward()
    {
        if($this->getRewardStatus() != ChargeRaffleDef::REWARDSTATUS_HASREWARD)
        {
            return FALSE;
        }    
        $this->setRewardTime(Util::getTime());
        return TRUE;
    }
    
    public function save()
    {
        $guid = RPCContext::getInstance()->getUid();
        if($this->uid != $guid)
        {
            Logger::warning('update.but current user %d is not guid %d',$this->uid,$guid);
            return;
        }
        if($this->dbInfo != $this->buffer)
        {
            ChargeRaffleDao::saveRaffleInfo($this->dbInfo);
            for($i=0;$i<$this->rewardNum;$i++)
            {
                ChargeRaffleLogic::sendRewardOnce($this->uid);
            }
            if($this->rewardNum > 0)
            {
                $lastRewardTime = empty($this->buffer)?0:$this->buffer[ChargeRaffleDef::TBLFIELD_REWARDTIME];
                Logger::info('reissue chargeraffle daily firstpay reward.last reward time is %d now time %d,reissue reward num is %d',
                        $lastRewardTime,Util::getTime(),$this->rewardNum);
                $this->rewardNum = 0;
            }
            $this->buffer = $this->dbInfo;
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */