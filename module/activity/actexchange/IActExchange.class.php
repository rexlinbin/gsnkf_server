<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IActExchange.class.php 250079 2016-07-05 10:23:14Z YangJin $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actexchange/IActExchange.class.php $$
 * @author $$Author: YangJin $$(hoping@babeltime.com)
 * @date $$Date: 2016-07-05 10:23:14 +0000 (Tue, 05 Jul 2016) $$
 * @version $$Revision: 250079 $$
 * @brief
 *
 **/
interface IActExchange
{
    /**
     * 获取商店数据
     * @return array
     * <code>
     * [
     *      goods_list:array
     *      [
     *          id:array
     *          [
     *              req:array('item'=>array(), 'gold'=>) 材料数组 公式左边
     *              seq:array(123, 345, '0'...) req材料和金币顺序 0表示金币
     *              index:int 公式顺序
     *              acq:array() 兑换商品 公式右边
     *              soldNum:int 已兑换数量
     *              refresh_num:int 该商品玩家刷新次数
     *              free_refresh_num:int 免费刷新次数
     *          ]
     *      ]
     *      sys_refresh_cd:int 系统自动刷新时间
     * ]
     * </code>
     */
    function getShopInfo();

    /**
     * 购买物品
     * @param id
     * @param num 数量
     * @return array
     * <code>
     * [
     *      ret:string 'ok'
     *      drop:array
     * ]
     * </code>
     */
    function buyGoods($id, $num);

    /**
     * 刷新商品列表
     * @param $id 商品id
     * @return array
     * <code>
     * [
     *      goods_list:array
     *      [
     *          id:array
     *          [
     *              req:array() 材料数组 公式左边
     *              acq:array() 兑换商品 公式右边
     *              soldNum:int 已兑换数量
     *              refresh_num:int 玩家刷新次数
     *          ]
     *      ]
     *      sys_refresh_cd:int 系统自动刷新时间
     * ]
     * </code>
     */
    function rfrGoodsList($id);

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */