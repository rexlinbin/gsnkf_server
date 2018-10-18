<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Roulette.def.php 175558 2015-05-29 03:37:33Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Roulette.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-29 03:37:33 +0000 (Fri, 29 May 2015) $
 * @version $Revision: 175558 $
 * @brief 
 *  
 **/
class RouletteDef
{
	const BTSTORE_ROULETTE_NEED_GOLD = 'roulette_need_gold';
	const BTSTORE_ROULETTE_INTEGERAL = 'roulette_integeral';
	const BTSTORE_ROULETTE_REWARD = 'roulette_reward';
	const BTSTORE_ROULETTE_TOTAL_NUM = 'rouletteTotalNum';
	const BTSTORE_ROULETTE_FREE_NUM = 'rouletteFreeNum';
	const BTSTORE_ROULETTE_FIELD_WEIGHT = 'refresh_weight';
	const BTSTORE_ROULETTE_ACCUM_DROP = 'accum_drop';
	const BTSTORE_ROULETTE_ROLL_DAY = 'roll_day';
	const BTSTORE_ROULETTE_MIN_POINT = 'min_point';
	const BTSTORE_ROULETTE_RANK_REWARD = 'rank_reward';
	
	const SQL_FIELD_UID = 'uid';
	const SQL_TODAY_FREE_NUM = 'today_free_num';
	const SQL_ACCUM_FREE_NUM = 'accum_free_num';
	const SQL_ACCUM_GOLD_NUM = 'accum_gold_num';
	const SQL_ACHIEVE_INTEGERAL = 'integeral';
	const SQL_LAST_RFR_TIME = 'last_refresh_time';
	const SQL_VA_BOX_REWARD = 'va_boxreward';
	const SQL_LAST_ROLL_TIME = 'last_roll_time'; 
	const SQL_IS_RANK_REWARDED = 'isRankRewarded';
	
	static $ALL_TABLE_FIELD = array(
			self::SQL_FIELD_UID,
			self::SQL_TODAY_FREE_NUM,
			self::SQL_ACCUM_FREE_NUM,
			self::SQL_ACCUM_GOLD_NUM,
			self::SQL_ACHIEVE_INTEGERAL,
			self::SQL_LAST_RFR_TIME,
			self::SQL_VA_BOX_REWARD,
			self::SQL_LAST_ROLL_TIME,
	);
	
	const NOT_RECEIVED = 0;   //未领取奖励
	const HAS_RECEIVED = 1;   //已领取奖励
	
	const RANK_LIST_NUM = 20; //排行榜人数
	public static $rank_level = array(
			1,2,3,10,20
	);
	
	const REWARD_BF_CLOSE = 3600; //距离活动结束一小时为全服发奖(已改)
	const REWARD_HOUE_AF_ROLL_END = 3; //抽奖结束后第几个小时发奖
	
	const MAX_MERGE_SERVER = 5;//排行榜支持合服最大数量
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */