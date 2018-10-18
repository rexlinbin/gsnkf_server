<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: User.def.php 254622 2016-08-03 13:01:23Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/User.def.php $
 * @author $Author: GuohaoZheng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-03 13:01:23 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254622 $
 * @brief
 *
 **/

class UserDef
{

	/**
	 * 用户已经删除
	 */
	const STATUS_DELETED = 0;

	/**
	 * online
	 */
	const STATUS_ONLINE = 1;

	/**
	 * offline
	 */
	const STATUS_OFFLINE = 2;

	/**
	 * suspend
	 */
	const STATUS_SUSPEND = 3;
	

	/**
	 * 随机名字可用
	 */
	const RANDOM_NAME_STATUS_OK = 0;

	/**
	 * 随机名字已经被使用
	 */
	const RANDOM_NAME_STATUS_USED = 1;

	
	/**
	 * 用户数据在session中的key
	 */
	const SESSION_KEY_USER = 'user.user';
	
	/**
	 * pid
	 */
	const SESSION_KEY_PID = 'global.pid';
	
	/**
	 * uid在session中的key
	 */
	const SESSION_KEY_UID = 'global.uid';
	
	/**
	 * 玩家的战斗力在session中的key
	 */
	const SESSION_KEY_FIGHTFORCE = 'global.fightForce';
	
	/**
	 * 玩家vip在session中的key
	 */
	const SESSION_KEY_VIP = 'global.vip';
	
	/**
	 * serverId
	 */
	const SESSION_KEY_SERVER_ID = 'global.serverId';
	
	const SESSION_KEY_PLOSGN  = 'global.plosgn';


	/**
	 * t_user表中va_hero unused hero的字段名
	 */
	const UNUSED_HERO_HTID = 0;
	
	const UNUSED_HERO_LEVEL = 1;
	
	
	/**
	 * 玩家更改名字的消费类型    1.金币更名  2.物品更名
	 */
	const CHANGE_NAME_SPEND_GOLD = 1;
	const CHANGE_NAME_SPEND_ITEM = 2;
	
    /**
     * user表的全部字段
     */
	public static $USER_FIELDS = array(
			'uid',
			'pid',
			'uname',
			'utid',
			'status',
			'create_time',
			'last_login_time',
			'last_logoff_time',
			'online_accum_time',
			'ban_chat_time',
			'mute',
			'level',
			'upgrade_time',
			'vip',
			'master_hid',
			'guild_id',
			'gold_num',
			'silver_num',
			'exp_num',
			'soul_num',
	        'jewel_num',
	        'prestige_num',
			'tg_num',
			'wm_num',
			'fame_num',
			'book_num',
	        'fs_exp',
			'jh',
	        'tally_point',
			'user_item_gold',
	        'tower_num',
	        
			'execution',
	        'execution_max_num',
			'execution_time',
			'buy_execution_time',
			'buy_execution_accum',
			'stamina',
	        'stamina_max_num',
			'stamina_time',
			'buy_stamina_time',
			'buy_stamina_accum',
			
			'fight_cdtime',
			'fight_force',
			'max_fight_force',
	        
	        'figure',
			'title',
	        'base_goldnum',
			'va_hero',
			'va_user',
	        'va_charge_info',
        );
	
	/**
	 * 在otherUser中更新时，直接忽略的字段
	 */
	public static $OTHER_UPDATE_IGNORE = array(
			'execution',
			'execution_time',
			'stamina',
			'stamina_time',
	        'execution_max_num',
			);
	
	
	
	/**
	 * 在otherUser中更新时，直接设置
	 */
	public static $OTHER_UPDATE_SET = array(
			'ban_chat_time',
			'fight_force',
			'max_fight_force',
			);
		
	/**
	 * 在otherUser中更新时，更新delt
	 */
	public static $OTHER_UPDATE_DELT = array(
			'gold_num',
			'silver_num',
			'exp_num',
			'soul_num',			
			'vip',
			);

	/**
	 * 不能为负数的字段
	 */
	public static $FIELD_CANT_NEGATIVE = array(
			'uid',
			'pid',			
			'utid',
			'status',
			'create_time',
			'last_login_time',
			'last_logoff_time',
			'online_accum_time',
			'ban_chat_time',
			'mute',
			'level',
			'vip',
			'master_hid',
			'gold_num',
			'silver_num',
			'experience_num',
			'soul_num',
	        'tally_point',
			'user_item_gold',
	        'tower_num',
			
			'jewel_num',
			'prestige_num',
			'tg_num',
			'wm_num',
			'fame_num',
			'book_num',
			'fs_exp',
			'jh',
			
			'execution',
			'execution_time',
			'buy_execution_time',
			'buy_execution_accum',
			'stamina',
			'stamina_time',
			'buy_stamina_time',
			'buy_stamina_accum',
			'base_goldnum',
				
			'fight_cdtime',
			'fight_force',
			'max_fight_force',
			);
}

/**
 * 订单类型
 * Enter description here ...
 * @author idyll
 *
 */
class OrderType
{
	const NORMAL_ORDER  = 0;
	
	const ONLINE_REWARD_GOLD = 1;
	
	/**
	 * 福利充值
	 * @var unknown_type
	 */
	const FULI_ORDER = 101;
	
	/**
	 * 错单处理
	 * @var unknown_type
	 */
	const ERROR_FIX_ORDER = 102;
}

class VA_USER
{
    const HERO_LIMIT    =    'hero_limit';
    const FLOP_NUM      =    'flop_num'; //翻牌次数记录 
    const DRESSINFO = 'dress';    //主角的时装信息
}

/**
 * 0-10 各有特殊用处
 * 10001-10010为比武机器人
 * 11001-16000竞技场npc
 * 
 */
class SPECIAL_UID
{
	const BROADCAST = 0;	
	
	const RFR_HEROSHOPINFO_INMC = 1;//刷新活动卡包系统的排名信息
	
	const CHARGERAFFLE_REWARDTIMER = 2;//添加充值抽奖活动结束发奖timer以及执行timer
	
	const MINERAL_ROB_LOG_UPDATE = 3;//更新资源矿抢占日志
	
	const GUILD_ROB = 4;
	
	const GUILD_WAR = 5;
	
	const INIT_GUILD_LEVELUP_TIME_UID = 6;//初始化粮仓的0级时间
	
	const RFR_ROULETTEINFO_INMC = 1;//刷新积分轮盘系统的排名信息
	
	const MIN_ROBOT_UID = 10001;
	
	const MAX_ROBOT_UID = 10010;
	
	const MIN_ARENA_NPC_UID = 11001;
	
	const MAX_ARENA_NPC_UID = 16000;
	
	const TRAVEL_SHOP_UID = 7;//云游商人
	
	const ONE_RECHARGE_TIMER_UID = 1; //活动:单充回馈设置“活动结束前1小时,发奖到奖励中心”的timer
	
}

/**
 * 偶尔需要场景广播，比如在boss，海贼的擂台赛
 * 可以设置global.arenaId，然后sendFilterMessage
 */
class SPECIAL_ARENA_ID
{
	const SESSION_KEY = 'global.arenaId';
	
	const ARENA		= 1;
	const HERO_SHOP = 2;
	const OLYMPIC = 3;
	const MINERAL = 4;
	const BOSS = 100;  //boss 100-199
	const GROUPON = 200;//团购活动
	const LORDWAR = 300;
	const GUILDROB = 400;
	const GUILDWAR = 500;
	const CHARGEDART = 600;
	const MINERALELVES =700;      
}

class BUYITEM_TYPE
{
    const MONTHLYCARD = 1;
}

class CHARGE_TYPE
{
    const CHARGE_GOLD = 1;
    const CHARGE_BUYMONTYLYCARD = 2;
}

class MASTERSKILL_SOURCE 
{
    const STAR = 1;
    const ATHENA = 2;
}

class FIRST_PAY_BACK_TYPE
{
    const ALL = 1;
    const PART = 2;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */