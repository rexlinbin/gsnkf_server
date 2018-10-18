<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DestinyDao.class.php 81493 2013-12-18 05:55:09Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/DestinyDao.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-18 05:55:09 +0000 (Wed, 18 Dec 2013) $
 * @version $Revision: 81493 $
 * @brief 
 *  
 **/
class DestinyDao
{
    private static $tblName = 't_destiny';
    
    public static function getDestinyInfo($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
             ->from(self::$tblName)
             ->where(array('uid','=',$uid))
             ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function updateDestinyInfo($uid,$destinyInfo)
    {
        $data = new CData();
        $data->update(self::$tblName)
             ->set($destinyInfo)
             ->where(array('uid','=',$uid))
             ->query();
    }
    
    public static function insertDestinyInfo($destinyInfo)
    {
        $data = new CData();
        $data->insertInto(self::$tblName)
             ->values($destinyInfo)
             ->query();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */