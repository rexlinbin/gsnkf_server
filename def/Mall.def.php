<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mall.def.php 254893 2016-08-05 10:31:56Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Mall.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-05 10:31:56 +0000 (Fri, 05 Aug 2016) $
 * @version $Revision: 254893 $
 * @brief 
 *  
 **/
/**
 * 新加的商店需要做的事情如下：
 * 1.定义商店类型:MALL_TYPE_XXXX;
 * 2.把商店类型加入$MALL_VALID_TYPES数组中;
 * 3.根据商品的刷新类型,加入不同的商品刷新类型数组中,例如:$EVERYDAY_REFRESH_TYPES;
 * 商店需要继承mall父类，调用父类的构造函数并且传递相应的商店类型参数，花费金币类型和获得金币类型(没有可以不传)，参照shop类;
 * 商品的解析脚本需要参照shop类里面的readGoods.script.php脚本。
 * 坑：策划可能会删除商品，getExchangeConf获得商品的配置时，不要抛异常，打warning日志，返回空数组即可。
 * @author tianming
 *
 */ 
class MallDef
{
	//商城类型
	const MALL_TYPE_SALE				=		1;				//活动商店
	const MALL_TYPE_BARTER	 			=		2;				//活动兑换商店
	const MALL_TYPE_SHOP 				=		3;				//普通商店
	const MALL_TYPE_SHOPEXCHANGE 		= 		4;				//普通兑换商店	
	const MALL_TYPE_GUILD				= 		5;				//军团商店
	const MALL_TYPE_ARENA				=		6;				//竞技场商店
	const MALL_TYPE_MYSTERY				=		7;				//神秘商店
    const MALL_TYPE_MYSMERCHANT         =       8;              //神秘商人
    const MALL_TYPE_ACTEXCHANGE         =       9;              //兑换活动
    const MALL_TYPE_COMPETE				= 		10;				//比武商店
    const MALL_TYPE_DRAGON				=		11;				//寻龙探宝商店
    const MALL_TYPE_WEEKENDSHOP         =       12;             //周末商店
    const MALL_TYPE_BARN				=		13;				//军团粮仓商店
    const MALL_TYPE_LIMITSHOP           =       14;             //限时商店
    const MALL_TYPE_PASS				=       15;				//神兵副本商店
    const MALL_TYPE_SCORESHOP           =       16;             //积分商店
    const MALL_TYPE_ZGSHOP				= 		17;				//战功商店
    const MALL_TYPE_TGSHOP				=		18;				//天工阁
    const MALL_TYPE_LORDWARSHOP			=		19;				//个人跨服战商店
    const MALL_TYPE_WORLDPASS_SHOP		=		20;				//跨服闯关大赛商店
    const MALL_TYPE_MISSION_SHOP		=		21;				//悬赏榜商店
    const MALL_TYPE_BLACKSHOP           =       22;             //黑市兑换商店
    const MALL_TYPE_WORLDCOMPETE_SHOP   =       23;             //跨服比武商店
    const MALL_TYPE_COUNTRYWAR_SHOP		= 		24;				//国战商店
    const MALL_TYPE_BINGFU_SHOP         =       25;             //兵符商店（水月之境新增）
    const MALL_TYPE_SEVENS_SHOP			=		26;				//七星台商店
    const MALL_TYPE_HELLTOWER           =       27;             //试炼梦魇商店
	
	//常量
	const REFRESH_OFFSET				=		0;
	const REFRESH_EVERYDAY				=		1;
	const REFRESH_NERVER				=		2;
	const REFRESH_EVERYWEEK				=		3;
	
	const ALL = 'all';
	const NUM = 'num';
	const TIME = 'time';
	
	//有效的商城类型
	public static $MALL_VALID_TYPES = array(
			self::MALL_TYPE_SALE,
			self::MALL_TYPE_BARTER,
			self::MALL_TYPE_SHOP,
			self::MALL_TYPE_SHOPEXCHANGE,
			self::MALL_TYPE_GUILD,
			self::MALL_TYPE_ARENA,
			self::MALL_TYPE_MYSTERY,
            self::MALL_TYPE_MYSMERCHANT,
            self::MALL_TYPE_ACTEXCHANGE,
			self::MALL_TYPE_COMPETE,
			self::MALL_TYPE_DRAGON,
            self::MALL_TYPE_WEEKENDSHOP,
			self::MALL_TYPE_BARN,
			self::MALL_TYPE_LIMITSHOP,
			self::MALL_TYPE_PASS,
			self::MALL_TYPE_SCORESHOP,
			self::MALL_TYPE_ZGSHOP,
			self::MALL_TYPE_TGSHOP,
			self::MALL_TYPE_LORDWARSHOP,
			self::MALL_TYPE_WORLDPASS_SHOP,
			self::MALL_TYPE_MISSION_SHOP,
			self::MALL_TYPE_BLACKSHOP,
			self::MALL_TYPE_WORLDCOMPETE_SHOP,
			self::MALL_TYPE_COUNTRYWAR_SHOP,
			self::MALL_TYPE_BINGFU_SHOP,
	        self::MALL_TYPE_HELLTOWER,
			self::MALL_TYPE_SEVENS_SHOP,
	);
	
	/*****************有效的商品刷新类型********************/
	//有效的每日刷新类型,每日固定时间刷新购买次数
	public static $EVERYDAY_REFRESH_TYPES = array(
			self::MALL_TYPE_SHOP,
			self::MALL_TYPE_GUILD,
			self::MALL_TYPE_ARENA,
			self::MALL_TYPE_MYSTERY,
			self::MALL_TYPE_MYSMERCHANT,
			self::MALL_TYPE_ACTEXCHANGE,
			self::MALL_TYPE_COMPETE,
			self::MALL_TYPE_DRAGON,
            self::MALL_TYPE_WEEKENDSHOP,
			self::MALL_TYPE_BARN,
			self::MALL_TYPE_PASS,
			self::MALL_TYPE_ZGSHOP,
			self::MALL_TYPE_TGSHOP,
			self::MALL_TYPE_WORLDPASS_SHOP,
			self::MALL_TYPE_MISSION_SHOP,
			self::MALL_TYPE_BLACKSHOP,
			self::MALL_TYPE_WORLDCOMPETE_SHOP,
			self::MALL_TYPE_COUNTRYWAR_SHOP,
			self::MALL_TYPE_BINGFU_SHOP,
	        self::MALL_TYPE_HELLTOWER,
			self::MALL_TYPE_SEVENS_SHOP,
	);
	
	//有效的每周刷新类型,每周固定时间刷新购买次数
	public static $EVERYWEEK_REFRESH_TYPES = array(
			self::MALL_TYPE_ARENA,
			self::MALL_TYPE_COMPETE,
			self::MALL_TYPE_BARN,
			self::MALL_TYPE_ZGSHOP,
			self::MALL_TYPE_TGSHOP,
			self::MALL_TYPE_WORLDPASS_SHOP,
			self::MALL_TYPE_GUILD,
			self::MALL_TYPE_WORLDCOMPETE_SHOP,
			self::MALL_TYPE_COUNTRYWAR_SHOP,
			self::MALL_TYPE_BINGFU_SHOP,
	        self::MALL_TYPE_HELLTOWER,
			self::MALL_TYPE_SEVENS_SHOP,
	);
	
	//有效的活动刷新类型,活动期结束后刷新购买次数
	public static $ACTIVITY_REFRESH_TYPES = array(
			self::MALL_TYPE_SALE,
			self::MALL_TYPE_LIMITSHOP,
			self::MALL_TYPE_SCORESHOP,
			self::MALL_TYPE_LORDWARSHOP,
			self::MALL_TYPE_BLACKSHOP,
			//self::MALL_TYPE_MISSION_SHOP,
			
	);
	/*****************有效的商品刷新类型********************/
	
	const MALL_EXCHANGE_REQ				=		'req';					//所需
	const MALL_EXCHANGE_ACQ				=		'acq';					//所得
	const MALL_EXCHANGE_EXTRA			=		'extra';				//需要的特殊物品
	const MALL_EXCHANGE_SERVICE 		= 		'need_open_time';		//开服时间
	const MALL_EXCHANGE_MODE			=		'mode';					//销售模式
	const MALL_EXCHANGE_START			=		'start_time';			//限时开启时间
	const MALL_EXCHANGE_END				=		'end_time';				//限时结束时间
	const MALL_EXCHANGE_LEVEL			=		'level';				//需要用户等级
	const MALL_EXCHANGE_LEVEL_NUM       =       'level_num';            //物品根据玩家对应等级的兑换次数
	const MALL_EXCHANGE_VIP				=		'vip';					//需要用户vip
	const MALL_EXCHANGE_NUM				=		'num';					//限购数量
	const MALL_EXCHANGE_TYPE			= 		'type';					//限购类型
	const MALL_EXCHANGE_OFFSET			=		'offset';				//时间偏移
	const MALL_EXCHANGE_DISCOUNT		=		'discount';				//vip折扣
	const MALL_EXCHANGE_GOLD 			= 		'gold';					//花费金币
	const MALL_EXCHANGE_INCRE			=		'incre';				//递增花费
	const MALL_EXCHANGE_SILVER			=		'silver';				//银币
	const MALL_EXCHANGE_SOUL			=		'soul';					//将魂
	const MALL_EXCHANGE_JEWEL			=		'jewel';				//魂玉
	const MALL_EXCHANGE_PRESTIGE		=		'prestige';				//声望
	const MALL_EXCHANGE_TREASFRAG		=		'treasfrag';			//宝物碎片
	const MALL_EXCHANGE_HERO			=		'hero';					//武将
	const MALL_EXCHANGE_ITEM			=		'item';					//物品
	const MALL_EXCHANGE_DROP			=		'drop';					//掉落表id
	const MALL_EXCHANGE_JH				=		'jh';					//武将精华
	const MALL_EXCHANGE_ISSOLD   ='is_sold';     //是否出售
	
	//sql
	const MALL_TABLE 					=		't_mall';
	const USER_ID						=		'uid';
	const MALL_TYPE						=		'mall_type';
	const VA_MALL						=		'va_mall';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */