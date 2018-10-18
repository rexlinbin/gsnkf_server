<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralManager.class.php 118878 2014-07-07 06:44:45Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/test/MineralManager.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-07 06:44:45 +0000 (Mon, 07 Jul 2014) $
 * @version $Revision: 118878 $
 * @brief 
 *  
 **/
class MineralManagerCopy
{
    /**
     * 
     * @var array
     * [
     *     uid=>array
     *     [
     *         array
     *         [
     *             pit_id:int
     *             domain_id:int
     *         ]
     *     ]
     * ]
     */
    private static $arrPitByUid = NULL;
    /**
     * 
     * @var array(PitObj)
     * [
     *     domainid=>array
     *     [
     *         pitid=>PitObj
     *     ]
     * ]
     */
    private static $arrPit=NULL;
    
    /**
     *
     * @var array
     * [
     *     domainid=>array
     *     [
     *         pitid=>array
     *         [
     *             uid=>uid
     *         ]
     *     ]
     * ]
     */
    private static $arrPitGurad = NULL;
    
    /**
     *
     * @var array
     * [
     *     uid=>array
     *     [
     *         uid=>int
     *         domain_id=>int
     *         pit_id=>int
     *         guard_time=>int
     *         due_timer=>int
     *         status=>int
     *     ]
     * ]
     */
    private static $arrGuard=NULL;
    private static $arrGuardBuffer=NULL;
    /**
     * 
     * @var MineralManager
     */
    private static $_instance = NULL;
    
    public static function getInstance()
    {
        if(self::$_instance == NULL)
        {
            self::$_instance = new MineralManagerCopy();
        }
        return self::$_instance;
    }
    
    /**
     * PitObj类
     * @param int $domainId
     * @param int $pitId
     * @return PitObj
     */
    public function getPitObj($domainId,$pitId)
    {
    	$this->getArrPitByPitId($domainId,array($pitId));
    	if(!isset(self::$arrPit[$domainId][$pitId]))
    	{
    	    return NULL;
    	}
    	return self::$arrPit[$domainId][$pitId];
    }
    
    /**
     * 某矿区多个矿坑数据
     * @param int $domianId 
     * @param array $arrPitId array($pitId)
     * @return array
     * [
     *     domainId=>array
     *     [
     *         pitId=>array 矿信息
     *         [
     *             uid:int
     *             pit_id:int
     *             domain_id:int
     *             domain_type:int
     *             pit_type:int
     *             ..............
     *         ]
     *     ]
     * ]
     */
    public function getArrPitByPitId($domainId, $arrPitId=array())
    {
    	$ret = array();
    	$fetch = FALSE;
    	$ret = array();
    	if(empty($arrPitId))
    	{
    		$fetch = TRUE;
    	}
    	else 
    	{
    		if(!isset(self::$arrPit[$domainId]))
    		{
    		    $fetch = TRUE;
    		}
    		else 
    		{
    		    foreach($arrPitId as $pitId)
    		    {
    		        if(!isset(self::$arrPit[$domainId][$pitId]))
    		        {
    		            $fetch = TRUE;
    		            continue;
    		        }
    		        $pitObj = self::$arrPit[$domainId][$pitId];
    		        $ret[$domainId][$pitId] = $pitObj->getPitInfo();
    		    }
    		}
    	}
    	if(!$fetch)
    	{
    	    return $ret;
    	}
    	$ret = array();
    	$dbRet = MineralDAO::getArrPit($domainId,$arrPitId);
        foreach($dbRet as $pitInfo)
        {
            $domainId = $pitInfo[TblMineralField::DOMAINID];
            $pitId = $pitInfo[TblMineralField::PITID];
            self::$arrPit[$domainId][$pitId] = new PitObj($pitInfo);
            $ret[$domainId][$pitId] = $pitInfo;
        }
        return $ret;    	
    }
    
    /**
     * 
     * @param int $uid
     * @return array
     * [
     *     array矿信息
     *     [
     *         uid:int
     *         pit_id:int
     *         pit_type:int
     *         domain_id:int
     *         domain_type:int
     *         ...................
     *     ]
     * ]
     */
    public function getArrPitByUid($uid)
    {
        $ret = array();
        if(isset(self::$arrPitByUid[$uid]))
        {
            foreach(self::$arrPitByUid[$uid] as $pitInfo)
            {
                $domainId = $pitInfo[TblMineralField::DOMAINID];
                $pitId = $pitInfo[TblMineralField::PITID];
                $pitObj = $this->getPitObj($domainId, $pitId);
                $ret[] = $pitObj->getPitInfo();
            }
            return $ret;
        }
        $dbRet = MineralDAO::getArrPitByUid($uid);
        self::$arrPitByUid[$uid] = array();
        foreach($dbRet as $pitInfo)
        {
            $domainId = $pitInfo[TblMineralField::DOMAINID];
            $pitId = $pitInfo[TblMineralField::PITID];
            self::$arrPitByUid[$uid][] = array(
                    TblMineralField::DOMAINID=>$domainId,
                    TblMineralField::PITID => $pitId);
            self::$arrPit[$domainId][$pitId] = new PitObj($pitInfo);
        }
        return $dbRet;
    }
    
    /**
     * 
     * @param int $domainId
     * @param array $arrPitId
     * @return array
     * [
     *     domainId=>array
     *     [
     *         pitId=>array
     *         [
     *             array守卫信息
     *             [
     *                 uid:int
     *                 domain_id:int
     *                 pit_id:int
     *                 guard_time:int
     *                 due_timer:int
     *             ]
     *         ]
     *     ]
     * ]
     */
    public function getArrGuardByPitId($domainId,$arrPitId=array())
    {
        $ret = array();
        $fetch = FALSE;
        $ret = array();
        if(empty($arrPitId))
        {
            $fetch = TRUE;
        }
        else
        {
            if(!isset(self::$arrPitGurad[$domainId]))
            {
                $fetch = TRUE;
            }
            else
            {
                foreach($arrPitId as $pitId)
                {
                    if(!isset(self::$arrPitGurad[$domainId][$pitId]))
                    {
                        $fetch = TRUE;
                        break;
                    }
                }
            }
        }
        if($fetch)
        {
            $dbRet = MineralDAO::getArrGuard($domainId,$arrPitId);
            foreach($arrPitId as $pitId)//TODO:NOTICE   直接unset
            {
                unset(self::$arrPitGurad[$domainId][$pitId]);
            }
            foreach($dbRet as $guardInfo)
            {
                $uid = $guardInfo[TblMineralGuards::UID];
                $pitId = $guardInfo[TblMineralGuards::PITID];
                $domainId = $guardInfo[TblMineralGuards::DOMAINID];
                self::$arrPitGurad[$domainId][$pitId][$uid] = $uid;
                self::$arrGuard[$uid] = $guardInfo;
                self::$arrGuardBuffer[$uid] = $guardInfo;
            }
        }
        foreach($arrPitId as $pitId)
        {
            $arrUid = self::$arrPitGurad[$domainId][$pitId];
            foreach($arrUid as $uid)
            {
                $ret[$domainId][$pitId][] = self::$arrGuard[$uid];
            }
        }
        return $ret;
    }
    
    /**
     * 
     * @param int $uid
     * @return array守卫信息
     * [
     *     pit_id:int
     *     domain_id:int
     *     due_timer:int
     *     guard_time:int
     *     ....................
     * ]
     */
    public function getArrGuardByUid($uid)
    {
        if(isset(self::$arrGuard[$uid]))
        {
            return self::$arrGuard[$uid];
        }
        $dbRet = MineralDAO::getGuardInfoByUid($uid);
        self::$arrGuard[$uid] = $dbRet;
        self::$arrGuardBuffer[$uid] = $dbRet;
        return self::$arrGuard[$uid];
    }
    
    
    public function addGuard($uid,$domainId,$pitId)
    {
        if(self::$arrGuard[$uid][TblMineralGuards::DOMAINID] !=0 ||
                (self::$arrGuard[$uid][TblMineralGuards::PITID] !=0))
        {
            Logger::warning('user %d has guard domain %d pit %d can not add guard for this user.',
                    $uid,self::$arrGuard[$uid][TblMineralGuards::DOMAINID],
                    self::$arrGuard[$uid][TblMineralGuards::PITID]);
            return FALSE;
        }
        $timerId = TimerTask::addTask($uid, Util::getTime()+self::getPitGuardTimeLimit(),
                'mineral.duePitGuard', array($uid,$domainId,$pitId));
        $guardInfo = array(
                TblMineralGuards::DOMAINID => $domainId,
                TblMineralGuards::PITID => $pitId,
                TblMineralGuards::DUETIMER => $timerId,
                TblMineralGuards::GUARDTIME=>Util::getTime(),
                TblMineralGuards::STATUS => GuardType::ISGUARD,
                );
        self::$arrGuard[$uid] = $guardInfo;
        self::$arrPitGurad[$domainId][$pitId][$uid] = $uid;
        return TRUE;
    }
    
    public function quitGuard($uid,$domainId,$pitId)
    {
        if(isset(self::$arrGuard[$uid]))
        {
            self::$arrGuard[$uid][TblMineralGuards::DOMAINID] = 0;
            self::$arrGuard[$uid][TblMineralGuards::PITID] = 0;
            $dueTimer = self::$arrGuard[$uid][TblMineralGuards::DUETIMER];
            TimerTask::cancelTask($dueTimer);
            if(isset(self::$arrPitGurad[$domainId][$pitId]))
            {
                if(isset(self::$arrPitGurad[$domainId][$pitId][$uid]))
                {
                    unset(self::$arrPitGurad[$domainId][$pitId][$uid]);
                }
            }
            return TRUE;
        }
        else
        {
            Logger::warning('user %d is not guard domain %d pit %d',$uid,$domainId,$pitId);
            return FALSE;
        }
    }
    
    public function dueGuard($uid,$domainId,$pitId)
    {
        if(isset(self::$arrGuard[$uid]))
        {
            self::$arrGuard[$uid][TblMineralGuards::DOMAINID] = 0;
            self::$arrGuard[$uid][TblMineralGuards::PITID] = 0;
            if(isset(self::$arrPitGurad[$domainId][$pitId]))
            {
                if(isset(self::$arrPitGurad[$domainId][$pitId][$uid]))
                {
                    unset(self::$arrPitGurad[$domainId][$pitId][$uid]);
                }
            }
            return TRUE;
        }
        else
        {
            Logger::warning('user %d is not guard domain %d pit %d',$uid,$domainId,$pitId);
            return FALSE;
        }
    }
    
    public function transferGuard($uid,$preDomainId,$prePitId,$afterDomainId,$afterPitId)
    {
        if($this->quitGuard($uid, $preDomainId, $prePitId) == FALSE)
        {
            return FALSE;
        }
        if($this->addGuard($uid, $afterDomainId, $afterPitId) == FALSE)
        {
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * 1.保存资源矿信息（发收益到奖励中心）
     * 2.保存守卫信息，对于能批量更新的就批量更新（发收益到奖励中心）
     * 3.必须先更新资源矿信息 （资源矿更新时，给矿主算收益时，要守卫军信息）
     */
    public function save()
    {
        $arrRfredPit = array();
        foreach(self::$arrPit as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $pitObj)
            {
                if($pitObj->save())
                {
                    $arrRfredPit[$domainId][$pitId] = TRUE;
                }
            }
        }
        $arrSaveGuard = array();
        if(self::$arrGuard != self::$arrGuardBuffer)
        {
            foreach(self::$arrGuard as $uid => $guardInfo)
            {
                if(!isset(self::$arrGuardBuffer[$uid]) || 
                        (self::$arrGuardBuffer[$uid] != $guardInfo))
                {
                    $domainId = $guardInfo[TblMineralGuards::DOMAINID];
                    $pitId = $guardInfo[TblMineralGuards::PITID];
                    $arrSaveGuard[$domainId][$pitId][] = $guardInfo;
                    if(empty($domainId) && (empty($pitId)) && (isset(self::$arrGuardBuffer[$uid])))
                    {
                        $domainId = self::$arrGuardBuffer[$uid][TblMineralGuards::DOMAINID];
                        $pitId = self::$arrGuardBuffer[$uid][TblMineralGuards::PITID];
                    }
                    if(!empty($domainId) && (!empty($pitId)))
                    {
                        $arrRfredPit[$domainId][$pitId] = TRUE;
                    }
                }
            }
        }
        foreach($arrSaveGuard as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $arrGuard)
            {
                if(empty($domainId) && (empty($pitId)))
                {
                    $arrUid = array_keys(Util::arrayIndex($arrGuard, TblMineralGuards::UID));
                    $this->saveQuitGuard($arrUid);
                }
                else if(empty($domainId) || empty($pitId))
                {
                    Logger::warning('save guard error.damainid %d pitid %d',$domainId,$pitId);
                }
                else
                {
                    foreach($arrGuard as $guardInfo)
                    {
                        $this->saveGuardInfo($guardInfo);
                    }
                }
            }
        }
        //TODO:发奖励  SendReward to center (给那些放弃或者被迫放弃守卫的玩家发奖励)
        foreach($arrSaveGuard as $uid => $guardInfo)
        {
            if($guardInfo[TblMineralGuards::DOMAINID] == 0 
                    && ($guardInfo[TblMineralGuards::PITID] == 0))
            {
                if(isset(self::$arrGuardBuffer[$uid]))
                {
                    $domainId = self::$arrGuardBuffer[$uid][TblMineralGuards::DOMAINID];
                    $pitId = self::$arrGuardBuffer[$uid][TblMineralGuards::PITID];
                    $guardTime = self::$arrGuardBuffer[$uid][TblMineralGuards::GUARDTIME];
                    
                }
            }
        }
        self::$arrGuardBuffer = self::$arrGuard;
        foreach($arrRfredPit as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $status)
            {
                //推送消息
            }
        }
    }
    
    public function saveGuardInfo($guardInfo)
    {
        MineralDAO::insertUpdateGuard($guardInfo);
    }
    
    public function saveQuitGuard($arrUid)
    {
        $arrWhere = array(
                array(TblMineralGuards::UID, 'IN', $arrUid)
        );
        $arrField = array(
                TblMineralGuards::DOMAINID => 0,
                TblMineralGuards::PITID => 0,
                TblMineralGuards::DUETIMER =>0,
                TblMineralGuards::STATUS => GuardType::ISNOTGUARD,
                TblMineralGuards::GUARDTIME => 0
        );
        MineralDAO::updateGuards($arrWhere, $arrField);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */