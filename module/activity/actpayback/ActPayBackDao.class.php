<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActPayBackDao.class.php 231354 2016-03-07 11:07:14Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actpayback/ActPayBackDao.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-07 11:07:14 +0000 (Mon, 07 Mar 2016) $
 * @version $Revision: 231354 $
 * @brief 
 *  
 **/
class ActPayBackDao
{
    private static $tbl = 't_pay_back';
    
    public static function getInfo($uid, $arrField)
    {
        $data = new CData();
        
        $ret = $data->select($arrField)
                    ->from(self::$tbl)
                    ->where(array('uid', '=', $uid))
                    ->query();
        
        return empty($ret) ? array() : $ret[0];
    }
    
    public static function insert($uid, $arrField)
    {
        $data = new CData();
        $ret = $data->insertInto(self::$tbl)
                    ->values($arrField)
                    ->query();
    }
    
    public static function update($uid, $arrField)
    {
        $data = new CData();
        $ret = $data->update(self::$tbl)
                    ->set($arrField)
                    ->where(array('uid', '=', $uid))
                    ->query();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */