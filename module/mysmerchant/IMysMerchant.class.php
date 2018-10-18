<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IMysMerchant.class.php 119178 2014-07-08 07:19:10Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/IMysMerchant.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-07-08 07:19:10 +0000 (Tue, 08 Jul 2014) $$
 * @version $$Revision: 119178 $$
 * @brief 
 *  
 **/

interface IMysMerchant
{

    /**
     * 购买物品
     * @param int $goodsId
     * @return array
     * <code>
     * [
     *     ret:string            'ok'
     *     drop:array
     * ]
     * </code>
     */
    public function buyGoods($goodsId);

    /**
     * @return array
     * <code>
     * [
     *     goods_list:array
     *     [
     *         goodsId=>canBuyNum  可购数量
     *     ]
     *     refresh_cd:int            系统自动刷新时间CD
     *     refresh_num:int            玩家刷新次数
     *     merchant_end_time           神秘商人触发状态截止时间(消失时间)
     * ]
     * </code>
     */
    public function getShopInfo();

    /**
     *
     * @param int $type 1.金币刷新  2.物品刷新
     * @return array
     * <code>
     * [
     *     goods_list:array
     *     [
     *         goodsId=>canBuyNum  可购数量
     *     ]
     *     refresh_cd:int            系统自动刷新时间CD
     *     refresh_num:int            玩家刷新次数
     *     merchant_end_time           神秘商人触发状态截止时间(消失时间)
     * ]
     * </code>
     */
    public function playerRfrGoodsList($type);

    /**
     * @return bool true 召唤成功
     */
    public function buyMerchantForever();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */