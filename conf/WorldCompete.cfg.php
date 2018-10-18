<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompete.cfg.php 240520 2016-04-28 02:58:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/WorldCompete.cfg.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-04-28 02:58:53 +0000 (Thu, 28 Apr 2016) $
 * @version $Revision: 240520 $
 * @brief 
 *  
 **/
 
class WorldCompeteConf
{
	const 	RIVAL_COUNT				= 3;						// 对手数量
	const 	WORSHIP_LIMIT           = 2;						// 膜拜次数上限
	const   ARENA_RANGE				= 20;						// 竞技场排名范围
	const	INNER_RANK_LIST_COUNT	= 50;						// 服内排行榜数目，最好别超过100
	const 	CROSS_RANK_LIST_COUNT	= 100;						// 跨服排行榜数目，最好别超过100
	const 	CROSS_REWARD_COUNT		= 20000;					// 排行奖励人数
	const 	CROSS_HONOR_MAX 		= 2000000000;				// 跨服荣誉上限
	const 	WORLD_COMPETE_DB_PREFIX = 'pirate_worldcompete_';	// 跨服db名称
	
	public static   $OPEN			= 1;						// 是否开启这个功能
	public static 	$TEST_MODE		= 0;						// 线上应该为0,1或者2是测试模式，会缩短整个活动周期，如果为1，则是奇数小时闯关，偶数小时发奖，如果为2相反
	//目前测试周期是两个小时，一个半小时比武，二十五分钟发奖，五分钟分组
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */