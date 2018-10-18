<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Activity.def.php 244763 2016-05-30 11:30:53Z LeiZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Activity.def.php $
 * @author $Author: LeiZhang $(wuqilin@babeltime.com)
 * @date $Date: 2016-05-30 11:30:53 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244763 $
 * @brief 
 *  
 **/

class ActivityName
{
	//const SALE 						= 'sale';			//限时促销			sale.csv

	//const FIRST_TOPUP 				= 'firstTopUp';		//活动：首充	

	//const GROWUP 					= 'growup';			//活动：成长基金  growth_fund.csv

	//const CARD_BIG_RUN				= 'cardBigRun';		//活动：卡牌大放送	card_sale.csv
	
	const SPEND						= 'spend';			//活动：消费累计	xiaofei_leiji.csv
	
	//const LEVELUP_FUND			= 'levelupFund';	//活动：不在活动系统
	
	//const DAILY_FUND				= 'dailyFund';		//活动：每日福利
	
	//const BARTER					= 'barter';			//活动：兑换商城	change.csv
	
	//const BARTER_FRONT				= 'barter_front';	//活动：兑换商城（给前端的） change_activity.csv
	
	const TOPUP_FUND				= 'topupFund';		//活动：充值回馈 recharge_back.csv
	
	const ARENA_REWARD				= 'arenaDoubleReward';	//竞技场双倍奖励 无csv
	
	const HERO_SHOP                 = 'heroShop';    	//活动：卡包活动   card_active.csv
	
	const HEROSHOP_REWARD           = 'heroShopReward'; //卡包活动的奖励 card_activereward.csv
	const ROB_TOMB                  = 'robTomb';        //陵墓探宝系统  ernie.csv

	const SIGN_ACTIVITY				= 'signActivity'; 	//活動簽到（策劃叫新年禮包，神馬玩意） accumulateactive.csv
	
	const WEAL						= 'weal'; 			//福利 wealActivity.csv
	
	const ACT_EXCHANGE				= 'actExchange';	//物品兑换活动

    const GROUPON                   = 'groupon';    //团购活动
    
    const CHARGERAFFLE              = 'chargeRaffle';  //充值抽奖活动（前端叫幸运转盘）   box_activity.csv

    const TOPUPREWARD               = 'topupReward';    //充值大放送 continue_pay.csv
    
    const MONTHLYCARDGIFT           = 'monthlyCardGift'; //月卡大礼包  没有配置文件
    
    const LORDWAR                   = WolrdActivityName::LORDWAR;
    
    const GUILDWAR					= WolrdActivityName::GUILDWAR;
    
    const WORLDARENA				= WolrdActivityName::WORLDARENA;
    
    const WORLDCARNIVAL				= WolrdActivityName::WORLDCARNIVAL;

    const REGRESS					= 'regress';

    const STEPCOUNTER               = 'stepCounter';    //计步活动    
    
    const ROULETTE                  = 'roulette';    //积分轮盘 ScoreWheel.csv
    
    const LIMITSHOP                 = 'limitShop';   //限时商店 LimitShop.csv
    
    const BOWL						= 'treasureBowl'; // 聚宝盆活动 treasurebowl.csv
    
    const FESTIVAL                  = 'festival';     // 节日活动（新福利活动）festival.csv
    
    const FRONTSHOW					= 'frontShow';
    
    const SCORESHOP                 = 'scoreShop';    // 积分商城   score_shop.csv
    
    const SUPPLY					= 'supply'; //吃烧鸡

    const WORLDGROUPON              = WolrdActivityName::WORLDGROUPON;
    
    const TRAVELSHOP 				= 'travelShop'; //云游商人
    
    const VALIDITY					= 'validity'; //专门用来检查活动的
    
    const MISSION					= 'mission';//悬赏榜活动
    
    const BLACKSHOP					= 'blackshop'; //黑市兑换活动   blackshop.csv
    
    const HAPPYSIGN					= 'happySign'; // 欢乐签到  happy_sign.csv
    
    const FSREBORN					= 'fsReborn'; //战魂重生
    
    const DESACT                    = 'desact';   //新类型福利活动（限时成就） des-act.csv
    
    const RECHARGEGIFT				= 'rechargeGift';	// 充值送礼 recharge_gift.csv
    
    const ENVELOPE                  = 'envelope'; //红包活动 giftmoney_activity.csv
    
    const ACTPAYBACK                = 'actpayback'; // 补偿活动  compensate.csv
    
    const ONERECHARGE				= 'oneRecharge'; // 单充回馈 dailypay.csv
    
    const MINERALELVES              ='mineralelves';//精灵矿区

    const FESTIVAL_ACT              = 'festivalAct';  //节日狂欢活动   festival_act.csv
    
    const FESTIVALACT_REWARD        = 'festivalActReward'; //节日狂欢活动奖励  festival_reward.csv
    
    //TODO添加活动的同学注意，如果是跨服的活动请确认下要不要加到一个activityConf的白名单里
    
}

class ActivityDef
{
	const SESSION_KEY_VERSION 				= 'activity.updateVersion';

	const MC_KEY_PRE 						= 'config';

	const MC_KEY_FRONT						= 'config.front';
	
	const FRONT_CALLBACK_UPDATE				= 're.activity.newConf';


	public static $ARR_CONF_FIELD = array(
			'name',
			'version',
			'start_time',
			'end_time',
			'need_open_time',
			'str_data',
			'va_data',
	);

	const BEGIN_TIME	= 'beginTime';
	const END_TIME		= 'endTime';
	const NEED_OPEN_TIME	= 'needOpenTime';
	
}

class WealDef
{
	//福利Id，如果福利涉及精英副本，这就是精英副本的id，又同时由于同一时间只有一条福利是有效的
	//所以涉及精英副本的福利在同一时间只能针对一个精英副本
	const ID					= 'id';
	///const BEGIN_TIME			= 'beginTime';
	///const END_TIME				= 'endTime';
	
	//该条配置是否开启
	const OPEN					= 'open';

	//活动副本次数的倍数
	const ACOPY_NUM				= 'acopyNum';
	//普通副本的加成
	const NCOPY_FUND 			= 'ncopyFund';
	//普通副本武魂掉落加成
	const NCOPY_DROP_HERO_FRAG	= 'ncopDropHeroFrag';
	//精英副本掉落加成
	const ECOPY_DROP			= 'ecopyDrop';
	//好友互赠耐力加成
	const FRIEND_LOVE			= 'friendLove';
	//军团贡献加倍
	const GUILD_CONTRI			= 'guildContri';
	//军团商店道具或珍品新的表
	const GUILD_GOODS_SALE		= 'guildGoodsSale';
	//名将送礼加成
	const STAR_GIFT				= 'star_gift';
	//翻牌黄金宝箱掉落概率提高
	const FLOP_CARD				= 'flopCard';
	//翻卡消耗积分
	const KA_CONSUME			= 'kaConsume';
	//活动期间积分上限(翻卡)
	const KA_INTERGRAL_LIMIT	= 'kaIntergralLimit';
	//翻卡是否开启
	const KA_OPEN				= 'kaOpen';
	//军团组队战次数翻倍
	const COPY_TEAM_GUILD		= 'copyTeamGuild';
	//资源矿产出翻倍
	const MINERAL_PRODUCE		= 'mineralProduce';
	//新服福利活动普通副本经验双倍等级限制
	const NS_NCOPY_EXP_NEED_LV  = 'nsNcopyExpLv';
	//翻牌活动累加积分的模块
	const KA_CONF_TYPE = 'kaConfType';
	//翻牌活动刷新类型
	const KA_RFR_TYPE = 'kaRfrType';
	
	

	public static $type = array(
			
			self::ID=>1,
			self::ACOPY_NUM => 2,
			self::NCOPY_FUND => 3,
			self::NCOPY_DROP_HERO_FRAG =>4,
			self::ECOPY_DROP => 5,
			self::FRIEND_LOVE => 6,
			self::GUILD_CONTRI => 7,
			self::GUILD_GOODS_SALE => 8,
			self::STAR_GIFT => 9,
			self::FLOP_CARD => 10,
			self::KA_CONSUME => 11,
			self::KA_INTERGRAL_LIMIT => 12,
			self::KA_OPEN => 13,
			self::COPY_TEAM_GUILD => 14,
			self::MINERAL_PRODUCE => 15,
			self::NS_NCOPY_EXP_NEED_LV => 16,
	        self::KA_CONF_TYPE => 17,
	        self::KA_RFR_TYPE => 18,
			
	);

	const WEAL_OPT = true;
	const WEAL_VALID_TIME		= 1800;
	
	//普通副本加成的类型
	const NCOPY_FUND_SILVER		= 1;
	const NCOPY_FUND_SOUL		= 2;
	const NCOPY_FUND_EXP		= 3;
	
	//新服期间普通副本经验翻倍
	const NCOPY_EXP_RATIO = 2;
	const NS_WEAL_ID = 11;
}

class KaDef
{
	// 	夺宝
	const FRAGSEIZE = 1;
	// 	比武
	const COMPETE = 2;
	// 	占星
	const DIVINE = 3;
	// 	攻击普通副本
	const NCOPY	= 4;
	// 	攻击精英副本
	const ECOPY = 5;
	// 	攻击活动副本
	const ACOPY = 6;
	// 	攻击军团副本
	const GCOPY = 7;
	// 	攻打世界boss
	const BOSS = 8;
	// 	竞技场
	const ARENA = 9;
	
	const KA_RFR_TYPE_DAY = 1; //积分按天刷新
	const KA_RFR_TYPE_ACT = 2; //积分活动内不刷新，跨届才刷
}

class NSActivityDef
{
	const BTS_NAME = 'btsName';
	const TIME_ARR = 'timeArr';
	const NEED_TOARRAY = 'needToArray';
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */