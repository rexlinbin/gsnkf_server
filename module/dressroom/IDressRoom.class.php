<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IDressRoom.class.php 139439 2014-11-11 03:29:52Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/IDressRoom.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-11 03:29:52 +0000 (Tue, 11 Nov 2014) $$
 * @version $$Revision: 139439 $$
 * @brief 
 *  
 **/
interface IDressRoom
{

    /**
     * @return array
     * [
     *  'cur_dress' => int $itemTmpid 0表示当前没穿时装
     *  'arr_dress' => [
     *      $itemTmpId => ['as' => 0(激活状态 0为激活 1 已激活)] 所有获得过的时装id
     *      ]
     * ]
     */
    function getDressRoomInfo();

    /**
     * 激活
     * @param $itemTmpId
     * @return 'ok'
     */
    function activeDress($itemTmpId);

    /**
     * 换装
     * @param $itemTmpId
     * @return 'ok'
     */
    function changeDress($itemTmpId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */