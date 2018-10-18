<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: export.php 31239 2012-11-19 11:27:11Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/script/export.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2012-11-19 19:27:11 +0800 (星期一, 19 十一月 2012) $
 * @version $Revision: 31239 $
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
	echo	"Usage:php MergeSever.php [options]:
				-u		db user, eg:admin;
				-p		db pa, eg:admin;
				-t		server ip, eg:192.168.1.1;
				-d		db name, eg:30002;
				-h		help list;
				-?		help list!\n";
}

function exportData($mysql, $table)
{
	$start = 0;
	$limit = 64;
	$max_limit = 65535;
	for ( $i = 0; $i < $max_limit; $i++ )
	{
		$query = $mysql->query("select * from $table limit $start, $limit");
		foreach ( $query as $rows )
		{
			$columns = "";
			$column_values = "";
			foreach ( $rows as $row => $row_value )
			{
				if ( !empty($columns) )
				{
					$columns .= ", ";
				}
				$columns .= "`$row`";

				if ( !empty($column_values) )
				{
					$column_values .= ", ";
				}

				//deal va
				if ( strpos($row, "va_") === 0 )
				{
					$row_value = Util::AMFEncode($row_value);
					$column_values .= "UNHEX(\"" . bin2hex($row_value) . "\")";
				}
				else
				{
					if ( is_string($row_value) )
					{
						$column_values .= "UNHEX(\"" . bin2hex($row_value) . "\")";
					}
					else
					{
						$column_values .= "\"$row_value\"";
					}
				}
			}
			$sql = "INSERT IGNORE INTO $table ($columns) values($column_values);\n";
			echo $sql;
		}

		if ( count($query) < $limit )
		{
			break;
		}
		else
		{
			$start += count($query);
		}
	}
}

function main()
{
	global $argc, $argv;

	$result = getopt('u:p:t:d:h:?');

	$server = '';
	$dbname = '';
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
				$server = strval($value);
				break;
			case 'd':
				$dbname = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit;
		}
	}

	if  ( empty($user) )
	{
		fwrite(STDERR,"-u should be set!\n");
		print_usage();
		exit;
	}

	if  ( empty($passwd) )
	{
		fwrite(STDERR,"-p should be set!\n");
		print_usage();
		exit;
	}

	if  ( empty($server) )
	{
		fwrite(STDERR,"-t should be set!\n");
		print_usage();
		exit;
	}

	if  ( empty($dbname) )
	{
		fwrite(STDERR,"-d should be set!\n");
		print_usage();
		exit;
	}

	$mysql = new MysqlQuery();

	$mysql->setServerInfo($server, $dbname, $user, $passwd);

	$query = $mysql->query("show tables;");
	foreach ( $query as $data )
	{
		foreach ( $data as $key => $value )
		{
			exportData($mysql, $value);
		}
	}

	fwrite(STDERR,"EXPORT END!\n");
}

main ($argc, $argv);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */