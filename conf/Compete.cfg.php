<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Compete.cfg.php 153622 2015-01-20 03:17:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Compete.cfg.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-01-20 03:17:41 +0000 (Tue, 20 Jan 2015) $
 * @version $Revision: 153622 $
 * @brief 
 *  
 **/
class CompeteConf
{
	//常量
	const COMPETE_RIVAL_NUM = 3;									// 比武对手数量, 必须小于机器人数量
	const COMPETE_TOP_TEN = 10;										// 比武排行榜前十
	const COMPETE_TOP_THREE = 3;									// 比武排行榜前三
	const COMPETE_START_TIME = "08:00:00";							// 比武开始时间
	const COMPETE_END_TIME = "23:00:00";							// 比武结束时间
	const REWARD_START_TIME = "23:00:00";							// 发奖开始时间
	const REWARD_END_TIME = "00:55:00";								// 发奖结束时间
	const NUM_OF_REWARD_PER = 10;
	const SLEEP_MTIME = 50;
	const OFFSET_TIME = 60;
	const REWARD_NUM = 10000;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */