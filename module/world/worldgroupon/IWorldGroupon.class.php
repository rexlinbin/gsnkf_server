<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
interface IWorldGroupon
{
    /**
     * 拉取数据
     * @param int $type 如果type==0，返回全数据 如果type==1 只返回跨服商品相关数据
     * @return array
     * [
     *  stage:{team:分组阶段, buy:购买阶段, reward:发奖阶段, invalid:无效的阶段：如果活动没开的话}
     *  buy_start_time:int 购买开始时间
     *  buy_end_time:int 购买结束时间
     *  如果购买阶段：
     *  crossInfo:array
     *  [
     *      $goodId => [goodId物品id, goodNum购买总数,],...
     *  ]
     *  userInfo:array
     *  [
     *      point => int 积分,
     *      coupon => int 团购券,
     *      his => array[
     *          array[0=>goodId物品id, 1=>num团购数量, 2=>gold花费金币, 3=>话费的coupon券, 4=>buyTime时间]
     *      ]
     *      pointReward => array[$reward已领取的奖励id,...]
     *  ]
     * ]
     */
    function getInfo($type = 0);

    /**
     * 购买
     * @param $goodId int 物品id
     * @param $num int 物品数量
     * @return array
     * [
     *  0=>goodId物品id, 1=>num团购数量, 2=>gold花费金币, 3=>coupon花费团购券, 4=>buyTime时间
     * ]
     */
    function buy($goodId, $num);

    /**
     * 领奖
     * @param $rewardId int 奖励id 注意：这里的奖励id就是奖励对应的积分 比如：100|7|60002|10 等于100
     * @return string "ok"
     */
    function recReward($rewardId);

    /**
     * 商品数据造假
     * @param $teamId int 分组id
     * @param $goodId int 商品id
     * @param $forgeNum int 造假数量
     * @return string "ok"
     */
    function forgeGoodNum($teamId, $goodId, $forgeNum);

    /**
     * 平台获取跨服团购数据
     * @return array
     * [
     *      $teamId => array[
     *          $goodId => array[
     *              $goodNum: int 商品数量,
     *              $forgeNum: int 造假数量,
     *          ]
     *      ]
     * ]
     */
    function getTeamInfo4Plat();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */