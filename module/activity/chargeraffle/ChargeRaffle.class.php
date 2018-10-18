<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeRaffle.class.php 125647 2014-08-08 03:04:15Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/ChargeRaffle.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-08 03:04:15 +0000 (Fri, 08 Aug 2014) $
 * @version $Revision: 125647 $
 * @brief 
 *  
 **/
class ChargeRaffle implements IChargeRaffle
{
    private $uid = NULL;
	/* (non-PHPdoc)
     * @see IChargeRaffle::getInfo()
     */
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
    
    public function getInfo ()
    {
        // TODO Auto-generated method stub
        Logger::trace('ChargeRaffle.getInfo start');
        $ret = ChargeRaffleLogic::getInfo($this->uid);
        Logger::trace('ChargeRaffle.getInfo end.ret %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IChargeRaffle::raffle()
     */
    public function raffle ($index)
    {
        // TODO Auto-generated method stub
        list($index) = Util::checkParam(__METHOD__, func_get_args());
        Logger::trace('ChargeRaffle.raffle start.params index %d',$index);
        $ret = ChargeRaffleLogic::raffle($index, $this->uid);
        Logger::trace('ChargeRaffle.raffle end ret %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IChargeRaffle::getReward()
     */
    public function getReward ()
    {
        // TODO Auto-generated method stub
        Logger::trace('ChargeRaffle.getReward start');
        $ret = ChargeRaffleLogic::fetchReward($this->uid);
        Logger::trace('ChargeRaffle.getReward end.ret %s',$ret);
        return $ret;
    }
    
    
    public function addRewardTimer()
    {
        Logger::trace('ChargeRaffle.addRewardTimer start');
        ChargeRaffleLogic::addRewardTimer();
        Logger::trace('ChargeRaffle.addRewardTimer end');
    }
    
    public function rewardUserOnLogin($uid)
    {
        Logger::trace('ChargeRaffle.rewardUserOnLogin start');
        $guid = RPCContext::getInstance()->getUid();
        if($guid != $uid)
        {
            Logger::info('uid %d is not online.do not send reward to center.',$uid);
            return;
        }
        $startTime = ChargeRaffleLogic::getActStartTime();
        $endTime = ChargeRaffleLogic::getActEndTime();
        if(Util::getTime() < $startTime)
        {
            return;
        }
        if(Util::getTime() > $startTime)
        {
            $endDayBreak = ChargeRaffleLogic::getDayBreak($endTime);
            if(Util::getTime() >= $endDayBreak + SECONDS_OF_DAY * 2)
            {
                return;
            }
        }
        $inst = MyChargeRaffle::getInstance($uid);
        if($inst == NULL)
        {
            return;
        }
        $inst->save();
        Logger::trace('ChargeRaffle.rewardUserOnLogin end.');
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */