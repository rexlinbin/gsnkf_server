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
class WorldGrouponCsvDef
{
    const ID = "id";    //商品id
    const DAY = "day";  //出售天数
    const ITEM = "item";    //团购物品
    const PRICE = "price";  //物品原价
    const DISCOUNT = "discount";    //团购折扣
    const NUM = "num";  //购买次数
    const RETURN_RATE = "return_rate";  //返券比例
    const POINT_REWARD = "point_reward";    //积分奖励
    const TIME_CFG = "time_cfg";    //分组及发奖时间
    const COUPON_USE_RATE = "coupon_use_rate";  //可用团购卷比例
    const NAME = "name";    //物品名字，为了给平台使用，方便他们看到物品名字
    const NEED_DAY = "need_day";  //开服天数要求

    const ARR_GOOD = "arrgood";  //商品信息
    const EXTRA = "extra";  //单独拿出来的信息
}

class WorldGrouponDef
{
    const STAGE_INVALID = "invalid";    //无效的阶段
    const STAGE_TEAM = "team";  //分组阶段
    const STAGE_BUY = "buy";    //购买阶段
    const STAGE_REWARD = "reward";  //补发差价阶段
    const STAGE_TEAM_NEED_TIME = 3600;  //分组阶段所需时间

    //数组转下标
    const GOOD_ID_IN_VA_INFO_FOR_FRONT = "goodId";
    const NUM_IN_VA_INFO_FOR_FRONT = "num";
    const GOLD_IN_VA_INFO_FOR_FRONT = "gold";
    const COUPON_IN_VA_INFO_FOR_FRONT = "coupon";
    const BUY_TIME_IN_VA_INFO_FOR_FRONT = "buyTime";

    public static $ARR_FOR_FRONT = array(
        self::GOOD_ID_IN_VA_INFO_FOR_FRONT,
        self::NUM_IN_VA_INFO_FOR_FRONT,
        self::GOLD_IN_VA_INFO_FOR_FRONT,
        self::COUPON_IN_VA_INFO_FOR_FRONT,
        self::BUY_TIME_IN_VA_INFO_FOR_FRONT,
    );
}

class WorldGrouponField
{
    const INNER = 'inner';
    const CROSS = 'cross';
}

class WorldGrouponSqlDef
{
    /**
     * t_world_groupon_cross_team表
     */
    const WORLD_GROUPON_CROSS_TEAM = "t_world_groupon_cross_team";
    const TBL_FIELD_TEAM_ID					= 'team_id';
    const TBL_FIELD_SERVER_ID				= 'server_id';
    const TBL_FIELD_UPDATE_TIME				= 'update_time';

    public static $CROSS_TEAM_ALL_FIELDS = array
    (
        self::TBL_FIELD_SERVER_ID,
        self::TBL_FIELD_TEAM_ID,
        self::TBL_FIELD_UPDATE_TIME,
    );

    /**
     * t_world_groupon_cross_info表
     */
    const WORLD_GROUPON_CROSS_INFO = "t_world_groupon_cross_info";
    const TBL_FIELD_GOOD_ID = "good_id";
    const TBL_FIELD_GOOD_NUM = "good_num";
    const TBL_FIELD_FORGE_NUM = "forge_num";
    const TBL_FIELD_UPD_TIME = "upd_time";

    public static $CROSS_INFO_ALL_FIELD = array(
        self::TBL_FIELD_TEAM_ID,
        self::TBL_FIELD_GOOD_ID,
        self::TBL_FIELD_GOOD_NUM,
        self::TBL_FIELD_FORGE_NUM,
        self::TBL_FIELD_UPD_TIME,
    );

    public static $CROSS_INFO_4_PLAT = array(
        self::TBL_FIELD_TEAM_ID,
        self::TBL_FIELD_GOOD_ID,
        self::TBL_FIELD_GOOD_NUM,
        self::TBL_FIELD_FORGE_NUM,
    );

    /**
     * t_world_groupon_inner_user
     */
    const WORLD_GROUPON_INNER_USER = "t_world_groupon_inner_user";
    const TBL_FIELD_UID = "uid";
    const TBL_FIELD_POINT = "point";
    const TBL_FIELD_COUPON = "coupon";
    const TBL_FIELD_OPTIME = "optime";
    const TBL_FIELD_REWARD_TIME = "reward_time";    //补发金币差价的时间
    const TBL_FIELD_VA_INFO = "va_info";
    const HIS_IN_VA_INFO = "his";
    const POINT_REWARD_IN_VA_INFO = "point_reward";
    //数组下标
    const GOOD_ID_IN_VA_INFO = 0;
    const NUM_IN_VA_INFO = 1;
    const GOLD_IN_VA_INFO = 2;
    const COUPON_IN_VA_INFO = 3;
    const BUY_TIME_IN_VA_INFO = 4;

    public static $INNER_USER_ALL_FIELD = array(
        self::TBL_FIELD_UID,
        self::TBL_FIELD_POINT,
        self::TBL_FIELD_COUPON,
        self::TBL_FIELD_OPTIME,
        self::TBL_FIELD_REWARD_TIME,
        self::TBL_FIELD_VA_INFO,
    );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */