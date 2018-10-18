<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoomUtil.class.php 139439 2014-11-11 03:29:52Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/DressRoomUtil.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-11 03:29:52 +0000 (Tue, 11 Nov 2014) $$
 * @version $$Revision: 139439 $$
 * @brief 
 *  
 **/
class DressRoomUtil
{
    public static function getAviableDressFromConf()
    {
        return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ARR_DRESS_ROOM_ID]->toArray();
    }

    public static function isItemTmpIdAviableDress($itemTmpId)
    {
        $arrAviableDress = self::getAviableDressFromConf();
        if (empty($itemTmpId))
        {
            return false;
        }
        if (in_array($itemTmpId, $arrAviableDress))
        {
            return true;
        }
        return false;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */