<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: StepCounterLogic.class.php 136576 2014-10-17 06:09:20Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/stepcounter/StepCounterLogic.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-17 06:09:20 +0000 (Fri, 17 Oct 2014) $$
 * @version $$Revision: 136576 $$
 * @brief 
 *  
 **/
class StepCounterLogic
{
    public static function getStepCounterConf()
    {
        $activityConf = EnActivity::getConfByName(ActivityName::STEPCOUNTER);
        return $activityConf['data'];
    }

    public static function getStepCounterDay()
    {
        return EnActivity::getActivityDay(ActivityName::STEPCOUNTER);
    }

    public static function isStepCounterOpen()
    {
        if(!EnActivity::isOpen(ActivityName::STEPCOUNTER))
        {
            throw new FakeException('SteperCounter activity is not open');
        }
        return true;
    }

    public static function checkStatus($uid)
    {
        $steperCounterTime = EnUser::getExtraInfo(UserExtraDef::STEP_COUNTER_TIME, $uid);
        if($steperCounterTime == FALSE)
        {
            return StepCounterDef::NO;
        }
        if(Util::isSameDay($steperCounterTime))
        {
            return StepCounterDef::YES;
        }
        return StepCounterDef::NO;
    }

    public static function recReward($uid)
    {
        $status = self::checkStatus($uid);
        if($status == StepCounterDef::YES)
        {
            throw new FakeException('reward have received');
        }
        $day = self::getStepCounterDay();
        $confData = self::getStepCounterConf();
        $rewards = $confData[$day + 1][StepCounterDef::REWARDS];    //某一天的奖励
        EnUser::setExtraInfo(UserExtraDef::STEP_COUNTER_TIME, Util::getTime(), $uid);
        $res = RewardUtil::reward3DArr($uid, $rewards, StatisticsDef::ST_FUNCKEY_STEP_COUNTER_REWARD);
        Logger::debug('recReward of stepCounter %s', $rewards);
        return $res;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */