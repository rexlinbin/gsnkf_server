<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: QueryCache.cfg.php 144064 2014-12-03 13:20:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/QueryCache.cfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-12-03 13:20:53 +0000 (Wed, 03 Dec 2014) $
 * @version $Revision: 144064 $
 * @brief
 *
 **/
class QueryCacheConf
{

	/**
	 * 是否启用QueryCache
	 * @var string
	 */
	const QUERY_CACHE = 'LocalQueryCache';

	static $ARR_TABLE_DEF = array (
			't_arena_lucky' => array (0 => 'begin_date' ),
			't_bag' => array (0 => 'uid', 1 => 'gid' ), 
			't_battle_record' => array (0 => 'brid' ), 
			't_hero' => array (0 => 'hid' ), 
			't_item' => array (0 => 'item_id' ), 
			't_user' => array (0 => 'uid' ), 	
	        't_user_extra' => array (0 => 'uid' ),	
			't_timer' => array (0 => 'tid' ), 
			't_global' => array (0 => 'sq_id' ),
			't_hero_formation' => array (0 => 'uid' ),
	        't_copy'=>array(0=>'uid',1=>'copy_id'),
	        't_elite_copy'=>array(0=>'uid'),
	        't_activity_copy'=>array(0=>'uid',1=>'copy_id'),
	        't_tower'=>array(0=>'uid'),
			't_mall' => array(0 => 'uid'),
			//'t_guild' => array (0 => 'guild_id' ), 军团有数据管理类了，因同步问题，这里不能缓存
			't_guild_member' => array (0 => 'uid' ),
			't_city_war' => array( 0 => 'city_id' ),
			't_compete' => array(0 => 'uid'),
		);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */