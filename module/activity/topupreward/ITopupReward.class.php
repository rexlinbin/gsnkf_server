<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: ITopupReward.class.php 122072 2014-07-22 08:05:51Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/ITopupReward.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-07-22 08:05:51 +0000 (Tue, 22 Jul 2014) $$
 * @version $$Revision: 122072 $$
 * @brief 
 *  
 **/

interface ITopupReward
{
    /**
     * 获得奖励数据
     * @return array
     * [
     *  'data' => array
     *      [
     *          0 => array[0, 0], （第一个参数：是否能够领奖0否1是， 第二个参数：是否已经领取0否1是）
     *          1 => array[0, 1], （第一个参数：是否能够领奖0否1是， 第二个参数：是否已经领取0否1是）
     *          ...
     *      ],
     *  'day' => int 活动第几天（从0开始）
     *  'gold' => int 今天已经充了多少钱
     * ]
     */
    function getInfo();

    /**
     * 领奖
     * @param $day int 领第X天的奖励 从0开始
     * @return bool true 领奖成功
     */
    function rec($day);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */