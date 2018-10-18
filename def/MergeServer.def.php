<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(mengbaoguo@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
 * Class       : MergeServerDef
 * Description : 合服活动数据常量类
 * Inherit     : 
 **********************************************************************************************************************/
class MergeServerDef
{
	const MSERVER_TYPE_LOGIN = 1;					    // 连续登陆
	const MSERVER_TYPE_RECHARGE = 2;				    // 充值返还
	const MSERVER_TYPE_EXP_GOLD = 3;				    // 经验宝物摇钱树
	const MSERVER_TYPE_ARENA = 4;				        // 竞技场 
	const MSERVER_TYPE_MONTH_CARD = 5;				    // 月卡大礼包 
	const MSERVER_TYPE_COMPENSATION = 6;                // 合服补偿
	const MSERVER_TYPE_MINERAL = 7;						// 资源矿产量提升

	const MSERVER_BASE_RATE = 1;                        // 合服基础倍率 
	const MSERVER_ARENA_PRESTIGE_RATE = 2;              // 合服竞技场声望奖励倍率
	
	/**
	 * t_mergeserver_reward表字段
	 */
	const TBL_FIELD_UID 					= 'uid';
	const TBL_FIELD_COMPENSATE_TIME 		= 'compensate_time';
	const TBL_FIELD_LOGIN_TIME 				= 'login_time';
	const TBL_FIELD_LOGIN_COUNT 			= 'login_count';
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_FIELD_LOGIN_GOT  	= 'login_reward_got';
	const TBL_VA_EXTRA_FIELD_RECHARGE_GOT  	= 'recharge_reward_got';
	
	public static $MERGESERVER_ALL_FIELDS = array(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_COMPENSATE_TIME,
			self::TBL_FIELD_LOGIN_TIME,
			self::TBL_FIELD_LOGIN_COUNT,
			self::TBL_FIELD_VA_EXTRA,
			);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */