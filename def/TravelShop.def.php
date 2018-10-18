<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShop.def.php 198408 2015-09-14 08:31:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/TravelShop.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-14 08:31:03 +0000 (Mon, 14 Sep 2015) $
 * @version $Revision: 198408 $
 * @brief 
 *  
 **/
class TravelShopDef
{	
	const LOCK_KEY = 'travel.shop';
	const TASK_NAME = 'travelshop.reward';
	
	const SCORE_LIMIT = 100;
	const REWARD_TIME = 3600;
	const DELAY = 5;
	const NOGAIN = 0;
	const GAIN = 1;
	
	//配置表字段
	const DAYS = 'days';
	const GOODS = 'goods';
	const LIMIT = 'limit';
	const COST = 'cost';
	const SCORE = 'score';
	const PAYBACK = 'payback';
	const REWARD = 'reward';
	const DEADLINE = 'deadline';
	const ALL = 'all';
	const BUY = 'buy';
	
	//SQL表名
	const TBL_TRAVEL_SHOP = 't_travel_shop';
	//SQL：字段
	const FIELD_ID = 'id';
	const FIELD_SUM = 'sum';
	const FIELD_REFRESH_TIME = 'refresh_time';
	//SQL：表字段
	public static $TBL_TS_FIELDS = array(
			self::FIELD_ID,
			self::FIELD_SUM,
			self::FIELD_REFRESH_TIME,
	);
	
	//SQL表名
	const TBL_TRAVEL_SHOP_USER = 't_travel_shop_user';
	//SQL：字段
	const FIELD_UID = 'uid';
	const FIELD_SCORE = 'score';
	const FIELD_START_TIME = 'start_time';
	const FIELD_FINISH_TIME = 'finish_time';
	const FIELD_VA_USER = 'va_user';
	//SQL：表字段
	public static $TBL_TSU_FIELDS = array(
			self::FIELD_UID,
			self::FIELD_SUM,
			self::FIELD_SCORE,
			self::FIELD_START_TIME,
			self::FIELD_FINISH_TIME,
			self::FIELD_REFRESH_TIME,
			self::FIELD_VA_USER,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */