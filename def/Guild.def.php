<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Guild.def.php 230582 2016-03-02 10:12:55Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Guild.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:12:55 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230582 $
 * @brief 
 *  
 **/

class GuildDef
{	
	//session军团id,name
	const SESSION_GUILD_ID = 'global.guildId';
	const SESSION_GUILD_NAME = 'global.guildName';
	
	//建筑类型
	const ALL = 0;
	const GUILD = 1;//军团大厅
	const TEMPLE = 2;//关公殿
	const STORE = 3;//商城
	const COPY = 4;//副本
	const TASK = 5;//任务
	const BARN = 6;//粮仓
	const TECH = 7;//科技
	
	//常量
	const SPECIAL 						= 		1;
	const NORMAL 						= 		2;
	//军团限制购买类型
	const REFRESH_EVERYDAY				=		4;
	const REFRESH_NERVER				=		5;
	
	const GUILD_LOCK_KEY_PREFIX         =       'guild.';
	
	//创建类型, 0银币，1金币
	public static $VALID_CREATE_TYPE = array(0, 1);
	
	//建筑对应配置表
	public static $TYPE_TO_CONFNAME = array(
			self::GUILD => 'GUILD',
			self::TEMPLE => 'GUILD_TEMPLE',
			self::STORE => 'GUILD_STORE',
			self::COPY => 'GUILD_COPY',
			self::TASK => 'GUILD_TASK_LIMIT',
			self::BARN => 'GUILD_BARN',
	);
	
	const LEADER_UID = 'leader_uid';
	const LEADER_UTID = 'leader_utid';
	const LEADER_HTID = 'leader_htid';
	const LEADER_DRESS = 'leader_dress';
	const LEADER_NAME = 'leader_name';
	const LEADER_LEVEL = 'leader_level';
	const LEADER_FORCE = 'leader_force';
	const MEMBER_NUM = 'member_num';
	const MEMBER_LIMIT = 'member_limit';
	const VP_NUM = 'vp_num';
	const RANK = 'rank';
	const NORMAL_GOODS = 'normal_goods';
	const SPECIAL_GOODS = 'special_goods';
	const REFRESH_LIST = 'refresh_list';
	const REFRESH_CD = 'refresh_cd';
	const FIELDS = 'fields';
	
	//GUILD-忠义堂1
	const GUILD_USER_LEVEL = 'userLevel';
	const GUILD_SILVER_CREATE = 'silverCreate';
	const GUILD_GOLD_CREATE = 'goldCreate';
	const GUILD_REJOIN_CD = 'rejoinCD';
	const GUILD_CAPACITY_BASE = 'capacityBase';
	const GUILD_CAPACITY_LIMIT = 'capacityLimit';
	const GUILD_MAX_LEVEL = 'maxLevel';
	const GUILD_EXP_ID = 'expId';
	const GUILD_CONTRI_ARR = 'contriArr';
	const GUILD_VP_NUM = 'vpNum';
	const GUILD_JOIN_EXTRA = 'joinExtra';
	const GUILD_IMPEACH_GOLD = 'impeachGold';
	const GUILD_TECH_OPEN = 'techOpen';
	
	//GUILD_TEMPLE-关公殿2
	const GUILD_LEVEL_RATIO = 'levelRatio';
	const GUILD_EXECUTION_BASE = 'executionBase';
	const GUILD_EXECUTION_INCRE = 'executionIncre';
	const GUILD_STAMINA_BASE = 'staminaBase';
	const GUILD_STAMINA_INCRE = 'staminaIncre';
	const GUILD_PRESTIGE_BASE = 'prestigeBase';
	const GUILD_PRESTIGE_INCRE = 'prestigeIncre';
	const GUILD_SOUL_BASE = 'soulBase';
	const GUILD_SOUL_INCRE = 'soulIncre';
	const GUILD_SILVER_BASE = 'silverBase';
	const GUILD_SILVER_INCRE = 'silverIncre';
	const GUILD_GOLD_BASE = 'goldBase';
	const GUILD_GOLD_INCRE = 'goldIncre';
	const GUILD_REWARD_START = 'rewardStart';
	const GUILD_REWARD_END = 'rewardEnd';
	const GUILD_REWARD_COST = 'rewardCost';
	
	//GUILD_STORE-商城3
	const GUILD_NORMAL_GOODS = 'normalGoods';
	const GUILD_SPECIAL_GOODS = 'specialGoods';
	const GUILD_SPECIAL_NUM = 'specialNum';
	const GUILD_SPECIAL_CD = 'specialCd';
	
	//GUILD_GOODS-商品
	const GUILD_STORE_LEVEL = 'storeLevel';
	const GUILD_GOODS_TYPE = 'goodsType';
	const GUILD_GOODS_LIMIT = 'goodsLimit';
	const GUILD_GOODS_WEIGHT = 'weight';
	
	//GUILD_COPY-副本
	const GUILD_COPY_ARR = 'copyArr';
	const GUILD_COPY_ADD = 'copyAdd';
	const GUILD_COPY_LIMIT = 'copyLimit';
	const GUILD_HIT_ROUNDS = 'copyHitRounds';
	const GUILD_HELP_NUM = 'copyHelpNum';
	const GUILD_SILVER_ADDTION = 'copySilverAdd';
	
	//GUILD_BARN-粮仓
	const GUILD_BARN_OPEN = 'barnOpen';
	const GUILD_FIELD_NUM = 'fieldNum';
	const GUILD_FIELD_EXPID = 'fieldExpId';
	const GUILD_HARVEST_GRAIN = 'harvestGain';
	const GUILD_HARVEST_SILVER = 'harvestSilver';
	const GUILD_HARVEST_NUM = 'harvestNum';
	const GUILD_REFRESH_OWN = 'refreshOwn';
	const GUILD_REFRESH_BASE = 'refreshBase';
	const GUILD_REFRESH_ADD = 'refreshAdd';
	const GUILD_CHALLENGE_COST = 'challengeCost';
	const GUILD_CHALLENGE_FREE = 'challengeFree';
	const GUILD_SHARE_CD = 'shareCd';
	const GUILD_GRAIN_CAPACITY = 'grainCapacity';
	const GUILD_HARVEST_EXP = 'harvestExp';
	const GUILD_SHARE_COEF = 'shareCoef';
	const GUILD_REFRESH_ALL_BYGOLD = 'refreshAllByGold';
	const GUILD_REFRESH_ALL_BYGUILDEXP = 'refreshAllByGuildExp';
	const GUILD_FIELD_LEVEL = 'fieldLevel';
	const GUILD_FIGHTBOOK_LIMIT = 'fightBookLimit';
	const GUILD_RFRALL_BYEXP_COST = 'rfrAllCostExp';
	const MAX_HARVEST_NUM = 'maxHarvestNum';
	const GUILD_HARVEST_EXTRA = 'harvestExtra';
	
	//GUILD_BARN_GOODS-粮仓商品
	const GUILD_BARN_LEVEL = 'barnLevel';
	const GUILD_BARN_SHOP_GRAIN = 'grain';
	const GUILD_BARN_SHOP_MERIT = 'merit';
	
	//GUILD_LOTTERY-箱子摇奖
	const GUILD_LOTTERY_NUM = 'lotteryNum';
	const GUILD_LOTTERY_DROP = 'lotteryDrop';
	const GUILD_LOTTERY_COST = 'lotteryCost';
	
	//GUILD_ICON-军团徽章
	const GUILD_ICON_NDLEVEL = 'iconNdLevel';
	
	//GUILD_SKILL-军团技能
	const GUILD_SKILL_TYPE = 'skillType';
	const GUILD_SKILL_ATTR = 'skillAttr';
	const GUILD_MEMBER_COST = 'memberCost';
	const GUILD_MANAGER_COST = 'managerCost';
		
	//sql
	const TABLE_GUILD =	't_guild';
	const GUILD_ID = 'guild_id';
	const GUILD_NAME = 'guild_name';
	const GUILD_LEVEL = 'guild_level';
	const GUILD_ICON = 'guild_icon';
	const FIGHT_FORCE = 'fight_force';
	const UPGRADE_TIME = 'upgrade_time';
	const CREATE_UID = 'create_uid';
	const CREATE_TIME = 'create_time';
	const JOIN_NUM = 'join_num';
	const JOIN_TIME = 'join_time';
	const CONTRI_NUM = 'contri_num';
	const CONTRI_TIME = 'contri_time';
	const REWARD_NUM = 'reward_num';
	const REWARD_TIME = 'reward_time';
	const GRAIN_NUM = 'grain_num';
	const ATTACK_NUM = 'attack_num';
	const DEFEND_NUM = 'defend_num';
	const ROBNUM_RFRTIME = 'robnum_rfrtime';
	const REFRESH_NUM = 'refresh_num';
	const REFRESH_NUM_BYGUILDEXP = 'refresh_num_byexp';
	const RFRNUM_RFRTIME = 'rfrnum_rfrtime';
	const FIGHT_BOOK = 'fight_book';
	const FIGHTBOOK_RFRTIME = 'fightbook_rfrtime';
	const CURR_EXP = 'curr_exp';
	const SHARE_CD = 'share_cd';
	const STATUS = 'status';
	const VA_INFO = 'va_info';
	const SLOGAN = 'slogan';
	const POST = 'post';
	const PASSWD = 'passwd';
	const GOODS = 'goods';
	const SUM = 'sum';
	const NUM = 'num';
	const TIME = 'time';
	const LEVEL = 'level';
	const ALLEXP = 'allExp';
	const SKILLS = 'skills';
	
	public static $GUILD_FIELDS = array(
			self::GUILD_ID,
			self::GUILD_NAME,
			self::GUILD_LEVEL,
			self::GUILD_ICON,
			self::FIGHT_FORCE,
			self::UPGRADE_TIME,
			self::CREATE_UID,
			self::CREATE_TIME,
			self::JOIN_NUM,
			self::JOIN_TIME,
			self::CONTRI_NUM,
			self::CONTRI_TIME,
			self::REWARD_NUM,
			self::REWARD_TIME,
			self::GRAIN_NUM,
			self::ATTACK_NUM,
			self::DEFEND_NUM,
	        self::ROBNUM_RFRTIME,
			self::REFRESH_NUM,
	        self::REFRESH_NUM_BYGUILDEXP,
	        self::RFRNUM_RFRTIME,
			self::FIGHT_BOOK,
	        self::FIGHTBOOK_RFRTIME,
			self::CURR_EXP,
			self::SHARE_CD,
			self::STATUS,
			self::VA_INFO
	);
	
	public static $GUILD_FIELDS_LOCK = array(
			self::GUILD_ICON,
	        self::CURR_EXP,
	        self::JOIN_NUM,
	        self::CONTRI_NUM,
	        self::REWARD_NUM,
	        self::GRAIN_NUM,
	        self::SHARE_CD,
	        self::RFRNUM_RFRTIME,
	        self::FIGHT_BOOK,
	        self::VA_INFO,
	);
	
	public static $GUILD_BOUND_FIELDS = array(
	        self::JOIN_NUM => array(self::JOIN_TIME),
	        self::CONTRI_NUM => array(self::CONTRI_TIME),
	        self::REWARD_NUM => array(self::REWARD_TIME),
			self::FIGHT_BOOK => array(self::FIGHTBOOK_RFRTIME),
			self::ATTACK_NUM => array(self::DEFEND_NUM, self::ROBNUM_RFRTIME),
			self::DEFEND_NUM => array(self::ATTACK_NUM, self::ROBNUM_RFRTIME),
			self::RFRNUM_RFRTIME => array(self::REFRESH_NUM, self::REFRESH_NUM_BYGUILDEXP),
	);
	
	const TABLE_GUILD_MEMBER = 't_guild_member';
	const USER_ID = 'uid';
	const MEMBER_TYPE = 'member_type';
	const CONTRI_POINT = 'contri_point';
	const CONTRI_TOTAL = 'contri_total';
	const CONTRI_WEEK = 'contri_week';
	const LAST_CONTRI_WEEK = 'last_contri_week';
	const REWARD_BUY_NUM = 'reward_buy_num';
	const REWARD_BUY_TIME = 'reward_buy_time';
	const PRIZE_TIME = 'prize_time';
   	const LOTTERY_NUM = 'lottery_num';
   	const LOTTERY_TIME = 'lottery_time';
   	const MERIT_NUM = 'merit_num';
   	const ZG_NUM = 'zg_num';
	const REJOIN_CD	= 'rejoin_cd';
    const PLAYWITH_NUM = 'playwith_num';   
    const BE_PLAYWITH_NUM = 'be_playwith_num';  
    const PLAYWITH_TIME = 'playwith_time'; 
    const VA_MEMBER = 'va_member';
	
	public static $GUILD_MEMBER_FIELDS = array(
			self::USER_ID,
			self::GUILD_ID,
			self::MEMBER_TYPE,
			self::CONTRI_POINT,
			self::CONTRI_TOTAL,
			self::CONTRI_WEEK,
			self::LAST_CONTRI_WEEK,
			self::CONTRI_NUM,
			self::CONTRI_TIME,
			self::REWARD_TIME,
			self::REWARD_BUY_NUM,
			self::REWARD_BUY_TIME,
			self::LOTTERY_NUM,
			self::LOTTERY_TIME,
			self::GRAIN_NUM,
			self::MERIT_NUM,
			self::ZG_NUM,
			self::REFRESH_NUM,
			self::REJOIN_CD,
            self::PLAYWITH_NUM,
            self::BE_PLAYWITH_NUM,
			self::PLAYWITH_TIME,
			self::VA_MEMBER,
	);
	
	public static $MEMBER_BOUND_FIELDS = array(
			self::CONTRI_NUM,
			self::CONTRI_TIME,
			self::REWARD_BUY_NUM,
			self::REWARD_BUY_TIME,
			self::LOTTERY_NUM,
			self::LOTTERY_TIME,
			self::REFRESH_NUM,
			self::PLAYWITH_NUM,
			self::BE_PLAYWITH_NUM,
			self::PLAYWITH_TIME,
			self::CONTRI_WEEK,
			self::LAST_CONTRI_WEEK,
	);
	
	const TABLE_GUILD_APPLY = 't_guild_apply';
	const APPLY_TIME = 'apply_time';
	
	public static $GUILD_APPLY_FIELDS = array(
			self::USER_ID,
			self::GUILD_ID,
			self::APPLY_TIME,
			self::STATUS
	);
	
	const TABLE_GUILD_RECORD = 't_guild_record';
	const RECORD_ID = 'grid';
	const RECORD_TYPE = 'record_type';
	const RECORD_DATA = 'record_data';
	const RECORD_TIME = 'record_time';
	
	public static $GUILD_RECORD_FIELDS = array(
			self::RECORD_ID,
			self::USER_ID,
			self::GUILD_ID,
			self::RECORD_TYPE,
			self::RECORD_DATA,
			self::RECORD_TIME,
			self::VA_INFO
	);
}

class GuildStatus
{
	const OK = 1;
	
	const DEL = 0;
}

class GuildApplyStatus
{
	const OK = 1;

	const CANCEL = 2;

	const REFUSED = 3;

	const AGREED = 4;
}

class GuildMemberType
{
	//平民
	const NONE = 0;
	//团长
	const PRESIDENT = 1;
	//副团长
	const VICE_PRESIDENT = 2;
}

class RefreshAllType
{
	//金币
    const GOLD = 1;
    //军团贡献
    const GUILDEXP = 2;
}

class GuildPrivType
{
	//管理团员:审批和踢出
	const MEMBER_MANAGE = 1;
	//修改宣言
	const SLOGAN_MODIFY = 2;
	//修改公告
	const POST_MODIFY = 3;
	//修改密码
	const PASSWD_MODIFY = 4;
	//升级军团
	const LEVEL_UP = 5;
	//设置副团
	const SET_VP = 6;
	//转让团长
	const ROLE_TRANS = 7;
	//解散军团
	const DISMISS = 8;
	//弹劾团长
	const IMPEACH = 9;
	//分粮
	const SHARE = 10;
	//建设度全团粮田刷新
	const REFRESH_BYEXP = 11;
	//购买挑战书
	const BUY_FIGHTBOOK = 12;
	//修改徽章
	const ICON_MODIFY = 13;
	//提升技能
	const PROMOTE_SKILL = 14;
	//修改军团名称
	const NAME_MODIFY = 15;
}

class GuildRecordType
{
	//贡献1-5
	const CONTRI_EXP = 5;
	//加入军团
	const JOIN_GUILD = 101;
	//退出军团
	const QUIT_GUILD = 102;
	//踢出军团
	const KICK_MEMBER = 103;
	//弹劾团长
	const IMPEACH_P = 104;
	//设置副团长
	const SET_VP = 105;
	//升级军团
	const UPGRADE_GUILD = 106;
	//转让团长
	const TRANS_P = 107;
	//参拜关公
	const GUAN_REWARD = 108;
	//捐献类型
	const ALL_CONTRI = 109;
	//留言类型
	const LEAVE_MSG = 110;
	//抢粮
	const ROB_GRAIN = 111;
	//金币全体粮田刷新
	const REFRESH_ALL = 112;
	//采集粮田
	const HARVEST_FIELD = 113;
	//军团副本全团突击
	const ALL_ATTACK = 114;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */