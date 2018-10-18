<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OlympicLog.class.php 255274 2016-08-09 08:36:25Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicLog.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-09 08:36:25 +0000 (Tue, 09 Aug 2016) $
 * @version $Revision: 255274 $
 * @brief 
 *  
 **/

/**
 * $vaLogInfo => array
 * [
 *      'progress' => array
 *      [
 *          stage => array
 *              [
 *                  status: int
 *                  begintime: int
 *                  endtime: int
 *              ]
 *      ]
 *      'atkres' => array
 *      [
 *          atker: int
 *          defer: int
 *          brid: int
 *          res: int
 *      ]
 *      'rewardpool' => array
 *      [
 *          silver_pool:int
 *          last_campion:int
 *      ]
 * ]
 */
class OlympicLog
{
    private $dateYmd = NULL;
    private $logType = NULL;
    private $vaLogInfo = array();
    private $vaLogInfoBuffer = array();
    private static $instance = array();

    private function __construct($logType, $dateYmd)
    {
        $this->dateYmd = $dateYmd;
        $this->logType = $logType;
        $this->init($this->dateYmd, $this->logType);
    }

    /**
     * 
     * @param unknown_type $logType
     * @param unknown_type $dateYmd
     * @return OlympicLog
     */
    public static function getInstance($logType, $dateYmd=NULL)
    {
        if(empty($dateYmd))
        {
            if($logType == OlympicLogType::REWARD_POOL)
            {
                $dateYmd = OlympicLogic::getCurSuperRoundStartTime();
            }
            else
            {
                $dateYmd = OlympicLogic::getCurRoundStartTime();
            }
        }
        if(!isset(self::$instance[$dateYmd][$logType]))
        {
            self::$instance[$dateYmd][$logType] = new self($logType, $dateYmd);
        }
        return self::$instance[$dateYmd][$logType];
    }
    
    public static function release($logType, $dateYmd=NULL)
    {
        if(empty($dateYmd))
        {
            if($logType == OlympicLogType::REWARD_POOL)
            {
                $dateYmd = OlympicLogic::getCurSuperRoundStartTime();
            }
            else
            {
                $dateYmd = OlympicLogic::getCurRoundStartTime();
            }
        }
        self::$instance[$dateYmd][$logType] = NULL;
    }

    private function init($dataYmd, $logType)
    {
        $vaLogInfo = OlympicDao::getOlympicLog($dataYmd, $logType);
        Logger::trace('getLogInfo from db %s', $vaLogInfo);
        if(empty($vaLogInfo))
        {
            $vaLogInfo = array();
            if($logType == OlympicLogType::BATTLE_PROGRESS)
            {
                $vaLogInfo = array(
                    OlympicLogDef::VA_INFO_PROGRESS => array(),
                );
            }
            else if($logType == OlympicLogType::REWARD_POOL)
            {
                $vaLogInfo = array(
                    OlympicLogDef::VA_INFO_REWAED_POOL => array(
                            OlympicLogDef::VA_INFO_REWAED_POOL_SILVER_POOL => 0,
                            OlympicLogDef::VA_INFO_REWAED_POOL_LAST_CAMPION => 0,
                            ),
                );
            }
            else
            {
                $vaLogInfo = array(
                    OlympicLogDef::VA_INFO_ATKRES => array(),
                );
            }
            OlympicDao::insertOlympicLog(
                array(
                    OlympicLogDef::FIELD_LOG_DATE_YMD => $this->dateYmd,
                    OlympicLogDef::FIELD_LOG_TYPE => $this->logType,
                    OlympicLogDef::FIELD_LOG_INFO => $vaLogInfo,
                )
            );
            Logger::trace('insert LogInfo to db %s', $vaLogInfo);
        }
        $this->vaLogInfo = $vaLogInfo;
        $this->vaLogInfoBuffer = $vaLogInfo;
        Logger::trace('vaLogInfo:%s', $vaLogInfo);
    }

    public function save()
    {
        if($this->vaLogInfo == $this->vaLogInfoBuffer)
        {
        	Logger::trace('olympiclog no need to update. dateYmd:%s, logType:%s', $this->dateYmd, $this->logType);
            return;
        }
        OlympicDao::updateOlympicLog($this->dateYmd, $this->logType, $this->vaLogInfo);
        Logger::trace('save olympiclog dateYmd:%s, logType:%s', $this->dateYmd, $this->logType);

        $this->vaLogInfoBuffer = $this->vaLogInfo;
    }


    public function getLogInfo()
    {
        if($this->logType == OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('getLogInfo logType must be battle_progress ');
        }
        return $this->vaLogInfo[OlympicLogDef::VA_INFO_ATKRES];
    }

    public function addLog($logInfo)
    {
        if($this->logType == OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('addLog logType must be battle_progress ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_ATKRES][] = $logInfo;
    }

    public function getStageInfo($stage)
    {
        if($this->logType != OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('getStageInfo logType must be battle_progress ');
        }
        if(!isset($this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage]))
        {
            return array();
        }
        return $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage];
    }

    public function getCurStageInfo()
    {
        if(empty($this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS]) ||
                !isset($this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$this->getCurStage()]))
        {
            return array();
        }
        
        return $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$this->getCurStage()];
    }
    
    public function getCurStage()
    {
        if($this->logType != OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('getCurStage logType must be battle_progress ');
        }
        $arrStageInfo = $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS];
        if(empty($arrStageInfo))
        {
            throw new FakeException(' arrStageInfo is empty ');
        }
        return max(array_keys($arrStageInfo));
    }

    public function getCurStageStatus()
    {
        $curStageInfo = $this->getCurStageInfo();
        return $curStageInfo[OlympicLogDef::VA_INFO_PROGRESS_STATUS];
    }
    
    public function getCurStageEndTime()
    {
        $curStageInfo = $this->getCurStageInfo();
        return $curStageInfo[OlympicLogDef::VA_INFO_PROGRESS_ENDTIME];
    }

    public function getCurStageBeginTime()
    {
        $curStageInfo = $this->getCurStageInfo();
        return $curStageInfo[OlympicLogDef::VA_INFO_PROGRESS_BEGINTIME];
    }

    public function updStageStatus($stage, $status)
    {
        if($this->logType != OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('updStageStatus logType must be battle_progress ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage][OlympicLogDef::VA_INFO_PROGRESS_UPDATETIME] = time();
        $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage][OlympicLogDef::VA_INFO_PROGRESS_STATUS] = $status;
    }

    public function updStageEndTime($stage, $endTime)
    {
        if($this->logType != OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('updStageEndTime logType must be battle_progress ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage][OlympicLogDef::VA_INFO_PROGRESS_ENDTIME] = $endTime;
    }

    public function updStageBeginTime($stage, $beginTime)
    {
        if($this->logType != OlympicLogType::BATTLE_PROGRESS)
        {
            throw new FakeException('updStageBeginTime logType must be battle_progress ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stage][OlympicLogDef::VA_INFO_PROGRESS_UPDATETIME] = $beginTime;
    }

    public function getSilverPool()
    {
        if($this->logType != OlympicLogType::REWARD_POOL)
        {
            throw new FakeException('getSilverPool logType must be reward_pool ');
        }
        if(!isset($this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_SILVER_POOL]))
        {
            return 0;
        }
        return $this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_SILVER_POOL];
    }

    public function getLastCampion()
    {
        if($this->logType != OlympicLogType::REWARD_POOL)
        {
            throw new FakeException('getLastCampion logType must be reward_pool ');
        }
        if(!isset($this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_LAST_CAMPION]))
        {
            return 0;
        }
        return $this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_LAST_CAMPION];
    }

    public function updSilverPool($silverPool)
    {
        if($this->logType != OlympicLogType::REWARD_POOL)
        {
            throw new FakeException('updSilverPool logType must be reward_pool ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_SILVER_POOL] = $silverPool;
    }

    public function updLastCampion($lastCampion)
    {
        if($this->logType != OlympicLogType::REWARD_POOL)
        {
            throw new FakeException(' updLastCampion logType must be reward_pool ');
        }
        $this->vaLogInfo[OlympicLogDef::VA_INFO_REWAED_POOL][OlympicLogDef::VA_INFO_REWAED_POOL_LAST_CAMPION] = $lastCampion;
    }

    public static function getLogByArrType($arrType)
    {
        if(in_array(OlympicLogType::REWARD_POOL, $arrType))
        {
            throw new FakeException('log reward_pool can not be fetched together with other log');
        }
        $dateYmd = OlympicLogic::getCurRoundStartTime();
        $ret = OlympicDao::getArrOlympicLog($dateYmd, $arrType);
        $arrUid = array();
        foreach($ret as $logType => $atkres)
        {
            foreach($atkres['atkres'] as $index => $tmpAtk)
            {
                $atker = $tmpAtk['attacker'];
                $defer = $tmpAtk['defender'];
                $arrUid[] = $atker;
                $arrUid[] = $defer;
            }
        }
        $arrUidTmp = array_unique($arrUid);
        $arrAtkerUname = EnUser::getArrUserBasicInfo($arrUidTmp, array('uname'));
        foreach($ret as $logType => $atkres)
        {
            foreach($atkres['atkres'] as $index => $tmpAtk)
            {
                $atker = $tmpAtk['attacker'];
                $defer = $tmpAtk['defender'];
                $ret[$logType]['atkres'][$index]['attackerName'] = $arrAtkerUname[$atker]['uname'];
                $ret[$logType]['atkres'][$index]['defenderName'] = $arrAtkerUname[$defer]['uname'];
            }
        }
        return $ret;
    }
    
    public function unsetOneStageForRepair($stageId)
    {
    	$orginStageInfo = array();
    	if (!empty($this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stageId])) 
    	{
    		$orginStageInfo = $this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stageId];
    	}
    	Logger::info('unset stage:%d for repair, orgin stage info:%s', $stageId, $orginStageInfo);
    	
    	unset($this->vaLogInfo[OlympicLogDef::VA_INFO_PROGRESS][$stageId]);
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */