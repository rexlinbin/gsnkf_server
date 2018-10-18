<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mission.cfg.php 214360 2015-12-07 09:09:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Mission.cfg.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-07 09:09:52 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214360 $
 * @brief 
 *  
 **/
class MissionConf
{
	const FIRST_SESS = 1;
	const TOPNUM = 500;//一下从db里拉取前500，一般不建议这么做，这里为了提高效率，突破了100的限制
	const REF_GAP_TIME = 1800;
	const REF_TEAMID_GAP_TIME = 1800;
	const FRONT_TOPNUM = 10;
	const CAN_DB_TIMES = 3;
	const VALID_TIME = 2;
	
}
class MissionType
{
	const FROM_FRONT = 1;
	const FROM_BACK = 2;
	
	const ITEM = 999;
	const GOLD = 911;
	
	const NORMAL_BASE = 1;
	const ECOPY = 2;
	const ACOPY = 3;
	const DIVINE = 4;
	const HUNT = 5;
	const FRAGSIZE = 6;
	const ARENA = 7;
	const TOWER = 8;
	const MINERAL = 9;
	const COMPETE = 10;
	const REF_MYSTSHOP = 11;
	
	static $front = array(
		
			self::ITEM ,
			self::GOLD ,
	);
	static $back = array(
		
			self::NORMAL_BASE ,
			self::ECOPY ,
			self::ACOPY ,
			self::DIVINE ,
			self::HUNT ,
			self::FRAGSIZE ,
			self::ARENA ,
			self::TOWER ,
			self::MINERAL ,
			self::COMPETE ,
			self::REF_MYSTSHOP ,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */