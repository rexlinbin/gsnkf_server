<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompete.def.php 241121 2016-05-05 07:36:35Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/WorldCompete.def.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 07:36:35 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241121 $
 * @brief 
 *  
 **/
 
class WorldCompeteInnerUserField
{
	/**
	 * t_world_compete_inner_user表字段
	 */
	const FIELD_PID						= 'pid';
	const FIELD_SERVER_ID				= 'server_id';
	const FIELD_UID						= 'uid';
	const FIELD_ATK_NUM					= 'atk_num';
	const FIELD_SUC_NUM					= 'suc_num';
	const FIELD_BUY_ATK_NUM				= 'buy_atk_num';
	const FIELD_REFRESH_NUM				= 'refresh_num';
	const FIELD_WORSHIP_NUM				= 'worship_num';
	const FIELD_MAX_HONOR				= 'max_honor';
	const FIELD_CROSS_HONOR				= 'cross_honor';
	const FIELD_HONOR_TIME				= 'honor_time';
	const FIELD_UPDATE_TIME				= 'update_time';
	const FIELD_REWARD_TIME				= 'reward_time';
	const FIELD_VA_EXTRA				= 'va_extra';
	const RIVAL 						= 'rival';
	const PRIZE							= 'prize';
	
	public static $ALL_FIELDS = array
	(
			self::FIELD_PID,
			self::FIELD_SERVER_ID,
			self::FIELD_UID,
			self::FIELD_ATK_NUM,
			self::FIELD_SUC_NUM,
			self::FIELD_BUY_ATK_NUM,
			self::FIELD_REFRESH_NUM,
			self::FIELD_WORSHIP_NUM,
			self::FIELD_MAX_HONOR,
			self::FIELD_CROSS_HONOR,
			self::FIELD_HONOR_TIME,
			self::FIELD_UPDATE_TIME,
			self::FIELD_REWARD_TIME,
			self::FIELD_VA_EXTRA,
	);
}

class WorldCompeteCrossUserField
{
	/**
	 * t_world_compete_cross_user表字段
	 */
	const FIELD_TEAM_ID					= 'team_id';
	const FIELD_PID						= 'pid';
	const FIELD_SERVER_ID				= 'server_id';
	const FIELD_UID                     = 'uid';
	const FIELD_UNAME                   = 'uname';
	const FIELD_VIP						= 'vip';
	const FIELD_LEVEL					= 'level';
	const FIELD_HTID					= 'htid';
	const FIELD_TITLE					= 'title';
	const FIELD_FIGHT_FORCE				= 'fight_force';
	const FIELD_MAX_HONOR				= 'max_honor';
	const FIELD_UPDATE_TIME				= 'update_time';
	const FIELD_VA_EXTRA				= 'va_extra';
	const FIELD_VA_BATTLE_FORMATION     = 'va_battle_formation';
	const DRESS							= 'dress';
	const FORMATION						= 'formation';
	
	public static $ALL_FIELDS = array
	(
			self::FIELD_TEAM_ID,
			self::FIELD_PID,
			self::FIELD_SERVER_ID,
			self::FIELD_UID,
			self::FIELD_UNAME,
			self::FIELD_VIP,
			self::FIELD_LEVEL,
			self::FIELD_HTID,
			self::FIELD_TITLE,
			self::FIELD_FIGHT_FORCE,
			self::FIELD_MAX_HONOR,
			self::FIELD_UPDATE_TIME,
			self::FIELD_VA_EXTRA,
			self::FIELD_VA_BATTLE_FORMATION,
	);
	
	public static $RIVAL_FIELDS = array
	(
			self::FIELD_PID,
			self::FIELD_SERVER_ID,
			self::FIELD_UNAME,
			self::FIELD_VIP,
			self::FIELD_LEVEL,
			self::FIELD_HTID,
			self::FIELD_TITLE,
			self::FIELD_FIGHT_FORCE,
			self::FIELD_VA_EXTRA,
	);
	
	public static $RANK_FIELDS = array
	(
			self::FIELD_PID,
			self::FIELD_SERVER_ID,
			self::FIELD_UID,
			self::FIELD_UNAME,
			self::FIELD_VIP,
			self::FIELD_LEVEL,
			self::FIELD_HTID,
			self::FIELD_TITLE,
			self::FIELD_FIGHT_FORCE,
			self::FIELD_MAX_HONOR,
			self::FIELD_VA_EXTRA,
	);
}

class WorldCompeteCrossTeamField
{
	/**
	 * t_world_compete_cross_team表字段
	 */
	const FIELD_TEAM_ID					= 'team_id';
	const FIELD_SERVER_ID				= 'server_id';
	const FIELD_UPDATE_TIME				= 'update_time';
	
	public static $ALL_FIELDS = array
	(
			self::FIELD_TEAM_ID,
			self::FIELD_SERVER_ID,
			self::FIELD_UPDATE_TIME,
	);
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */