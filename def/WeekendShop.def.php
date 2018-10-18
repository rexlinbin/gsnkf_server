<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: WeekendShop.def.php 137238 2014-10-23 02:53:13Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/WeekendShop.def.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-23 02:53:13 +0000 (Thu, 23 Oct 2014) $$
 * @version $$Revision: 137238 $$
 * @brief 
 *  
 **/
class WeekendShopCsvDef
{
    const ID = 'id';    //id
    const CIRCLE_ID = 'circleId';    //循环ID组
    const GOLD_BASE = 'goldBase';    //金币刷新消耗基础值
    const GOLD_GROW = 'goldGrow';    //金币刷新消耗递增值
    const GOLD_MAX = 'goldMax';     //金币刷新消耗最大值
    const LAST_TIME = 'lastTime';    //商店持续时间
    const SHOW_ITEMS = 'showitems';    //本周物品ID组
    const COST_ITEM = 'costItem';    //刷新消耗物品ID
    const TEAM_NUMS = 'teamNums';    //每组物品数量
    const TEAM1 = 'team1';    //刷新物品ID组1
    const TEAM2 = 'team2';    //刷新物品ID组2
    const TEAM3 = 'team3';    //刷新物品ID组3
    const TEAM4 = 'team4';    //刷新物品ID组4
    const TEAM5 = 'team5';    //刷新物品ID组5

    const TEAM6 = 'team6';    //刷新物品ID组6
    const TEAM7 = 'team7';    //刷新物品ID组7
    const TEAM8 = 'team8';    //刷新物品ID组8
    const TEAM9 = 'team9';    //刷新物品ID组9
    const TEAM10 = 'team10';    //刷新物品ID组10

    const GOODID = 'id';    //物品id
}

class WeekendGoodsCsvDef
{
    const ID = 'id';    //id
    const ITEMS = 'items';    //出售物品ID组
    const COST_TYPE = 'costType';    //花费类型
    const COST_NUM = 'costNum';    //花费数量
    const WEIGHT = 'weight';    //刷新权重
    const ISSOLD = 'isSold';    //是否出售
    const LEVEL_LIMIT = 'level_limit';    //主角等级要求
    const ISHOT = 'isHot';    //是否是热点商品

    const SOLD = 1;    //出售，可被随机到
    const NOSOLD = 0;    //不出售， 不可被随机到

    //acq
    const ACQ_TYPE_ITEM = 1;    //得到类型物品
    const ACQ_TYPE_HERO = 2;    //得到类型英雄卡牌
    //req
    const REQ_TYPE_JEWEL = 1;    //花费类型为魂玉
    const REQ_TYPE_GOLD = 2;    //花费类型为金币
    const REQ_TYPE_SILVER = 3;    //花费类型为银币（预留）
}

class WeekendShopDef
{
    const WEEKENDSHOP_ALL = 'all';
    const WEEKENDSHOP_GOODSLIST = 'goodslist';
    const WEEKENDSHOP_TIME = 'weekendshop_time';
    const WEEKENDSHOP_NUM = 'weekendshop_num';
    const HAS_BUY_NUM = 'has_buy_num';
    const RFR_NUM_BY_PLAYER = 'rfr_num_by_player';

    const RFR_TYPE_GOLD = 1;    //金币刷新
    const RFR_TYPE_ITEM = 2;    //物品刷新
    const RFR_TYPE_SYS = 3;    //系统免费刷新

    const DEFAULT_ID = 1;    //默认id

    public static $arrNumToWeekday = array(
        '1', '2', '3', '4', '5', '6', '7',
    );

    public static $arrRfrType = array(
        self::RFR_TYPE_GOLD,
        self::RFR_TYPE_ITEM,
    );

    const WEEKENDSHOP_STARTTIME = '20141020';
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */