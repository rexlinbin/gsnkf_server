<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Union.def.php 241834 2016-05-10 07:33:24Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Union.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 07:33:24 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241834 $
 * @brief 
 *  
 **/
class UnionDef
{	
	// session key
	const SESSION_KEY = 'union.info';
	
	//缘分堂
	const FATE = 0;
	//忠义堂
	const LOYAL = 1;
	//演武堂
	const MARTIAL = 2;
	
	//对应配置表名字
	public static $TYPE_TO_CONFNAME = array(
			self::FATE => 'UNION_FATE',
			self::LOYAL => 'UNION_LOYAL',
			self::MARTIAL => 'UNION_MARTIAL'
	);
	
	//配置表字段
	const ITEM_TPLID = 'itemTplId';
	const GOLD_NUM = 'goldNum';
	const ITEM_NUM_ARR = 'itemNumArr';
	const UNION_ID = 'unionId';
	const NEED_LEVEL = 'needLevel';
	const NEED_ARR = 'needArr';
	const ADD_ATTR = 'add_attr';
	const TYPE = 'type'; 
	const NUM = 'num';
	const FATE_ATTR = 'fate_attr';
	const LOYAL_ATTR = 'loyal_attr';
	const LISTS = 'lists';
	
	//自动寻龙试炼，额外获得积分
	const TYPE_DRAGON_AIDO_ADDPOINT = 1;
	//自动寻龙（探宝+试炼）免费
	const TYPE_DRAGON_AIDO_ISFREE = 2;
	//资源矿产量提高百分比
	const TYPE_MINERAL_PIT_ADDRATE = 3;
	//良将招募时间减少X分钟
	const TYPE_SHOP_SRECRUIT_SUBCD = 4;
	//神将招募时间减少X分钟
	const TYPE_SHOP_GRECRUIT_SUBCD = 5;
	
	//SQL表名
	const TBL_UNION = 't_union';
	//SQL：字段
	const FIELD_UID = 'uid';
	const FIELD_VA_FATE = 'va_fate';
	const FIELD_VA_LOYAL = 'va_loyal';
	const FIELD_VA_MARTIAL = 'va_martial';
	//SQL：表字段
	public static $TBL_FIELDS = array(
			self::FIELD_UID,
			self::FIELD_VA_FATE,
			self::FIELD_VA_LOYAL,
			self::FIELD_VA_MARTIAL,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */