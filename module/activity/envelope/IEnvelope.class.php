<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IEnvelope.class.php 222129 2016-01-14 09:38:44Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/envelope/IEnvelope.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-01-14 09:38:44 +0000 (Thu, 14 Jan 2016) $
 * @version $Revision: 222129 $
 * @brief 
 *  
 **/
interface IEnvelope
{
    /**
     * 拉取红包信息
     * @param int $type 种类(所有/军团/个人)
     * @return array[
     *          'canSendTotal' : num 总共剩余可发金币数
     *          'canSendToday': num  今日剩余可发金币数
     *          'rankList' : array[
     *                          0 : array[
     *                                  'uid' : uid  发红包人的用户id
     *                                  'uname' : uname 发红包人的角色名
     *                                  'eid' : eid 红包id
     *                                  'left' : num 剩余数量
     *                                  'gold' : num 抢到的数量（个人红包页面专用，0表示自己发的）
     *                                  'sendTime' : timestamp 发红包的时间(个人红包页面中领的别人红包此值为错误值)
     *                                 ]
     *                          1 : array
     *                      ]
     *          ]
     */
    public function getInfo($type);

    /**
     * 获取单个红包信息
     * @param int $eid
     * @return array[
     *                  'uid' : uid         发送者的uid
     *                  'uname' : uname     发送者的角色名
     *                  'shareNum' : num    份数
     *                  'leftNum' : num     剩余份数
     *                  'sendTime' : num    发送时间时间戳
     *                  'msg': msg          留言
     *                  'rankList' : array[
     *                                  0 : array[
     *                                          'uid'   : uid   用户id
     *                                          'uname' : uname 角色名
     *                                          'htid'  : htid
     *                                          'dressInfo' : array[]
     *                                          'gold' : gold   抢到的金币数
     *                                          ]
     *                                  ]
     *              ]
     */
    
    public function getSingleInfo($eid);
    
    /**
     * 发红包
     * @param int $eType        类型（世界/军团）
     * @param int $goldNum      金币总数
     * @param int $shareNum     份数
     * @param string $msg       附带信息
     * @return 'ok'
     */
    public function send($scale, $goldNum, $divNum, $msg);
    
    /**
     * 拆红包
     * @param int $eid    红包id
     * @return int $num   抢到的金币数（为0则说明没抢到）
     */
    public function open($eid);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */