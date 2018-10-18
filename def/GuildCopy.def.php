<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopy.def.php 232255 2016-03-11 07:49:37Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/GuildCopy.def.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:49:37 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232255 $
 * @brief 
 *  
 **/
 
class GuildCopyDef
{
	const COUNTRY_TYPE_WEI	= 1;
	const COUNTRY_TYPE_SHU  = 2;
	const COUNTRY_TYPE_WU	= 3;
	const COUNTRY_TYPE_QUN	= 4;
}

class GuildCopyUserField
{
	const TBL_FIELD_UID 						= 'uid';
	const TBL_FIELD_ATK_DAMAGE 					= 'atk_damage';
	const TBL_FIELD_ATK_DAMAGE_LAST				= 'atk_damage_last';
	const TBL_FIELD_ATK_NUM 					= 'atk_num';
	const TBL_FIELD_BUY_NUM 					= 'buy_num';
	const TBL_FIELD_UPDATE_TIME 				= 'update_time';
	const TBL_FIELD_RECV_PASS_REWARD_TIME 		= 'recv_pass_reward_time';
	const TBL_FIELD_RECV_BOX_REWARD_TIME 		= 'recv_box_reward_time';
	const TBL_FIELD_RECV_RANK_REWARD_TIME 		= 'recv_rank_reward_time';
	const TBL_FIELD_REFRESH_TIME		 		= 'refresh_time';
	const TBL_FIELD_VA_EXTRA 					= 'va_extra';
	
	// Boss 相关数据
	const TBL_FIELD_ATK_BOSS_NUM				= 'atk_boss_num';
	const TBL_FIELD_BUY_BOSS_NUM				= 'buy_boss_num';
	
	const TBL_VA_EXTRA_SUBFIELD_DAMAGE	        = 'damage';

	public static $GUILD_COPY_USER_ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_ATK_DAMAGE,
			self::TBL_FIELD_ATK_DAMAGE_LAST,
			self::TBL_FIELD_ATK_NUM,
			self::TBL_FIELD_BUY_NUM,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_RECV_PASS_REWARD_TIME,
			self::TBL_FIELD_RECV_BOX_REWARD_TIME,
			self::TBL_FIELD_RECV_RANK_REWARD_TIME,
			self::TBL_FIELD_REFRESH_TIME,
			self::TBL_FIELD_VA_EXTRA,
			self::TBL_FIELD_ATK_BOSS_NUM,
			self::TBL_FIELD_BUY_BOSS_NUM,
	);
}

class GuildCopyField
{
	const TBL_FIELD_GUILD_ID							= 'guild_id';
	const TBL_FIELD_CURR								= 'curr';
	const TBL_FIELD_NEXT								= 'next';
	const TBL_FIELD_MAX_PASS_COPY						= 'max_pass_copy';
	const TBL_FIELD_REFRESH_NUM							= 'refresh_num';
	const TBL_FIELD_PASS_TIME							= 'pass_time';
	const TBL_FIELD_MAX_PASS_TIME						= 'max_pass_time';
	const TBL_FIELD_UPDATE_TIME							= 'update_time';
	const TBL_FIELD_VA_EXTRA 							= 'va_extra';
	const TBL_FIELD_VA_LAST_BOX							= 'va_last_box';

	//BOSS相关数据
	const TBL_FIELD_VA_BOSS								= 'va_boss';
	
	const TBL_VA_EXTRA_SUBFIELD_COPY	          		= 'copy';
	const TBL_VA_EXTRA_SUBFIELD_BOX     	     		= 'box';
	const TBL_VA_EXTRA_SUBFIELD_REFRESHER				= 'refresher';

	public static $GUILD_COPY_ALL_FIELDS = array
	(
			self::TBL_FIELD_GUILD_ID,
			self::TBL_FIELD_CURR,
			self::TBL_FIELD_NEXT,
			self::TBL_FIELD_MAX_PASS_COPY,
			self::TBL_FIELD_REFRESH_NUM,
			self::TBL_FIELD_PASS_TIME,
			self::TBL_FIELD_MAX_PASS_TIME,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
			self::TBL_FIELD_VA_LAST_BOX,
			
			self::TBL_FIELD_VA_BOSS,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */