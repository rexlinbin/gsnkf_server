<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IWeekendShop.class.php 136238 2014-10-15 05:57:22Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/weekendshop/IWeekendShop.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-15 05:57:22 +0000 (Wed, 15 Oct 2014) $$
 * @version $$Revision: 136238 $$
 * @brief 
 *  
 **/
interface IWeekendShop
{
    /**
     * 另 每天24点调用一次，会系统刷新
     * @return array
     * <code>
     *      good_list:array
     *      [
     *          goodId => canBuyNum:int    可购买数量
     *      ]
     *      weekendshop_num:int 活动已开总次数（循环显示用）
     *      has_buy_num:int 当天已经购买总次数
     *      rfr_num_by_player:int 当天玩家刷新次数（计算金币用）
     * </code>
     */
    public function getInfo();

    /**
     * @param type:int 1.使用金币刷新， 2.物品刷新
     * @param extra:int 刷新需要的物品模板id
     * @return array
     * <code>
     *      good_list:array
     *      [
     *          goodId => canBuyNum:int    可购买数量
     *      ]
     *      weekendshop_num:int 活动已开总次数（循环显示用）
     *      has_buy_num:int 已经购买总次数
     *      rfr_num_by_player:int 当天玩家刷新次数（计算金币用）
     * </code>
     */
    public function rfrGoodList($type, $extra=NULL);

    /**
     * @param $goodId
     * @return string
     * <code>
     *      'ok'
     * </code>
     */
    public function buyGood($goodId);

    /**
     * 方便在活动没开启的时候 提供前端用 从0开始
     * @return int 活动已开总次数（循环显示用）
     */
    public function getShopNum();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */