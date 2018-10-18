<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: AthenaDao.class.php 164379 2015-03-31 03:15:47Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/AthenaDao.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-03-31 03:15:47 +0000 (Tue, 31 Mar 2015) $$
 * @version $$Revision: 164379 $$
 * @brief 
 *  
 **/
class AthenaDao
{
    public static function loadData($uid)
    {
        $data = new CData();
        $ret = $data->select(AthenaSql::$arrColumn)
                    ->from(AthenaSql::TABLE)
                    ->where(array(AthenaSql::UID, '=', $uid))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }

    public static function update($athena)
    {
        $data = new CData();
        $arrRet = $data->insertOrUpdate(AthenaSql::TABLE)
            ->values($athena)
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */