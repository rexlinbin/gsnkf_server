<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoom.class.php 139439 2014-11-11 03:29:52Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/DressRoom.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-11 03:29:52 +0000 (Tue, 11 Nov 2014) $$
 * @version $$Revision: 139439 $$
 * @brief 
 *  
 **/
class DressRoom implements IDressRoom
{

    function getDressRoomInfo()
    {
        Logger::trace('DressRoom::getDressRoomInfo start');
        return DressRoomLogic::getDressRoomInfo();
    }

    function activeDress($itemTmpId)
    {
        Logger::trace('DressRoom::activeDress itemTmpId:%d start', $itemTmpId);
        return DressRoomLogic::activeDress($itemTmpId);
    }

    function changeDress($itemTmpId)
    {
        Logger::trace('DressRoom::changeDress itemTmpId:%d start', $itemTmpId);
        return DressRoomLogic::changeDress($itemTmpId);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */