<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRob.cfg.php 195257 2015-08-28 08:27:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/GuildRob.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-28 08:27:26 +0000 (Fri, 28 Aug 2015) $
 * @version $Revision: 195257 $
 * @brief 
 *  
 **/
 
class GuildRobConf
{
	const MAX_SPEC_BARN_NUMBER             			= 2; 	 // 牛掰粮仓的个数
	const ROAD_NUM                         			= 3; 	 // 通道条数
	const ROB_BATTLE_BEGIN_OFFSET          			= 1;     // 前段创建抢粮战后，通知后端开始的时间偏移量
	const SPEC_BARN_MAX_NUM				   			= 2;     // 蹲点粮仓的个数
	const SPEEDUP_MAX_NUM_PER_JOIN		   			= 1;     // 每次在通道上的时候，最多可以加速多少次
	const KILL_NUM_TOP_N				   			= 10;    // 击杀排行榜人数
	const ROB_AREA_NUM 					   			= 5;     // 抢粮军团列表中，一个区域显示的军团个数
	const GUILD_ROB_END_CHECK_OFFSET       			= 10;    // 抢粮战结束的最晚时间过后，再过多长时间去检查一下
	const BROADCAST_CHANCE			     			= 3000;  // 广播击杀排行榜的概率
	const TEST_MODE = 0;	 								 // 大于0为测试模式，0为正常模式，线上必须为0
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */