<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalAct.def.php 248346 2016-06-27 09:42:23Z LeiZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/FestivalAct.def.php $
 * @author $Author: LeiZhang $(zhengguohao@babeltime.com)
 * @date $Date: 2016-06-27 09:42:23 +0000 (Mon, 27 Jun 2016) $
 * @version $Revision: 248346 $
 * @brief 
 *  
 **/
class FestivalActDef
{
	// 解析配置
	const ID = 'id';
	const START_TIME = 'start';
	const END_TIME = 'end';
	const MISSION = 'mission';

	// 奖励解析配置
	const MID = 'mid';
	const BIGTYPE = 'big_type';
	const TYPE_ID = 'type_id';
	const NEED = 'need';
	const GET = 'get';
	const NUM = 'num';
	const DAY_RESET = 'day_reset';

	// 数据库中字段
	const UID = 'uid';
	const UPDATE_TIME = 'update_time';
	const VA_DATA = 'va_data';
	const VA_PERIOD = 'period';
	const VA_EXCHANGE = 'exchange';

	// 活动类型
	const ACT_TYPE_TASK = 1;       // 完成任务
	const ACT_TYPE_DISCOUNT = 2;   // 折扣物品
	const ACT_TYPE_EXCHANGE = 3;   // 兑换
	const ACT_TYPE_CHARGE = 4;     // 充值领奖

	// 完成任务的分类
	const  TASK_LOGIN = 101;           // 登陆任务
	const  TASK_COPY_ANY_NUM   = 102;  // 成功攻打任意副本N次
	const  TASK_COPY_ELITE_NUM = 103;  // 成功攻打精英副本N次
	const  TASK_FRAG_NUM       = 104;  // 夺宝N次
	const  TASK_COMPETE_NUM    = 105;  // 比武攻打N次
	const  TASK_WORLD_GROUPON_POINT = 106; // 跨服团购积分达到M
	const  TASK_SCORE_WHEEL_POINT  = 107;  // 积分轮盘积分达到M
	const  TASK_BLACK_SHOP_EXCHARGE_NUM = 108;  // 黑市兑换兑换次数达N次
	const  TASK_TRAVEL_SHOP_BUY_NUM = 109; // 云游商人购买次数达到N次
	const  TASK_ARENA_NUM = 111; // 竞技场攻打次数N

	// 任务的状态类型
	const STATUS_UNFINISH = 0;           // 任务未完成
	const STATUS_FINISH = 1;             // 任务已完成还未领奖
	const STATUS_REWARD = 2;             // 任务已经领奖

	// 补签需要的金币
	const SIGN_FILL_IN_NEED    = 20;   // 补签需要的金币数

	// 预留一天的领奖时间
	const REWARD_TIME = SECONDS_OF_DAY;
	// 测试预留领奖时间
	const TEST_REWARD_TIME = 300;

	public static $ALL_TABLE_FIELD = array(
			self::UID,
			self::UPDATE_TIME,
			self::VA_DATA
	);

	// 活动sess，供其他任务通知时使用
	const SESSI	= 'a.curActivity';
	// sess有效时间
	const VALIDITY_SECONDS = 1800;
}

class FestivalActSessionField
{
    const SET_TIME = 'upToTime';
    const START_TIME = 'startTime';
    const END_TIME = 'endTime';
    const LITTLE_DATA = 'littleData';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
