<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(hoping@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class GroupOnDef
{
    const ID = 'id';
    const PRICE = 'price';
    const VIP = 'vip';
    const ORIPEICE = 'oriprice'; //原价
    const ICON = 'icon';    //图标
    const NAME = 'name';    //名称
    const GOOD = 'good';    //商品
    const QUALITY = 'quality';  //品质
    const NUMTOP = 'numtop';    //奖励阶数
    const NUMOFREWARD1 = 'num1';
    const REWARD1 = 'reward1';
    const NUMOFREWARD2 = 'num2';
    const REWARD2 = 'reward2';
    const NUMOFREWARD3 = 'num3';
    const REWARD3 = 'reward3';
    const NUMOFREWARD4 = 'num4';
    const REWARD4 = 'reward4';
    const NUMOFREWARD5 = 'num5';
    const REWARD5 = 'reward5';
    const NUMOFREWARD6 = 'num6';
    const REWARD6 = 'reward6';
    const NUMOFREWARD7 = 'num7';
    const REWARD7 = 'reward7';
    const NUMOFREWARD8 = 'num8';
    const REWARD8 = 'reward8';
    const NUMOFREWARD9 = 'num9';
    const REWARD9 = 'reward9';
    const NUMOFREWARD10 = 'num10';
    const REWARD10 = 'reward10';
    const NUMOFREWARD11 = 'num11';
    const REWARD11 = 'reward11';
    const NUMOFREWARD12 = 'num12';
    const REWARD12 = 'reward12';
    const NUMOFREWARD13 = 'num13';
    const REWARD13 = 'reward13';
    const NUMOFREWARD14 = 'num14';
    const REWARD14 = 'reward14';
    const NUMOFREWARD15 = 'num15';
    const REWARD15 = 'reward15';
    const NUMOFREWARD16 = 'num16';
    const REWARD16 = 'reward16';
    const NUMOFREWARD17 = 'num17';
    const REWARD17 = 'reward17';
    const NUMOFREWARD18 = 'num18';
    const REWARD18 = 'reward18';
    const NUMOFREWARD19 = 'num19';
    const REWARD19 = 'reward19';
    const NUMOFREWARD20 = 'num20';
    const REWARD20 = 'reward20';
    const GROUPONIDS = 'grouponids';
    const REFRESHTIME = 'refreshtime';
    const REWARD = 'reward';    //整理存放所有的奖励
    const NUM = 'num';  //整理存放所有各阶的奖励人数

    const AID = 'id';  //活动id
    const VADATA = 'va_data';
    const TGROUPON = 't_groupon'; //表t_groupon

    const UID = 'uid';  //用户id
    const BUYTIME = 'buy_time'; //最后一次购买时间
    const USERVADATA = 'va_data';
    const TGROUPONUSER = 't_groupon_user';

    const TBL_FIELD_GOODSLIST = 'goods_list';
    const TBL_FIELD_REFRESHTIME = 'refreshtime';
    const TBL_FIELD_TIMERID = 'timerid';
    const TBL_FIELD_BUY_TIME = 'buytime';   //最后一次购买时间
    const TBL_FIELD_USER_GROUP_DATA = 'usergrdata';

    //解析相关
    const GROUPON_SPEND_TYPE_SILVER = '1';    //银币
    const GROUPON_SPEND_TYPE_SOUL = '2';    //将魂
    const GROUPON_SPEND_TYPE_GOLD = '3';    //金币
    const GROUPON_SPEND_TYPE_EXTRENGTH = '4';   //体力
    const GROUPON_SPEND_TYPE_ENDURANCE = '5';   //耐力
    const GROUPON_SPEND_TYPE_ITEM = '6';    //物品
    const GROUPON_SPEND_TYPE_ITEMS = '7';   //多个物品
    const GROUPON_SPEND_TYPE_LEVEL_SILVER = '8';    //等级*银币
    const GROUPON_SPEND_TYPE_LEVEL_SOUL = '9';  //等级*将魂
    const GROUPON_SPEND_TYPE_HERO   = '10'; //单个英雄
    const GROUPON_SPEND_TYPE_JEWEL = '11'; //魂玉
    const GROUPON_SPEND_TYPE_PRESTFRAG = '12';  //声望
    const GROUPON_SPEND_TYPE_HEROS = '13';  //多个英雄
    const GROUPON_SPEND_TYPE_TREASFRAG = '14';  //宝物碎片

    //timer def
    const GROUPON_REISSUE_REWARD_TASK_NAME = 'groupon.reissueForTime'; //补发奖励

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */