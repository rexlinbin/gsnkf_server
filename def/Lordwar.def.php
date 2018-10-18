<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Lordwar.def.php 171762 2015-05-08 03:00:05Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Lordwar.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-05-08 03:00:05 +0000 (Fri, 08 May 2015) $
 * @version $Revision: 171762 $
 * @brief 
 *  
 **/
class LordwarField
{
	const BLANK = 'blank';
	const INNER = 'inner';
	const CROSS = 'cross';
	
}

class LordwarRound
{
	const OUT_RANGE = 0;//不在活动期间
	const BLANK = 1;//活动期间的非round时间
	
	//所有的round
	const REGISTER = 2;
	
	const INNER_AUDITION = 3;
	const INNER_32TO16 = 4;
	const INNER_16TO8 = 5;
	const INNER_8TO4 = 6;
	const INNER_4TO2 = 7;
	const INNER_2TO1 = 8;
	
	const CROSS_AUDITION = 9;
	const CROSS_32TO16 = 10;
	const CROSS_16TO8 = 11;
	const CROSS_8TO4 = 12;
	const CROSS_4TO2 = 13;
	const CROSS_2TO1 = 14;
	
	static $INNER_PROMO = array(
			self::INNER_32TO16,
			self::INNER_16TO8,
			self::INNER_8TO4,
			self::INNER_4TO2,
			self::INNER_2TO1,
	);
	static $CROSS_PROMO = array(
			self::CROSS_32TO16,
			self::CROSS_16TO8,
			self::CROSS_8TO4,
			self::CROSS_4TO2,
			self::CROSS_2TO1,
	);
	
	static $INNER_ROUND = array(
			self::REGISTER,
			self::INNER_AUDITION,
			self::INNER_32TO16,
			self::INNER_16TO8,
			self::INNER_8TO4,
			self::INNER_4TO2,
			self::INNER_2TO1,
	);
	
	static $CROSS_ROUND = array(
			self::CROSS_AUDITION,
			self::CROSS_32TO16,
			self::CROSS_16TO8,
			self::CROSS_8TO4,
			self::CROSS_4TO2,
			self::CROSS_2TO1,
	);
	
	static $ROUND_RET_NUM = array(
		
			self::INNER_AUDITION => 32,
			self::INNER_32TO16 => 16,
			self::INNER_16TO8 => 8,
			self::INNER_8TO4 => 4,
			self::INNER_4TO2 => 2,
			self::INNER_2TO1 => 1,
	
			self::CROSS_AUDITION => 32,
			self::CROSS_32TO16 => 16,
			self::CROSS_16TO8 => 8,
			self::CROSS_8TO4 => 4,
			self::CROSS_4TO2 => 2,
			self::CROSS_2TO1 => 1,
	);
}

class LordwarStatus
{
	const NO = 0;
	const PREPARE = 10;
	const WAIT_TIME_END = 11;
	const FIGHTING = 20;
	const FIGHTEND = 30;
	const REWARDEND = 40;
	const DONE = 100;
	
}

class LordwarTeamType
{
	const NO = 0;
	const WIN = 1;
	const LOSE = 2;
	
	public static $TEAM_TYPE_ALL = array(self::WIN, self::LOSE);
}

class LordwarDef
{
	const LDW_DB_PREFIX = 'pirate_lordwar_';
	
	const BTL_RET_WIN = 0;
	
	const BTL_RET_LOSE = 1;
	
	const MAX_CACHE_USER_NUM = 256;
}

class LordwarKey
{
	const TIMEARR = 'timeArr';
	
	const REGISTER_START = 'registerStart';
	const REGISTER_END	= 'registerEnd';
	
	const SESS = 'sess';
	
	const FIELD_LINK = '#';
}

class LordwarReward
{
	const SUPPORT = 1;
	const RPOMOTION = 2;
	const WHOLEWORLD = 3;
}

class LordwarPush
{
	const NOW_STATUS = 1; //状态变化
	
	const NEW_REWARD = 2; //发完奖，有新奖励
	
	const NEW_MAIL = 3; //邮件提醒
}

class LwShop
{
	const WM  = 'wm';
	const NEEDWM = 'needWm';
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */