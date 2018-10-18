<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MysMerchant.def.php 119255 2014-07-08 10:44:29Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/MysMerchant.def.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-07-08 10:44:29 +0000 (Tue, 08 Jul 2014) $$
 * @version $$Revision: 119255 $$
 * @brief 
 *  
 **/

class MysMerchantDef
{
    const TBL_FIELD_VA_ALL = 'all';//商品购买信息
    const TBL_FIELD_VA_GOODSLIST = 'goodslist';//商品列表
    const TBL_FIELD_VA_REFRNUM_BYPLAYER = 'refresh_num';//当日玩家主动刷新次数
    const TBL_FIELD_VA_REFRTIME_BYPLAYER = 'refresh_time';//玩家最后一次主动刷新时间
    const TBL_FIELD_VA_MERCHANT_END_TIME = 'merchant_end_time';//神秘商人触发状态截止时间(消失时间)
    const TBL_FIELD_VA_SYS_REFRTIME = 'sys_refresh_time';   //最后一次系统刷新时间

    const MYSMERCHANT_GOODS_TYPE_ITEM = 1;//神秘商店商人类型之物品
    const MYSMERCHANT_GOODS_TYPE_HERO = 2;//神秘商店商人类型之卡牌
    const MYSMERCHANT_GOODS_TYPE_TREASFRAG = 3;//神秘商人商品类型之宝物碎片

    const MYSMERCHANT_SPEND_TYPE_JEWEL = 1;//神秘商人消费类型之魂玉
    const MYSMERCHANT_SPEND_TYPE_GOLD = 2;//神秘商人消费类型之金币
    const MYSMERCHANT_SPEND_TYPE_SILVER = 3;//神秘商人消费类型之银币

    const MYSMERCHANT_REFR_LIST_TYPE_GOLD = 1;//使用金币刷新神秘商人列表
    const MYSMERCHANT_REFR_LIST_TYPE_ITEM = 2;//使用物品刷新神秘商人列表

    const MYSMERCHANT_OPEN_FOREVER = -1;    //永久开启神秘商人状态

    const MYSMERCHANT_OPEN_LEVEL = 36;//神秘商人开启等级

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */