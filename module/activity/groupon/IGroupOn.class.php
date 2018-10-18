<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IGroupOn.class.php 151576 2015-01-10 09:59:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/IGroupOn.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-10 09:59:17 +0000 (Sat, 10 Jan 2015) $$
 * @version $$Revision: 151576 $$
 * @brief 
 *  
 **/
interface IGroupOn
{
    /**
     * 获取团购商店信息
     * <code>
     * [
     *      good_list:array
     *      [
     *          goodId => array
     *              [
     *                  state:int 是否已参团 0 否, 1 是
     *                  soldNum:int 总参团数量
     *                  rewards:array
     *                  [
     *                      rewardid:int 已领取过的rewardid
     *                  ]
     *              ]
     *      ]
     *      day:int 活动第几天
     * ]
     * </code>
     * @return mixed
     */
    function getShopInfo();

    /**
     * 购买商品
     * @param $goodid
     * @return string
     *  'ok'
     */
    function buyGood($goodid);

    /**
     * 领取奖励
     * @param $goodid
     * @param $rewardId
     * @return string
     * 'ok'
     */
    function recReward($goodid, $rewardId);

    /**
     * 离开团购
     * @return string 'ok'
     */
    function leaveGroupOn();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */