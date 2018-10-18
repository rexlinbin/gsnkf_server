<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Bowl.def.php 153602 2015-01-20 02:06:58Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Bowl.def.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-20 02:06:58 +0000 (Tue, 20 Jan 2015) $
 * @version $Revision: 153602 $
 * @brief 
 *  
 **/
 
class BowlDef
{
	const BOWL_BUY_DAYS = 'buy_days';
	const BOWL_REWARD_DAYS = 'reward_days';
	const BOWL_BUY_NEED = 'need';
	const BOWL_BUY_COST = 'cost';
	const BOWL_BUY_REWARD = 'reward';
	
	const BOWL_REWARD_STATE_EMPTY = 0;	// 没奖
	const BOWL_REWARD_STATE_HAVE = 1;	// 有奖
	const BOWL_REWARD_STATE_RECEIVED = 2; //已领
	
	/**
	 * t_bowl表字段
	 */
	const TBL_FIELD_UID 					= 'uid';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_FIELD_BOWLTIME       = 'btime';
	const TBL_VA_EXTRA_FIELD_REWARD		 	= 'reward';
	
	public static $BOWL_ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class BowlType
{
	const COPPER = 1;
	const SILVER = 2;
	const GOLD   = 3;
	
	public static $ALL_TYPE = array
	(
			self::COPPER,
			self::SILVER,
			self::GOLD,
	);
}

class BowlStage
{
	const BOWL = 1;
	const NO_BOWL = 2;
}

class BowlState
{
	const CAN_NOT_BUY = 1;
	const CAN_BUY = 2;
	const ALREADY_BUY = 3;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */