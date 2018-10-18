<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DragonDao.class.php 119964 2014-07-11 08:42:10Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/DragonDao.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-07-11 08:42:10 +0000 (Fri, 11 Jul 2014) $$
 * @version $$Revision: 119964 $$
 * @brief 
 *  
 **/
class DragonDao
{
    public static function loadData($uid)
    {
        $data = new CData();
        $arrRet = $data->select(TblDragonDef::$DRAGON_FIELDS)
            ->from(TblDragonDef::TABELDRAGON)
            ->where(array(TblDragonDef::UID, '=', $uid))
            ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0];
        }
        return array();
    }

    public static function insert($dragonInfo)
    {
        $data = new CData();
        $ret = $data->insertInto(TblDragonDef::TABELDRAGON)
                ->values($dragonInfo)
                ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function update($dragonInfo, $uid)
    {
        $data = new CData();
        $ret = $data->update(TblDragonDef::TABELDRAGON)
                ->set($dragonInfo)
                ->where(array(TblDragonDef::UID, '=', $uid))
                ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function updateTotalPoint($point, $uid)
    {
        $data = new CData();
        $ret = $data->update(TblDragonDef::TABELDRAGON)
                    ->set(array(TblDragonDef::TOTAL_POINT => $point))
                    ->where(array(TblDragonDef::UID, '=', $uid))
                    ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */