<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossModifyLevel.class.php 65620 2013-09-22 08:41:21Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/script/BossModifyLevel.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-09-22 16:41:21 +0800 (星期日, 22 九月 2013) $
 * @version $Revision: 65620 $
 * @brief
 *
 **/

require_once dirname ( dirname ( __FILE__ ) ) . '/conf/SQLTable.conf.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/def/Common.def.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/def/Data.def.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/Mysql.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/MysqlManager.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/partitionTable.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/Util.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/MergeServer/MergeServer.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/MergeServer/Items.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/MergeServer/UserDao.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/MergeServer/GuildDao.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/MergeServer/CheckPresident.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/SQLModify/SQLModify.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/SQLModify/SQLModifyDAO.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/module/SQLModify/VACallback.class.php';

function print_usage()
{
	echo	"Usage:php BossModifyLevel.php [options]:
				-u		db user, eg:admin;
				-p		db pa, eg:admin;
				-t		target server ip, eg:192.168.1.1;
				-d		target db name, eg:30002;
				-s		src server ip, eg:192.168.1.1
				-b		src db name, eg:30002;
				-h		help list;
				-?		help list!\n";
}

function main()
{
	global $argc, $argv;

	$result = getopt('u:p:t:d:s:b:h:?');

	$src_server = '';
	$tar_server = '';
	$src_dbname = '';
	$tar_dbname = '';
	$user = '';
	$passwd = '';

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'u':
				$user = strval($value);
				break;
			case 'p':
				$passwd = strval($value);
				break;
			case 't':
				$tar_server = strval($value);
				break;
			case 'd':
				$tar_dbname = strval($value);
				break;
			case 's':
				$src_server = strval($value);
				break;
			case 'b':
				$src_dbname = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit(1);
		}
	}

	if  ( empty($user) )
	{
		fwrite(STDERR,"-u should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($passwd) )
	{
		fwrite(STDERR,"-p should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($tar_server) )
	{
		fwrite(STDERR,"-t should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($tar_dbname) )
	{
		fwrite(STDERR,"-d should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($src_server) )
	{
		fwrite(STDERR,"-s should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($src_dbname) )
	{
		fwrite(STDERR,"-b should be set!\n");
		print_usage();
		exit(1);
	}

	$mysql = new MysqlQuery();

	$mysql->setServerInfo($src_server, $src_dbname, $user, $passwd);

	$target_mysql = new MysqlQuery();

	$target_mysql->setServerInfo($tar_server, $tar_dbname, $user, $passwd);

	//DEAL BOSS
	$query = $mysql->query("select * from t_boss");
	foreach ( $query as $rows )
	{
		$boss_id = intval($rows['boss_id']);
		$hp = intval($rows['hp']);
		$level = intval($rows['level']);
		$target_mysql->query("update t_boss set hp = $hp, level = $level where boss_id = $boss_id;");
	}

	fwrite(STDERR,"BOSS FIXED END!\n");
}

main ($argc, $argv);
exit(0);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */