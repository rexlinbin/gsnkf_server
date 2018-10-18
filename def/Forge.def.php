<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Forge.def.php 210405 2015-11-18 04:04:57Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Forge.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-18 04:04:57 +0000 (Wed, 18 Nov 2015) $
 * @version $Revision: 210405 $
 * @brief 
 *  
 **/

class ForgeDef
{
	//常量
	const WEEKEND										=				7;
	const DAYTIME										=				86400;
	const WEEKTIME										=				604800;
	const MAXLOOP										=				65536;
	
	//锻造厂，紫装合成橙装
	const FOUNDRY_BASE = 'foundry_base';
	const FOUNDRY_FORM = 'foundry_form';
	const FOUNDRY_COST = 'foundry_cost';
	const FOUNDRY_ITEM = 'foundry_item';
	const BASE_QUALITY = 'base_quality';
	const FORM_QUALITY = 'form_quality';
	
	//潜能转移类型
	const POTENCE_TRANSFER_TYPE_GOLD					=				1;
	const POTENCE_TRANSFER_TYPE_ITEM					=				2;
	const POTENCE_TRANSFER_TYPE_FREE					=				3;
	
	public static $VALID_POTENCE_REFRESH_TYPES = array(0,1,2,3,4);
	
	/**
	 *
	 * 合理的潜能转移方式
	 *
	 * @var array(int)
	 */
	public static $VALID_POTENCE_TRANSFER_TYPES			=				array(
			self::POTENCE_TRANSFER_TYPE_GOLD,
			self::POTENCE_TRANSFER_TYPE_ITEM,
			self::POTENCE_TRANSFER_TYPE_FREE,
	);
	
	//SQL：表名
	const FORGE_TABLE_NAME  = 't_forge';
	//SQL：字段名
	const FORGE_USER_ID = 'uid';
	const FORGE_TRANSFER_NUM = 'transfer_num';
	const FORGE_TRANSFER_TIME = 'transfer_time';

	//SQL：表字段
	public static $FORGE_FIELDS = array (
			self::FORGE_TRANSFER_NUM,
			self::FORGE_TRANSFER_TIME
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */