<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: User.cfg.php 259225 2016-08-29 13:20:02Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/User.cfg.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-29 13:20:02 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259225 $
 * @brief
 *
 **/
class UserConf
{
	
	/**
	 * 放置用户在lcserver内卡死的安全删除连接时间
	 * @var int
	 */
	const SAFE_DEL_TIME = 86400;

	/**
	 * 最大登录重试次数
	 * @var int
	 */
	const MAX_LOGIN_RC = 10;

	/**
	 *
	 * 登录重试间隔
	 * @var int
	 */
	const LOGIN_RC_INTERVAL = 100000;
	
	/**
	 * 是否开启防沉迷
	 */
	const ANTI_WALLOW = 1;
	
	/**
	 * 用户升级经验表
	 */
	const EXP_TABLE_ID = 2;

	/**
     * 用户配置信息
     * <code>
     * array
     * {
     * 		0:用户模版id，不可重复
	 *	 	array
     * 		{
     * 			性别 ：0：女 1：男
	 * 			主英雄htid
	 * 		}
	 * }
	 * </code>
     *
     */
	public static $USER_INFO = array(
		1 => array(
			0,
			20002,
		),

		2 => array(
			1,
			20001,
		),
	);
	/**
	 * 8级的时候发放一个物品101201
	 * @var array
	 */
	public static $LEVEL_REWARD_ITEM = array(
	        9=>array(101201=>1)
	        );

	
	/**
	 * 服务器可创建用户数量
	 * @var int
	 */
	const MAX_USER_NUM = 1;

	/**
	 * 用户名字最大长度 (中文算两个字符，英文算一个字符)
	 * @var int
	 */
	const MAX_USER_NAME_LEN = 10;

	/**
	 * 用户名字最小长度
	 * @var int
	 */
	const MIN_USER_NAME_LEN = 1;
	
	/**
	 * 返回随机名最大的数量
	 */
	const NUM_RANDOM_NAME = 20;

	/**
	 * 从数据库选择随机名范围
	 */
	const RANDOM_NAME_OFFSET = 1000;

	/**
	 * 人物最大等级
	 */
	const MAX_LEVEL = 150;

	/**
	 * 初始阅历
	 */
	const INIT_EXPERIENCE = 0;
	
	/**
	 * 初始银两 
	 */
	const INIT_SILVER = 10000;

	/**
	 * 初始金币
	 */
	const INIT_GOLD = 250000;

	/**
	 * 初始vip
	 */
	const INIT_VIP = 14;

	/**
	 * 初始行动力
	 */
	const INIT_EXECUTION = 500;
	
	/**
	 * 初始耐力值
	 */
    const INIT_STAMINA    =    0;
	
	/**
	 * 最大体力
	 */
	const MAX_EXECUTION = 150;
	
	/**
	 * 最大耐力
	 */
	const MAX_STAMINA = 20;

	/**
	 * 恢复一点行动力需要多少秒  6分钟
	 */
	const SECOND_PER_EXECUTION = 360;
	
	/**
	 * 恢复一点耐力需要多少秒
	 * @var unknown_type 15分钟
	 */
	const SECOND_PER_STAMINA    =    900;

	/**
	 * 银两最大上限
	 */
	const SILVER_MAX = 2000000000;
	
	/*
	 * 超上限后，将银币转化为物品的数量
	 */
	const SILVER_TRANS_MAX = 1000000000;
	const SILVER_ITEM_VALUE = 100000000;
	const SILVER_ITEM_TEMPLATE = 10020;

	/**
	 * 金币最大上限值
	 */
	const GOLD_MAX = 2000000000;
	
	/**
	 * 阅历最大上限
	 */
	const EXPERIENCE_MAX = 2000000000;
	
	/**
	 * 将魂最大上限
	 */
	const SOUL_MAX = 2000000000;
	
    /**
     * 魂玉最大上限
     */
	const JEWEL_MAX = 2000000000;
	
	/**
	 * 声望最大上限
	 */
	const PRESTIGE_MAX = 2000000000;
	
	/**
	 * 天工令最大上限
	 */
	const TG_MAX = 2000000000;
	
	/**
	 * 威名最大值
	 */
	const WM_MAX = 2000000000;
	
	/**
	 * 名望上限
	 */
	const FAME_MAX = 2000000000;
	
	/**
	 * 书上限
	 */
	const BOOK_MAX = 2000000000;
	
	/**
	 * 战魂经验上限
	 */
	const FSEXP_MAX = 2000000000;
	
	/**
	 * 武将精华上限
	 */
	const JH_MAX = 2000000000;
	
	/**
	 * 兵符积分上限
	 */
	const TALLYPOINT_MAX = 2000000000;
	
	/**
	 * 使用道具获得金币增加的vip经验上限
	 */
	const USE_ITEM_GOLD_MAX = 2000000000;
	
	/**
	 * 试炼币上限
	 */
	const TOWER_NUM_MAX = 2000000000;
	
	/**
	 * 保留的pid的最大值,
	 */
	const PID_MAX_RETAIN = 10;

	/**
	 * 保存每天消耗金币数记录的个数
	 */
	const SPEND_GOLD_DATE_NUM = 999;
	
	/**
	 * 保存每天消耗体力数记录的个数
	 */
	const SPEND_EXECUTION_DATE_NUM = 10;
	
	/**
	 * 保存每天消耗耐力数记录的个数
	 */
	const SPEND_STAMINA_DATE_NUM = 10;

	/**
	 * session有效时间 user.login检查
	 */
	const LOGIN_DIFF_TIME = 900;

	/**
	 * 服务器人数限制
	 */
	const MAX_ONLINE_USER = 8000;
	
	/**
	 * 排行榜最大数量
	 * @var unknown_type
	 */
	const MAX_TOP = 100;
	
	/**
	 * 封号信息的最大长度
	 */
	const BAN_MSG_MAX_LEN = 30;
	
	/**
	 * 前端配置数组最大值
	 */
	const VA_CONFIG_SIZE = 10;
	
	/**
	 * 前端arr配置数组最大值
	 */
	const ARR_CONFIG_SIZE = 50;
	
	/**
	 * 每升一级获得金币数目
	 */
	const PRE_LEVEL_UP_GET_GOLD    =    10;
	
	/**
	 * 
	 * @var unknown_type
	 */
	const NOTIFY_START_LEVEL = 25;
	
	const LEVEL_UP_ADD_STAMINA = 20;
	
	const MAX_FLOP_NUM = 1000;
	/**
	 * 每天首次分享奖励金币
	 */
	const FIRST_SHARE_GETGOLD = 100;
	/**
	 * 每天的首次分享奖励银币
	 */
	const DAILY_FIRST_SHARE_GETSILVER = 1000;
	/**
	 * 每天的首次分享奖励体力
	 */
	const DAILY_FIRST_SHARE_EXECUTION = 10;
	/**
	 * 创建用户时，给的初始武将
	 * @var unknown_type
	 */
	static $INIT_ARR_HTID = array(
			10092 => 1,//3星
			30001 => 3,//1星
	);
	
	
	static $STAMINA_RECOVER = array(
			'100000' => 20,
			'120000' => 20,
			'220000' => 100,
			);
	
	/**
	 * 用户创建时各个模块希望被调用的初始化函数
	 * 函数形式： func( $uid, $userAttr )
	 * @var array
	 */
	static $CREATE_USER_FUNC_LIST = array(
			'EnFormation::initFormation',
	        'EnUser::initUserExtra',
			);
	/**
	 * 玩家登录时，各个模块希望被调用的函数
	 * 函数形式： func( $uid, $utid, $uname )
	 * @var array
	 */
	static $LOGIN_FUNC_LIST = array(
			//在线奖励计时开始
			'OnlineLogic::login',
			//登陆即算签到
			'AccsignLogic::getSignInfo',
			//活动累计签到
			'EnSignactivity::signForSignActivity',
			//活动欢乐签到
			'EnHappySign::updateSignTime',
	        //登录时，判断是否有新的功能节点开启
	        'EnSwitch::checkSwitch',
			//好友登陆通知
			'EnFriend::loginNotify',
			//军团用户登录通知
			'EnGuild::loginNotify',
			//城池战用户登录通知
			'CityWarLogic::loginNotify',
	        //竞技场用户登录后遍历发放排名奖励
			'EnArena::sendRankReward',  
			//精英回归
			'Regress::oldUserBonus',
			//合服登陆通知
			'EnMergeServer::loginNotify',
			//新服活动("开服7天乐")累计登陆任务
			'EnNewServerActivity::updateAccSign',
			);
	
	/**
	 * 玩家登陆时，延迟调用的的函数
	 * 函数形式 func($uid)
	 * @var array()
	 */
	static $LOGIN_DELAY_CALL_FUNC_LIST = array(
			//月卡  登陆时将奖励发到奖励中心
			'EnMonthlyCard::loginToGetReward',
			//充值抽奖   登陆时将奖励发到奖励中心
			'EnChargeRaffle::loginToGetReward',
            'EnTopupReward::loginToGetReward',
			'EnCountryWar::loginToGetReward',
	);
	
	/**
	 * 玩家下线时，各个模块希望被调用的函数
	 * 函数形式： func( $uid, $utid, $uname )
	 * @var array
	 */
	static $LOGOFF_FUNC_LIST = array(
			//在线奖励计时结束
			'OnlineLogic::logoff',
			//好友下线通知
			'EnFriend::logoffNotify',
			'CityWarLogic::logoffNotify',
			'GuildRobLogic::logoffNotify',
			);
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
