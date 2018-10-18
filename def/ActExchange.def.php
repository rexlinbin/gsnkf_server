<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: ActExchange.def.php 129987 2014-09-01 11:10:57Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/ActExchange.def.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-09-01 11:10:57 +0000 (Mon, 01 Sep 2014) $$
 * @version $$Revision: 129987 $$
 * @brief 
 *  
 **/
class ActExchangeDef
{
    const TBL_FIELD_VA_ALL = 'all'; //商品购买信息
    const TBL_FIELD_VA_GOODSLIST = 'goods_list'; //商品列表
    const TBL_FIELD_VA_REFRESH_NUM = 'refresh_num';   //用户主动刷新次数
    const TBL_FIELD_VA_REFRESH_TIME = 'refresh_time';  //玩家最后一次主动刷新时间
    const TBL_FIELD_VA_SYS_REFRESH_TIME = 'sys_refresh_time';  //最后一次系统刷新时间

    const ACTEXCHANGE_SPEND_TYPE_GOLD = '1'; //消费类型金币
    const ACTEXCHANGE_SPEND_TYPE_ITEM = '2'; //消费类型物品
    const ACTEXCHANGE_SPEND_TYPE_DROP = '3'; //消费类型掉落表

    const ACTEXCHANGE_ID = 'id'; //ID
    const ACTEXCHANGE_NAME = 'name';
    const ACTEXCHANGE_MATERIA_QUANTITY = 'quantity';    //兑换材料数量
    const ACTEXCHANGE_MATERIAL_1 = 'material1'; //兑换材料1
    const ACTEXCHANGE_MATERIAL_2 = 'material2'; //兑换材料2
    const ACTEXCHANGE_MATERIAL_3 = 'material3'; //兑换材料3
    const ACTEXCHANGE_MATERIAL_4 = 'material4'; //兑换材料4
    const ACTEXCHANGE_MATERIAL_5 = 'material5'; //兑换材料5
    const ACTEXCHANGE_TARGET_ITEMS = 'targetItems'; //目标材料
    const ACTEXCHANGE_CHANGE_NUM = 'changeTime';  //兑换次数
    const ACTEXCHANGE_REFRESH_TIME = 'refreshTime'; //每日刷新公式时间
    const ACTEXCHANGE_CONVERSION_FORMULA = 'conversion_formula';    //兑换公式
    const ACTEXCHANGE_REWARD_NORMAL = 'rewardNormal';   //普通副本掉落
    const ACTEXCHANGE_GOLD = 'gold';    //刷新金币相关
    const ACTEXCHANGE_LEVEL = 'level';
    const ACTEXCHANGE_GOLD_TOP = 'goldtop'; //金币上限

    const ACTEXCHANGE_ITEM_VIEW = 'itemView';   //物品预览
    const ACTEXCHANGE_VIEW_NAME = 'viewName';   //预览名称
    const ACTEXCHANGE_ISREFRESH = 'isRefresh';  //是否显示刷新按钮
    const ACTEXCHANGE_SDCJ = 'sdcj';    //商店抽将掉落表组
    const ACTEXCHANGE_SMSD = 'smsd';    //神秘商店额外商品
    const ACTEXCHANGE_SMSR = 'smsr';    //神秘商人额外商品

    const ACTEXCHANGE_LHZL = 'lhzl';    //猎魂系统里 用金币召唤龙珠

    const ACTEXCHANGE_SPEND_FIELD_WEIGHT = 'refresh_weight'; //刷新权重

    const ACTEXCHANGE_GOODS_DEFAULT_ID = '1';       //通用配置所属id号
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */ 