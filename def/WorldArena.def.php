<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArena.def.php 244613 2016-05-30 06:49:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/WorldArena.def.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-30 06:49:52 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244613 $
 * @brief 
 *  
 **/
 
class WorldArenaDef
{
	// 重置类型
	const RESET_TYPE_GOLD		= 'gold';
	const RESET_TYPE_SILVER		= 'silver';
	public static $VALID_RESET_TYPE    = array
	(
			self::RESET_TYPE_GOLD,
			self::RESET_TYPE_SILVER,
	);
	
	// 所处阶段的类型
	const STAGE_TYPE_INVALID			= 'invalid';
	const STAGE_TYPE_BEFORE_SIGNUP		= 'before_signup';
	const STAGE_TYPE_SIGNUP				= 'signup';
	const STAGE_TYPE_RANGE_ROOM			= 'range_room';
	const STAGE_TYPE_ATTACK				= 'attack';
	const STAGE_TYPE_REWARD				= 'reward';
	public static $VALID_STAGE_TYPE		= array
	(
			self::STAGE_TYPE_BEFORE_SIGNUP,
			self::STAGE_TYPE_SIGNUP,
			self::STAGE_TYPE_RANGE_ROOM,
			self::STAGE_TYPE_ATTACK,
			self::STAGE_TYPE_REWARD,
	);
	
	const OFFSET_ONE 					= 1;					// 传给战斗系统的偏移量1
	const OFFSET_TWO 					= 2;					// 传给战斗系统的偏移量2
}

class WorldArenaField
{
	const INNER = 'inner';
	const CROSS = 'cross';
}

class WorldArenaInnerUserField
{
	/**
	 * t_world_arena_inner_user表字段
	 */
	const TBL_FIELD_PID						= 'pid';
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_UID						= 'uid';
	const TBL_FIELD_ATKED_NUM				= 'atked_num';
	const TBL_FIELD_BUY_ATK_NUM				= 'buy_atk_num';
	const TBL_FIELD_SILVER_RESET_NUM		= 'silver_reset_num';
	const TBL_FIELD_GOLD_RESET_NUM			= 'gold_reset_num';
	const TBL_FIELD_SIGNUP_TIME				= 'signup_time';
	const TBL_FIELD_UPDATE_FMT_TIME			= 'update_fmt_time';
	const TBL_FIELD_LAST_ATTACK_TIME		= 'last_attack_time';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	const TBL_FIELD_VA_FMT	 				= 'va_fmt';

	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_INHERIT			 	= 'inherit';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_PID,
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_UID,
			self::TBL_FIELD_ATKED_NUM,
			self::TBL_FIELD_BUY_ATK_NUM,
			self::TBL_FIELD_SILVER_RESET_NUM,
			self::TBL_FIELD_GOLD_RESET_NUM,
			self::TBL_FIELD_SIGNUP_TIME,
			self::TBL_FIELD_UPDATE_FMT_TIME,
			self::TBL_FIELD_LAST_ATTACK_TIME,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_FMT,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class WorldArenaCrossUserField
{
	/**
	 * t_world_arena_cross_user表字段
	 */
	const TBL_FIELD_PID						= 'pid';
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_ROOM_ID					= 'room_id';
	const TBL_FIELD_UID						= 'uid';
	const TBL_FIELD_UNAME					= 'uname';
	const TBL_FIELD_VIP						= 'vip';
	const TBL_FIELD_LEVEL					= 'level';
	const TBL_FIELD_HTID					= 'htid';
	const TBL_FIELD_TITLE					= 'title';
	const TBL_FIELD_FIGHT_FORCE				= 'fight_force';
	const TBL_FIELD_POS						= 'pos';
	const TBL_FIELD_KILL_NUM				= 'kill_num';
	const TBL_FIELD_CUR_CONTI_NUM			= 'cur_conti_num';
	const TBL_FIELD_MAX_CONTI_NUM			= 'max_conti_num';
	const TBL_FIELD_PROTECT_TIME			= 'protect_time';
	const TBL_FIELD_POS_REWARD_TIME			= 'pos_reward_time';
	const TBL_FIELD_KILL_REWARD_TIME		= 'kill_reward_time';
	const TBL_FIELD_CONTI_REWARD_TIME		= 'conti_reward_time';
	const TBL_FIELD_KING_REWARD_TIME		= 'king_reward_time';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_DRESS			 	= 'dress';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_PID,
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_ROOM_ID,
			self::TBL_FIELD_UID,
			self::TBL_FIELD_UNAME,
			self::TBL_FIELD_VIP,
			self::TBL_FIELD_LEVEL,
			self::TBL_FIELD_HTID,
			self::TBL_FIELD_TITLE,
			self::TBL_FIELD_FIGHT_FORCE,
			self::TBL_FIELD_POS,
			self::TBL_FIELD_KILL_NUM,
			self::TBL_FIELD_CUR_CONTI_NUM,
			self::TBL_FIELD_MAX_CONTI_NUM,
			self::TBL_FIELD_PROTECT_TIME,
			self::TBL_FIELD_POS_REWARD_TIME,
			self::TBL_FIELD_KILL_REWARD_TIME,
			self::TBL_FIELD_CONTI_REWARD_TIME,
			self::TBL_FIELD_KING_REWARD_TIME,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class WorldArenaCrossTeamField
{
	/**
	 * t_world_arena_cross_team表字段
	 */
	const TBL_FIELD_TEAM_ID					= 'team_id';
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_UPDATE_TIME,
	);
}

class WorldArenaCrossIdField
{
	/**
	 * t_world_arena_cross_team表字段
	 */
	const TBL_FIELD_TEAM_ID					= 'team_id';
	const TBL_FIELD_ROOM_ID					= 'room_id';
	const TBL_FIELD_ID						= 'id';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_ROOM_ID,
			self::TBL_FIELD_ID,
	);
}

class WorldArenaCrossRecordField
{
	/**
	 * t_world_arena_cross_record表字段
	 */
	const TBL_FIELD_ID						= 'id';
	const TBL_FIELD_TEAM_ID					= 'team_id';
	const TBL_FIELD_ROOM_ID					= 'room_id';
	const TBL_FIELD_ATTACKER_SERVER_ID		= 'attacker_server_id';
	const TBL_FIELD_ATTACKER_PID			= 'attacker_pid';
	const TBL_FIELD_ATTACKER_UNAME			= 'attacker_uname';
	const TBL_FIELD_ATTACKER_HTID			= 'attacker_htid';
	const TBL_FIELD_ATTACKER_RANK			= 'attacker_rank';
	const TBL_FIELD_ATTACKER_CONTI			= 'attacker_conti';
	const TBL_FIELD_ATTACKER_TERMINAL_CONTI	= 'attacker_terminal_conti';
	const TBL_FIELD_DEFENDER_SERVER_ID		= 'defender_server_id';
	const TBL_FIELD_DEFENDER_PID			= 'defender_pid';
	const TBL_FIELD_DEFENDER_UNAME			= 'defender_uname';
	const TBL_FIELD_DEFENDER_HTID			= 'defender_htid';
	const TBL_FIELD_DEFENDER_RANK			= 'defender_rank';
	const TBL_FIELD_DEFENDER_CONTI			= 'defender_conti';
	const TBL_FIELD_DEFENDER_TERMINAL_CONTI = 'defender_terminal_conti';
	const TBL_FIELD_ATTACK_TIME				= 'attack_time';
	const TBL_FIELD_RESULT					= 'result';
	const TBL_FIELD_BRID					= 'brid';
	
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_ID,
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_ROOM_ID,
			self::TBL_FIELD_ATTACKER_SERVER_ID,
			self::TBL_FIELD_ATTACKER_PID,
			self::TBL_FIELD_ATTACKER_UNAME,
			self::TBL_FIELD_ATTACKER_HTID,
			self::TBL_FIELD_ATTACKER_RANK,
			self::TBL_FIELD_ATTACKER_CONTI,
			self::TBL_FIELD_ATTACKER_TERMINAL_CONTI,
			self::TBL_FIELD_DEFENDER_SERVER_ID,
			self::TBL_FIELD_DEFENDER_PID,
			self::TBL_FIELD_DEFENDER_UNAME,
			self::TBL_FIELD_DEFENDER_HTID,
			self::TBL_FIELD_DEFENDER_RANK,
			self::TBL_FIELD_DEFENDER_CONTI,
			self::TBL_FIELD_DEFENDER_TERMINAL_CONTI,
			self::TBL_FIELD_ATTACK_TIME,
			self::TBL_FIELD_RESULT,
			self::TBL_FIELD_BRID,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */