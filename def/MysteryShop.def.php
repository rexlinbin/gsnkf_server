<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MysteryShop.def.php 103251 2014-04-23 10:18:03Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/MysteryShop.def.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-04-23 10:18:03 +0000 (Wed, 23 Apr 2014) $
 * @version $Revision: 103251 $
 * @brief 
 *  
 **/
class MysteryShopDef
{
    const TBL_FIELD_VA_ALL = 'all';//商品购买信息
    const TBL_FIELD_VA_GOODSLIST = 'goodslist';//商品列表
    const TBL_FIELD_VA_SYS_REFRTIME = 'sys_refresh_time';//最后一次系统刷新时间
    const TBL_FIELD_VA_REFRNUM_BYPLAYER = 'refresh_num';//当日玩家主动刷新次数
    const TBL_FIELD_VA_REFRTIME_BYPLAYER = 'refresh_time';//玩家最后一次主动刷新时间
    const TBL_FIELD_VA_SYS_RFRNUM = 'sys_refresh_num';//累积的系统刷新次数
    
    const MYSTERY_GOODS_TYPE_ITEM = 1;//神秘商店商品类型之物品
    const MYSTERY_GOODS_TYPE_HERO = 2;//神秘商店商品类型之卡牌
    const MYSTERY_GOODS_TYPE_TREASFRAG = 3;//神秘商店商品类型之宝物碎片
    
    const MYSTERY_SPEND_TYPE_JEWEL = 1;//神秘商店消费类型之魂玉
    const MYSTERY_SPEND_TYPE_GOLD = 2;//神秘商店消费类型之金币
    const MYSTERY_SPEND_TYPE_SILVER = 3;//神秘商店消费类型之银币
    
    const MYSTERY_REFR_LIST_TYPE_GOLD = 1;//使用金币刷新神秘商店列表
    const MYSTERY_REFR_LIST_TYPE_ITEM = 2;//使用物品刷新神秘商店列表
    const MYSTERY_REFR_LIST_TYPE_FREE = 3;//免费系统刷新
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */