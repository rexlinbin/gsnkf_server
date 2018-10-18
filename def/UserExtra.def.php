<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserExtra.def.php 251028 2016-07-11 10:26:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/UserExtra.def.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-11 10:26:26 +0000 (Mon, 11 Jul 2016) $
 * @version $Revision: 251028 $
 * @brief 
 *  
 **/
class UserExtraDef
{
	/*INVALID KEY const USER_EXTRA_KEY_UID = 'uid'*/
	
    const USER_EXTRA_FIELD_UID = 'uid';
    const USER_EXTRA_FIELD_EXECUTION_TIME = 'execution_time';
    const USER_EXTRA_FIELD_SHARE_TIME = 'last_share_time';
    const USER_EXTRA_FIELD_OPEN_GOLD_NUM = 'open_gold_num';
    const USER_EXTRA_FIELD_VA_USER = 'va_user';
    
    
    /*  fields these are in va      */
	const SPEND_REWARD 		= 		'spendReward';
	
	const LEVELUP_REWARD	=		'levelupReward';
	
	const TOPUP_REWARD		=		'topupReward';	
	
	const BUYNUMS        =    'buyNums';
	
	const BUYNUM_RFRTIME = 'buyNumRfrTime';
	//各种购买攻击次数
	const ECOPY_BUYNUM     =    'ecopyBuyNum';
	
	const TEAMCOPY_BUYNUM    =    'teamcopyBuyNum';
	
	const TOWER_BUYNUM    =    'towerBuyNum';
	
	const GOLDTREE_BUYNUM    =    'goldtreeBuyNum';
	
	const EXPTREAS_BUYNUM    =    'exptreasBuyNum';

    const STEP_COUNTER_TIME  =   'stepCounterTime';  //领取计步活动奖励时间
    
    const SYS_REWARD_INFO = 'sysRewardInfo';  // web端发系统奖励的个数信息
    
    const SPECIAL = 'special'; 
	
	public static $VALID_FIELD = array(
	        self::USER_EXTRA_FIELD_EXECUTION_TIME=>true,
	        self::USER_EXTRA_FIELD_SHARE_TIME=>true,
	        self::USER_EXTRA_FIELD_OPEN_GOLD_NUM => true,
	        
	        );
	public static $ARR_FIELD = array(
			self::USER_EXTRA_FIELD_UID,
			self::USER_EXTRA_FIELD_EXECUTION_TIME,
	        self::USER_EXTRA_FIELD_SHARE_TIME,
	        self::USER_EXTRA_FIELD_OPEN_GOLD_NUM,
			self::USER_EXTRA_FIELD_VA_USER,
			);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */