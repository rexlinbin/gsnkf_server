<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IDesact.class.php 202029 2015-10-14 03:23:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/IDesact.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-14 03:23:20 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202029 $
 * @brief 
 *  
 **/
interface IDesact
{
    /**
     * 获取新类型福利活动数据
     * @return array
     *         [
     *             'config' => array                              活动配置
     *                         [
     *                              'start_time':int
     *                              'end_time':int
     *                              'desc': string
     *                              'name': string
     *                              'tip' : string
     *                              'id'  : int
     *                              'reward':array
     *                                      [
     *                                          0 : array
     *                                              [
     *                                                  num:int
     *                                                  reward:array
     *                                              ]
     *                                      ]
     *                         ]
     *             'taskInfo' => array                            任务信息
     *                         [
     *                             num : int                       达成次数
     *                             rewarded : array
     *                                         [
     *                                             rid : int       已领取奖励id(0,1,2,3,……)
     *                                         ]
     *                         ]
     *         ]
     */
    function getDesactInfo();
    
    /**
     * 领奖
     * @param int $id 奖励id(从0开始)
     * @return 'ok'
    */
    function gainReward($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */