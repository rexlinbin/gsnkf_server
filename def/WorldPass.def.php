<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPass.def.php 177959 2015-06-10 11:50:09Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/WorldPass.def.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-10 11:50:09 +0000 (Wed, 10 Jun 2015) $
 * @version $Revision: 177959 $
 * @brief 
 *  
 **/

class WorldPassShopField
{
	const TBL_FIELD_VA_ALL = 'all';									//商品购买信息
	const TBL_FIELD_VA_GOODS_LIST = 'goods_ist';					//商品列表
	const TBL_FIELD_VA_LAST_SYS_RFR_TIME = 'last_sys_rfr_time';		//最后一次系统刷新时间
	const TBL_FIELD_VA_LAST_USR_RFR_TIME = 'last_usr_rfr_time';		//最后一次玩家刷新时间
	const TBL_FIELD_VA_USR_RFR_NUM = 'usr_rfr_num';					//当日玩家主动刷新次数
}
 
class WorldPassInnerUserField
{
	/**
	 * t_world_pass_inner_user表字段
	 */
	const TBL_FIELD_PID						= 'pid';
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_UID						= 'uid';
	const TBL_FIELD_PASSED_STAGE			= 'passed_stage';
	const TBL_FIELD_MAX_POINT				= 'max_point';
	const TBL_FIELD_MAX_POINT_TIME			= 'max_point_time';
	const TBL_FIELD_CURR_POINT 				= 'curr_point';
	const TBL_FIELD_HELL_POINT 				= 'hell_point';
	const TBL_FIELD_ATK_NUM	 				= 'atk_num';
	const TBL_FIELD_BUY_ATK_NUM				= 'buy_atk_num';
	const TBL_FIELD_REFRESH_NUM				= 'refresh_num';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	const TBL_FIELD_REWARD_TIME				= 'reward_time';
	
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_CHOICE			 	= 'choice';
	const TBL_VA_EXTRA_FORMATION		 	= 'formation';
	const TBL_VA_EXTRA_POINT		 		= 'point';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_PID,
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_UID,
			self::TBL_FIELD_PASSED_STAGE,
			self::TBL_FIELD_MAX_POINT,
			self::TBL_FIELD_MAX_POINT_TIME,
			self::TBL_FIELD_CURR_POINT,
			self::TBL_FIELD_HELL_POINT,
			self::TBL_FIELD_ATK_NUM,
			self::TBL_FIELD_BUY_ATK_NUM,
			self::TBL_FIELD_REFRESH_NUM,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_REWARD_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

class WorldPassCrossUserField
{
	/**
	 * t_world_pass_cross_user表字段
	 */
	const TBL_FIELD_TEAM_ID					= 'team_id';
	const TBL_FIELD_PID						= 'pid';
	const TBL_FIELD_SERVER_ID				= 'server_id';
	const TBL_FIELD_MAX_POINT				= 'max_point';
	const TBL_FIELD_UPDATE_TIME				= 'update_time';
	
	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_TEAM_ID,
			self::TBL_FIELD_PID,
			self::TBL_FIELD_SERVER_ID,
			self::TBL_FIELD_MAX_POINT,
			self::TBL_FIELD_UPDATE_TIME,
	);
}

class WorldPassCrossTeamField
{
	/**
	 * t_world_pass_cross_team表字段
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


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */