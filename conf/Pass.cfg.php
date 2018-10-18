<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pass.cfg.php 232209 2016-03-11 06:12:24Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Pass.cfg.php $
 * @author $Author: DuoLi $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-11 06:12:24 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232209 $
 * @brief 
 *  
 **/
class PassCfg
{
	static $initInfo = array(
			'refresh_time' => 0,
			'luxurybox_num' => 0,
			'cur_base' => 0,
			'reach_time' => 0,
			'pass_num' => 0,
			'point' => 0,
			'star_star' => 0,
			'coin' => 0,
			'reward_time' => 0,
			'lose_num' => 0,
			'buy_num' => 0,
			'va_pass' => array(),
	);
	
	const MAX_HERO_NUM = 6;
	const FULL_PERCENT = 10000000;
	
	const CONF_BASE = 10000;
	
	const REWARD_MAX_RANK = 10000;
	
	const DEGREE_SIMPLE = 1;
	const DEGREE_NOMAL = 2;
	const DEGREE_HARD = 3;
	
	const HANDSOFF_LASTTIME = 3600;
	const HANDSOFF_BEGINTIME = "040000";
	
	const SLEEP_COUNT = 10;
	const USECONDS = 20;
	
	const VICE_NUM = 2 ;
	
	// 扫荡获得评价
	const SWEEP_STAR = 3;
	// 扫荡获得积分
	const SWEEP_POINT = 2.5;
	// 可扫荡关卡数与上次通关数的的比例
	const SWEEP_RATIO = 0.7;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */