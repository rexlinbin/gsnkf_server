<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLottery.def.php 254875 2016-08-05 10:28:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/SevensLottery.def.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-08-05 10:28:59 +0000 (Fri, 05 Aug 2016) $
 * @version $Revision: 254875 $
 * @brief 
 *  
 **/
class SevensLotteryDef
{
	const SEVENS_LOTTERY_START = '20150105 00:00:00';
	
	//配置表
	const LOTTERY_ID = 'lottery_id';
	const LOTTERY_LIMIT = 'lottery_limit';
	const LOTTERY_COST = 'lottery_cost';
	const LOTTERY_POINT = 'lottery_point';
	const LUCKY_MAX	= 'lucky_max';
	const LUCKY_RANGE = 'lucky_range';
	const PERIOD_TIME = 'period_time';
	const LOTTERY_DROP = 'lottery_drop';
	const LUCKY_REWARD = 'lucky_reward';
	const LOTTERY_ITEM = 'lottery_item';
	
	//sql
	const TBL_SEVENS_LOTTERY = 't_sevens_lottery';
	
	const FIELD_UID = 'uid';
	const FIELD_NUM = 'num';
	const FIELD_POINT = 'point';
	const FIELD_LUCKY = 'lucky';
	const FIELD_FREE_TIME = 'free_time';
	const FIELD_REFRESH_TIME = 'refresh_time';
	 
	//SQL：表字段
	public static $TBL_SEVENS_LOTTERY_FIELDS = array(
			self::FIELD_UID,
			self::FIELD_NUM,
			self::FIELD_POINT,
			self::FIELD_LUCKY,
			self::FIELD_FREE_TIME,
			self::FIELD_REFRESH_TIME,
	);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */