<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Statistics.def.php 259654 2016-08-31 06:58:45Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Statistics.def.php $
 * @author $Author: YangJin $(jhd@babeltime.com)
 * @date $Date: 2016-08-31 06:58:45 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259654 $
 * @brief
 *
 **/

class StatisticsDef
{
	const ST_STATISTICS_SERVICE_NAME					=	'stat';

	//SQL
	//TABLE NAME
	const ST_TABLE_ONLINE_TIME							=	'pirate_onlinetime_log';
	const ST_TABLE_GOLD									=	'pirate_gold_log';

	//SQL
	const ST_SQL_PID									=	'pid';
	const ST_SQL_SERVER									=	'server_key';
	//online table
	const ST_SQL_LOGIN_TIME								=	'login_time';
	const ST_SQL_LOGOUT_TIME							=	'logout_time';
	const ST_SQL_LOGIN_IP								=	'ip';
	//gold
	const ST_SQL_FUNCTION								=	'function_key';
	const ST_SQL_GOLD_DIRECTION							=	'direction';
	const ST_SQL_GOLD_NUM								=	'num';
	const ST_SQL_GOLD_TIME								=	'created';
	const ST_SQL_ITEM_TEMPLATE_ID						=	'item_template_id';
	const ST_SQL_ITEM_NUM								=	'item_num';
	const ST_SQL_CUR_NUM								=	'cur_num';
	const ST_SQL_UUID									=	'uuid';
	

	/*****function key define*****/
	//系统级
	const ST_FUNCKEY_SYSTEM_COMPENSATION				=	100;
	//首充返还金币
	const ST_FUNCKEY_FIRST_PAY_REWARD                   =   101;
	//充值返还金币
	const ST_FUNCKEY_PAY_BACK                           =   102;  	
	//奖励中心中，系统给某个人的补偿／奖励
	const ST_FUNCKEY_REWARD_SYSTEM_GENERAL				=	103;
	//代充扣除金币
	const ST_FUNCKEY_BAD_ORDER							=	104;
	//奖励中心中，系统给某个人的补偿／奖励，只供后端用
	const ST_FUNCKEY_REWARD_SYSTEM_GENERAL_FOR_BACKEND	=	105;
	               
	
	
	
	
	//用户相关
	//金币购买体力
	const ST_FUNCKEY_BUY_EXECUTION   					= 	201;
	//开启武将背包
	const ST_FUNCKEY_OPEN_HEROLIMIT                     =   202;
	//主角升级金币奖励
	const ST_FUNCKEY_LVUP_REWARD                        =   203;
	//微信微博首次分享金币奖励
	const ST_FUNCKEY_FIRST_SHARE                        =   204;
	//武将传承觉醒能力 花费金币
	const ST_FUNCKEY_HERO_INHERIT_TALENT                =   205;
	//武将变身
	const ST_FUNCKEY_HERO_TRANSFER                		=   206;
	
	//bag
	//开启背包格子-----每个背包使用一个key
	const ST_FUNCKEY_BAG_OPENGRID						=	300;
	//使用物品花费
	const ST_FUNCKEY_BAG_USEITEM_COST					=	301;
	//使用物品获得金币
	const ST_FUNCKEY_BAG_USEITEM_GET					=	302;
	//开启装备背包格子
	const ST_FUNCKEY_BAG_OPENGRID_ARM					=	303;
	//开启道具背包格子
	const ST_FUNCKEY_BAG_OPENGRID_PROPS					=	304;
	//开启宝物背包格子
	const ST_FUNCKEY_BAG_OPENGRID_TREAS					=	305;
	//开启装备碎片背包格子
	const ST_FUNCKEY_BAG_OPENGRID_ARM_FRAG				=	306;
	//开启时装背包格子
	const ST_FUNCKEY_BAG_OPENGRID_DRESS					=	307;
    //开启神兵背包格子
    const ST_FUNCKEY_BAG_OPENGRID_GOD_WEAPON            =   308;
    //开启神兵碎片背包格子
    const ST_FUNCKEY_BAG_OPENGRID_GOD_WEAPON_FRAG       =   309;
    //开启符印背包格子
    const ST_FUNCKEY_BAG_OPENGRID_RUNE            	    =   310;
    //开启符印碎片背包格子
    const ST_FUNCKEY_BAG_OPENGRID_RUNE_FRAG       		=   311;
    //开启锦囊背包格子
    const ST_FUNCKEY_BAG_OPENGRID_POCKET       			=   312;
    //开启兵符背包格子
    const ST_FUNCKEY_BAG_OPENGRID_TALLY            	    =   313;
    //开启兵符碎片背包格子
    const ST_FUNCKEY_BAG_OPENGRID_TALLY_FRAG       		=   314;
    //开启战车背包格子
    const ST_FUNCKEY_BAG_OPENGRID_CHARIOT                  =315;
		
	// 普通副本(100-199)
	// 副本自动挂机（金币完成）（无效）
	const ST_FUNCKEY_COPY_REVIVIE_CARD					=   400;
	// 清除战斗CD（无效）
	const ST_FUNCKEY_COPY_CLEARCDTIME					=   401;
	//获得副本宝箱金币奖励
	const ST_FUNCKEY_COPY_GETPRIZE						=   402;
	//重置据点攻击次数花费
	const ST_FUNCKEY_COPY_RESET_ATKNUM                  =   403;
	//金币攻击金钱树（无效）
	const ST_FUNCKEY_COPY_ATK_GOLDTREE                  =   404;
	//金币消除连战CD
	const ST_FUNCKEY_COPY_CLEAR_SWEEPCD                 =   405;
	
	// beauty 
	// 赠送礼物--金币消耗（无效）
	const ST_FUNCKEY_STAR_FAVOR							=	501;
	// 增进感情--金币奖励（无效）
	const ST_FUNCKEY_STAR_REWARD						=	502;
	// 刷新列表--金币消耗（无效）
	const ST_FUNCKEY_STAR_REFRESH						=	503;
	// 名将好感度互换
	const ST_FUNCKEY_STAR_SWAP							=	504;
	// 金币翻牌花费
	const ST_FUNCKEY_STAR_DRAW							=	505;
	// 金币切磋花费
	const ST_FUNCKEY_STAR_CHALLENGE						=	506;
	// 洗牌切磋花费
	const ST_FUNCKEY_STAR_SHUFFLE						=	507;

	
	//tower 爬塔系统 
	//使用金币购买挑战次数
	const ST_FUNCKEY_TOWER_BUY_DEFEAT_NUM 				=	601;
	//使用金币通关塔层（无效）
	const ST_FUNCKEY_TOWER_GOLD_PASS					=	602;
	//金币抽奖（无效）
	const ST_FUNCKEY_TOWER_GOLD_LOTTERY					=	603;
	//金币购买神秘塔层
	const ST_FUNCKEY_TOWER_BUY_STOWER                   =   604;
	//金币扫荡
	const ST_FUNCKEY_TOWER_SWEEP_BYGOLD                 =   605;
	
	// arena	
	//竞技场幸运排行奖励金币
	const ST_FUNCKEY_ARENA_LUCKY						= 	700;
	//（无效）
	const ST_FUNCKEY_ARENA_RANK							=	701;
	// 清除cd时间（无效）
	const ST_FUNCKEY_ARENA_CLEAR_CDTIME					=   702;
	// 购买挑战次数（无效）
	const ST_FUNCKEY_ARENA_ADD_CHALLENGE				=   703;
	// 刷新对手列表（无效）
	const ST_FUNCKEY_ARENA_REFRESH_OPPTS				=	704;
	
	// shop
	// 酒馆金币招将---分招单次和招十次
	const ST_FUNCKEY_SHOP_GOLD_RECRUIT					=   801;
	// 购买vip礼包花费金币
	const ST_FUNCKEY_SHOP_VIP_GIFT						=   802;
	//十连抽发奖（无效）
	const ST_FUNCKEY_SHOP_TEN_RECRUIT					=	803;
	// 酒馆金币招将---招十次
	const ST_FUNCKEY_SHOP_GOLD_RECRUIT_TEN				=   804;
	// 酒馆金币招将---招单次
	const ST_FUNCKEY_SHOP_GOLD_RECRUIT_ONE				=   805;
	
	//divine
	//刷新占星星座金币花费
	const ST_FUNCKEY_DIVI_REFRESH						=	901;
	//占星奖励金币
	const ST_FUNCKEY_DIVI_REWARD						=	902;
	//刷新占星奖励花费金币
	const ST_FUNCKEY_DIVI_REF_REWARD					=	903;
	//一键占星花费金币
	const ST_FUNCKEY_DIVI_ONECLICK						= 	904;
	
	//online
	//在线奖励金币
	const ST_FUNCKEY_ONLINE_REWARD						=	1001;
	
	//sign
	//每日签到奖励金币
	const ST_FUNCKEY_SIGN_REWARD						=	1101;
	//累积登录活动奖励金币
	const ST_FUNCKEY_SIGN_ACTIVITY						=	1102;
	//累计登陆活动补签消费的金币
	const ST_FUNCKEY_SIGN_ACTIVITY_COST					=	1103;
	
	
	//forge
	//随机洗练花费金币（无效）
	const ST_FUNCKEY_FORGE_RAND_REFRESH 				= 	1201;
	//装备洗练花费金币
	const ST_FUNCKEY_FORGE_FIXED_REFRESH				=	1202;
	//潜能转移花费金币（无效）
	const ST_FUNCKEY_FORGE_POTENCE_TRANSFER				= 	1203;
	//橙装锻造花费金币
	const ST_FUNCKEY_FORGE_COMPOSE_COST					=	1204;
	//宝物转换花费金币
	const ST_FUNCKEY_FORGE_TRANSFER_TREASURE			=	1205;
	//兵符转换花费金币
	const ST_FUNCKEY_FORGE_TRANSFER_TALLY				=	1206;
	
	//Mall
	//商城兑换花费金币（通用）
	const ST_FUNCKEY_MALL_EXCHANGE_COST					=	1301;
	//商城兑换获得金币（通用）
	const ST_FUNCKEY_MALL_EXCHANGE_GET					=	1302;
	//活动商店花费金币（无效）
	const ST_FUNCKEY_MALL_SALE_COST						=	1303;
	//活动商店获得金币（无效）
	const ST_FUNCKEY_MALL_SALE_GET						=	1304;
	//活动兑换商店花费金币（无效）
	const ST_FUNCKEY_MALL_BARTER_COST					=	1305;
	//活动兑换商店获得金币（无效）
	const ST_FUNCKEY_MALL_BARTER_GET					=	1306;
	//普通商店花费金币
	const ST_FUNCKEY_MALL_SHOP_COST						=	1307;
	//普通商店获得金币
	const ST_FUNCKEY_MALL_SHOP_GET						=	1308;
	//普通兑换商店花费金币（无效）
	const ST_FUNCKEY_MALL_SHOPEXCHANGE_COST				=	1309;
	//普通兑换商店获得金币（无效）
	const ST_FUNCKEY_MALL_SHOPEXCHANGE_GET				=	1310;
	//军团商店花费金币
	const ST_FUNCKEY_MALL_GUILD_COST					=	1311;
	//军团商店获得金币
	const ST_FUNCKEY_MALL_GUILD_GET						=	1312;
	//竞技场商店花费金币
	const ST_FUNCKEY_MALL_ARENA_COST					=	1313;
	//竞技场商店获得金币
	const ST_FUNCKEY_MALL_ARENA_GET						=	1314;
	//神秘商店花费金币
	const ST_FUNCKEY_MALL_MYSTERY_COST					=	1315;
	//神秘商店获得金币
	const ST_FUNCKEY_MALL_MYSTERY_GET					=	1316;
	//神秘商人花费金币
	const ST_FUNCKEY_MALL_MYSMERCHANT_COST				=	1317;
	//神秘商人获得金币
	const ST_FUNCKEY_MALL_MYSMERCHANT_GET				=	1318;
    //兑换活动花费金币
    const ST_FUNCKEY_MALL_ACTEXCHANGE_COST              =   1319;
    //兑换活动获得金币
    const ST_FUNCKEY_MALL_ACTEXCHANGE_GET               =   1320;
    //周末商店花费金币
    const ST_FUNCKEY_MALL_WEEKENDSHOP_COST              =   1321;
    //周末商店获得金币
	const ST_FUNCKEY_MALL_WEEKENDSHOP_GET               =   1322;
	//限时商店花费金币
	const ST_FUNCKEY_MALL_LIMITSHOP_COST                =   1323;
	//限时商店获得金币
	const ST_FUNCKEY_MALL_LIMITSHOP_GET                 =   1324;
	//神兵商店花费金币
	const ST_FUNCKEY_MALL_PASSSHOP_COST	                =   1325;
	//神兵商店获得金币
	const ST_FUNCKEY_MALL_PASSSHOP_GET	                =   1326; 
	//积分商城花费金币
	const ST_FUNCKEY_MALL_SCORESHOP_COST                =   1327;
	//积分商城获得金币
	const ST_FUNCKEY_MALL_SCORESHOP_GET                 =   1328;
	//个人跨服战商店花费金币
	const ST_FUNCKEY_MALL_LORDWARSHOP_COST              =   1329;
	//个人跨服战商店获得金币
	const ST_FUNCKEY_MALL_LORDWARSHOP_GET               =   1330;
	//国战商店花费金币
	const ST_FUNCKEY_MALL_COUNTRYWARSHOP_COST			= 	1331;
	//国战商店获得金币
	const ST_FUNCKEY_MALL_COUNTRYWARSHOP_GET			= 	1332;
	//试炼梦魇商店花费金币
	const ST_FUNCKEY_MALL_HELLTOWER_COST                =   1333;
	//试炼梦魇商店获得金币
	const ST_FUNCKEY_MALL_HELLTOWER_GET                 =   1334;

	//Guild
	//创建军团花费金币
	const ST_FUNCKEY_GUILD_CREATE_COST					=	1401;
	//军团建设花费金币
	const ST_FUNCKEY_GUILD_CONTRI_COST					=	1402;
	//摇奖获得金币（无效）
	const ST_FUNCKEY_GUILD_LOTTERY_GOLD					=	1403;
	//弹劾军团长花费金币
	const ST_FUNCKEY_GUILD_IMPEACH_COST					=	1404;
	//关公殿发金币
	const ST_FUNCKEY_GUILD_REWARD_GUAN 					=   1405;
	//金币参拜关公殿奖励金币
	const ST_FUNCKEY_GUILD_BUY_REWARD_GUAN				=	1406;
	//刷新自己粮田花费金币
	const ST_FUNCKEY_GUILD_REFRESH_OWN					=	1407;
	//刷新全体粮田花费金币
	const ST_FUNCKEY_GUILD_REFRESH_ALL					=	1408;
	//购买战书花费金币
	const ST_FUNCKEY_GUILD_BUY_FIGHT_BOOK				=	1409;
	//分粮
	const ST_FUNCKEY_GUILD_SHARE_GRAIN					=  	1410;
	//军团改名
	const ST_FUNCKEY_GUILD_CHANGE_NAME					=	1411;
	
	
	//flop
	//翻牌掉落金币
	const ST_FUNCKEY_FLOP_GOLD							=	1501;
	
	//猎魂
	//召唤将魂龙珠（猎魂第四个场景）花费金币
	const ST_FUNCKEY_HUNT_SKIP							=	1601;
	//10次召唤神龙
	const ST_FUNCKEY_HUNT_SKIP_HUNT						=	1602;
	
	//每日任务
	//每日任务金币奖励（直接领取奖励）
	const ST_FUNCKEY_ACTIVE_PRIZE						=	1701;
	//每日任务单个任务对应的奖励
	const ST_FUNCKEY_ACTIVE_TASK_REWARD					=   1702;
	
	//citywar
	//城池战金币奖励
	const ST_FUNCKEY_CITYWAR_REWARD						=	1801;
	//城池战购买连胜次数金币花费
	const ST_FUNCKEY_CITYWAR_BUYWIN						=	1802;
	//城池战金币鼓舞（暂时没有金币鼓舞）
	const ST_FUNCKEY_CITYWAR_INSPIRE					=	1803;
	//修复城防秒Cd金币花费
	const ST_FUNCKEY_CITYWAR_CLEAR_CD					=   1804;
	
	//formation
	//开小伙伴位置
	const ST_FUNCKEY_FORMATION_OPEN_EXTRA				=	1901;
	//开属性小伙伴位置
	const ST_FUNCKEY_FORMATION_OPEN_ATTR_EXTRA			=	1902;
	
	//activity
	//活动:成长计划购买花费金币
	const ST_FUNCKEY_GROWUP_COST						=	2001;
	//成长计划领取金币
	const ST_FUNCKEY_GROWUP_REWARD						=	2002;
	
	//活动：等级礼包金币奖励
	const ST_FUNKEY_LEVELUP_PRIZE						=	2101;
	
	//活动： 累计消费 奖励金币（暂无）
	const ST_FUNKEY_SPEND_PRIZE							=	2201;
	//活动：充值回馈奖励金币
	const ST_FUNKEY_TOPUP_PRIZE							=	2202;
	
	//活动：卡牌大放送 抽卡（暂无）
	const ST_FUNKEY_BUY_CARD							=	2301;
	
	//黑市兑换花费金币
	const ST_FUNKEY_BLACKSHOP_USE                       =   2310;
	//黑市兑换花费金币
	const ST_FUNKEY_BLACKSHOP_GET                       =   2311;
	
	//模块 资源矿
	//强占资源矿花费金币
	const ST_FUNKEY_GRAB_MINERAL                        =    2401;
    //延长资源矿占领时间花费金币    
    const ST_FUNKEY_DELAY_PIT                           =   2402;
    //占领第二个资源矿花费金币
    const ST_FUNKEY_SEC_PIT                             =   2403;
    //占领金币矿花费金币
    const ST_FUNCKEY_CAPTURE_GOLDPIT                     =   2404;
    //抢占金币矿花费金币
    const ST_FUNCKEY_GRAB_GOLDPIT                       =   2405; 
    
    //聚义厅
    //镶嵌卡牌花费金币
    const ST_FUNCKEY_UNION_FILL_COST					= 	2501;
    
    //云游商人
    //购买商品花费金币
    const ST_FUNCKEY_TRAVEL_SHOP_BUY                    =   2601;
    //领取充值返利金币
    const ST_FUNCKEY_TRAVEL_SHOP_PAYBACK                =   2602;
    //购买商品花费金币，统计了商品id和个数
    const ST_FUNCKEY_TRAVEL_SHOP_BUY_ID                 =   2603;
	
    //活动:单充回馈
    //活动奖励中的金币
    const ST_FUNCKEY_ONE_RECHARGE_GET					= 	2701;
    
	//宠物 金币开启可上阵槽位
	const ST_FUNCKEY_PET_OPEN_SQUAND_SLOT				=	3001;
	//宠物生产的金币
	const ST_FUNCKEY_PET_PRODUCTION						=	3002;
	//宠物技能重置花费金币
	const ST_FUNCKEY_PET_RESET							=	3003;
	//宠物锁定技能花费金币
	const ST_FUNCKEY_PET_LOCKSKILL						=	3004;
	//开启宠物背包花费金币
	const ST_FUNCKEY_OPEN_KEEPER_SLOT					=	3005;
	//宠物进阶花费金币
	const ST_FUNCKEY_PET_EVOLVE							=	3006;
	//宠物交换属性花费金币
	const ST_FUNCKEY_PET_EXCHANGE_ATTR					=	3007;
	
	//使用礼包码获得金币
	const ST_FUNCKEY_REWARD_GIFT_CODE					= 	4001;
	
	//聊天金币发送消息（无线）
	const ST_FUNCKEY_CHAT_SEND							=	5001;
	
	//boss
	//世界BOSS复活花费金币
	const ST_FUNCKEY_BOSS_REVIVE						=	6001;
	//世界BOSS奖励金币
	const ST_FUNCKEY_BOSS_KILL							=	6002;
	//世界BOSS排名奖励金币
	const ST_FUNCKEY_BOSS_RANK							=	6003;
	//世界BOSS金币鼓舞
	const ST_FUNCKEY_BOSS_INSPIRE						=	6004;

	//比武
	//比武排名奖励金币
	const ST_FUNCKEY_COMPETE_RANK						=	7001;
	//比武购买次数
	const ST_FUNCKEY_COMPETE_BUY						=	7002;
	
	//神秘商店
	//神秘商店刷新商品列表花费金币
	const ST_FUNCKEY_MYSTERYSHOP_REFR					=	8001;
	//炼化炉重生武将金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_HERO            =   8002;
	//炼化炉重生装备金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_ARM				=   8003;
	//炼化炉重生时装金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_DRESS			=   8004;
	//炼化炉重生宝物金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_TREASURE		=   8005;
	//炼化炉重生锦囊金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_POCKET			=   8006;
	//炼化炉重生红将金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_RED_HERO        =   8007;
	//炼化炉重生兵符金币花费
	const ST_FUNCKEY_MYSTERYSHOP_REBORN_TALLY        	=   8008;

	//夺宝免战金币花费
	const ST_FUNCKEY_WHITE_FLAG							=	9001;
	
	//卡包活动
	//活动：限时神将抽将花费金币
	const ST_FUNCKEY_HEROSHOP_GOLD_BUY                           =    10001; 
	//活动：限时神将积分奖励金币
	const ST_FUNCKEY_HERO_SHOP_SCORE							 =	  10002;
	//活动：限时神将排名奖励金币
	const ST_FUNCKEY_HERO_SHOP_RANK								 =	  10003;
	//活动：皇陵探宝挖宝花费金币
	const ST_FUNCKEY_ROB_TOMB_GOLDROB                            =    10004;
	//更改玩家名字消耗金币
	const ST_FUNCKEY_CHANGE_NAME                                 =    11001;
	
	//vip每日奖励获得金币
	const ST_FUNCKEY_VIP_DAILY_BONUS					=	12001;
	const ST_FUNCKEY_VIP_WEEK_GIFT_COST					=   12002;
	const ST_FUNCKEY_VIP_WEEK_GIFT_REWARD				=	12003;
	
	//福利活动翻卡获得金币
	const ST_FUNCKEY_WEAL_KA							=   13001;
	
	//每日任务奖励金币（奖励中心领取）
	const ST_FUNCKEY_DAILY_TASK							=   14001;

    //神秘商人
    //神秘商人刷新商品列表花费金币
    const ST_FUNCKEY_MYSMERCHANT_REFR					=	15001;
    //无效
    const ST_FUNCKEY_MYSMERCHANT_REBORN_HERO            =   15002;
    //无效
    const ST_FUNCKEY_MYSMERCHANT_REBORN_ARM				=   15003;
    const ST_FUNCKEY_MYSMERCHANT_OPEN_FOREVER           =   15004;
    
    //VIP购买攻击次数
    //购买精英副本攻击次数花费金币
    const ST_FUNCKEY_BUY_ECOPY_ATKNUM                =    16001;
    //购买试练塔重置次数花费金币
    const ST_FUNCKEY_BUY_TOWER_ATKNUM                =    16002;
    //购买摇钱树攻击次数花费金币
    const ST_FUNCKEY_BUY_GOLDTREE_ATKNUM                =    16003;
    //购买经验宝物攻击次数花费金币
    const ST_FUNCKEY_BUY_EXPTREAS_ATKNUM                =    16004;
    //购买军团副本组队次数花费金币
    const ST_FUNCKEY_BUY_TEAMCOPY_ATKNUM                =    16005;
    //购买天命副本次数花费金币
    const ST_FUNCKEY_BUY_DESTINY_ATK_NUM             =   16006;

    //兑换活动刷新商品列表
    const ST_FUNCKEY_ACTEXCHANGE_REFR                =   17001;
    
    //武将列传  激活天赋
    //武将列传激活天赋消耗金币
    const ST_FUNCKEY_TALENT_ACTIVATE                =    18001;
    
    //团购发到奖励中心
    const ST_FUNCKEY_GROUPON_REWARD					=	19001;
    //团购消耗金币
    const ST_FUNCKEY_GROUPON_SPEND_GOLD             =   19002;
    //团购直接给玩家发奖励
    const ST_FUNCKEY_GROUPON_GOOD                   = 19003;
    
    //军团任务奖励
    const ST_FUNCKEY_GUILD_TASK						= 20001;
    //军团任务刷新
    const ST_FUNCKEY_GUILD_TASK_REF					= 20002;
    //军团任务立即完成
    const ST_FUNCKEY_GUILD_TASK_RITNOW				= 20003;
    
    //月卡每日奖励
    const ST_FUNCKEY_MONTHLY_CARD					= 21001;
    //月卡大礼包   开服七天内首次购买月卡 获得月卡大礼包
    const ST_FUNCKEY_MONTHLYCARD_GIFT               = 21002; 
    //月卡返还金币
    const ST_FUNCKEY_MONTHCARD_GOLD                 = 21003;
    //购买月卡花费金币
    const ST_FUNCKEY_BUYCARD_SPEND_GOLD             = 21004;
    //月卡2每日奖励
    const ST_FUNCKEY_MONTHLY_CARD2					= 21005;
    //购买月卡2花费金币
    const ST_FUNCKEY_BUYCARD_SPEND_GOLD2            = 21006;
    
    //充值抽奖  每日首冲奖励
    const ST_FUNCKEY_CHARGERAFFLE_GIFT              = 22002;

    //寻龙探宝 重置消耗金币
    const ST_FUNCKEY_DRAGON_RESET              = 23001;
    //寻龙探宝 寻宝事件
    const ST_FUNCKEY_DRAGON_EVENT_TYPE_XB           = 23002;
    //寻龙探宝 购买行动力消耗金币
    const ST_FUNCKEY_DRAGON_BUY_ACT                 = 24003;
    //寻龙探宝 购买血池血量消耗金币
    const ST_FUNCKEY_DRAGON_BUY_HP                  = 24004;
    //寻龙探宝 贿赂怪物消耗金币
    const ST_FUNCKEY_DRAGON_BRIBE                   = 24005;
    //寻龙探宝 一键跳过消耗金币
    const ST_FUNCKEY_DRAGON_ONEKEY                  = 24006;
    //寻龙探宝 自动寻龙消耗金币
    const ST_FUNCKEY_DRAGON_AIDO                    = 24007;
    //寻龙探宝 BOSS金币通关
    const ST_FUNCKEY_DRAGON_GOLD_BOSS               = 24008;

    //充值大放送
    const ST_FUNCKEY_TOPUP_REWARD                   = 25001;
    
    //擂台赛
    //金币消除挑战CD
    const ST_FUNCKEY_OLYMPIC_CLEAR_CD               = 26001;
    //擂台赛3-32名奖励
    const ST_FUNCKEY_OLYMPIC_NORMAL_RANK            = 26002;
    //擂台赛亚军奖励
    const ST_FUNCKEY_OLYMPIC_SECOND               	= 26003;
    //擂台赛冠军奖励
    const ST_FUNCKEY_OLYMPIC_FIRST              	= 26004;
    //擂台赛助威奖励
    const ST_FUNCKEY_OLYMPIC_CHEER               	= 26005;
    //擂台赛幸运奖励
    const ST_FUNCKEY_OLYMPIC_LUCKY               	= 26006;
    //擂台赛超级幸运奖励（现在没有）
    const ST_FUNCKEY_OLYMPIC_SUPERLUCKY             = 26007;
    //擂台赛奖池奖励
    const ST_FUNCKEY_OLYMPIC_REWARDPOOL				= 26008;
    
    //跨服战
    //清复活cd
    const ST_FUNCKEY_LORDWAR_CLR_CD                 = 27001;
    //助威奖励
    const ST_FUNCKEY_LORDWAR_SUPPORT_REWARD			= 27101;
    //晋级赛奖励
    const ST_FUNCKEY_LORDWAR_PROMOTION_REWARD		= 27102;
	
    //膜拜花费
    const ST_FUNCKEY_LORDWAR_WORSHIP_COST			= 27201;
    //膜拜奖励
    const ST_FUNCKEY_LORDWAR_WORSHIP_PRIZE			= 27202;
    //助威花费
    const ST_FUNCKEY_LORDWAR_SUPPORT_COST			= 27301;

    //计步活动
    const ST_FUNCKEY_STEP_COUNTER_REWARD            = 28001;

    const ST_FUNCKEY_REGRESS_ELITE					= 29001;
    const ST_FUNCKEY_REGRESS_INSISTENT				= 29002;
    
	//合服活动
    // 连续登陆
	const ST_FUNCKEY_MERGE_SERVER_LOGIN_PRIZE        = 30001; 
	// 充值返回
	const ST_FUNCKEY_MERGE_SERVER_RECHARGE_PRIZE     = 30002;
	// 合服补偿
	const ST_FUNCKEY_MERGE_SERVER_COMPENSATION_PRIZE = 30003; 

    //周末商店
    //周末商店刷新列表花费金币
    const ST_FUNCKEY_WEEKENDSHOP_RFR_GOODLIST       = 31001;
    
    //月签到奖励
    const ST_FUNCKEY_MONTH_SIGN_REWARD				= 32001;
    
    //积分轮盘
    //积分轮盘抽奖发奖
    const ST_FUNCKEY_ROULETTE_REWARD                = 33001;
    //积分轮盘宝箱奖励
    const ST_FUNCKEY_ROULETTE_BOX_REWARD            = 33002;
    //积分轮盘金币摇奖
    const ST_FUNCKEY_ROULETTE_GOLD_ROULETTE			= 33003;
    //积分轮盘排名奖励
    const ST_FUNCKEY_ROULETTE_RANK_REWARD           = 33004;
   
    //军团粮仓
    // 删除参战冷却
    const ST_FUNCKEY_GUILD_ROB_REMOVE_JOIN_CD        = 34001;
    // 加速
    const ST_FUNCKEY_GUILD_ROB_SPEED_UP              = 34002; 
    // 击杀排名
    const ST_FUNCKEY_GUILD_ROB_KILL_RANK             = 34003; 
    
    //过关斩将
    //过关斩将通关固定奖励获得
    const ST_FUNCKEY_PASS_PERMERNENT_REWARD			= 35001;
    //过关斩将开金币宝箱花费
    const ST_FUNCKEY_PASS_GOLD_CHEST_COST			= 35002;
    //过关斩将开金币宝箱获得
    const ST_FUNCKEY_PASS_GOLD_CHEST_REWARD			= 35003;
    //过关斩将刷新过关斩将商店
    const ST_FUNCKEY_PASS_SHOP_REFRESH_COST         = 35004;
    //过关斩将排名奖励
    const ST_FUNCKEY_PASS_RANK_REWARD				= 35005;
    //过关斩将购买次数花费
    const ST_FUNCKEY_PASS_BUY_NUM					= 35006;
    
    
    //资源追回
    // 世界BOSS资源追回
    const ST_FUNCKEY_RETRIEVE_BOSS					 = 36001; 
    // 擂台赛资源追回
    const ST_FUNCKEY_RETRIEVE_OLYMPIC				 = 36002;
    // 国战资源追回
    const ST_FUNCKEY_RETRIEVE_COUNTRYWAR			 = 36003;
    // 吃烧鸡追回
    const ST_FUNCKEY_RETRIEVE_SUPPLY                 = 36004;

    //成就
    const ST_FUNCKEY_ACHIEVE_REWARD                 = 37001;

    //神兵
    //神兵重生花费
    const ST_FUNCKEY_GOD_WEAPON_REBORN              = 38001;
    //神兵金币洗练
    const ST_FUNCKEY_GOD_WEAPON_WASH                = 38002;
    //神兵先练属性传承
    const ST_FUNCKEY_GOD_WEAPON_LEGEND              = 38003;
    //神兵转换
    const ST_FUNCKEY_GOD_WEAPON_TRANSFER            = 38004;
    
    //聚宝盆
    //聚宝盆发奖
    const ST_FUNCKEY_BOWL_REWARD                    = 39001;
    //聚宝花费金币
    const ST_FUNCKEY_BOWL_COST                      = 39002;
    
    //跨服军团战
    //清除更新战斗数据的CD
    const ST_FUNCKEY_GUILD_WAR_CLEAR_CD	            = 40001;
    //购买最大连胜次数
    const ST_FUNCKEY_GUILD_WAR_BUY_MAX_WIN          = 40002;
    //膜拜消耗金币
    const ST_FUNCKEY_GUILD_WAR_WORSHIP_COST	        = 40003;
    //膜拜奖励
    const ST_FUNCKEY_GUILD_WAR_WORSHIP_PRIZE        = 40004;
    //助威奖励
    const ST_FUNCKEY_GUILDWAR_SUPPORT_REWARD		= 40005;
    //晋级赛奖励
    const ST_FUNCKEY_GUILDWAR_RANK_REWARD			= 40006;

    //吃烧鸡获得金币
    const ST_FUNCKEY_SUPPLY_REWARD					= 41001;

    //星魂
    //星魂技能升级消耗金币
    const ST_FUNCKEY_ATHENA_SKILL_UPGRADE           = 42001;
    //合成星魂
    const ST_FUNCKEY_ATHENA_SYNTHESIS               = 42002;
    //购买合成星魂所需材料
    const ST_FUNCKEY_ATHENA_SYNTHESIS_BUY_MATERIAL  = 42003;
    
    //军团副本
    //军团副本买次数花费
    const ST_FUNCKEY_GUILD_COPY_COST				= 43001;
    //军团副本全团突击花费
    const ST_FUNCKEY_GUILD_REFRESH_COST				= 43002;
    //军团副本排名奖励
    const ST_FUNCKEY_GUILD_COPY_RANK_REWARD			= 43003;
    //军团副本攻击奖励
    const ST_FUNCKEY_GUILD_COPY_ATTACK_REWARD		= 43004;
    //军团副本阳光普照奖
    const ST_FUNCKEY_GUILD_COPY_PASS_REWARD			= 43005;
    //军团副本宝箱奖励
    const ST_FUNCKEY_GUILD_COPY_BOX_REWARD			= 43006;
    //军团副本商店花费金币
    const ST_FUNCKEY_GUILD_COPY_SHOP_COST	        = 43007;
    //军团副本商店获得金币
    const ST_FUNCKEY_GUILD_COPY_SHOP_GET	        = 43008;
    //军团副本BOSS奖励
    const ST_FUNCKEY_GUILD_COPY_BOSS_REWARD			= 43009;
    //军团副本BOSS购买花费
    const ST_FUNCKEY_GUILD_COPY_BUY_BOSS			= 43010;
    
    //水月之镜
    //购买攻击次数花费
    const ST_FUNCKEY_MOON_BUY_NUM_COST				= 44001;
    //开宝箱奖励
    const ST_FUNCKEY_MOON_BOX_REWARD				= 44002;
    //攻击BOSS奖励
    const ST_FUNCKEY_MOON_BOSS_REWARD				= 44003;
    //天工阁花费金币
    const ST_FUNCKEY_MOON_TGSHOP_COST	            = 44004;
    //天工阁获得金币
    const ST_FUNCKEY_MOON_TGSHOP_GET	            = 44005;
    //刷新天工阁花费
    const ST_FUNCKEY_MOON_TGSHOP_REFRESH_COST		= 44006;
    //天工阁购买上面的宝箱花费
    const ST_FUNCKEY_MOON_TGSHOP_BUY_BOX_COST		= 44007;
    //天工阁宝箱掉落奖励
    const ST_FUNCKEY_MOON_TGSHOP_BOX_REWARD			= 44008;
    //兵符商店花费金币
    const ST_FUNCKEY_MOON_BINGFU_SHOP_COST          = 44009;
    //兵符商店获得金币
    const ST_FUNCKEY_MOON_BINGFU_SHOP_REWARD        = 44010;
    
    //跨服闯关大赛
    //购买攻击次数花费
    const ST_FUNCKEY_WORLD_PASS_BUY_NUM_COST		= 45001;
    //刷新备选武将列表花费
    const ST_FUNCKEY_WORLD_PASS_REFRESH_HERO_COST	= 45002;
    //商店花费金币
    const ST_FUNCKEY_WORLD_PASS_SHOP_COST	        = 45003;
    //商店获得金币
    const ST_FUNCKEY_WORLD_PASS_SHOP_GET	        = 45004;
    //刷新商店花费
    const ST_FUNCKEY_WORLD_PASS_SHOP_REFRESH_COST	= 45005;
    //排名奖励
    const ST_FUNCKEY_WORLD_PASS_RANK_REWARD			= 45006;
    
    //跨服竞技场
    //攻击别人，胜利获得的奖励
    const ST_FUNCKEY_WORLD_ARENA_ATTACK_WIN_REWARD	= 46001;
    //攻击别人，失败获得的奖励
    const ST_FUNCKEY_WORLD_ARENA_ATTACK_LOSE_REWARD	= 46002;
    //被别人攻击，胜利获得的奖励
    const ST_FUNCKEY_WORLD_ARENA_BE_ATTACKED_REWARD	= 46003;
    //购买挑战次数花费
    const ST_FUNCKEY_WORLD_ARENA_BUY_ATK_NUM_COST	= 46004;
    //重置花费
    const ST_FUNCKEY_WORLD_ARENA_RESET_COST			= 46005;
    //位置排名奖励
    const ST_FUNCKEY_WORLD_ARENA_POS_RANK_REWARD	= 46006;
    //击杀排名奖励
    const ST_FUNCKEY_WORLD_ARENA_KILL_RANK_REWARD	= 46007;
    //最大连杀排名奖励
    const ST_FUNCKEY_WORLD_ARENA_CONTI_RANK_REWARD	= 46008;
    //三榜排名第一奖励
    const ST_FUNCKEY_WORLD_ARENA_KING_RANK_REWARD	= 46009;

    //跨服团购
    //团购物品花费金币
    const ST_FUNCKEY_WORLD_GROUPON_BUY              = 47001;
    //团购玩家得到购买物品
    const ST_FUNCKEY_WORLD_GROUPON_BUY_REWARD       = 47002;
    //团购补发玩家金币差价
    const ST_FUNCKEY_WORLD_GROUPON_PURCHASE_GOLD    = 47003;
    //团购玩家领取积分奖励
    const ST_FUNCKEY_WORLD_GROUPON_REC_REWARD       = 47004;
    
    //跨服悬赏榜
    //捐献金币
    const ST_FUNCKEY_MISSION_DONATE					= 48001;
    //跨服悬赏榜每日领奖
    const ST_FUNCKEY_MISSION_DAY_REWARD				= 48004;
	//跨服悬赏榜商店花费金币
	const ST_FUNCKEY_MALL_MISSION_SHOP_COST			= 48002;
	//跨服悬赏榜商店获得金币
	const ST_FUNCKEY_MALL_MISSION_SHOP_GET			= 48003;
	//悬赏榜排名奖励
	const ST_FUNCKEY_MISSION_RANK_REWARD			= 48005;
	
	// 欢乐签到
	// 欢乐签到获得的金币
	const ST_FUNCKEY_HAPPY_SIGN						= 49001;
	// 补签需要花费的金币
	const ST_FUNCKEY_HAPPY_SIGN_COST 			    = 49002;
	
	//跨服比武
	//商店花费金币
	const ST_FUNCKEY_WORLD_COMPETE_SHOP_COST        = 50001;
	//购买挑战次数花费
	const ST_FUNCKEY_WORLD_COMPETE_BUY_NUM_COST		= 50002;
	//刷新对手列表花费
	const ST_FUNCKEY_WORLD_COMPETE_REFRESH_RIVAL_COST	= 50003;
	//每日胜场奖励
	const ST_FUNCKEY_WORLD_COMPETE_DAY_SUC_PRIZE	= 50004;
	//每日膜拜奖励
	const ST_FUNCKEY_WORLD_COMPETE_WORSHIP_REWARD	= 50005;
	//排名奖励
	const ST_FUNCKEY_WORLD_COMPETE_RANK_REWARD		= 50006;
	
	
	//新类型福利活动（desact）
	//完成任务领奖
	const ST_FUNCKEY_WORLD_DESACT_TASK_REWARD 		= 51001; 
	
	// 活动：充值送礼获得的金币
	const ST_FUNCKEY_RECHARGE_GIFT					= 52001;
	
	//国战
	//报名奖励
	const ST_FUNCKEY_COUNTRYWAR_SIGN_REWARD 			= 55001;
	//对换cocoin
	const ST_FUNCKEY_COUNTRYWAR_EXCHANGE_COCOIN 		= 55002;
	//助威个人
	const ST_FUNCKEY_COUNTRYWAR_SUPPOR_REWARD_USER		= 55003;
	//助威国家
	const ST_FUNCKEY_COUNTRYWAR_SUPPOR_REWARD_COUNTRY	= 55004;
	//初赛奖励
	const ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_AUDITION 	= 55005;
	//决赛奖励
	const ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_FINALTION 	= 55006;
	//膜拜奖励
	const ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_WORSHIP 	= 55007;
	//胜者方奖励
	const ST_FUNCKEY_COUNTRYWAR_REWARD_WIN_SIDE 		= 55008;
	//清cd
	const ST_FUNCKEY_COUNTRYWAR_CLEAR_CD 				= 55009;
	//鼓舞
	const ST_FUNCKEY_COUNTRYWAR_INSPIRE 				= 55010;
	//自动回血
	const ST_FUNCKEY_COUNTRYWAR_AUTO_RECOVER 			= 55011;
	//手动回血
	const ST_FUNCKEY_COUNTRYWAR_RECOVER 				= 55012;
	//将国战币通过脚本的形式返还给国家时玩家的国战币扣除
	const ST_FUNCKEY_COUNTRYWAR_BACKUSER_SUBCOCOIN		= 55013;
	//将国战币通过脚本的形式返还给国家时玩家的金币返还
	const ST_FUNCKEY_COUNTRYWAR_BACKUSER_GOLD			= 55014;
	
	//红包系统
	//红包系统发红包花费金币
	const ST_FUNCKEY_ENVELOPE_SEND_COST                 = 56001;
	//红包系统领红包获得金币
	const ST_FUNCKEY_ENVELOPE_RECV_GET                  = 56002;
	//红包系统回收金币
	const ST_FUNCKEY_ENVELOPE_RECYCLE_GET               = 56003;
	
	//补偿活动
	const ST_FUNCKEY_ACT_PAY_BACK_GET                   = 57001;
	
	//宠物
	
	//木牛流马
	const ST_FUNCKEY_CHARGEDART_LOOK_COST               = 59001;
	const ST_FUNCKEY_CHARGEDART_ROB_RAGE_COST           = 59002;
	const ST_FUNCKEY_CHARGEDART_REFRESH_BY_GOLD         = 59003;
	const ST_FUNCKEY_CHARGEDART_OPEN_RAGE_COST          = 59004;
	const ST_FUNCKEY_CHARGEDART_FINISH_BY_GOLD          = 59005;
	const ST_FUNCKEY_CHARGEDART_BUY_ROB                 = 59006;
	const ST_FUNCKEY_CHARGEDART_BUY_SHIP                = 59007;
	const ST_FUNCKEY_CHARGEDART_BUY_ASSIST              = 59008;
	//奖励中心获得金币
	const ST_FUNCKEY_REWARDCENTER_GET_GOLD_USER         = 59009;
	const ST_FUNCKEY_REWARDCENTER_GET_GOLD_ASSIST       = 59010;
	const ST_FUNCKEY_REWARDCENTER_GET_GOLD_ROB          = 59011;
	
	//新服活动("开服7天乐")
	const ST_FUNCKEY_NEWSERVERACTIVITY_GET				= 60001;
	const ST_FUNCKEY_NEWSERVERACTIVITY_COST				= 60002;
	
	//矿精灵获得金币
	const ST_FUNCKEY_MINERAL_ELVES_GET  = 61001;
	
	//武将天命
	//重置花费金币
	const ST_FUNCKEY_HERO_DESTINY_RESET_COST = 62001;
	//重置返还材料
	const ST_FUNCKEY_HERO_DESTINY_RESET_REWARD = 62002;

	//夏日狂欢活动
	//夏日活动-限时折扣
	const ST_FUNCKEY_FESTIVALACT_BUY                    = 63001;
	//夏日活动-限时兑换
	const ST_FUNCKEY_FESTIVALACT_EXCHANGE               = 63002;
	//夏日活动-补签
	const ST_FUNCKEY_FESTIVALACT_SIGN                   = 63003;
	//夏日活动-任务领奖
	const ST_FUNCKEY_FESTIVALACT_TASKREWARD             = 63004;
	
	//战车重生花费金币
	const ST_FUNCKEY_CHARIOT_REBORN_COST          =64005;
	
	//七星台（精华招募）
	//招募花费金币
	const ST_FUNCKEY_SEVENS_LOTTERY_COST   				= 65001;
	//招募获得奖励
	const ST_FUNCKEY_SEVENS_LOTTERY_REWARD				= 65002;
	
	//试炼噩梦
	//试炼噩梦扫荡获得金币
	const ST_FUNCKEY_HELL_TOWER_SWEEP_REWARD            = 66001;
	//试炼噩梦购买重置次数花费金币
	const ST_FUNCKEY_BUY_HELLTOWER_ATKNUM               = 66002;
	//试炼噩梦购买失败次数花费金币
	const ST_FUNCKEY_BUY_HELLTOWER_DEFEATNUM            = 66003;
	//试炼噩梦扫荡立即完成花费金币
	const ST_FUNCKEY_HELLTOWER_SWEEP_BY_GOLD            = 66004;
	
	//老玩家回归-礼包获得金币
	const ST_FUNCKEY_WELCOMEBACK_GIFT_GET				= 67001;
	//老玩家回归-任务获得金币
	const ST_FUNCKEY_WELCOMEBACK_TASK_GET				= 67002;
	//老玩家回归-单充获得金币
	const ST_FUNCKEY_WELCOMEBACK_RECHARGE_GET			= 67003;
	//老玩家回归-折扣商店消耗金币
	const ST_FUNCKEY_WELCOMEBACK_SHOP_COST				= 67004;
	//老玩家回归-折扣商店获得金币
	const ST_FUNCKEY_WELCOMEBACK_SHOP_GET				= 67005;
	//老玩家回归-补发获得金币
	const ST_FUNCKEY_WELCOMEBACK_BUFA_GET				= 67006;
	
	//银币转化为物品
	const ST_FUNCKEY_SILVER_TRANS_GET					= 68001;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
