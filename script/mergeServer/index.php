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

require_once dirname ( __FILE__ ) . '/conf/SQLTable.conf.php';
require_once dirname ( __FILE__ ) . '/def/Common.def.php';
require_once dirname ( __FILE__ ) . '/def/Data.def.php';
require_once dirname ( __FILE__ ) . '/lib/Mysql.class.php';
require_once dirname ( __FILE__ ) . '/lib/MysqlManager.class.php';
require_once dirname ( __FILE__ ) . '/lib/partitionTable.class.php';
require_once dirname ( __FILE__ ) . '/lib/Util.class.php';
require_once dirname ( __FILE__ ) . '/module/MergeServer/MergeServer.class.php';
require_once dirname ( __FILE__ ) . '/module/MergeServer/Items.class.php';
require_once dirname ( __FILE__ ) . '/module/MergeServer/UserDao.class.php';
require_once dirname ( __FILE__ ) . '/module/MergeServer/GuildDao.class.php';
require_once dirname ( __FILE__ ) . '/module/MergeServer/CheckPresident.php';
require_once dirname ( __FILE__ ) . '/module/SQLModify/SQLModify.class.php';
require_once dirname ( __FILE__ ) . '/module/SQLModify/SQLModifyDAO.class.php';
require_once dirname ( __FILE__ ) . '/module/SQLModify/VACallback.class.php';
require_once dirname ( __FILE__ ) . '/module/SQLModify/IdGenerator.class.php';
require_once dirname ( __FILE__ ) . '/conf/MergeGlobal.conf.php';

function print_usage()
{
	echo	"Usage:php MergeSever.php [options]:
				-mf		first merge server game id, eg:30001;
				-md		second merge server game id, eg:30002;
				-tg		target merge server game id, eg:30003;
				-td		target merge server dataproxy host, eg:192.168.3.26;
				-mp		multi process num: eg:1/2/4, default 1;
				-u		db user, eg:admin
				-p		db password, eg:BabelTime
				-h		help list;
				-?		help list!\n";
}

function main()
{
	global $argc, $argv;

	$result = getopt('u:p:x:h:?', array('mf:', 'md:', 'tg:', 'td:', 'mp:'));

	$merge_game_ids = '';
	$merge_game_dbs = '';
	$target_merge_game_id = 0;
	$target_merge_db_host = '';
	$db_user = '';
	$db_password = '';
	$multi_proccess_num = 1;

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'mf':
				$merge_game_ids = strval($value);
				break;
			case 'md':
				$merge_game_dbs = strval($value);
				break;
			case 'tg':
				$target_merge_game_id = strval($value);
				break;
			case 'td':
				$target_merge_db_host = strval($value);
				break;
			case 'mp':
				$multi_proccess_num = intval($value);
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
	if  ( empty($merge_game_ids) || !file_exists($merge_game_ids) )
	{
		fwrite(STDERR,"-mf should be set!\n");
		print_usage();
		exit;
	}

	//检测是否存在合并服务器的dbs
	if  ( empty($merge_game_dbs) || !file_exists($merge_game_dbs))
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

	if ( $multi_proccess_num <= 0 )
	{
		$multi_proccess_num = 1;
	}

	if ( !empty($dataxmlpath) )
	{
		partitionTable::$XMLPATH = $dataxmlpath;
	}

	//设置db的用户名和密码
	DataDef::$DB_USER = $db_user;
	DataDef::$DB_PASSWORD = $db_password;

	$merge_game_id_arr = file_get_contents($merge_game_ids);
	$merge_game_id_arr = explode("\n", trim($merge_game_id_arr));

	$merge_game_db_arr = file_get_contents($merge_game_dbs);
	$merge_game_db_arr = explode("\n", trim($merge_game_db_arr));

	for ($i = 0; $i < count($merge_game_id_arr); $i++)
	{
		//设置game_id和db_host的对应关系
		MysqlManager::setDBHost($merge_game_id_arr[$i], $merge_game_db_arr[$i]);
	}

	MysqlManager::setDBHost($target_merge_game_id, $target_merge_db_host);
	
	sort($merge_game_id_arr);
	if ( IdGenerator::init($merge_game_id_arr, $target_merge_game_id) == FALSE )
	{
		fwrite(STDERR, "IdGenerator::init failed\n");
		exit(1);
	}

	$has_mistake = FALSE;
	for ( $i=0; $i < count($merge_game_id_arr);)
	{
		for ( $j=0; $j < $multi_proccess_num; $j++, $i++ )
		{
			if ( !isset($merge_game_id_arr[$i]) )
			{
				break;
			}
			$pid = pcntl_fork();
			if ( $pid == 0 )
			{
				MysqlManager::clearDbLink();
				MergeServer::merge($merge_game_id_arr, $merge_game_id_arr[$i], $target_merge_game_id);
				return;
			}
			else
			{
				$pidlist[] = $pid;
			}
		}
		for ( $j = 0; $j < count($pidlist); $j++ )
		{
			$status = 0;
			pcntl_waitpid($pidlist[$j], $status);
			if ( $status != 0 )
			{
				$has_mistake = TRUE;
			}
		}
		$pidlist = array();
	}

	if ( $has_mistake == TRUE )
	{
		exit(1);
	}
	else
	{
		echo "MERGE ALL DONE!\n";
	}
}

main ($argc, $argv);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */