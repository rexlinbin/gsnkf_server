<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWar.cfg.php 227968 2016-02-17 13:34:58Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/CountryWar.cfg.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-02-17 13:34:58 +0000 (Wed, 17 Feb 2016) $
 * @version $Revision: 227968 $
 * @brief 
 *  
 **/
class CountryWarConf
{
	const BEGIN_REWARD_OFFSET			= 600;	// 决赛结束后，多长时间触发发奖。
	const UNTEAMED 						= -1;
	const ZK_PATH_LC 					= '/card/lcserver/lcserver#';
	const ROOM_MAX 						= 1000;
	const BATTLE_MAX 					= 10;
	const SPACE_LEFT_PERCENT			= 2000;
	const SERIAL_EXPIREDTIME 			= 5;
	const RANK_MEM_EXPIRETIME 			= SECONDS_OF_DAY;
	const TOKEN_EXPIREDTIME 			= 20; 
	const FIRST_ROOM_ID 				= 1;
	const RANK_LIST_VALID_TIME 			= 3;
	const NEED_CREATE_ROOM_RANGE 		= 1;
	const DEFAULT_RECOVER_PERCENT 		= 3000;
	const AUTO_RECOVER_ON 				= 1;
	const AUTO_RECOVER_OFF 				= 2;
	const SIDE_A 						= 1;
	const SIDE_B 						= 2;
	const INSPIRE_ATK = 1;
	const INSPIRE_DED = 2;
	const NOSIDEWIN = 0;
	const ONLINE_TEST = false;
	
	static $ONLINE_TEST_VALID_PIDARR = array(
		
	);
	
	static $BOTH_SIDE = array(
			
			self::SIDE_A,
			self::SIDE_B,
	);
	
	static $TRANSFERARR = array(
			
			self::SIDE_A => array(0,1,2,3),
			self::SIDE_B => array(4,5,6,7),
	);
	
	static $ALL_INPIRE_TYPE = array(
		
			self::INSPIRE_ATK,
			self::INSPIRE_DED,
	);
	
}

class CountryWarRankType
{
	const AUDITION 						= 'audition';
	const FINALTION 					= 'finaltion';
	const SUPPORT 						= 'support';
	
	static $ALL = array(
			self::AUDITION,
			self::FINALTION,
			self::SUPPORT,
	);
}

class CountryWarStage
{
	const TEAM 							= 'team';
	const SINGUP 						= 'signup';
	const RANGE_ROOM 					= 'rangeRoom';
	const AUDITION 						= 'audition';
	const SUPPORT 						= 'support';
	const FINALTION 					= 'finaltion';
	const WORSHIP 						= 'worship';
	
	const VERY_BEGIN_WEEKDAY 			= 7;					//1-7
	const VERY_BEGIN_TIME 				= '18:00:00';			//00:00:00-23:59:59
	static $ALL_STAGE = array(
																//周五
			self::TEAM 			=> array( 0, 0 ),				//18:00:00开始分组,持续60分钟  			必须为0 
			self::SINGUP 		=> array( 0, 3600 ),			//19:00:00开始报名,持续5分钟  			线下用的时候至少与前一个阶段相差70秒 
			self::RANGE_ROOM 	=> array( 0, 3900 ),			//19:05:00开始分房,持续5分钟
			self::AUDITION 		=> array( 0, 4200 ),			//19:10:00开始初赛,持续时间10分30秒  		线下用的时候至少与前一个阶段相差70秒
			self::SUPPORT 		=> array( 0, 4830 ),			//19:20:30开始助威,持续3分钟 					
			self::FINALTION 	=> array( 0, 5010 ),			//19:23:30开始决赛,持续10分30秒  					
			self::WORSHIP 		=> array( 0, 5640 ),			//19:34:00开始助威,持续至下次开始
	);
	
}

class CountryWarScene
{
	const INNER 						= 'inner';
	const CROSS 						= 'cross';
}

class CountryWarCountryId
{
	//要和hero表中的國家對應
	const WEI 							= 1;
	const SHU 							= 2;
	const WU 							= 3;
	const QUN 							= 4;
	 
	static $ALL = array(
			self::WEI,
			self::SHU,
			self::WU,
			self::QUN,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
