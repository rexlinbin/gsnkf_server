<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnvelopeDao.class.php 222858 2016-01-18 06:47:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/envelope/EnvelopeDao.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-01-18 06:47:20 +0000 (Mon, 18 Jan 2016) $
 * @version $Revision: 222858 $
 * @brief 
 *  
 **/
class EnvelopeDao
{
    private static $tbl_envelope = 't_envelope';
    private static $tbl_envelope_user = 't_envelope_user';
    
    public static function getSingleEnvelopeInfo($eid, $arrField)
    {
        $data = new CData();
        
        $ret = $data->select($arrField)
                    ->from(self::$tbl_envelope)
                    ->where(array('eid', '=', $eid))
                    ->query();
        
        return empty($ret[0]) ? array() : $ret[0];
    }
    
    public static function getArrEnvelopeInfo($arrEid, $arrField)
    {
        if (empty($arrEid))
        {
            return array();
        }
        
        $eidNum = count($arrEid);
        
        $arrEidList = array();
        
        $startIndex = 0;
        $detaIndex = DataDef::MAX_FETCH;
        
        $offset = 0;
        
        do 
        {
            $arrEidList[] = array_slice($arrEid, $startIndex, $detaIndex);
            $offset += $detaIndex;
            
        }while ($offset < $eidNum);
        
        $data = new CData();
        
        $ret = array();
        
        foreach ($arrEidList as $value)
        {
            $tmpRet = $data->select($arrField)
                            ->from(self::$tbl_envelope)
                            ->where(array('eid', 'IN', $arrEid))
                            ->query();
            $ret = array_merge($ret, $tmpRet);
        }
        
        return empty($ret) ? array() : $ret;
    }
    
    public static function getEnvelopeList($arrWhere, $arrField, $offset=0, $limit = DataDef::MAX_FETCH)
    {
        $ret = array();
        
        $tmpLimit = DataDef::MAX_FETCH;
        
        if ($tmpLimit > $limit)
        {
            $tmpLimit = $limit;
        }
        
        $data = new CData();
        
        $tmpRet = array();
        
        do 
        {
            $data->select($arrField)->from(self::$tbl_envelope);
            
            foreach ($arrWhere as $where)
            {
                $data->where($where);
            }
            
            $tmpRet = $data->orderBy(EnvelopeDef::SQL_ENVELOPE_SEND_TIME, FALSE)
                            ->orderBy(EnvelopeDef::SQL_ENVELOPE_EID, FALSE)
                            ->limit($offset, $tmpLimit)
                            ->query();
            
            $ret = array_merge($ret, $tmpRet);
            $offset += $tmpLimit;
            
        }while (count($tmpRet) == $tmpLimit);
        
        return $ret;
    }
    
    public static function getSendListByUid($uid, $arrField, $startTime = 0, $endTime = PHP_INT_MAX, $offset = 0, $limit = DataDef::MAX_FETCH)
    {
        $ret = array();
        
        $data = new CData();
        
        $tmpLimit = DataDef::MAX_FETCH;
        
        if ($tmpLimit > $limit)
        {
            $tmpLimit = $limit;
        }
        
        $tmpRet = array();
        
        do 
        {
            $tmpRet = $data->select($arrField)
                            ->from(self::$tbl_envelope)
                            ->where(array('uid', '=', $uid))
                            ->where(array('send_time', 'BETWEEN', array($startTime,$endTime)))
                            ->orderBy(EnvelopeDef::SQL_ENVELOPE_SEND_TIME, FALSE)
                            ->limit($offset, $tmpLimit)
                            ->query();
            
            $ret = array_merge($ret, $tmpRet);
            
            $offset += $tmpLimit;
            
        }while ( count($tmpRet) == $tmpLimit );
        
        return $ret;
    }
    
    public static function addNewEnvelope($arrValue)
    {
        $data = new CData();
        
        $ret = $data->insertInto(self::$tbl_envelope)
                    ->values($arrValue)
                    ->query();
        
        if ( 0 == $ret[DataDef::AFFECTED_ROWS] )
		{
			throw new FakeException( 'insert envelope failed, arrValue:%s',$arrValue);
		}
    }
    
    public static function updateEnvelope($eid, $arrValue)
    {
        $data = new CData();
    
        $ret = $data->update(self::$tbl_envelope)
                    ->set($arrValue)
                    ->where(array('eid', '=', $eid))
                    ->query();
    
        if ( 0 == $ret[DataDef::AFFECTED_ROWS] )
        {
            throw new FakeException( 'update envelope failed. eid:%d arrValue:%s.', $eid, $arrValue);
        }
    }
    
    public static function updateArrEnvelope($arrEid, $arrValue)
    {
        if ( empty($arrEid) )
        {
            return ;
        }
        
        $data = new CData();
        
        $ret = $data->update(self::$tbl_envelope)
                    ->set($arrValue)
                    ->where(array('eid', 'IN', $arrEid))
                    ->query();
        
        if ( 0 == $ret[DataDef::AFFECTED_ROWS] )
        {
            throw new FakeException( 'update envelope failed. arrEid:%s arrValue:%s.', $arrEid, $arrValue);
        }
    }
    
    public static function addNewEnvelopeUser($arrValue)
    {
        $data = new CData();
        
        $ret = $data->insertInto(self::$tbl_envelope_user)
                    ->values($arrValue)
                    ->query();
        
        if ( 0 == $ret[DataDef::AFFECTED_ROWS] )
        {
            throw new FakeException( 'insert envelope user failed, vals: %s', $arrValue  );
        }
    }
    
    public static function getEnvelopeUserListByEid($eid, $arrField)
    {
        $data = new CData();
        
        $ret = $data->select($arrField)
                    ->from(self::$tbl_envelope_user)
                    ->where(array(EnvelopeDef::SQL_ENVELOPE_USER_EID, '=', $eid))
                    ->orderBy(EnvelopeDef::SQL_ENVELOPE_USER_RECV_GOLD, FALSE)
                    ->orderBy(EnvelopeDef::SQL_ENVELOPE_USER_RECV_TIME, TRUE)
                    ->orderBy(EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID, TRUE)
                    ->query();
        
        return empty($ret) ? array() : $ret;
    }
    
    public static function getEnvelopeUserInfoByUidAndEid($uid, $eid, $arrField)
    {
        $data = new CData();
        
        $ret = $data->select($arrField)
                    ->from(self::$tbl_envelope_user)
                    ->where(array(EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID, '=', $uid))
                    ->where(array(EnvelopeDef::SQL_ENVELOPE_USER_EID, '=', $eid))
                    ->query();
        
        return empty($ret[0]) ? array() : $ret[0];
    }
    
    public static function getEnvelopeUserRecvListByUid($uid, $arrField, $startTime = 0, $endTime = PHP_INT_MAX, $offset = 0, $limit = DataDef::MAX_FETCH)
    {
        $ret = array();
        
        $data = new CData();
        
        $tmpRet = array();
        do 
        {
            $tmpRet = $data->select($arrField)
                        ->from(self::$tbl_envelope_user)
                        ->where(array(EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID, '=', $uid))
                        ->where(array(EnvelopeDef::SQL_ENVELOPE_USER_RECV_TIME, 'BETWEEN', array($startTime, $endTime)))
                        ->orderby(EnvelopeDef::SQL_ENVELOPE_USER_RECV_TIME, FALSE)
                        ->query();
            
            $ret = array_merge($ret, $tmpRet);
            $offset += $limit;
            
        }while (count($tmpRet) == $limit);
        
        return empty($ret) ? array() : $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */