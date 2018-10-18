<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Stylish.def.php 241037 2016-05-04 10:44:34Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Stylish.def.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-04 10:44:34 +0000 (Wed, 04 May 2016) $
 * @version $Revision: 241037 $
 * @brief 
 *  
 **/
class StylishDef
{
	//配置表TITLE
	const TITLE_LAST_TIME = 'title_last_time';
	const TITLE_ACTIVE_ATTR = 'title_active_attr';
	const TITLE_EQUIP_ATTR = 'title_equip_attr';
	const TITLE_COST_ITEM = 'title_cost_item';
	const TITLE_EQUIP_TYPE = 'title_equip_type';
	
	//sql
	const TBL_STYLISH = 't_stylish';
	
	const FIELD_UID = 'uid';
	const FIELD_VA_TITLE = 'va_title';
	const TITLE = 'title';
	const NUM = 0;
	const TIME = 1;
	 
	//SQL：表字段
	public static $TBL_STYLISH_FIELDS = array(
			self::FIELD_UID,
			self::FIELD_VA_TITLE,
	);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */