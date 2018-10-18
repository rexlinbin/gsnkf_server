<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PitManager.class.php 240013 2016-04-25 09:00:00Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/PitManager.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-25 09:00:00 +0000 (Mon, 25 Apr 2016) $
 * @version $Revision: 240013 $
 * @brief 
 *  
 **/
class PitManager
{
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
    private static $arrPit=array();
    
    /**
     *
     * @var PitManager
     */
    private static $_instance = NULL;
    
    public static function getInstance()
    {
        if(self::$_instance == NULL)
        {
            self::$_instance = new PitManager();
        }
        return self::$_instance;
    }
    
    public static function release()
    {
        self::$_instance = NULL;
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
        $arrdbPitInfo = MineralDAO::getArrPit($domainId,$arrPitId);
        $arrDbGuardInfo = MineralDAO::getArrGuard($domainId,$arrPitId);
        $arrGuardInfo = array();
        foreach($arrDbGuardInfo as $guardInfo)
        {
            $domainId = $guardInfo[TblMineralGuards::DOMAINID];
            $pitId = $guardInfo[TblMineralGuards::PITID];
            $uid = $guardInfo[TblMineralGuards::UID];
            $arrGuardInfo[$domainId][$pitId][$uid] = $guardInfo;
        }
        foreach($arrdbPitInfo as $pitInfo)
        {
            $domainId = $pitInfo[TblMineralField::DOMAINID];
            $pitId = $pitInfo[TblMineralField::PITID];
            if( isset(self::$arrPit[$domainId][$pitId]) )
            {
                Logger::debug('already get obj for dmainId:%d, pitId:%s', $domainId, $pitId);
                continue;
            }
            if(MineralLogic::isGoldDomain($domainId))
            {
                $pitObj = new GoldPitObj($pitInfo);
            }
            else
            {
                $guardInfo = array();
                if(isset($arrGuardInfo[$domainId][$pitId]))
                {
                    $guardInfo = $arrGuardInfo[$domainId][$pitId];
                }
                $guardInfo = Util::arrayIndex($guardInfo, TblMineralGuards::UID);
                $pitObj = new PitObj($pitInfo,$guardInfo);
            }
            self::$arrPit[$domainId][$pitId] = $pitObj;
            $ret[$domainId][$pitId] = $pitObj->getDbInfo();
        }
        return MineralLogic::resetArrPitInfo($ret);
    }
    
    public function save()
    {
        foreach(self::$arrPit as $domainId => $arrPit)
        {
            foreach($arrPit as $pitId => $pitObj)
            {
                $pitObj->save();
            }
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */