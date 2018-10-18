<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoomDao.class.php 141049 2014-11-21 03:33:20Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/DressRoomDao.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-21 03:33:20 +0000 (Fri, 21 Nov 2014) $$
 * @version $$Revision: 141049 $$
 * @brief 
 *  
 **/
class DressRoomDao
{
    public static function loadAll($uid)
    {
        $data = new CData();
        $arrRet = $data->select(array(TblDressRoomDef::VA_DATA))
             ->from(TblDressRoomDef::TBLDRESSROOM)
             ->where(array(TblDressRoomDef::UID, '=', $uid))
             ->query();
        if (!empty($arrRet[0]))
        {
            return $arrRet[0][TblDressRoomDef::VA_DATA];
        }
        return array();
    }

    public static function insertData($uid, $vadata)
    {
        $data = new CData();
        $arrRet = $data->insertIgnore(TblDressRoomDef::TBLDRESSROOM)
            ->values(array(
                    TblDressRoomDef::UID => $uid,
                    TblDressRoomDef::VA_DATA => $vadata,
                    ))
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function updateData($uid, $vadata)
    {
        $data = new CData();
        $arrRet = $data->update(TblDressRoomDef::TBLDRESSROOM)
            ->set(array(TblDressRoomDef::VA_DATA => $vadata))
            ->where(array(TblDressRoomDef::UID, '=', $uid))
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */