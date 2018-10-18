<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactDao.class.php 202034 2015-10-14 03:39:38Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/DesactDao.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-14 03:39:38 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202034 $
 * @brief 
 *  
 **/
class DesactDao
{
    private static $tblInnerDesact = 't_desact';
    private static $tblCrossDesact = 't_world_desact_config';
    
    public static function getDesactUser($uid, $arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
                    ->from(self::$tblInnerDesact)
                    ->where(array('uid', '=', $uid))
                    ->query();
        return empty($ret[0]) ? array() : $ret[0]; 
    }
    
    public static function insertDesactInfo($info)
    {
        $data = new CData();
        $ret = $data->insertInto(self::$tblInnerDesact)
                    ->values($info)
                    ->query();
        
        if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
        {
            throw new InterException('insert failed, data: %s', $info);
        }
    }
    
    public static function updateDesact($uid, $info)
    {
        $data = new CData();
        $ret = $data->update(self::$tblInnerDesact)
                    ->set($info)
                    ->where(array('uid', '=', $uid))
                    ->query();
        
        if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
        {
            throw new InterException( 'update failed, data: %s', $info);
        }
    }
    
    public static function getLastCrossConfig($arrField, $asc=FALSE, $offset=0, $limit=1)
    {
        $data = new CData();
        $ret = $data->useDb(WorldCarnivalUtil::getCrossDbName())
                    ->select($arrField)
                    ->from(self::$tblCrossDesact)
                    ->where(array('sess', '!=', 0))
                    ->orderBy('sess', $asc)
                    ->limit($offset, $limit)
                    ->query();
        
        return $ret;
    }
    
    public static function selectCrossConfig($sess, $arrField)
    {
        $data = new CData();
        $ret = $data->useDb(WorldCarnivalUtil::getCrossDbName())
                    ->select($arrField)
                    ->from(self::$tblCrossDesact)
                    ->where(array('sess', '=', $sess))
                    ->query();
        
        return empty($ret[0]) ? array() : $ret[0];
    }
    
    public static function insertCrossConfig($arrField)
    {
        $data = new CData();
        $ret = $data->useDb(WorldCarnivalUtil::getCrossDbName())
                    ->insertIgnore(self::$tblCrossDesact)
                    ->values($arrField)
                    ->query();
        
        return $ret;
    }
    
    public static function updateCrossConfig($sess, $arrField)
    {
        $data = new CData();
        $ret = $data->useDb(WorldCarnivalUtil::getCrossDbName())
                    ->update(self::$tblCrossDesact)
                    ->set($arrField)
                    ->where(array('sess', '=', $sess))
                    ->query();
        
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */