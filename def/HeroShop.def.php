<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroShop.def.php 206806 2015-11-03 08:00:29Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/HeroShop.def.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-11-03 08:00:29 +0000 (Tue, 03 Nov 2015) $
 * @version $Revision: 206806 $
 * @brief 
 *  
 **/
class HeroShopDef
{
    const HEROSHOP_MEMCACHE_KEY_MINSCORE = 'heroshop.minscore';
    const HEROSHOP_MEMCACHE_KEY_BROADCAST_TIME = 'heroshop.broadcast.time';
    const HEROSHOP_MEMCACHE_KEY_RANKINFO = 'heroshop.rankinfo';
    
    const MC_FIELD_MIN_SCORE = 'min_score';
    const MC_FIELD_RFR_TIME = 'rfr_time';
    
    
    const RFR_RANK_INFO_TIMEGAP = 5;//更新数据库排名信息的时间间隔
    
    const RANK_SIZE = 20;//排名列表的大小
    
    const SQL_FIELD_UID = 'uid';
    const SQL_FIELD_SCORE = 'score';
    const SQL_FIELD_SCORE_TIME = 'score_time';
    const SQL_FIELD_FREE_CD = 'free_cd';
    const SQL_FIELD_FREE_NUM = 'free_num';
    const SQL_FIELD_GOLD_BUY_NUM = 'buy_num';
    const SQL_FIELD_SPECIAL_BUY_NUM = 'special_buy_num';
    const SQL_FIELD_REWARD_TIME = 'reward_time';
    
    const BUY_HERO_TYPE_FREE = 1;
    const BUY_HERO_TYPE_GOLD_FREE = 2;
    const BUY_HERO_TYPE_GOLD = 3;
    
    const INIT_FREE_BUY_NUM = 0;
    /**
     * 延迟发奖时间    比如活动12点结束  发奖时间就是12点之后延迟5秒
     */
    const HEROSHOP_REWARDTIMER_DELAY = 5;
    
    const HEROSHOP_OPEN_LEVEL = 25;
    
    /**
     * 合服系数即发奖的人数增加倍率
     * 比如当前服是3个服合成一个服    发奖时会有3个20名
     * 当前服是7个服合成一个服  合服系数是5
     */
    const MAX_MERGESERVER_RATIO = 5;
    
    /**
     * 发奖重试次数
     */
    const REWARD_TRY_NUM = 5;
}

class HeroShopBtstore
{
    const BT_FIELD_ID = 'id';
    const BT_FREE_GETSCORE = 'free_get_score';
    const BT_GOLD_GETSCORE = 'gold_get_score';
    const BT_GOLDBUY_NEEDGOLD = 'gold_buy_need_gold';
    const BT_FREE_BUY_CD = 'free_buy_cd';
    const BT_REWARDTBL_ID = 'reward_tbl_id';
    const BT_PRESCORE_GET_FREENUM = 'pre_score_get_freenum';
    const BT_FREE_BUY_SHOP_ID = 'free_shopid';
    const BT_GOLD_BUY_SHOP_ID = 'gold_shopid';
    const BT_ACT_CLOSE_DELAY = 'act_close_delay';
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */