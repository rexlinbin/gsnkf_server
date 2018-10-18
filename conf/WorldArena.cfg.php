<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArena.cfg.php 207536 2015-11-05 08:53:59Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/WorldArena.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-11-05 08:53:59 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207536 $
 * @brief 
 *  
 **/
 
class WorldArenaConf
{
	const 	WORLD_ARENA_DB_PREFIX 	= 'pirate_worldarena_';	// 跨服db名称
	const 	PLAYER_NUM_EVERY_PAGE	= 4;					// 每次拉取对手信息需要拉取的人数，包括自己
	const	USER_RECORD_COUNT		= 20;					// 我的战报的条数，不能超过100
	const   CONTI_RECORD_COUNT		= 20;					// 同一个房间连杀战报的条数，不能超过100
	const	POS_RANK_MAX_COUNT		= 100;					// 位置排行榜最大人数，不能超过100
	const 	KILL_RANK_MAX_COUNT		= 100;					// 总击杀排行榜最大人数，不能超过100
	const 	CONTI_RANK_MAX_COUNT	= 100;					// 连续击杀排行榜最大人数，不能超过100
	const	POS_REWARD_MAX_COUNT	= 1000;					// 位置排行奖励最大人数
	const 	KILL_REWARD_MAX_COUNT	= 1000;					// 总击杀排行奖励最大人数
	const 	CONTI_REWARD_MAX_COUNT	= 1000;					// 连续击杀排行奖励最大人数
	const 	MAX_MISS_USER_PER_TEAM  = 10;					// 允许的每个组没有正确分配房间的人数上限
	const   CD_DURATION				= 600;					// 结束前有一段时间攻打后会有cd，例如结束前10分钟
	
	public static 	$TEST_MODE		= 0;					// 线上应该为0,1或者2是测试模式，会缩短整个活动周期，如果为1，则是奇数小时闯关，偶数小时发奖，如果为2相反
	public static   $TEST_OFFSET	= array(300,600,900,6900);
	public static   $MY_SWITCH		= TRUE;					// 为TRUE的话正常接收用户请求，为FALSE的话抛弃用户请求
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */