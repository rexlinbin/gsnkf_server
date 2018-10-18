<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OlympicDao.class.php 126387 2014-08-12 09:10:47Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicDao.class.php $
 * @author $Author: ShijieHan $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-12 09:10:47 +0000 (Tue, 12 Aug 2014) $
 * @version $Revision: 126387 $
 * @brief 
 *  
 **/
class OlympicDao
{
    private static $tblOlympic = 't_olympic';
    private static $tblOlympicLog = 't_olympic_log';
    private static $tblUserOlympic = 't_user_olympic';
    private static $tblOlympicRank = 't_olympic_rank';
    private static $tblOlympicGlobal = 't_olympic_global';
    
    public static function getOlympicRank($signUpIndex)
    {
        $data = new CData();
        $ret = $data->select(OlympicRankDef::$ALL_FIELD)
                    ->from(self::$tblOlympicRank)
                    ->where(array(OlympicRankDef::FIELD_SIGNUP_INDEX,'=',$signUpIndex))
                    ->query();
        if(empty($ret) || empty($ret[0]))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function insertOlympicRank($rankInfo)
    {
        $data = new CData();
        $data->insertInto(self::$tblOlympicRank)
             ->values($rankInfo)
             ->query();
    }
    
    public static function getAllSignUpUser()
    {
        $data = new CData();
        $ret = $data->select(OlympicRankDef::$ALL_FIELD)
                    ->from(self::$tblOlympicRank)
                    ->where(array(OlympicRankDef::FIELD_UID,'>',0))
                    ->query();
        return $ret;
    }
    
    public static function saveRankInfo($rankInfo,$signUpIndex)
    {
        $data = new CData();
        $data->update(self::$tblOlympicRank)
             ->set($rankInfo)
             ->where(array(OlympicRankDef::FIELD_SIGNUP_INDEX,'=',$signUpIndex))
             ->query();
    }
    
    public static function setAllRank($arrValue)
    {
    	$data = new CData();
    	$data->update(self::$tblOlympicRank)
    		->set($arrValue)
	    	->where(array(OlympicRankDef::FIELD_SIGNUP_INDEX,'>=',0))
	    	->query();
    }
    
    public static function getRankInfoByUid($uid)
    {
        $data = new CData();
        $data->select(OlympicRankDef::$ALL_FIELD)
             ->from(self::$tblOlympicRank)
             ->where(array(OlympicRankDef::FIELD_UID,'=',$uid));
        $ret = $data->query();
        if(empty($ret) || (empty($ret[0])))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function getUserOlympicInfo($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
                    ->from(self::$tblUserOlympic)
                    ->where(array(UserOlympicDef::FIELD_UID,'=',$uid))
                    ->query();
        if(empty($ret) || (empty($ret[0])))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function insertUserOlympicInfo($olympicInfo)
    {
        $data = new CData();
        $data->insertInto(self::$tblUserOlympic)
             ->values($olympicInfo)
             ->query();
    }
    
    public static function saveUserOlympicInfo($uid,$olympicInfo)
    {
        $data = new CData();
        $data->update(self::$tblUserOlympic)
             ->set($olympicInfo)
             ->where(array(UserOlympicDef::FIELD_UID,'=',$uid))
             ->query();
    }

    public static function getUserOlympicCheer($cheerUid, $arrField, $preStartTime)
    {
        $data = new CData();
        $ret = $data->select($arrField)
            ->from(self::$tblUserOlympic)
            ->where(array(UserOlympicDef::FIELD_CHEERUID,'=',$cheerUid))
            ->where(array(UserOlympicDef::FIELD_CHEER_TIME, '>', $preStartTime))
            ->query();
        return $ret;
    }

    public static function updateUserOlympicWithWherre($arrField, $confWhere)
    {
        $data = new CData();
        $data->update(self::$tblUserOlympic)
            ->set($arrField)
            ->where($confWhere)
            ->query();
    }

    public static function getUserOlympicWithWhere($arrField, $arrConfWhere)
    {
        $data = new CData();
        $data->select($arrField)
            ->from(self::$tblUserOlympic);
        foreach($arrConfWhere as $confWhere)
        {
            $data->where($confWhere);
        }
        $ret = $data->query();
        return $ret;
    }

    /******************************************************************************************************************
     * t_olympic_log 表相关实现
     ******************************************************************************************************************/

    public static function insertOlympicLog($set)
    {
        $data = new CData();
        $ret = $data->insertInto(self::$tblOlympicLog)
                    ->values($set)
                    ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function updateOlympicLog($date_ymd, $log_type, $va_log_info)
    {
        $data = new CData();
        $arrRet = $data->update(self::$tblOlympicLog)
                    ->set(array('va_log_info' => $va_log_info))
                    ->where(array('date_ymd', '=', $date_ymd))
                    ->where(array('log_type', '=', $log_type))
                    ->query();
        return $arrRet;
    }

    public static function resetOlympicLog($date_ymd, $log_type)
    {
        $data = new CData();
        $arrRet = $data->update(self::$tblOlympicLog)
                    ->set(array('va_log_info' => array()))
                    ->where(array('date_ymd', '=', $date_ymd))
                    ->where(array('log_type', '=', $log_type))
                    ->query();
        return $arrRet;
    }

    public static function getOlympicLog($date_ymd, $log_type)
    {
        $data = new CData();
        $arrRet = $data->select(array('va_log_info'))
                    ->from(self::$tblOlympicLog)
                    ->where(array('date_ymd', '=', $date_ymd))
                    ->where(array('log_type', '=', $log_type))
                    ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0]['va_log_info'];
        }
        return array();
    }
    
    public static function getArrOlympicLog($dateYmd,$arrLogType)
    {
        $data = new CData();
        $arrRet = $data->select(array(OlympicLogDef::FIELD_LOG_INFO,OlympicLogDef::FIELD_LOG_TYPE))
                       ->from(self::$tblOlympicLog)
                       ->where(array(OlympicLogDef::FIELD_LOG_DATE_YMD, '=', $dateYmd))
                       ->where(array(OlympicLogDef::FIELD_LOG_TYPE, 'IN', $arrLogType))
                       ->query();
        return Util::arrayIndexCol($arrRet, 
                OlympicLogDef::FIELD_LOG_TYPE, OlympicLogDef::FIELD_LOG_INFO);
    }

    //**********************t_reward_pool***********************
    public static function getOlympicGlobal($id)
    {
        $data = new CData();
        $arrRet = $data->select(OlympicGlobalDef::$REWARD_POOL_ARR_FIELD)
                    ->from(self::$tblOlympicGlobal)
                    ->where(array(OlympicGlobalDef::ID, '=', $id))
                    ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0];
        }
        return array();
    }

    public static function insertOlympicGlobal($set)
    {
        $data = new CData();
        $ret = $data->insertInto(self::$tblOlympicGlobal)
                ->values($set)
                ->query();
        if($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function addSilverPool($addSilver, $id)
    {
        $batch = new BatchData();
        $data = $batch->newData();
        $opSilverPool = new IncOperator($addSilver);
        $data->update(self::$tblOlympicGlobal)
            ->set(array(OlympicGlobalDef::SILVER_POOL => $opSilverPool))
            ->where(OlympicGlobalDef::ID, '=', $id)
            ->query();
        $batch->query();
    }

    public static function getSilverPool($id)
    {
        $data = new CData();
        $ret = $data->select(array(OlympicGlobalDef::SILVER_POOL))
                ->from(self::$tblOlympicGlobal)
                ->where(OlympicGlobalDef::ID, '=', $id)
                ->query();
        if(!empty($ret[0]))
        {
            return $ret[0];
        }
        return array();
    }

    public static function updOlympicGlobal($set, $id)
    {
        $data = new CData();
        $ret = $data->update(self::$tblOlympicGlobal)
            ->set($set)
            ->where(array(OlympicGlobalDef::ID, '=', $id))
            ->query();
        if($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */