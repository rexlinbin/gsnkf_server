<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Hunt.def.php 91925 2014-03-03 09:48:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Hunt.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-03-03 09:48:25 +0000 (Mon, 03 Mar 2014) $
 * @version $Revision: 91925 $
 * @brief 
 *  
 **/
class HuntDef
{
	//跳转类型
	const SKIP_TYPE_ITEM = 0;
	const SKIP_TYPE_GOLD = 1;
	
	public static $VALID_SKIP_TYPES = array(
			self::SKIP_TYPE_ITEM,
			self::SKIP_TYPE_GOLD
	);
	
	//猎魂配置表
	const HUNT_NEXT_RATE = 'hunt_next_rate';						//开启下一场景概率
	const HUNT_NEXT_PLACE = 'hunt_next_place';						//下1场景id
	const HUNT_PLACE_COST = 'hunt_place_cost';						//探索消耗银币
	const HUNT_PLACE_DROP = 'hunt_place_drop';						//场景对应掉落表
	const HUNT_PLACE_POINT = 'hunt_place_point';					//场景累积积分
	const HUNT_SPECIAL_SERIAL = 'hunt_special_serial';				//累积变更需要积分
	const HUNT_SPECIAL_DROP = 'hunt_special_drop';					//变更掉落表ID
	
	//SQL表名
	const HUNT_TABLE = 't_hunt';
	//SQL：字段
	const HUNT_UID = 'uid';
	const HUNT_PLACE = 'place';
	const HUNT_POINT = 'point';
	const HUNT_VAINFO = 'va_hunt';
	const ALL = 'all';
	const CHANGE = 'change';
	
	//SQL：表字段
	public static $HUNT_FIELDS = array(
			self::HUNT_UID,
			self::HUNT_PLACE,
			self::HUNT_POINT,
			self::HUNT_VAINFO,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */