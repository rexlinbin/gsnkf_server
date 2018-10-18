<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FsReborn.def.php 200751 2015-09-28 06:20:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/FsReborn.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-28 06:20:22 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200751 $
 * @brief 
 *  
 **/
class FsRebornDef
{	
	//配置表字段
	const NUMS = 'nums';
	const RATE = 'rate';
	
	//SQL表名
	const TBL_NAME = 't_fs_reborn';
	//SQL：字段
	const FIELD_UID = 'uid';
	const FIELD_NUM = 'num';
	const FIELD_REFRESH_TIME = 'refresh_time';
	//SQL：表字段
	public static $TBL_FIELDS = array(
			self::FIELD_UID,
			self::FIELD_NUM,
			self::FIELD_REFRESH_TIME,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */