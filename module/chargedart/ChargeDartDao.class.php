<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDartDao.class.php 239308 2016-04-20 09:42:10Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/ChargeDartDao.class.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-04-20 09:42:10 +0000 (Wed, 20 Apr 2016) $
 * @version $Revision: 239308 $
 * @brief 
 *  
 **/
class ChargeDartDao
{
    private static $tb_user = 't_charge_dart_user';
    private static $tb_road = 't_charge_dart_road';
    private static $tb_record = 't_charge_dart_record';
    
    /******t_charge_dart_user******/
    public static function insertOrchangeUserInfo($arrFeild)
    {
        $data = new CData();
        $arrRet = $data->insertOrUpdate(self::$tb_user)->values($arrFeild)->query();
        return $arrRet;
    }
    
    public static function getUserInfoByUid($uid,$arrFeild) 
    {
        $data = new CData();
        
        $data->select($arrFeild)->from(self::$tb_user);
        $data->where(array(ChargeDartDef::SQL_UID,'=',$uid));
        
        $arrRet = $data->query();
        return empty($arrRet)?array():$arrRet[0];
    }
    
    public static function changeUserInfo($uid,$arrFeild)
    {
        $data = new CData();
        
        $arrRet = $data->update(self::$tb_user)
        ->set($arrFeild)
        ->where(array(ChargeDartDef::SQL_UID,'=',$uid))
        ->query();
        
        return $arrRet;
    }
    
    public static function getUserInfoByPage($stageId, $pageId, $time)
    {
        $data = new CData();
        $arrFeild = array(
            ChargeDartDef::SQL_UID,
            ChargeDartDef::SQL_ROAD_ID,
            ChargeDartDef::SQL_BEGIN_TIME,
            ChargeDartDef::SQL_BE_ROBBED_NUM,
        );
        
        $data->select($arrFeild)->from(self::$tb_user);
        
        $data->where(array(ChargeDartDef::SQL_STAGE_ID,'=',$stageId))
        ->where(array(ChargeDartDef::SQL_PAGE_ID,'=',$pageId))
        ->where(array(ChargeDartDef::SQL_BEGIN_TIME,'>=',$time))
        ->orderBy(ChargeDartDef::SQL_ROAD_ID, true)
        ->orderBy(ChargeDartDef::SQL_BEGIN_TIME, true);
        
        $ret = $data->query();
        return $ret;
    }
    
    /******t_charge_dart_road******/
    public static function getFirstFreeRoad($time,$stageId)
    {
        $data = new CData();
        
        $selectFeild = array(
            ChargeDartDef::SQL_PAGE_ID,
            ChargeDartDef::SQL_ROAD_ID,
            ChargeDartDef::SQL_PREVIOUS_TIME,
        );
        
        $arrRet = $data->select($selectFeild)
        ->from(self::$tb_road)
        ->where(array(ChargeDartDef::SQL_STAGE_ID,'=',$stageId))
        ->where(array(ChargeDartDef::SQL_PREVIOUS_TIME,'<',$time))
        ->orderBy(ChargeDartDef::SQL_PAGE_ID, true)
        ->orderBy(ChargeDartDef::SQL_ROAD_ID, true)
        ->limit(0, 1)
        ->query();
        
        return empty($arrRet)?array():$arrRet[0];
    }
    
    public static function changeRoadTime($stageId, $pageId, $roadId, $time, $changeTime)
    {
        $data = new CData();
        
        $arrFeild = array(
            ChargeDartDef::SQL_PREVIOUS_TIME => $changeTime,  
        );
        
        $data->update(self::$tb_road)->set($arrFeild)
        ->where(array(ChargeDartDef::SQL_STAGE_ID,'=',$stageId))
        ->where(array(ChargeDartDef::SQL_PAGE_ID,'=',$pageId))
        ->where(array(ChargeDartDef::SQL_ROAD_ID,'=',$roadId))
        ->where(array(ChargeDartDef::SQL_PREVIOUS_TIME,'=',$time));
        
        $arrRet = $data->query();
        
        if($arrRet['affected_rows'] != 1)
        {
            return false;
        }
        else{
            return true;
        }
    }
    
    
    /******t_charge_dart_record******/
    public static function saveRecord($uid, $beUid, $type, $beRobbedNum, $stageId, $success = 1, $vaInfo = array())
    {
        $data = new CData();
        
        $arrFeild = array(
            ChargeDartDef::SQL_STAGE_ID => $stageId,
            ChargeDartDef::SQL_UID => $uid,
            ChargeDartDef::SQL_TIME => Util::getTime(),
            ChargeDartDef::SQL_BE_UID => $beUid,
            ChargeDartDef::SQL_TYPE => $type,
            ChargeDartDef::SQL_BE_ROBBED_NUM => $beRobbedNum,
            ChargeDartDef::SQL_SUCCESS => $success,
            ChargeDartDef::SQL_VA_INFO => $vaInfo,
        );
        
        $arrRet = $data->insertIgnore(self::$tb_record)->values($arrFeild)->uniqueKey(ChargeDartDef::SQL_RECORD_ID)->query();
        return $arrRet;
    }
    
    public static function getRecord($wheres, $arrFeild, $limit)
    {
        $data = new CData();
        
        $data->select($arrFeild)->from(self::$tb_record);
        foreach ($wheres as $whe)
        {
            $data->where($whe);
        }
        
        $data->orderBy(ChargeDartDef::SQL_TIME, desc);
        $data->limit(0, $limit);
        $arrRet = $data->query();
        
        return $arrRet;
    }
    
    public static function getLookRecord($uid, $beUid, $time)
    {
        $data = new CData();
        
        $arrRet = $data->selectCount()->from(self::$tb_record)
        ->where(array(ChargeDartDef::SQL_UID,'=',$uid))
        ->where(array(ChargeDartDef::SQL_BE_UID,'=',$beUid))
        ->where(array(ChargeDartDef::SQL_TIME,'>=',$time))
        ->where(array(ChargeDartDef::SQL_TYPE,'=',ChargeDartDef::TYPE_LOOK))
        ->query();
        
        return $arrRet[0]['count'];
    } 
    
    //成功抢夺的信息
    public static function getBattleRecord($uid, $beUid, $time)
    {
        $data = new CData();
        
        $arrRet = $data->selectCount()->from(self::$tb_record)
        ->where(array(ChargeDartDef::SQL_UID,'=',$uid))
        ->where(array(ChargeDartDef::SQL_BE_UID,'=',$beUid))
        ->where(array(ChargeDartDef::SQL_TIME,'>=',$time))
        ->where(array(ChargeDartDef::SQL_TYPE,'=',ChargeDartDef::TYPE_BATTLE))
        ->where(array(ChargeDartDef::SQL_SUCCESS,'=',1))
        ->query();
        
        return $arrRet[0]['count'];
    }
    
    //根据时间和区域id来拉取成功的战报
    public static function getBattleRecordByStage($stageId, $limit = DataDef::MAX_FETCH)
    {
        $nowTime = Util::getTime();
        $data = new CData();
        
        $arrFeild = array(
            ChargeDartDef::SQL_UID,
            ChargeDartDef::SQL_BE_UID,
            ChargeDartDef::SQL_TIME,
            ChargeDartDef::SQL_VA_INFO,
        );
        
        $arrRet = $data->select($arrFeild)->from(self::$tb_record)
        ->where(array(ChargeDartDef::SQL_STAGE_ID,'=',$stageId))
        ->where(array(ChargeDartDef::SQL_TYPE,'=',ChargeDartDef::TYPE_BATTLE))
        ->where(array(ChargeDartDef::SQL_SUCCESS,'=',1))
        ->where(array(ChargeDartDef::SQL_TIME,'>',$nowTime - ChargeDartDef::GETINFO_INTERVAL))
        ->orderBy(ChargeDartDef::SQL_TIME, false)
        ->limit(0, $limit)
        ->query();
        
        return $arrRet;
    }
    
    //根据时间和区域id来拉取成功的战报
    public static function getAllRecordByUid($uid, $limit = DataDef::MAX_FETCH)
    {
        $nowTime = Util::getTime();
        $data = new CData();
        
        $arrFeild = array(
            ChargeDartDef::SQL_STAGE_ID,
            ChargeDartDef::SQL_UID,
            ChargeDartDef::SQL_BE_UID,
            ChargeDartDef::SQL_TYPE,
            ChargeDartDef::SQL_TIME,
            ChargeDartDef::SQL_SUCCESS,
            ChargeDartDef::SQL_VA_INFO,
        );
        
        $arrRetUid = $data->select($arrFeild)->from(self::$tb_record)
        ->where(array(ChargeDartDef::SQL_UID,'=',$uid))
        ->where(array(ChargeDartDef::SQL_TYPE,'IN',array(ChargeDartDef::TYPE_BATTLE)))
        ->where(array(ChargeDartDef::SQL_TIME,'>',$nowTime - ChargeDartDef::GETINFO_INTERVAL))
        ->orderBy(ChargeDartDef::SQL_TIME, false)
        ->limit(0, $limit)
        ->query();
        
        $arrRetBeUid = $data->select($arrFeild)->from(self::$tb_record)
        ->where(array(ChargeDartDef::SQL_BE_UID,'=',$uid))
        ->where(array(ChargeDartDef::SQL_TYPE,'IN',array(ChargeDartDef::TYPE_BATTLE,ChargeDartDef::TYPE_LOOK)))
        ->where(array(ChargeDartDef::SQL_TIME,'>',$nowTime - ChargeDartDef::GETINFO_INTERVAL))
        ->orderBy(ChargeDartDef::SQL_TIME, false)
        ->limit(0, $limit)
        ->query();
        
        
        return array_merge($arrRetUid,$arrRetBeUid);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */