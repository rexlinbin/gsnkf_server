<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: MysqlManager.class.php 62652 2013-09-03 05:53:33Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/lib/MysqlManager.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-09-03 13:53:33 +0800 (星期二, 03 九月 2013) $
 * @version $Revision: 62652 $
 * @brief
 *
 **/

class MysqlManager
{
	private static $MYSQLDBHOST = array();
	private static $MYSQLLINKS = array();

	public static function getMysql($game_id)
	{
		if ( !isset(self::$MYSQLDBHOST[$game_id]) )
		{
			throw new Exception('invalid game_id in mysql manager!');
		}

		$dbhost = self::$MYSQLDBHOST[$game_id];

		if ( !isset(self::$MYSQLLINKS[$game_id]) )
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($dbhost, $game_id, DataDef::$DB_USER, DataDef::$DB_PASSWORD);
			self::$MYSQLLINKS[$game_id] = $mysql;
		}
		return self::$MYSQLLINKS[$game_id];
	}
	
	public static function clearDbLink()
	{
		self::$MYSQLLINKS = array();
	}

	public static function setDBHost($game_id, $db_host)
	{
		self::$MYSQLDBHOST[$game_id] = $db_host;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */