<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActPayBackLogic.class.php 232942 2016-03-16 07:41:05Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actpayback/ActPayBackLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-16 07:41:05 +0000 (Wed, 16 Mar 2016) $
 * @version $Revision: 232942 $
 * @brief 
 *  
 **/
class ActPayBackLogic
{
    public static function getAvailableRewardList($uid)
    {
        if ( FALSE == EnActivity::isOpen(ActivityName::ACTPAYBACK) )
        {
            return array();
        }
        
        $payBackObj = ActPayBackObj::getInstance($uid);
        $arrRecvList = $payBackObj->getRewarded();
        $arrRecvRidList = array_keys($arrRecvList);
        
        $arrRewardList = self::getRewardListConf();
        
        $arrAvailableRewardList = array();
        foreach ($arrRewardList as $rid => $rewardInfo)
        {
            $pid = EnUser::getUserObj($uid)->getPid();
            
            $now = Util::getTime();
            
            $openserverTime = strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME);
            
            $show = TRUE;
            if ( $now < $rewardInfo['start_time'] + $rewardInfo['time'] 
                && !in_array($pid, $rewardInfo['pid']))
            {
                $show = FALSE;
            }
            
            if ( $now >= $rewardInfo['start_time']
                && $now <= $rewardInfo['end_time']
                && $openserverTime <= $rewardInfo['need_open_time'] 
                && TRUE == $show
                && !in_array($rid, $arrRecvRidList))
            {
                $arrAvailableRewardList[] = array(
                    RewardDef::SQL_RID => $rid,
					RewardDef::SQL_SOURCE => RewardSource::ACT_PAY_BACK_REWARD,
					RewardDef::SQL_SEND_TIME => $rewardInfo['start_time'],
					RewardDef::EXPIR_TIME => $rewardInfo['end_time'],
					RewardDef::SQL_RECV_TIME => 0,
					RewardDef::SQL_DELETE_TIME => 0,
					RewardDef::SQL_VA_REWARD => $rewardInfo['reward'],
                );
            }
        }
        
        return $arrAvailableRewardList;
    }
    
    public static function receive($uid, $arrRid)
    {   
        $payBackObj = ActPayBackObj::getInstance($uid);
        $payBackObj->receiveRewards($arrRid);
        $payBackObj->update();
    }
    
    public static function getPayBackByArrRidAndTime($arrRid, $time = 0)
    {
        if ( empty($time) )
        {
            $time = Util::getTime();
        }
        
        $conf = ActivityConfDao::getLastConfContainTime(ActivityName::ACTPAYBACK, $time, ActivityDef::$ARR_CONF_FIELD);
        
        if ( empty($conf) )
        {
            return array();
        }
        
        $arrRewardInfo = array();
        foreach ($arrRid as $rid)
        {
            if ( !isset($conf['va_data'][$rid]) )
            {
                $arrRewardInfo[$rid] = array();
            }
            else 
            {
                $arrRewardInfo[$rid] = array(
                    RewardDef::SQL_RID => $rid,
                    RewardDef::SQL_SOURCE => RewardSource::ACT_PAY_BACK_REWARD,
                    RewardDef::SQL_SEND_TIME => $conf['va_data'][$rid]['start_time'],
                    RewardDef::EXPIR_TIME => $conf['va_data'][$rid]['end_time'],
                    RewardDef::SQL_RECV_TIME => 0,
                    RewardDef::SQL_DELETE_TIME => 0,
                    RewardDef::SQL_VA_REWARD => $conf['va_data'][$rid]['reward'],
                );
            }
        }
        
        return $arrRewardInfo;
    }
    
    public static function getRewardListConf()
    {
        $arrActConf = EnActivity::getConfByName(ActivityName::ACTPAYBACK);
        return $arrActConf['data'];
    }
    
    public static function getActStartTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::ACTPAYBACK);
        return $conf['start_time'];
    }
    
    public static function getActEndTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::ACTPAYBACK);
        return $conf['end_time'];
    }
    
    public static function getAvailableByArrId($uid, $arrActPaybackId)
    {
        $arrAvailableRewardList = self::getAvailableRewardList($uid);
        $arrAvailableRewardList = Util::arrayIndex($arrAvailableRewardList, 'rid');
        
        $arrAvailableRid = array_keys($arrAvailableRewardList);
        
        $arrActPaybackInfo = array();
        foreach ($arrActPaybackId as $rid)
        {
            if ( in_array($rid, $arrAvailableRid) )
            {
                $arrActPaybackInfo[$rid] = $arrAvailableRewardList[$rid];
            }
            else 
            {
                Logger::warning("actpayback id %d is not available now.", $rid);
            }
        }
        
        return $arrActPaybackInfo;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */