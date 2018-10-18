<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Shop.def.php 94156 2014-03-19 08:05:07Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Shop.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-03-19 08:05:07 +0000 (Wed, 19 Mar 2014) $
 * @version $Revision: 94156 $
 * @brief 
 *  
 **/
class ShopDef
{
	//常量
	const RECRUIT_TYPE_BRONZE = 1;
	const RECRUIT_TYPE_SILVER = 2;
	const RECRUIT_TYPE_GOLD = 3;
	
	//是否花金币招将
	const COST_TYPE_FREE = 0;
	const COST_TYPE_GOLD = 1;
	
	//初始不同五星武将的数量
	const INIT_DISTINCT_HERO_NUM = 3;
	
	//银将和金将的初始延迟时间
	const SILVER_RECRUIT_DELAY = 1800;
	const GOLD_RECRUIT_DELAY = 3600;
	
	//首刷状态
	const NO_FREE_GOLD = 0; //免费和金币首刷都未使用
	const FREE_NO_GOLD = 1;	//免费首刷使用，金币首刷未使用
	const GOLD_NO_FREE = 2; //金币首刷使用，免费首刷未使用
	const FREE_GOLD_NO = 3; //免费和金币首刷都使用
	
	const GOLD_RECRUIT = 'gold_recruit';
	const SILVER_RECRUIT = 'silver_recruit';
	const NUM = '_num';
	const TIME = '_time';
	const STATUS = '_status';
	
	public static $COST_VALID_TYPES = array(
			self::COST_TYPE_FREE,
			self::COST_TYPE_GOLD,
	);
	
	public static $RECRUIT_VALID_TYPES = array(
			self::RECRUIT_TYPE_SILVER => self::SILVER_RECRUIT,
			self::RECRUIT_TYPE_GOLD => self::GOLD_RECRUIT,
	);
	
	//酒馆招将配置
	const RECRUIT_TYPE_ID = 'recruit_type_id';
	const RECRUIT_CD_TIME = 'recruit_cd_time';
	const RECRUIT_COST_GOLD = 'recruit_cost_gold';
	const RECRUIT_COST_ITEM = 'recruit_cost_item';
	const RECRUIT_POINT_BASE = 'recruit_point_base';
	const RECRUIT_GOLD_DROP = 'recruit_gold_drop';
	const RECRUIT_FREE_DROP = 'recruit_free_drop';
	const RECRUIT_DEFAULT_GOLD = 'recruit_default_gold';
	const RECRUIT_DEFAULT_FREE = 'recruit_default_free';
	const RECRUIT_SPECIAL_NUM = 'recruit_special_num';
	const RECRUIT_SPECIAL_DROP = 'recruit_special_drop';
	const RECRUIT_EXTRA_DROP = 'recruit_extra_drop';
	const RECRUIT_MULTI_COST = 'recruit_multi_cost';
	const RECRUIT_SPECIAL_SERIAL = 'recruit_special_serial';
	const RECRUIT_ANOTHER_SERIAL = 'recruit_another_serial';
	const RECRUIT_ANOTHER_DROP = 'recruit_another_drop';
	
	//sql
	//表名
	const TABLE_NAME_SHOP = 't_shop';
	//字段
	const USER_ID = 'uid';
	const POINT = 'point';
	const BRONZE_RECRUIT_NUM = 'bronze_recruit_num';
	const SILVER_RECRUIT_NUM = 'silver_recruit_num';
	const SILVER_RECRUIT_TIME = 'silver_recruit_time';
	const SILVER_RECRUIT_STATUS = 'silver_recruit_status';
	const GOLD_RECRUIT_NUM = 'gold_recruit_num';
	const GOLD_RECRUIT_TIME = 'gold_recruit_time';
	const GOLD_RECRUIT_STATUS = 'gold_recruit_status';
	const VA_SHOP = 'va_shop';
	const VIP_GIFT = 'vip_gift';
	const TEN = 'ten';
	
	//SQL：表字段
	public static $SHOP_FIELDS = array (
			self::USER_ID,
			self::POINT,
			self::BRONZE_RECRUIT_NUM,
			self::SILVER_RECRUIT_NUM,
			self::SILVER_RECRUIT_TIME,
			self::SILVER_RECRUIT_STATUS,
			self::GOLD_RECRUIT_NUM,
			self::GOLD_RECRUIT_TIME,
			self::GOLD_RECRUIT_STATUS,
			self::VA_SHOP
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */