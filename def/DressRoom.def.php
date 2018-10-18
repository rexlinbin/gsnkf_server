<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoom.def.php 189229 2015-08-06 06:36:26Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/DressRoom.def.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-08-06 06:36:26 +0000 (Thu, 06 Aug 2015) $$
 * @version $$Revision: 189229 $$
 * @brief 
 *  
 **/
class TblDressRoomDef
{
    const TBLDRESSROOM = 't_dress_room';    //表名
    const UID = 'uid';
    const VA_DATA = 'va_data';
    const ARRDRESS = 'arr_dress';   //已收集的时装
    const ACTIVESTATUS = 'as';  //激活状态
    const CURDRESS = 'cur_dress';   //当天所穿套装
}

class DressRoomDef
{
    const ACTIVESTATUSYES = 1;    //已激活
    const ACTIVESTATUSNO = 0;   //未激活
}

class SuitDressCsvDef
{
    const ID = "id";
    const SUIT_ITEMS = "suit_items";
    const SUIT_ATTR = "suit_attr";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */