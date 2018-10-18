<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRob.def.php 259124 2016-08-29 09:39:11Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/GuildRob.def.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-29 09:39:11 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259124 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobDef
* Description : 军团抢粮战数据常量类
* Inherit     :
**********************************************************************************************************************/
class GuildRobDef
{
	const GUILD_STATE_IN_ROB			   = 1;		// 军团在抢粮战中
	const GUILD_STATE_NOT_IN_ROB		   = 0;		// 军团不在抢粮战中
	
	const GUILD_ROB_NOT_IN_BATTLE_FIELD    = 0;     // 不在通道也不在传送阵
	const GUILD_ROB_IN_BATTLE_TRANSFER	   = 1;     // 在传送阵
	const GUILD_ROB_IN_BATTLE_ROAD		   = 2;     // 在通道上
	
	const SESSION_LEAVE_BATTLE_TIME = 'groupBattle.leaveBattleTime';
	const SESSION_QUIT_BATTLE_TIME = 'groupBattle.quitBattleTime';
	const SESSION_GROUP_BATTLE_ID = 'global.groupBattleId';
	const SESSION_SPEED_UP_TIMES = 'groupBattle.speedUpTimes';
	
	const GUILD_ROB_OFFLINE_TYPE_CONFIM = 1;   // 勾选离线
	const GUILD_ROB_OFFLINE_TYPE_CANCEL = 2;   // 取消离线
}

class GuildRobOperationType
{
	const GUILD_ROB_OPERATION_TYPE_CREATE = 1;
	const GUILD_ROB_OPERATION_TYPE_ENTER = 2;
	const GUILD_ROB_OPERATION_TYPE_GET_ENTER_INFO = 3;
	const GUILD_ROB_OPERATION_TYPE_JOIN = 4;
	const GUILD_ROB_OPERATION_TYPE_REMOVE_JOIN_CD = 5;
	const GUILD_ROB_OPERATION_TYPE_SPEEDUP = 6;
	const GUILD_ROB_OPERATION_TYPE_ENTER_SPEC_BARN = 7;
}

class GuildRobBasicState
{
	const GUILD_ROB_BASIC_STATE_OK = 'ok';
	const GUILD_ROB_BASIC_STATE_NOT_IN_GUILD = 'not_in_guild';
	const GUILD_ROB_BASIC_STATE_NOT_IN_EFFECT_TIME = 'not_in_effect_time';
	const GUILD_ROB_BASIC_STATE_NOT_HAVE_RIGHT = 'do_not_have_right';
}

class GuildRobCreateRet
{
	const GUILD_ROB_CREATE_RET_OK = 'ok';
	const GUILD_ROB_CREATE_RET_ATTACK_BARN_NOT_OPEN = 'attack_barn_not_open';
	const GUILD_ROB_CREATE_RET_DEFEND_BARN_NOT_OPEN = 'defend_barn_not_open';
	const GUILD_ROB_CREATE_RET_DEFEND_IN_SHELTER = 'defend_in_shelter';
	const GUILD_ROB_CREATE_RET_DEFEND_LOW_GRAIN = 'defend_low_grain';
	const GUILD_ROB_CREATE_RET_DEFEND_TOO_MUCH = 'defend_too_much';
	const GUILD_ROB_CREATE_RET_ATTACK_TOO_MUCH = 'attack_too_much';
	const GUILD_ROB_CREATE_RET_ATTACK_IN_CD = 'attack_in_cd';
	const GUILD_ROB_CREATE_RET_LACK_FIGHT_BOOK = 'lack_fight_book';
	const GUILD_ROB_CREATE_RET_ATTACKER_DEFENDING = 'attacker_defending';
	const GUILD_ROB_CREATE_RET_ATTACKER_ATTACKING = 'attacker_attacking';
	const GUILD_ROB_CREATE_RET_DEFENDER_DEFENDING = 'defender_defending';
	const GUILD_ROB_CREATE_RET_DEFENDER_ATTACKING = 'defender_attacking';
}

class GuildRobUserField
{
	const TBL_FIELD_UID 						= 'uid';
	const TBL_FIELD_ROB_ID 						= 'rob_id';
	const TBL_FIELD_GUILD_ID                    = 'guild_id';
	const TBL_FIELD_UNAME                    	= 'uname';
	const TBL_FIELD_REMOVE_CD_NUM			    = 'remove_cd_num';
	const TBL_FIELD_SPEEDUP_NUM	    			= 'speedup_num';
	const TBL_FIELD_KILL_NUM	    			= 'kill_num';
	const TBL_FIELD_MERIT_NUM	    			= 'merit_num';
	const TBL_FIELD_CONTR_NUM	    			= 'contr_num';
	const TBL_FIELD_USER_GRAIN_NUM	   			= 'user_grain_num';
	const TBL_FIELD_GUILD_GRAIN_NUM    			= 'guild_grain_num';
	const TBL_FIELD_REWARD_TIME                 = 'reward_time';
	const TBL_FIELD_JOIN_TIME                   = 'join_time';
	const TBL_FIELD_KILL_TIME                   = 'kill_time';
	const TBL_FIELD_OFFLINE_TIME                = 'offline_time';
	
	public static $GUILD_ROB_USER_ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_ROB_ID,
			self::TBL_FIELD_GUILD_ID,
			self::TBL_FIELD_UNAME,
			self::TBL_FIELD_REMOVE_CD_NUM,
			self::TBL_FIELD_SPEEDUP_NUM,
			self::TBL_FIELD_KILL_NUM,
			self::TBL_FIELD_MERIT_NUM,
			self::TBL_FIELD_CONTR_NUM,
			self::TBL_FIELD_USER_GRAIN_NUM,
			self::TBL_FIELD_GUILD_GRAIN_NUM,
			self::TBL_FIELD_REWARD_TIME,
			self::TBL_FIELD_JOIN_TIME,
			self::TBL_FIELD_KILL_TIME,
	        self::TBL_FIELD_OFFLINE_TIME,
	);
}

class GuildRobField
{
	const TBL_FIELD_GUILD_ID							= 'guild_id';
	const TBL_FIELD_DEFEND_GUILD_ID 					= 'defend_guild_id';
	const TBL_FIELD_START_TIME                     		= 'start_time';
	const TBL_FIELD_END_TIME							= 'end_time';
	const TBL_FIELD_STAGE								= 'stage';
	const TBL_FIELD_TATAL_ROB_NUM 						= 'total_rob_num';
	const TBL_FIELD_ROB_LIMIT   						= 'rob_limit';
	const TBL_FIELD_VA_EXTRA 							= 'va_extra';
	
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN          		= 'spec_barn';
	
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID      		= 'uid';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UNAME      	= 'name';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_TID      		= 'tid';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_GUILD_ID     	= 'guild_id';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN    		= 'begin';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_MAXHP    		= 'maxhp';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_ARRHP    		= 'arrhp';
	const TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_WIN_STREAK	= 'winStreak';
	
	const GUILD_ROB_STAGE_INIT 							= 0; // 初始化
	const GUILD_ROB_STAGE_START							= 1; // 开始抢粮
	const GUILD_ROB_STAGE_END 							= 2; // 抢粮结束
	const GUILD_ROB_STAGE_SYNC 							= 3; // 粮草同步结束
	const GUILD_ROB_STAGE_REWARD						= 4; // 给玩家发奖结束
	
	public static $GUILD_ROB_ALL_FIELDS = array
	(
			self::TBL_FIELD_GUILD_ID,
			self::TBL_FIELD_DEFEND_GUILD_ID,
			self::TBL_FIELD_START_TIME,
			self::TBL_FIELD_END_TIME,
			self::TBL_FIELD_STAGE,
			self::TBL_FIELD_TATAL_ROB_NUM,
			self::TBL_FIELD_ROB_LIMIT,
			self::TBL_FIELD_VA_EXTRA,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */