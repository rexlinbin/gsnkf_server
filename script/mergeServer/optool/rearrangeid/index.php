<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: index.php 66336 2013-09-25 10:19:25Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/index.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-09-25 18:19:25 +0800 (星期三, 25 九月 2013) $
 * @version $Revision: 66336 $
 * @brief
 *
 **/

define ( 'ROOT', dirname( dirname ( dirname ( __FILE__ ) ) ) );

require_once ROOT . '/conf/SQLTable.conf.php';
require_once ROOT . '/def/Common.def.php';
require_once ROOT . '/def/Data.def.php';
require_once ROOT . '/lib/Mysql.class.php';
require_once ROOT . '/lib/MysqlManager.class.php';
require_once ROOT . '/lib/partitionTable.class.php';
require_once ROOT . '/lib/Util.class.php';
require_once ROOT . '/module/MergeServer/MergeServer.class.php';
require_once ROOT . '/module/MergeServer/Items.class.php';
require_once ROOT . '/module/MergeServer/UserDao.class.php';
require_once ROOT . '/module/MergeServer/GuildDao.class.php';
require_once ROOT . '/module/MergeServer/CheckPresident.php';
require_once ROOT . '/module/SQLModify/SQLModify.class.php';
require_once ROOT . '/module/SQLModify/SQLModifyDAO.class.php';
require_once ROOT . '/module/SQLModify/VACallback.class.php';
require_once ROOT . '/module/SQLModify/IdGenerator.class.php';
require_once ROOT . '/conf/MergeGlobal.conf.php';
require_once ROOT . '/optool/rearrangeid/RearrangeId.class.php';

function print_usage()
{
	echo	"Usage:php MergeSever.php [options]:
				-tg		target merge server game id, eg:30003;
				-td		target merge server dataproxy host, eg:192.168.3.26;
				-u		db user, eg:admin
				-p		db password, eg:BabelTime
				-h		help list;
				-?		help list!\n";
}

function main()
{
	global $argc, $argv;
	
	$result = getopt('u:p:x:h:?', array('mf:', 'md:', 'tg:', 'td:'));
	
	$merge_game_id = '';
	$merge_game_db = '';
	$target_merge_game_id = 0;
	$target_merge_db_host = '';
	$db_user = '';
	$db_password = '';
	
	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'mf':
				$merge_game_id = strval($value);
				break;
			case 'md':
				$merge_game_db = strval($value);
				break;
			case 'tg':
				$target_merge_game_id = strval($value);
				break;
			case 'td':
				$target_merge_db_host = strval($value);
				break;
			case 'u':
				$db_user = strval($value);
				break;
			case 'p':
				$db_password = strval($value);
				break;
			case 'x':
				$dataxmlpath = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit;
		}
	}
	
	//检测是否存在合并服务器的ids
	if  ( empty($merge_game_id) )
	{
		fwrite(STDERR,"-mf should be set!\n");
		print_usage();
		exit;
	}
	
	//检测是否存在合并服务器的dbs
	if  ( empty($merge_game_db) )
	{
		fwrite(STDERR,"-md should be set!\n");
		print_usage();
		exit;
	}
	
	//检测是否存在目标服务器
	if ( empty($target_merge_game_id) )
	{
		fwrite(STDERR,"-tg should be set!\n");
		print_usage();
		exit;
	}
	
	//检测是否设置了目标合并服务器的DB
	if ( empty($target_merge_db_host) )
	{
		fwrite(STDERR, "-fd should be set!");
		print_usage();
		exit;
	}
	
	//检测db的用户名是否被设置
	if ( empty($db_user) )
	{
		fwrite(STDERR, "-u should be set!");
		print_usage();
		exit;
	}
	
	//检测db的密码是否被设置
	if ( empty($db_password) )
	{
		fwrite(STDERR, "-p should be set!");
		print_usage();
		exit;
	}
	
	if ( !empty($dataxmlpath) )
	{
		partitionTable::$XMLPATH = $dataxmlpath;
	}

	//设置db的用户名和密码
	DataDef::$DB_USER = $db_user;
	DataDef::$DB_PASSWORD = $db_password;

	$merge_game_id_arr = array($merge_game_id);
	$merge_game_db_arr = array($merge_game_db);
	for ($i = 0; $i < count($merge_game_id_arr); $i++)
	{
		//设置game_id和db_host的对应关系
		MysqlManager::setDBHost($merge_game_id_arr[$i], $merge_game_db_arr[$i]);
	}
	
	MysqlManager::setDBHost($target_merge_game_id, $target_merge_db_host);
	
	if ( IdGenerator::init($merge_game_id_arr, $target_merge_game_id) == FALSE )
	{
		fwrite(STDERR, "IdGenerator::init failed\n");
		exit(1);
	}

	Rearrange::merge($merge_game_id_arr, $merge_game_id, $target_merge_game_id);

	echo "MERGE ALL DONE!\n";
	
}

main ($argc, $argv);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */