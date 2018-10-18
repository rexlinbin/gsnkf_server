<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWar.def.php 158607 2015-02-12 04:03:58Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/GuildWar.def.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-12 04:03:58 +0000 (Thu, 12 Feb 2015) $
 * @version $Revision: 158607 $
 * @brief 
 *  
 **/
 
class GuildWarDef
{
	const GDW_DB_PREFIX = 'pirate_guildwar_';
}

class GuildWarPush
{
	const NOW_STATUS = 1;
	const NEW_REWARD = 2;
	const NEW_MAIL = 3;
	
	public static $ALL_TYPE = array
	(
			self::NOW_STATUS,
			self::NEW_REWARD,
			self::NEW_MAIL,
	);
}

class GuildWarField
{
	const INNER = 'inner';
	const CROSS = 'cross';
}

class GuildWarServerField
{
	/**
	 * t_guild_war_cross_server表字段
	 */
	const TBL_FIELD_SESSION					= 'session';
	const TBL_FIELD_GUILD_ID 				= 'guild_id';
	const TBL_FIELD_GUILD_SERVER_ID 		= 'guild_server_id';
	const TBL_FIELD_TEAM_ID 				= 'team_id';
	const TBL_FIELD_SIGN_TIME 				= 'sign_time';
	const TBL_FIELD_GUILD_LEVEL 			= 'guild_level';
	const TBL_FIELD_GUILD_BADGE 			= 'guild_badge';
	const TBL_FIELD_GUILD_NAME 				= 'guild_name';
	const TBL_FIELD_GUILD_SERVER_NAME 		= 'guild_server_name';
	const TBL_FIELD_FINAL_RANK 				= 'final_rank';
	const TBL_FIELD_LOSE_TIMES 				= 'lose_times';
	const TBL_FIELD_POS		 				= 'pos';
	const TBL_FIELD_FIGHT_FORCE 			= 'fight_force';
	const TBL_FIELD_LAST_FIGHT_TIME         = 'last_fight_time';
	
	const TBL_FIELD_VA_REPLAY 				= 'va_replay';
	const TBL_VA_REPLAY_FIELD_AUDITION	 	= 'audition';
	const TBL_VA_REPLAY_FIELD_FINALS	 	= 'finals';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_FIELD_CANDIDATES 	= 'candidates';
	const TBL_VA_EXTRA_FIELD_LOSERS	 		= 'losers';
	const TBL_VA_EXTRA_FIELD_FIGHTERS	 	= 'fighters';
	const TBL_VA_EXTRA_FIELD_HP			 	= 'hp';
	const TBL_VA_EXTRA_FIELD_PRESIDENT_INFO	= 'president_info';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_SESSION,
			self::TBL_FIELD_GUILD_ID,
			self::TBL_FIELD_GUILD_SERVER_ID,
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_SIGN_TIME,
			self::TBL_FIELD_GUILD_LEVEL,
			self::TBL_FIELD_GUILD_BADGE,
			self::TBL_FIELD_GUILD_NAME,
			self::TBL_FIELD_GUILD_SERVER_NAME,
			self::TBL_FIELD_FINAL_RANK,
			self::TBL_FIELD_LOSE_TIMES,
			self::TBL_FIELD_POS,
			self::TBL_FIELD_FIGHT_FORCE,
			self::TBL_FIELD_LAST_FIGHT_TIME,
			self::TBL_FIELD_VA_REPLAY,
			self::TBL_FIELD_VA_EXTRA,
	); 
}

class GuildWarUserField
{
	/**
	 * t_guild_war_inner_user表字段
	 */
	const TBL_FIELD_UID						= 'uid';
	const TBL_FIELD_UNAME					= 'uname';
	const TBL_FIELD_CHEER_GUILD_ID			= 'cheer_guild_id';
	const TBL_FIELD_CHEER_GUILD_SERVER_ID	= 'cheer_guild_server_id';
	const TBL_FIELD_CHEER_ROUND				= 'cheer_round';
	const TBL_FIELD_BUY_MAX_WIN_NUM 		= 'buy_max_win_num';
	const TBL_FIELD_BUY_MAX_WIN_TIME 		= 'buy_max_win_time';
	const TBL_FIELD_WORSHIP_TIME	 		= 'worship_time';
	const TBL_FIELD_FIGHT_FORCE				= 'fight_force';
	const TBL_FIELD_UPDATE_FMT_TIME			= 'update_fmt_time';
	const TBL_FIELD_SEND_PRIZE_TIME			= 'send_prize_time';
	const TBL_FIELD_LAST_JOIN_TIME			= 'last_join_time';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_CHEER			 	= 'cheer';
	const TBL_VA_EXTRA_BATTLE_FMT		 	= 'battle_fmt';
	
	const TBL_VA_EXTRA_GUILD_ID 			= 'guild_id';
	const TBL_VA_EXTRA_GUILD_NAME 			= 'guild_name';
	const TBL_VA_EXTRA_SERVER_ID 			= 'server_id';
	const TBL_VA_EXTRA_SERVER_NAME 			= 'server_name';
	const TBL_VA_EXTRA_REWARD_TIME 			= 'reward_time';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_UNAME,
			self::TBL_FIELD_CHEER_GUILD_ID,
			self::TBL_FIELD_CHEER_GUILD_SERVER_ID,
			self::TBL_FIELD_CHEER_ROUND,
			self::TBL_FIELD_BUY_MAX_WIN_NUM,
			self::TBL_FIELD_BUY_MAX_WIN_TIME,
			self::TBL_FIELD_WORSHIP_TIME,
			self::TBL_FIELD_FIGHT_FORCE,
			self::TBL_FIELD_UPDATE_FMT_TIME,
			self::TBL_FIELD_SEND_PRIZE_TIME,
			self::TBL_FIELD_LAST_JOIN_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class GuildWarProcedureField
{
	/**
	 * t_guild_war_procedure表字段
	 */
	const TBL_FIELD_SESSION					= 'session';
	const TBL_FIELD_TEAM_ID					= 'team_id';
	const TBL_FIELD_ROUND					= 'round';
	const TBL_FIELD_STATUS					= 'status';
	const TBL_FIELD_SUB_ROUND				= 'sub_round';
	const TBL_FIELD_SUB_STATUS				= 'sub_status';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_SESSION,
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_ROUND,
			self::TBL_FIELD_STATUS,
			self::TBL_FIELD_SUB_ROUND,
			self::TBL_FIELD_SUB_STATUS,
			self::TBL_FIELD_UPDATE_TIME,
	);
}

class GuildWarTempleField
{
	/**
	 * t_guild_war_inner_temple表字段
	 */
	const TBL_FIELD_SESSION									= 'session';
	const TBL_FIELD_VA_EXTRA 								= 'va_extra';
	
	const TBL_VA_EXTRA_GUILD_ID			 					= 'guild_id';
	const TBL_VA_EXTRA_GUILD_NAME			 				= 'guild_name';
	const TBL_VA_EXTRA_GUILD_SERVER_ID			 			= 'guild_server_id';
	const TBL_VA_EXTRA_GUILD_SERVER_NAME			 		= 'guild_server_name';
	const TBL_VA_EXTRA_GUILD_BADGE			 				= 'guild_badge';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_UNAME			 	= 'president_uname';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_HTID			 		= 'president_htid';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_LEVEL			 	= 'president_level';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_VIP_LEVEL			= 'president_vip_level';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_FIGHT_FORCE			= 'president_fight_force';
	const TBL_VA_EXTRA_GUILD_PRESIDENT_PRESIDENT_DRESS		= 'president_dress';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_SESSION,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class GuildWarRound
{
	const INVALID			= -1;			// 无效阶段
	const IDLE				= 0;			// 空闲阶段，未开始报名
	const SIGNUP 			= 1;			// 报名阶段
	const AUDITION 			= 2;			// 海选阶段
	const ADVANCED_16 		= 3;			// 16进8阶段
	const ADVANCED_8 		= 4;			// 8进4阶段
	const ADVANCED_4 		= 5;			// 4进2阶段
	const ADVANCED_2 		= 6;			// 2进1阶段
	
	public static $ValidRound = array
	(
			self::SIGNUP,
			self::AUDITION,
			self::ADVANCED_16,
			self::ADVANCED_8,
			self::ADVANCED_4,
			self::ADVANCED_2,
	);
	
	public static $FinalsRound = array
	(
			self::ADVANCED_16,
			self::ADVANCED_8,
			self::ADVANCED_4,
			self::ADVANCED_2,
	);
}

class GuildWarStatus
{
	const NO = 0;
	const PREPARE = 10;
	const WAIT_TIME_END = 11;
	const FIGHTING = 20;
	const FIGHTEND = 30;
	const REWARDEND = 40;
	const DONE = 100;
}

class GuildWarSubStatus
{
	const NO = 0;
	const FIGHTING = 20;
	const FIGHTEND = 30;
}

class GuildWarWorshipType
{
	const X = 0;
	const Y = 1;
	const Z = 2;
	
	public static $ALL_TYPE = array
	(
			self::X,
			self::Y,
			self::Z,
	);
}

class GuildWarCsvTag
{
	const ID					= 'id';
	const SESSION				= 'session';
	const NEED_LEVEL			= 'need_level';
	const NEED_MEMBER_COUNT 	= 'need_member_count';
	const FAIL_NUM 				= 'fail_num';
	const TIME_CONFIG			= 'time_config';
	const START_TIME			= 'start_time';
	const AUDITION_GAP			= 'audition_gap';
	const FINALS_GAP			= 'finals_gap';
	const CD					= 'cd';
	const AUDITION_UPD_CD   	= 'audition_upd_cd';
	const AUDITION_UPD_LIMIT   	= 'audition_upd_limit';
	const FINALS_UPD_CD     	= 'finals_upd_cd';
	const FINALS_UPD_LIMIT     	= 'finals_upd_limit';
	const FINALS_TEAM_UPD_CD	= 'finals_team_upd_cd';
	const FINALS_TEAM_UPD_LIMIT	= 'finals_team_upd_limit';
	const CANDIDATES_COUNT		= 'candidates_count';
	const CANDIDATES_PRIZE 		= 'candidates_prize';
	const NOT_CANDIDATES_PRIZE 	= 'not_candidates_prize';
	const CHEER_BASE_COST		= 'cheer_base_cost';
	const CHEER_PRIZE			= 'cheer_prize';
	const ALL_SERVER_PRIZE		= 'all_server_prize';
	const WORSHIP_PRIZE			= 'worship_prize';
	const LAST_ID				= 'last_id';
	const CLEAR_CD_BASE_COST	= 'clear_cd_base_cost';
	const DEFAULT_WIN_TIME		= 'default_win_time';
	const BUY_WIN_TIME_COST		= 'buy_win_time_cost';
	const CHEER_LIMIT			= 'cheer_limit';
	const ALL_TEAM				= 'all_team';
	const WORSHIP_COST			= 'worship_cost';
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */