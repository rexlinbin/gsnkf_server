<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeGlobal.conf.php 161359 2015-03-13 06:06:21Z HaidongJia $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/conf/MergeGlobal.conf.php $
 * @author $Author: HaidongJia $(hoping@babeltime.com)
 * @date $Date: 2015-03-13 14:06:21 +0800 (星期五, 13 三月 2015) $
 * @version $Revision: 161359 $
 * @brief 
 *  
 **/

class MergeGlobal
{
	/**
	 * 记录所有合并服务器id组的数据
	 * 
	 * @var array(string/int)
	 */
	private static $MERGE_SERVER_IDS = array();
	
	public static function setMergeServerIDs($merge_server_ids)
	{
		self::$MERGE_SERVER_IDS = $merge_server_ids;
	}
	
	public static function getMergeServerIDs()
	{
		return self::$MERGE_SERVER_IDS;
	}
	
	/**
	 * 合并目标服务器的数组
	 * 
	 * @var string
	 */
	private static $MERGE_SERVER_TARGET_ID = NULL;
	
	public static function setMergeServerTargetID($target_game_id)
	{
		self::$MERGE_SERVER_TARGET_ID = $target_game_id;
	}
	
	public static function getMergeServerTargetID()
	{
		return self::$MERGE_SERVER_TARGET_ID;
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */