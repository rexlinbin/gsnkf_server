<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Moon.def.php 221489 2016-01-13 02:36:37Z NanaPeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Moon.def.php $
 * @author $Author: NanaPeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-13 02:36:37 +0000 (Wed, 13 Jan 2016) $
 * @version $Revision: 221489 $
 * @brief 
 *  
 **/

class MoonTypeDef
{
	const BOSS_NORMAL_TYPE    = 0;     //普通模式boss
	const BOSS_NIGHTMARE_TYPE = 1;  //梦魇模式boss
	
}

class MoonGridStatus
{
	const LOCK   = 1;
	const UNLOCK = 2;
	const DONE	 = 3;
}

class MoonGridType
{
	const BOX		= 'box';
	const MONSTER	= 'monster';
}

class MoonShopField
{
	const TBL_FIELD_VA_ALL = 'all';									//商品购买信息
	const TBL_FIELD_VA_GOODS_LIST = 'goods_ist';					//商品列表
	const TBL_FIELD_VA_LAST_SYS_RFR_TIME = 'last_sys_rfr_time';		//最后一次系统刷新时间
	const TBL_FIELD_VA_LAST_USR_RFR_TIME = 'last_usr_rfr_time';		//最后一次玩家刷新时间
	const TBL_FIELD_VA_USR_RFR_NUM = 'usr_rfr_num';					//当日玩家主动刷新次数
	const TBL_FIELD_VA_FREE_RFR_NUM = 'free_rfr_num';				//当日玩家已经使用了的免费刷新次数
}

class BingfuShopField
{
	const TBL_FIELD_VA_ALL = 'all';									//商品购买信息
	const TBL_FIELD_VA_GOODS_LIST = 'goods_list';					//商品列表
	const TBL_FIELD_VA_LAST_SYS_RFR_TIME = 'last_sys_rfr_time';		//最后一次系统刷新时间
	const TBL_FIELD_VA_LAST_USR_RFR_TIME = 'last_usr_rfr_time';		//最后一次玩家刷新时间
	const TBL_FIELD_VA_USR_RFR_NUM = 'usr_rfr_num';					//当日玩家主动刷新次数
	const TBL_FIELD_VA_FREE_RFR_NUM = 'free_rfr_num';				//当日玩家已经使用了的免费刷新次数
}
 
class MoonField
{
	const TBL_FIELD_UID 						= 'uid';
	const TBL_FIELD_ATK_NUM 					= 'atk_num';
	const TBL_FIELD_BUY_NUM 					= 'buy_num';
	const TBL_FIELD_NIGHTMARE_ATK_NUM 			= 'nightmare_atk_num';
	const TBL_FIELD_NIGHTMARE_BUY_NUM 			= 'nightmare_buy_num';
	const TBL_FIELD_BOX_NUM 					= 'box_num';
	const TBL_FIELD_MAX_PASS_COPY		 		= 'max_pass_copy';
	const TBL_FIELD_MAX_NIGHTMARE_PASS_COPY		= 'max_nightmare_pass_copy';
	const TBL_FIELD_UPDATE_TIME 				= 'update_time';
	const TBL_FIELD_VA_EXTRA 					= 'va_extra';

	const TBL_VA_EXTRA_SUBFIELD_GRID	        = 'grid';

	public static $ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_ATK_NUM,
			self::TBL_FIELD_BUY_NUM,
			self::TBL_FIELD_NIGHTMARE_ATK_NUM,
			self::TBL_FIELD_NIGHTMARE_BUY_NUM,
			self::TBL_FIELD_BOX_NUM,
			self::TBL_FIELD_MAX_PASS_COPY,
			self::TBL_FIELD_MAX_NIGHTMARE_PASS_COPY,
			self::TBL_FIELD_UPDATE_TIME,
			self::TBL_FIELD_VA_EXTRA,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */