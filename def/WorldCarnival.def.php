<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnival.def.php 198211 2015-09-11 11:58:22Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/WorldCarnival.def.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 11:58:22 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198211 $
 * @brief 
 *  
 **/

class WorldCarnivalRound
{
	const ROUND_0			= 0;			// 未开始比赛阶段
	const ROUND_1 			= 1;			// A组比赛
	const ROUND_2 			= 2;			// B组比赛
	const ROUND_3 			= 3;			// 决赛 
}
 
class WorldCarnivalField
{
	const INNER = 'inner';
	const CROSS = 'cross';
}

class WorldCarnivalCrossUserField
{
	/**
	 * t_world_carnival_cross_user表字段
	 */
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_PID						= 'pid';
	const TBL_FIELD_RANK					= 'rank';
	const TBL_FIELD_LOSE_TIMES				= 'lose_times';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_BATTLE			 	= 'battle';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_PID,
			self::TBL_FIELD_RANK,
			self::TBL_FIELD_LOSE_TIMES,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class WorldCarnivalProcedureStatus
{
	const INVALID 			= 0;
	const FIGHTING			= 10;
	const FIGHTEND			= 100;
}

class WorldCarnivalProcedureSubStatus
{
	const INVALID 			= 0;
	const FIGHTING			= 10;
	const FIGHTEND			= 100;
}

class WorldCarnivalProcedureField
{
	/**
	 * t_world_carnival_cross_procedure表字段
	 */
	const TBL_FIELD_SESSION					= 'session';
	const TBL_FIELD_ROUND					= 'round';
	const TBL_FIELD_STATUS					= 'status';
	const TBL_FIELD_SUB_ROUND				= 'sub_round';
	const TBL_FIELD_SUB_STATUS				= 'sub_status';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_RECORD			 	= 'record';
	const TBL_VA_EXTRA_FIGHT_TIME			= 'fight_time';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_SESSION,
			self::TBL_FIELD_ROUND,
			self::TBL_FIELD_STATUS,
			self::TBL_FIELD_SUB_ROUND,
			self::TBL_FIELD_SUB_STATUS,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */