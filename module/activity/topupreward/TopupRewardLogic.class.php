<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: TopupRewardLogic.class.php 133379 2014-09-19 10:46:12Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/TopupRewardLogic.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-09-19 10:46:12 +0000 (Fri, 19 Sep 2014) $$
 * @version $$Revision: 133379 $$
 * @brief 
 *  
 **/
class TopupRewardLogic
{
    public static function getInfo($uid)
    {
        $topupManager = TopupRewardManager::getInstance($uid);
        $ret = $topupManager->getInfo();
        $topupManager->update();
        return $ret;
    }

    public static function rec($uid, $day)
    {
        $topupManager = TopupRewardManager::getInstance($uid);
        $res = $topupManager->rec($day);
        $topupManager->update();
        if(!empty($res["userModify"]) && $res["userModify"]) {
            $user = EnUser::getUserObj($uid);
            $user->update();
        }
        if(!empty($res["bagModify"]) && $res["bagModify"]) {
            $bag = BagManager::getInstance()->getBag($uid);
            $bag->update();
        }
        return true;
    }

    public static function rewardUserOnLogin($uid)
    {
        $guid = RPCContext::getInstance()->getUid();
        if($guid != $uid)
        {
            Logger::info('topupReward: uid %d is not online.do not send reward to center.',$uid);
            return;
        }
        if(!EnActivity::isOpen(ActivityName::TOPUPREWARD))
        {
            Logger::info('topupreward activity not open');
            return;
        }
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        if(Util::getTime() > $startTime)
        {
            //$endDayBreak = self::getDayBreak($endTime);
            if(Util::getTime() >= $endTime)
            {
                return;
            }
        }
        $topupManager = TopupRewardManager::getInstance($uid);
        if($topupManager == NULL)
        {
            return;
        }
        $topupManager->noneRecReward2Center();
        $topupManager->update();
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
        $conf = EnActivity::getConfByName(ActivityName::TOPUPREWARD);
        $start = $conf['start_time'];
        return $start;
    }

    public static function getActEndTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::TOPUPREWARD);
        $start = $conf['end_time'];
        return $start;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */