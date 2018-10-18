<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
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

	$srcServer = '';
	$tarServer = '';
	$srcDbname = '';
	$tarDbname = '';
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
				$tarServer = strval($value);
				break;
			case 'd':
				$tarDbname = strval($value);
				break;
			case 's':
				$srcServer = strval($value);
				break;
			case 'b':
				$srcDbname = strval($value);
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

	if  ( empty($tarServer) )
	{
		fwrite(STDERR,"-t should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($tarDbname) )
	{
		fwrite(STDERR,"-d should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($srcServer) )
	{
		fwrite(STDERR,"-s should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($srcDbname) )
	{
		fwrite(STDERR,"-b should be set!\n");
		print_usage();
		exit(1);
	}

	$mysql = new MysqlQuery();

	$mysql->setServerInfo($srcServer, $srcDbname, $user, $passwd);

	$targetMysql = new MysqlQuery();

	$targetMysql->setServerInfo($tarServer, $tarDbname, $user, $passwd);

	$now = time();
	
	$arrTimerMethod = array('heroshop.rewardUserOnActClose', 'roulette.rewardUserBfClose');
	
	$curTid = 0; //GenID时会把tid设成100，所以这里能使用100以下的tid
	foreach( $arrTimerMethod as $timerMethod )
	{
		$sql = sprintf("select * from t_timer where execute_method = '%s' and  status = 1", $timerMethod);
		$query = $mysql->query($sql);
		if( empty($query) )
		{
			printf("no timer:%s\n", $timerMethod);
		}
		else
		{
			$executeTime = $query[0]['execute_time'];
			if( $executeTime > $now  )
			{
				printf("insert timer. method:%s,execute_time:%s\n",
				$query[0]['execute_method'], date('Y-m-d H:i:s', $query[0]['execute_time']));
					
				$curTid += 1;
				if ( $curTid > 99 )
				{
					printf("FATAL: not enough tid, arrTimerMethod:%s\n", var_export($arrTimerMethod, true));
					exit(1);
				}
				$query[0]['tid'] = $curTid;
				$sql = Util::genInsertSql('t_timer', $query[0]);
				printf("%s\n", $sql);
				$targetMysql->query($sql);
			}
			else
			{
				printf("WARN: method:%s time:%s not execute, please check it\n", $query[0]['execute_method'], date('Y-m-d H:i:s', $query[0]['execute_time']) );
			}
		}
	}
	

	
	fwrite(STDERR,"FIX GAME TIMER END!\n");
}

main ($argc, $argv);
exit(0);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
