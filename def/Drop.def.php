<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Drop.def.php 114852 2014-06-17 04:04:40Z TiantianZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Drop.def.php $
 * @author $Author: TiantianZhang $(jhd@babeltime.com)
 * @date $Date: 2014-06-17 04:04:40 +0000 (Tue, 17 Jun 2014) $
 * @version $Revision: 114852 $
 * @brief
 *
 **/

class DropDef
{	
	// 掉落物品
	const DROP_TYPE_ITEM = 0;	
	// 掉落武将		
	const DROP_TYPE_HERO = 1;
	// 掉落银币
	const DROP_TYPE_SILVER = 2;
	// 混合掉落
	const DROP_TYPE_MIXED = 3;
	// 掉落将魂
	const DROP_TYPE_SOUL = 4;
	// 掉落宝物碎片
	const DROP_TYPE_TREASFRAG = 5;
	
	const DROP_TYPE_STR_ITEM = 'item';
	const DROP_TYPE_STR_HERO = 'hero';
	const DROP_TYPE_STR_SILVER = 'silver';
	const DROP_TYPE_STR_SOUL = 'soul';
	const DROP_TYPE_STR_TREASFRAG = 'treasFrag';
	
	public static $DROP_TYPE_TO_STRTYPE = array(
	        self::DROP_TYPE_ITEM => self::DROP_TYPE_STR_ITEM,
	        self::DROP_TYPE_HERO => self::DROP_TYPE_STR_HERO,
	        self::DROP_TYPE_SILVER => self::DROP_TYPE_STR_SILVER,
	        self::DROP_TYPE_SOUL => self::DROP_TYPE_STR_SOUL,
	        self::DROP_TYPE_TREASFRAG => self::DROP_TYPE_STR_TREASFRAG
	        );
	
	public static $DROP_VALID_TYPES = array(
			self::DROP_TYPE_ITEM,
			self::DROP_TYPE_HERO,
			self::DROP_TYPE_SILVER,
			self::DROP_TYPE_MIXED,
			self::DROP_TYPE_SOUL,
			self::DROP_TYPE_TREASFRAG,
	);
	
	const DROP_ID									=				'dropId';
	const DROP_NUM_LIST								=				'dropNumList';
	const DROP_RULE									=				'dropRule';
	const DROP_TYPE									=				'dropType';
	const DROP_LIST_NUM								=				'dropListNum';
	const DROP_LIST									=				'dropList';
	
	const DROP_NUM									=				'dropNum';
	const DROP_WEIGHT								=				'weight';
	const DROP_ITEM_TEMPLATE						=				ItemDef::ITEM_ATTR_NAME_TEMPLATE;
	const DROP_ITEM_NUM								=				ItemDef::ITEM_ATTR_NAME_NUM;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */