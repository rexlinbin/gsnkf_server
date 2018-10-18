<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RankInfo.class.php 175603 2015-05-29 06:49:10Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/RankInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-05-29 06:49:10 +0000 (Fri, 29 May 2015) $
 * @version $Revision: 175603 $
 * @brief 
 *  
 **/
/**
 * 
 * @author dell
 *
 */
class RankInfo
{
    private $rankInfo = NULL;
    private $minScore = NULL;
    private $broadcastTime = NULL;
    private static $_instance = NULL;
    
    private function __construct($useDataInMc=TRUE)
    {
        $rankInfo = array();
        if($useDataInMc == TRUE)
        {
            $rankInfo = $this->getRankInfoFromMc();
        }
        if(empty($rankInfo) || ($useDataInMc == FALSE))
        {
            $rankInfo = $this->getRankInfoFromDb();
        }
        $this->rankInfo = $rankInfo;
    }
    /**
     * @return RankInfo
     */
    public static function getInstance()
    {
        if(self::$_instance == NULL)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function getMinScore()
    {
        return $this->getMinScoreFromRankInfo();
    }
    
    public function getBroadcastTime()
    {
        if($this->broadcastTime == NULL)
        {
            $broadcastTime = 0;
            $key = HeroShopDef::HEROSHOP_MEMCACHE_KEY_BROADCAST_TIME;
            $ret = McClient::get($key);
            if(!empty($ret))
            {
                $broadcastTime = $ret;
            }
            $this->broadcastTime = $broadcastTime;
        }
        return $this->broadcastTime;
    }
    
    public function setBroadcastTime($time)
    {
        $this->broadcastTime = $time;
    }
    
    public function getMinScoreFromRankInfo()
    {
        if(count($this->rankInfo) < HeroShopLogic::getSizeOfRealTimeRank())
        {
            return 0;
        }
        return $this->rankInfo[count($this->rankInfo)]['score'];
    }
    
    public function getRankInfo()
    {
        return $this->rankInfo;
    }
    
    public function getRankInfoFromMc()
    {
        $key = HeroShopDef::HEROSHOP_MEMCACHE_KEY_RANKINFO;
        $ret = McClient::get($key);
        if(empty($ret))
        {
            return $ret;
        }
        if($ret[count($ret)][HeroShopDef::SQL_FIELD_SCORE_TIME] < HeroShopLogic::getActStartTime())
        {
            return array();
        }
        return $ret;
    }
    
    public function getRankInfoFromDb()
    {
        $tmpRet = HeroShopDao::getRankList(HeroShopLogic::getActStartTime());
        $rankInfo = array();
        $arrUid = array();
        foreach($tmpRet as $index => $userInfo)
        {
            $rankInfo[$index+1] = $userInfo;
            $uid = $userInfo['uid'];
            $arrUid[] = $uid;
        }
        $ret = EnUser::getArrUser($arrUid, array('uid','uname'));
        $uidToUname = $ret;
        foreach($rankInfo as $rank => $userInfo)
        {
            $uid = $userInfo[HeroShopDef::SQL_FIELD_UID];
            $rankInfo[$rank]['uname'] = $uidToUname[$uid]['uname'];
        }
        return $rankInfo;
    }
    
    public function rfrRankInfoDbToMc()
    {
        $this->rankInfo = $this->getRankInfoFromDb();
        $this->minScore = $this->getMinScoreFromRankInfo();
    }
    
    public function saveInfoToMc()
    {
        McClient::set(HeroShopDef::HEROSHOP_MEMCACHE_KEY_RANKINFO, $this->rankInfo);
        if($this->broadcastTime != NULL)
        {
            McClient::set(HeroShopDef::HEROSHOP_MEMCACHE_KEY_BROADCAST_TIME,$this->broadcastTime);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */