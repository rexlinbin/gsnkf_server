<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RobTombDao.class.php 202938 2015-10-17 10:46:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/RobTombDao.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:46:51 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202938 $
 * @brief 
 *  
 **/
class RobTombDao
{
    private static $tblName = 't_rob_tomb';
    
    public static function getRobInfo($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
             ->from(self::$tblName)
             ->where(array(RobTombDef::SQL_FIELD_UID,'=',$uid))
             ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function insertRobInfo($arrField)
    {
        $data = new CData();
        $data->insertInto(self::$tblName)
             ->values($arrField)
             ->query();   
    }
    
    public static function updateRobInfo($uid,$arrField)
    {
        $data = new CData();
        $data->update(self::$tblName)
             ->set($arrField)
             ->where(array(RobTombDef::SQL_FIELD_UID,'=',$uid))
             ->query();   
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */