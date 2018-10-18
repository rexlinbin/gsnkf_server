<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Reward.def.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Reward.def.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief
 *
 **/

class RewardSource
{
	const SYSTEM_COMPENSATION	=	8;				//系统补偿

	const FIRST_TOPUP			=	1;				//首充奖励

	const ARENA_RANK			=	3;				//竞技场排名奖励

	const ARENA_LUCKY			= 	2;				//竞技场幸运奖励

	const MINERAL               =   4;              //资源矿

	const BOSS_RANK				=	5;				//boss奖励

	const BOSS_KILL				=	6;				//boss击杀奖励

	const DIVI_REMAIN			=	7;				//占星没有领取的

	const COMPETE_RANK			=	9;				//比武排名奖励

	const TOP_UP_FEED_BACK		=	10;				//充值回馈

	const TEN_RECRUIT			=	11;				//酒馆十连抽

	const SYSTEM_GENERAL		=	12;				//系统发给个人的奖励／补偿； 上面的8是全服补偿

	const HERO_SHOP_INTEGRAL	=	13;				//卡包活动积分奖励

	const HERO_SHOP_RANK		=	14;				//卡包活动排名奖励

	const TOWER_SWEEP			=	15;				//爬塔扫荡奖励

	const VIP_DAILY_BONUS		=	16;				//vip每日奖励

	const DAILY_TASK			=	17;				//每日任务奖励
	
	const GROUPON				=	18;				//团购奖励
	
	const GUILD_TASK			=	19;				//公会任务奖励
	
	const MONTHLY_CARD			=	20;				//月卡每日奖励
	
	const CHARGE_RAFFLE         =   21;             //充值抽奖每日首冲奖励

    const TOPUP_REWARD          =   22;             //充值大放送奖励
    
    const OLYMPIC_NORMAL_RANK 	= 23;
    
    const OLYMPIC_SECOND 		= 24;
    
    const OLYMPIC_FIRST			= 25;
    
    const OLYMPIC_CHEER  		=   26;             //擂台赛助威奖励
    
    const OLYMPIC_LUCKY  		=   27;             //擂台赛幸运奖励
    
    const OLYMPIC_SUPERLUCKY  	=   28;             //擂台赛幸运奖励   

    const OLYMPIC_REWARDPOOL    =   29;             //擂台赛奖池奖励
    
    const LORDWAR_PROM_INNER_WIN_NORMAL		=	30;		//个人跨服战服内、胜者组32-4名奖励
    const LORDWAR_PROM_INNER_LOSE_NORMAL	=	31;
    const LORDWAR_PROM_INNER_WIN_SECOND		=	32;		//个人跨服战服内、胜者组亚军奖励
    const LORDWAR_PROM_INNER_LOSE_SECOND	=	33;
    const LORDWAR_PROM_INNER_WIN_FIRST		=	34;
    const LORDWAR_PROM_INNER_LOSE_FIRST		=	35;
    const LORDWAR_PROM_CROSS_WIN_NORMAL		=	36;
    const LORDWAR_PROM_CROSS_LOSE_NORMAL	=	37;
    const LORDWAR_PROM_CROSS_WIN_SECOND		=	38;
    const LORDWAR_PROM_CROSS_LOSE_SECOND	=	39;
    const LORDWAR_PROM_CROSS_WIN_FIRST		=	40;
    const LORDWAR_PROM_CROSS_LOSE_FIRST		=	41;
    
    const LORDWAR_SUPPORT_INNER				=	42;
    const LORDWAR_SUPPORT_CROSS				=	43;
    
	const REGRESS_ELITE						=	45;	//老用户回归中的精英回归
	const REGRESS_INSISTENT					=	46;

	const MERGE_SERVER_COMPENSATION         =   47; // 合服补偿
	const GUILD_SHARE_GRAIN					=	48; // 军团分粮
	
	const PASS_RANK_REWARD					=	49; //过关斩将排名奖励
	
	const GUILDWAR_RANK_NORMAL				= 	51; // 跨服军团战排名奖16-4
	const GUILDWAR_RANK_SECOND				= 	52;	// 跨服军团战亚军
	const GUILDWAR_RANK_FIRST				=  	53; // 跨服军团战冠军
	const GUILDWAR_SUPPORT					= 	54; // 跨服军团战助威奖
	
	const GUILDCOPY_RANK_REWARD				=   55; // 军团副本排名奖
	const GUILDCOPY_COMPENSATIONE_REWARD	=	56; // 军团副本补发的通关奖和宝箱奖
	
	const ROULETTE_RANK_REWARD              =   57; //积分轮盘排名奖未领取的发到奖励中心
	
	const WORLD_PASS_RANK_REWARD			=	58; //跨服闯关大赛排名奖励
	
	const WORLD_ARENA_POS_RANK_REWARD		=	59;	//跨服竞技场位置排名奖励
	const WORLD_ARENA_KILL_RANK_REWARD		=	60;	//跨服竞技场击杀排名奖励
	const WORLD_ARENA_CONTI_RANK_REWARD		=	61;	//跨服竞技场最大连杀排名奖励

    const WORLD_GROUPON_PURCHASE_GOLD       =   62; //跨服团购补发金币差价
    const TRAVEL_SHOP_PAY_BACK_GOLD 		=	63; //云游商人补发返利金币
    
    const MISSION_RANK_REWARD				=	64; //悬赏榜排行奖励
    
    const WORLD_COMPETE_RANK_REWARD 		=   65; //跨服比武排行奖励
    
    const COUNTRY_WAR_RANK_REWARD_AUDITION 	=   66; //国战初赛排行奖励
    const COUNTRY_WAR_RANK_REWARD_FINALTION =   67; //国战决赛排行奖励
    const COUNTRY_WAR_REWARD_WIN_SIDE 		=   68; //国战勢力奖励
    const COUNTRY_WAR_SUPPORT_REWARD_USER 	=  69; //国战助威奖励
    const COUNTRY_WAR_SUPPORT_REWARD_COUNTRY =  70; //国战助威奖励
    
    const RED_ENVELOPE_RECYCLE_REWARD       =   71; //红包系统金币回收
    
    const ACT_PAY_BACK_REWARD               =   72; //补偿活动
    
    const ACT_ONE_RECHARGE_REWARD			= 	73; //活动:单测回馈
    
    const CHARGEDART_REWARD_USER            =   74; //木牛流马 主人奖励
    const CHARGEDART_REWARD_ASSIST          =   75; //木牛流马 协助者奖励
    const CHARGEDART_REWARD_ROB             =   76; //木牛流马 抢夺
    
    const MONTHLY_CARD2						=	77;	//大月卡每日奖励
    
    const MINERALELVES                       =78;  //矿精灵奖励
    
    const WORLD_ARENA_KING_RANK_REWARD		=	79;	//跨服竞技场三榜排名奖励：如三榜都是第一的奖励
    
    const SYSTEM_GENERAL_FOR_BACKEND	=	80;				//系统发给个人的奖励／补偿； 同12一模一样，主要是用于后端，只不过不想和平台发的类型一致
    
    const HELL_TOWER_SWEEP_REWARD = 81; // 试炼噩梦扫荡奖励
    
    const WELCOMEBACK_BUFA_REWARD = 82; // 老玩家回归，任务完成奖励的补发
    
    const SILVER_TRANS_2_ITEM = 83; // 超上限的银币转化为物品
}


//策划表中配置的奖励类型，（礼包码也使用这个类型）
//礼包码支持的类型：  4=>array(1=>'银币',2=>'将魂',3=>'金币',4=>'体力',5=>'耐力',6=>'物品',8=>'等级*银币',9=>'等级*将魂',10=>'单个武将模板','11'=>'魂玉',12=>'声望',13=>'多个英雄',14=>'碎片'),//,7=>'多个物品'
class RewardConfType
{
	//这个主要是为了rewardUtil直接发奖的时候用的
	const SILVER			=	1;		//银币
	const SOUL				=	2;		//将魂
	const GOLD				=	3;		//金币
	const EXECUTION			=	4;		//体力
	const STAMINA			=	5;		//耐力
	const ITEM				=	6;		//物品（单个物品模块ID）
	const ITEM_MULTI		=	7;		//多个物品（ 物品模块ID，对应个数；   物品模块ID，对应个数；。。。 ）
	const SILVER_MUL_LEVEL	=	8;		//银币×等级
	const SOUL_MUL_LEVEL	=	9;		//将魂×等级
	const HERO				=	10;		//单个武将模板
	const JEWEL				= 	11;		//魂玉
	const PRESTIGE			=	12;		//声望
	const HERO_MULTI		=	13;		//多个英雄
	const TREASURE_FRAG_MULTI	=	14;		//碎片
	const GUILD_CONTRI			=	15;		//军团贡献
	const GUILD_EXP				=	16;		//军团建设度
	const HORNOR				=	17;		//荣誉
	const GRAIN					=	18;		//军粮
	const COIN					=	19;		//神兵令
	const ZG					= 	20;		//战功
	const TG					=	21;		//天工令
	const WM					=	22;		//威名
	const HELL_POINT			=	23;		//炼狱令
	const FAME					=	24;		//名望。。。
	const CROSS_HONOR			=	25;		//跨服荣誉
	const JH					=	26;		//武将精华
	const COPOINT				=	27;		//国战可消耗的积分
	const TALLY_POINT           =   28;     //兵符积分
	const BOOK					=	29;		//赤卷天书
	const HELL_TOWER            =   30;     //试炼币
	const SEVEN_POINTS			=	31;     //七星台中的货币，放在这，暂时先不支持，有需要了再说！！！
	const EXP					=   32;     //主角经验
	const EXP_MUL_LEVEL			=   33;     //主角经验×等级

	
	public static $drop2reward = array(
			0 => self::ITEM_MULTI,
			1 => self::HERO_MULTI,
			2 => self::SILVER,
			4 => self::SOUL,
			5 => self::TREASURE_FRAG_MULTI,
	);

	public static $rewardUtil2Center = array(
			self::SILVER => RewardType::SILVER,
			self::SOUL => RewardType::SOUL,
			self::GOLD => RewardType::GOLD,
			self::EXECUTION => RewardType::EXE,
			self::STAMINA => RewardType::STAMINA,

			self::JEWEL => RewardType::JEWEL,
			self::PRESTIGE => RewardType::PRESTIGE,

			self::SILVER_MUL_LEVEL => RewardType::SILVER,
			self::SOUL_MUL_LEVEL => RewardType::SOUL,

			self::HERO_MULTI => RewardType::ARR_HERO_TPL,
			self::ITEM_MULTI => RewardType::ARR_ITEM_TPL,
			self::TREASURE_FRAG_MULTI => RewardType::ARR_TF_TPL,

			self::GUILD_CONTRI =>RewardType::GUILD_CONTRI,
			self::GUILD_EXP => RewardType::GUILD_EXP,
			self::HORNOR => RewardType::HORNOR,
			self::GRAIN => RewardType::GRAIN,
			self::COIN => RewardType::COIN,
			self::ZG => RewardType::ZG,
			self::TG => RewardType::TG,
			self::WM => RewardType::WM,
			self::HELL_POINT => RewardType::HELL_POINT,
			self::CROSS_HONOR => RewardType::CROSS_HONOR,
			self::JH => RewardType::JH,
			self::COPOINT => RewardType::COPOINT,
			self::TALLY_POINT => RewardType::TALLY_POINT,
			self::BOOK => RewardType::BOOK_NUM,
	        self::HELL_TOWER => RewardType::HELL_TOWER,
			self::EXP => RewardType::EXP_NUM,
			self::EXP_MUL_LEVEL => RewardType::EXP_NUM,
	);
}

//奖励中心支持的奖励类型
class RewardType
{
	const GOLD 					=	'gold';

	const SILVER				=	'silver';

	const SOUL					=	'soul';

	const ARR_HERO_TPL			=	'arrHeroTpl';

	const ARR_ITEM_ID			=	'arrItemId';	//物品id， 已存在于系统中的物品

	const ARR_ITEM_TPL			=	'arrItemTpl';	//物品模板和对应的个数

	const PRESTIGE				=	'prestige';		//声望

	const JEWEL					=	'jewel';		//魂玉

	const EXE					=	'execution';	//行动力

	const STAMINA				=	'stamina';		//耐力

	const ARR_TF_TPL			=   'arrTreasureFragTpl';//宝物碎片
	
	const GUILD_CONTRI			=	'contri';//军团贡献
	
	const GUILD_EXP				=	'guildExp';//军团建设度
	
	const HORNOR				=	'honor';//荣誉
	
	const GRAIN					=	'grain';//军粮
	
	const COIN					=	'coin'; //神兵令
	
	const ZG					= 	'zg';	//战功--
	
	const TG					= 	'tg_num';	//天工令
	
	const WM					=	'wm_num';	//威名
	
	const HELL_POINT			=	'hellPoint';	//炼狱令
	
	const FAME_NUM				=	'fame_num';
	
	const CROSS_HONOR			=	'cross_honor';

	const FS_EXP				=	'fs_exp';	//战魂经验
	
	const JH					=	'jh';		//武将精华
	
	const COPOINT				=	'copoint';	//国战可消耗积分
	
	const TALLY_POINT			=	'tally_point';	//兵符积分
	
	const BOOK_NUM			=	'book_num';	//天书
	
	const HELL_TOWER            =    'tower_num'; //试炼币
	
	const EXP_NUM			=	'exp_num';	//经验
}

class RewardDef
{

	const SQL_TABLE				=	't_reward';
	const SQL_RID				=	'rid';
	const SQL_UID				=	'uid';
	const SQL_SOURCE			=	'source';
	const SQL_SEND_TIME			=	'send_time';
	const SQL_RECV_TIME			=	'recv_time';
	const SQL_DELETE_TIME		=	'delete_time';
	const SQL_VA_REWARD			=	'va_reward';

	const EXPIR_TIME			=	'expire_time';

	const REWARD_STEP_SET_PAYBACK = 1;
	const REWARD_STEP_SET_REWARD = 2;
	const REWARD_STEP_USER = 3;
	const REWARD_STEP_ITEM = 4;
	const REWARD_STEP_BAG = 5;
	const REWARD_STEP_TREASFRAG = 6;


	public static $GOLD_STATISTICS_TYPE = array(
			RewardSource::SYSTEM_COMPENSATION => StatisticsDef::ST_FUNCKEY_SYSTEM_COMPENSATION,
			RewardSource::ARENA_LUCKY => StatisticsDef::ST_FUNCKEY_ARENA_LUCKY,
			RewardSource::DIVI_REMAIN => StatisticsDef::ST_FUNCKEY_DIVI_REWARD,
			RewardSource::HERO_SHOP_INTEGRAL => StatisticsDef::ST_FUNCKEY_HERO_SHOP_SCORE,
			RewardSource::HERO_SHOP_RANK => StatisticsDef::ST_FUNCKEY_HERO_SHOP_RANK,
			RewardSource::ARENA_RANK => StatisticsDef::ST_FUNCKEY_ARENA_RANK,
			RewardSource::COMPETE_RANK => StatisticsDef::ST_FUNCKEY_COMPETE_RANK,
			RewardSource::TEN_RECRUIT => StatisticsDef::ST_FUNCKEY_SHOP_TEN_RECRUIT,
			RewardSource::SYSTEM_GENERAL => StatisticsDef::ST_FUNCKEY_REWARD_SYSTEM_GENERAL,
			RewardSource::SYSTEM_GENERAL_FOR_BACKEND => StatisticsDef::ST_FUNCKEY_REWARD_SYSTEM_GENERAL_FOR_BACKEND,
			RewardSource::BOSS_RANK => StatisticsDef::ST_FUNCKEY_BOSS_RANK,
			RewardSource::BOSS_KILL => StatisticsDef::ST_FUNCKEY_BOSS_KILL,
			RewardSource::VIP_DAILY_BONUS => StatisticsDef::ST_FUNCKEY_VIP_DAILY_BONUS,
			RewardSource::DAILY_TASK => StatisticsDef::ST_FUNCKEY_DAILY_TASK,
			RewardSource::GROUPON => StatisticsDef::ST_FUNCKEY_GROUPON_REWARD,
			RewardSource::GUILD_TASK => StatisticsDef::ST_FUNCKEY_GUILD_TASK,
			RewardSource::MONTHLY_CARD => StatisticsDef::ST_FUNCKEY_MONTHLY_CARD,
			Rewardsource::MONTHLY_CARD2 => StatisticsDef::ST_FUNCKEY_MONTHLY_CARD2,
			
			RewardSource::OLYMPIC_NORMAL_RANK => StatisticsDef::ST_FUNCKEY_OLYMPIC_NORMAL_RANK,
			RewardSource::OLYMPIC_SECOND => StatisticsDef::ST_FUNCKEY_OLYMPIC_SECOND,
			RewardSource::OLYMPIC_FIRST => StatisticsDef::ST_FUNCKEY_OLYMPIC_FIRST,
			RewardSource::OLYMPIC_CHEER => StatisticsDef::ST_FUNCKEY_OLYMPIC_CHEER,
			RewardSource::OLYMPIC_LUCKY => StatisticsDef::ST_FUNCKEY_OLYMPIC_LUCKY,
			RewardSource::OLYMPIC_SUPERLUCKY => StatisticsDef::ST_FUNCKEY_OLYMPIC_SUPERLUCKY,
            RewardSource::TOPUP_REWARD  => StatisticsDef::ST_FUNCKEY_TOPUP_REWARD,
			RewardSource::OLYMPIC_REWARDPOOL => StatisticsDef::ST_FUNCKEY_OLYMPIC_REWARDPOOL,
			
			RewardSource::LORDWAR_PROM_INNER_WIN_NORMAL => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_INNER_LOSE_NORMAL => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_INNER_WIN_SECOND => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_INNER_LOSE_SECOND => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_INNER_WIN_FIRST => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_INNER_LOSE_FIRST => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_WIN_NORMAL => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_LOSE_NORMAL => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_WIN_SECOND => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_LOSE_SECOND => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_WIN_FIRST => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			RewardSource::LORDWAR_PROM_CROSS_LOSE_FIRST => StatisticsDef::ST_FUNCKEY_LORDWAR_PROMOTION_REWARD,
			
			
			RewardSource::LORDWAR_SUPPORT_INNER => StatisticsDef::ST_FUNCKEY_LORDWAR_SUPPORT_REWARD,
			RewardSource::LORDWAR_SUPPORT_CROSS => StatisticsDef::ST_FUNCKEY_LORDWAR_SUPPORT_REWARD,
			
			RewardSource::REGRESS_ELITE => StatisticsDef::ST_FUNCKEY_REGRESS_ELITE,
			RewardSource::REGRESS_INSISTENT => StatisticsDef::ST_FUNCKEY_REGRESS_INSISTENT,

			RewardSource::MERGE_SERVER_COMPENSATION => StatisticsDef::ST_FUNCKEY_MERGE_SERVER_COMPENSATION_PRIZE,
			RewardSource::GUILD_SHARE_GRAIN => StatisticsDef::ST_FUNCKEY_GUILD_SHARE_GRAIN,
			
			//RewardSource::PASS_PERMERNENT_REWARD => StatisticsDef::ST_FUNCKEY_PASS_PERMERNENT_REWARD,
			//RewardSource::PASS_CHEST_REWARD => StatisticsDef::ST_FUNCKEY_PASS_GOLD_CHEST_REWARD,
			RewardSource::PASS_RANK_REWARD => StatisticsDef::ST_FUNCKEY_PASS_RANK_REWARD,
			
			RewardSource::GUILDWAR_RANK_NORMAL => StatisticsDef::ST_FUNCKEY_GUILDWAR_RANK_REWARD,
			RewardSource::GUILDWAR_RANK_SECOND => StatisticsDef::ST_FUNCKEY_GUILDWAR_RANK_REWARD,
			RewardSource::GUILDWAR_RANK_FIRST => StatisticsDef::ST_FUNCKEY_GUILDWAR_RANK_REWARD,
			RewardSource::GUILDWAR_SUPPORT => StatisticsDef::ST_FUNCKEY_GUILDWAR_SUPPORT_REWARD,
			
			RewardSource::GUILDCOPY_RANK_REWARD => StatisticsDef::ST_FUNCKEY_GUILD_COPY_RANK_REWARD,
			RewardSource::GUILDCOPY_COMPENSATIONE_REWARD => StatisticsDef::ST_FUNCKEY_GUILD_COPY_BOX_REWARD,//就用宝箱的吧，通关奖是战功，没有gold
			
			RewardSource::ROULETTE_RANK_REWARD => StatisticsDef::ST_FUNCKEY_ROULETTE_RANK_REWARD,
			
			RewardSource::WORLD_PASS_RANK_REWARD => StatisticsDef::ST_FUNCKEY_WORLD_PASS_RANK_REWARD,
			
			RewardSource::WORLD_ARENA_POS_RANK_REWARD => StatisticsDef::ST_FUNCKEY_WORLD_ARENA_POS_RANK_REWARD,
			RewardSource::WORLD_ARENA_KILL_RANK_REWARD => StatisticsDef::ST_FUNCKEY_WORLD_ARENA_KILL_RANK_REWARD,
			RewardSource::WORLD_ARENA_CONTI_RANK_REWARD => StatisticsDef::ST_FUNCKEY_WORLD_ARENA_CONTI_RANK_REWARD,

            RewardSource::WORLD_GROUPON_PURCHASE_GOLD => StatisticsDef::ST_FUNCKEY_WORLD_GROUPON_PURCHASE_GOLD,
			
			RewardSource::TRAVEL_SHOP_PAY_BACK_GOLD => StatisticsDef::ST_FUNCKEY_TRAVEL_SHOP_PAYBACK,
			RewardSource::MISSION_RANK_REWARD => StatisticsDef::ST_FUNCKEY_MISSION_RANK_REWARD,
			RewardSource::WORLD_COMPETE_RANK_REWARD => StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_RANK_REWARD,
			
			RewardSource::COUNTRY_WAR_SUPPORT_REWARD_USER => StatisticsDef::ST_FUNCKEY_COUNTRYWAR_SUPPOR_REWARD_USER,
			RewardSource::COUNTRY_WAR_SUPPORT_REWARD_COUNTRY => StatisticsDef::ST_FUNCKEY_COUNTRYWAR_SUPPOR_REWARD_COUNTRY,
			RewardSource::COUNTRY_WAR_RANK_REWARD_AUDITION => StatisticsDef::ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_AUDITION,
			RewardSource::COUNTRY_WAR_RANK_REWARD_FINALTION => StatisticsDef::ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_FINALTION,
			RewardSource::COUNTRY_WAR_REWARD_WIN_SIDE => StatisticsDef::ST_FUNCKEY_COUNTRYWAR_REWARD_WIN_SIDE,
	        RewardSource::RED_ENVELOPE_RECYCLE_REWARD => StatisticsDef::ST_FUNCKEY_ENVELOPE_RECYCLE_GET,
	        RewardSource::ACT_PAY_BACK_REWARD => StatisticsDef::ST_FUNCKEY_ACT_PAY_BACK_GET,
			RewardSource::ACT_ONE_RECHARGE_REWARD => StatisticsDef::ST_FUNCKEY_ONE_RECHARGE_GET,
	        RewardSource::CHARGEDART_REWARD_USER => StatisticsDef::ST_FUNCKEY_REWARDCENTER_GET_GOLD_USER,
	        RewardSource::CHARGEDART_REWARD_ASSIST => StatisticsDef::ST_FUNCKEY_REWARDCENTER_GET_GOLD_ASSIST,
	        RewardSource::CHARGEDART_REWARD_ROB => StatisticsDef::ST_FUNCKEY_REWARDCENTER_GET_GOLD_ROB,
			RewardSource::MINERALELVES=>StatisticsDef::ST_FUNCKEY_MINERAL_ELVES_GET,
			RewardSource::WORLD_ARENA_KING_RANK_REWARD=>StatisticsDef::ST_FUNCKEY_WORLD_ARENA_KING_RANK_REWARD,
	        RewardSource::HELL_TOWER_SWEEP_REWARD => StatisticsDef::ST_FUNCKEY_HELL_TOWER_SWEEP_REWARD,
			RewardSource::WELCOMEBACK_BUFA_REWARD => StatisticsDef::ST_FUNCKEY_WELCOMEBACK_BUFA_GET,
			RewardSource::SILVER_TRANS_2_ITEM => StatisticsDef::ST_FUNCKEY_SILVER_TRANS_GET,
			);

	const EXT_DATA		=	'extra';	//奖励信息字段

	const TITLE			=	'title';	//奖励的自定义标题，目前只有 RewardSource::SYSTEM 使用

	const MSG			=	'msg';		//奖励的自定义信息，目前只有 RewardSource::SYSTEM 使用

	const RECEIVE_NUM	=	20;			//批量领取奖励的上限

	const RID_DIVISION	=	100000;		//奖励id的最小值，用以区分补偿id和奖励id
	
	const RECEIVED_NUM  =   20;         //获取已领取奖励列表的条数

}

class FlopDef
{
	//翻牌类型，竞技场，比武，夺宝
	const FLOP_TYPE_ARENA = 0;
	const FLOP_TYPE_COMPETE = 1;
	const FLOP_TYPE_FRAGSEIZE = 2;

	const FLOP_TEMPLATE_ID = 'flop_template_id';		//翻牌模板id
	const FLOP_DROP_ARRAY = 'flop_drop_array';			//翻牌掉落表id数组
	const FLOP_ROB_MIN =  'flop_rob_min';				//翻牌掠夺最低等级
	const FLOP_ROB_SUC = 'flop_rob_suc';				//翻牌掠夺成功获得银币基础值
	const FLOP_ROB_FAIL = 'flop_rob_fail';				//翻牌掠夺失败损失银币基础值
	const FLOP_RAND_NUM = 'flop_rand_num';				//翻牌银币随机系数
	const FLOP_DROP_GOLD = 'flop_drop_gold';			//翻牌掉落金币数量
	const FLOP_DROP_SPECIAL = 'flop_drop_special';      //翻牌特殊掉落表
}

class VipBonusDef
{
	const SQL_TABLE = 't_vipbonus';		//表名称
	const SQL_UID = 'uid';				//玩家uid
	const SQL_BONUS_RECE_TIME = 'bonus_rece_time';	//上次领取vip福利的时间
	const SQL_VA_INFO = 'va_info';
	
	public static $TABLE_FIELDS = array(
			self::SQL_UID,
			self::SQL_BONUS_RECE_TIME,
			self::SQL_VA_INFO,
	);
	
	const BONUS = 'bonus';
	const WEEK_GIFT = 'week_gift';
}

class UpdateKeys
{
	const REWARDINFO  = 'rewardInfo';
	const USER = 'userModify';
	const BAG  = 'bagModify';
	const TFRAG  = 'tFragModify';
	const CONTRI  = 'contriModify';
	const GRAIN  = 'grainModify';
	const COIN  = 'coinModify';
	const ZG = 'zgModify';
	const GUILDEXP  = 'guildExpModify'; 
	const CROSSHONOR = 'crossHonorModify';
	const COPOINT = 'copointModify';
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
