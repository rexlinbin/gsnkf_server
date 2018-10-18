<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactLogic.class.php 203828 2015-10-22 04:24:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/DesactLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-22 04:24:26 +0000 (Thu, 22 Oct 2015) $
 * @version $Revision: 203828 $
 * @brief 
 *  
 **/
class DesactLogic
{
    public static function getInfo($uid)
    {
    	if ( self::isActOpen() == false )
    	{
    		return array();
    	}
        
        $curConf = self::getConf();
        
        if (empty($curConf))
        {
            Logger::info('act desact is not open!');
            return array();
        }
        
        $config = array(
            'start_time' => $curConf['start_time'],
            'end_time' => $curConf['end_time'],
            'id' => $curConf['tid'],
            'desc' => $curConf['conf'][DesactDef::DESCRIPTION],
            'name' => $curConf['conf'][DesactDef::MISSION_NAME],
            'tip' => $curConf['conf'][DesactDef::MISSION_TIPS],
            'reward' => $curConf['conf']['reward']
        );
        
        $desactObj = DesactObj::getInstance($uid);
        $info = $desactObj->getInfo();
        
        $num = isset($info[$curConf['tid']]['num']) ? $info[$curConf['tid']]['num'] : 0;
        $rewarded = isset($info[$curConf['tid']]['rewarded']) ? array_keys($info[$curConf['tid']]['rewarded']) : array();
        
        $taskInfo = array(
            'num' => $num,
            'rewarded' => $rewarded
        );
        
        return array(
            'config' => $config,
            'taskInfo' => $taskInfo
        );
    }
    
    public static function isActOpen()
    {
    	if( ActivityNSLogic::inNS() )
    	{
    		return false;
    	}
    	
    	$conf = EnActivity::getConfByName(ActivityName::DESACT);
    	
    	if( $conf['start_time'] == 0 || Util::getTime() < $conf['start_time'])
    	{
    		return false;
    	}
    	return true;
    }
    
    public static function gainReward($uid, $rid)
    {
    	if ( self::isActOpen() == false )
    	{
    		throw new FakeException('act not open');
    	}
    	
        $curConf = self::getConf();
        $tid = $curConf['tid'];
        
        if (FALSE == self::isCanGainReward($uid, $tid, $rid))
        {
            throw new FakeException('user:%d can not gain reward. tid:%d, rid:%d.',$uid, $tid,$rid);
        }
        
        $confObj = DesactTaskObj::getInstance();
        $conf = $confObj->getConf();
        
        $reward = array();
        foreach ($conf as $key => $value)
        {
            if ($tid == $value[DesactDef::ID])
            {
                $reward = $value[DesactDef::REWARD][$rid]['reward'];
                break;
            }
        }
        
        $myDesact = DesactObj::getInstance($uid);
        $myDesact->gainReward($tid, $rid);
        
        $rewardInfo = RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_WORLD_DESACT_TASK_REWARD, false, false);
        
        unset($rewardInfo['rewardInfo']);
        
        $myDesact->update();
        RewardUtil::updateReward($uid, $rewardInfo);
        
        return 'ok';
    }
    
    public static function isCanGainReward($uid, $tid, $rid)
    {   
        //背包满
        if (TRUE == BagManager::getInstance()->getBag($uid)->isFull())
        {
            Logger::fatal('user:%d bag is full.',$uid);
            return false;
        }
        
        $desactObj = DesactObj::getInstance($uid);
        $info = $desactObj->getInfo();
        
        //已领过
        if ( isset($info[$tid]['rewarded']) && in_array( $rid, array_keys($info[$tid]['rewarded']) ) )
        {
            Logger::fatal('user:%d has received tid:%d, rid:%d.', $uid, $tid, $rid);
            return false;
        }
        
        $num = empty($info[$tid]['num']) ? 0 : $info[$tid]['num'];
        $rewarded = empty($info[$tid]['rewarded']) ? array() : array_keys($info[$tid]['rewarded']);
        
        $confObj = DesactTaskObj::getInstance();
        $conf = $confObj->getConf();
        
        $onePieceConf = array();
        foreach ($conf as $key => $value)
        {
            if ($tid == $value[DesactDef::ID])
            {
                $onePieceConf = $value;
                break;
            }
        }
        
        //没配置
        if ( empty($onePieceConf) )
        {
            Logger::fatal('no conf for tid:%d.',$tid);
            return false;
        }
        
        $rewardConf = $onePieceConf[DesactDef::REWARD][$rid];
        
        //次数不够
        if ($num < $rewardConf['num'])
        {
            Logger::fatal('num is not enough. num:%d, need:%d',$num,$rewardConf['num']);
            return false;
        }
        
        return true;
    }
    
    public static function doDesact($uid, $type, $num)
    {
        $curTidAndTime = self::getCurTidAndUdtTime();
        $tid = $curTidAndTime['tid'];
        
        if ($type != $tid)
        {
            Logger::debug('not current tid. type:%d, curTid:%d.',$type, $tid);
            return ;
        }
        
        $myDesact = DesactObj::getInstance($uid);
        $myDesact->doTask($type, $num);
        $myDesact->update();
        
        return 'ok';
    }
    
    public static function getConf()
    {
        $confObj = DesactTaskObj::getInstance();
        
        $data = $confObj->getInfo();
        
        if (empty($data))
        {
            return array();
        }
        
        $updateTime = $confObj->getUpdateTime();
        $conf = $confObj->getConf();
        
        if (empty($conf))
        {
            return array();
        }
        
        $now = Util::getTime();
        $startTime = intval( strtotime( date("Y-m-d", $updateTime) ) );
        $endTime = $startTime;
        $roundStartTime = $startTime;
        
        $day = 0;
        $tid = 0;
        $curConf = array();
        foreach ($conf as $key => $value)
        {
            if (!in_array($value[DesactDef::ID], DesactDef::$ARR_TASK_TYPE))
            {
                Logger::fatal('unsupported id in this platform. id:%d.',$value[DesactDef::ID]);
                return array();
            }
            
            $startTime = $roundStartTime + $day * SECONDS_OF_DAY;
            $day += $value[DesactDef::LAST_DAY];
            $endTime =  $roundStartTime + $day * SECONDS_OF_DAY - 1;
            
            if ($startTime <= $now && $endTime >= $now)
            {
                $tid = $value[DesactDef::ID];
                $curConf = $value;
                break;
            }
        }
        
        if (empty($tid) || empty($curConf))
        {
            return array();
        }
        
        return array(
            'start_time' => $startTime, 
            'end_time' => $endTime,
            'tid' => $tid, 
            'conf' => $curConf,
            'update_time' => $updateTime
        );
    }
    
    public static function getRandList($arrData, $seed, $lastTid=0)
    {
        if ( empty($arrData) )
        {
            throw new InterException('rand data is empty.');
        }    
    
        $randList = self::randBySeed($arrData, $seed);
            
        $arrRandData = self::adjustRankList($arrData, $randList, $seed, $lastTid);
        
        return $arrRandData;
    }
    
    public static function randBySeed($arrData, $seed)
    {
        $randList = array();
        
        srand($seed);
        
        while (!empty($arrData))
        {
            $randIndex = array_rand($arrData, 1);
            $randList[] = $randIndex;
            unset($arrData[$randIndex]);
        }
        
        return $randList;
    }
    
    /**
     * 调整顺序
     * @param array 原数据
     * @param array 随机序列
     * @param int 开始时间（0点）
     * @param int 上一轮最后一个任务的id
     * @return array 以数字索引的随机好的序列
     */
    public static function adjustRankList($arrData, $randList, $startTime, $lastTid)
    {
        $firstTid = $randList[0];
        
        if (!empty($lastTid) && $lastTid == $firstTid)
        {
            $randList[0] = $randList[1];
            $randList[1] = $firstTid;
        }
        
        $arrRandData = array();
        foreach ($randList as $key => $tid)
        {
            $arrRandData[$key] = $arrData[$tid];
        }
               
        if (in_array(DesactDef::COMPETE, $randList))
        {
            $confCompete = $arrData[DesactDef::COMPETE];
            $competeDay = $confCompete[DesactDef::LAST_DAY];
            
            $keyCompete = 0;
            
            if (FALSE == self::isConfValid($arrRandData, $startTime, $lastTid, $competeDay))
            {   
                $tmpReplaceList = array();
                foreach ($arrRandData as $key => $value)
                {
                    if ( ($value[DesactDef::ID] != $lastTid) && ($value[DesactDef::LAST_DAY] == $confCompete[DesactDef::LAST_DAY]) && $value[DesactDef::ID] != DesactDef::COMPETE )
                    {
                        $tmpReplaceList[$key] = $value;
                    }
                    
                    if (DesactDef::COMPETE == $value[DesactDef::ID])
                    {
                        $keyCompete = $key;
                    }
                }
                
                if (DesactDef::COMPETE == $lastTid && in_array(0, array_keys($tmpReplaceList)))
                {
                    unset($tmpReplaceList[0]);
                }
                
                $arrKeyReplace = Util::noBackSample($tmpReplaceList, 1, DesactDef::LAST_DAY);
                $keyReplace = $arrKeyReplace[0];
                $confReplace = $arrRandData[$keyReplace];
                
                $arrPart = array(
                    0 => array(),
                    1 => array(),
                    2 => array()
                );
                
                $confIndex = 0;
                foreach ($arrRandData as $key => $value)
                {
                    if ($key == $keyCompete || $key == $keyReplace)
                    {
                        $confIndex ++;
                        continue;
                    }
                    
                    $arrPart[$confIndex][] = $value;
                }
                
                $arrRandData = array_merge(
                        $arrPart[0],
                        array(0=>$confCompete),
                        array(0=>$confReplace),
                        $arrPart[1],
                        $arrPart[2]
                    );
                
                if (FALSE == self::isConfValid($arrRandData, $startTime, $lastTid, $competeDay))
                {
                    $arrRandData = array_merge(
                            $arrPart[0],
                            array(0=>$confReplace),
                            array(0=>$confCompete),
                            $arrPart[1],
                            $arrPart[2]
                        );
                }               
            }
        }
    
        return $arrRandData;
    }
    
    public static function isConfValid($arrData, $startTime, $lastTid, $competeDay)
    {
        if ( $lastTid == $arrData[0][DesactDef::ID] )
        {
            return false;
        }
        
        foreach ($arrData as $key => $value)
        {
            if (DesactDef::COMPETE == $value[DesactDef::ID])
            {
                break;
            }
            
            $startTime += $value[DesactDef::LAST_DAY] * SECONDS_OF_DAY;
        }
        
        $endTime = $startTime + $competeDay * SECONDS_OF_DAY - 1;
        
        if ( TRUE == self::isContainSunday($startTime, $endTime) )
        {
            return false;
        }
        
        return true;
    }
    
    //判断时间点内是否包含周几(1~7)
    public static function isContainSunday($startTime, $endTime, $w=7)
    {
        $SECONDS_OF_WEEK = 604800;
        if ( $endTime - $startTime >= $SECONDS_OF_WEEK )
        {
            return true;
        }
        
        $startWeek = intval ( date( 'w', $startTime ) );
        $endWeek = intval( date( 'w', $endTime ) );
        
        $startWeek = empty($startWeek) ? 7 : $startWeek;
        $endWeek = empty($endWeek) ? 7 : $endWeek;
        
        if ($endWeek >= $startWeek)
        {
            if ($startWeek <= $w && $endWeek >= $w)
            {
                return true;
            }
        }
        else 
        {
            if ($startWeek <= $w || $endWeek >= $w)
            {
                return true;
            }
        }
        
        return false;
    }
    
    public static function getCurTidAndUdtTime()
    {
        $tid = RPCContext::getInstance()->getSession(DesactCrossDef::SESSION_KEY_TID);
        $startTime = RPCContext::getInstance()->getSession(DesactCrossDef::SESSION_KEY_START_TIME);
        $endTime = RPCContext::getInstance()->getSession(DesactCrossDef::SESSION_KEY_END_TIME);
        $setTime = RPCContext::getInstance()->getSession(DesactCrossDef::SESSION_KEY_SET_TIME);
        $updateTime = RPCContext::getInstance()->getSession(DesactCrossDef::SESSION_KEY_CONF_UPDATE_TIME);
        
        if (empty($tid))
        {
            $tid = 0;
        }
        
        if ( empty($setTime) )
        {
            $setTime = 0;
        }
        
        if ( empty($updateTime) )
        {
            $updateTime = 0;
        }
        
        $now = Util::getTime();
        
        if (empty($tid)
            || $setTime + DesactCrossDef::SESSION_VALID < $now
            || ($endTime >= $setTime && $now >= $endTime) )
        {
            $curConf = self::getConf();
        
            if (!empty($curConf))
            {
                $tid = $curConf['tid'];
                $updateTime = $curConf['update_time'];
                
                RPCContext::getInstance()->setSession(DesactCrossDef::SESSION_KEY_TID, $tid);
                RPCContext::getInstance()->setSession(DesactCrossDef::SESSION_KEY_START_TIME, $curConf['start_time']);
                RPCContext::getInstance()->setSession(DesactCrossDef::SESSION_KEY_END_TIME, $curConf['end_time']);
                RPCContext::getInstance()->setSession(DesactCrossDef::SESSION_KEY_SET_TIME, $now);
                RPCContext::getInstance()->setSession(DesactCrossDef::SESSION_KEY_CONF_UPDATE_TIME, $updateTime);
            }
        }
        
        return array(
            'tid' => $tid,
            'update_time' => $updateTime
        );
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */