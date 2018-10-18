<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPass.cfg.php 179316 2015-06-16 05:20:33Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/WorldPass.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-16 05:20:33 +0000 (Tue, 16 Jun 2015) $
 * @version $Revision: 179316 $
 * @brief 
 *  
 **/
 
class WorldPassConf
{
	const	STAGE_COUNT				= 6;					// 关卡数量
	const	CHOICE_COUNT			= 5;					// 备选武将的个数			
	const	INNER_RANK_LIST_COUNT	= 50;					// 服内排行榜数目，最好别超过100
	const 	CROSS_RANK_LIST_COUNT	= 100;					// 跨服排行榜数目，最好别超过100
	const 	CROSS_REWARD_COUNT		= 20000;				// 排行奖励人数
	const 	HELL_POINT_MAX 			= 2000000000;			// 炼狱积分上限
	const 	WORLD_PASS_DB_PREFIX 	= 'pirate_worldpass_';	// 跨服db名称
	
	public static 	$TEST_MODE		= 0;					// 线上应该为0,1或者2是测试模式，会缩短整个活动周期，如果为1，则是奇数小时闯关，偶数小时发奖，如果为2相反
	public static   $MY_SWITCH		= TRUE;					// 为TRUE的话正常接收用户请求，为FALSE的话抛弃用户请求
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */