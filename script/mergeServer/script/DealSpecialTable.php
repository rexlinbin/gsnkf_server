<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DealSpecialTable.php 255982 2016-08-12 08:46:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/mergeServer/script/DealSpecialTable.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-12 08:46:57 +0000 (Fri, 12 Aug 2016) $
 * @version $Revision: 255982 $
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
	echo	"Usage:php DealSpecialTable.php [options]:
				-gl		game info list. eg: 30001|192.168.3.26|30001;30002|192.168.3.26|30002;
				-u		db user, eg:admin
				-p		db password, eg:BabelTime
				-x		path of data.xml
				-h		help list;
				-t		target server ip, eg:192.168.1.1;
				-b		target db name, eg:30002;
				-?		help list!\n";
}

function getGameInfo($infoStr)
{
	$infoStr = trim($infoStr, '; ');
	$arrGameInfoStr = explode(';', $infoStr);
	$arrGameInfo = array();

	foreach($arrGameInfoStr as $gameInfoStr)
	{
		$arr = explode('|', $gameInfoStr);
		if( count($arr) != 3 )
		{
			return array();
		}
		$arrGameInfo[$arr[0]] = array(
				'dbHost' => $arr[1],
				'dbName' => $arr[2],
		);
	}
	return $arrGameInfo;
}



function getTableInfo($dataXmlPath)
{

	if ( !file_exists($dataXmlPath)  )
	{
		throw new Exception("$dataXmlPath not exist");
	}
	$dataXmlRoot = simplexml_load_file($dataXmlPath);

	$arrTableInfo = array();
	foreach( $dataXmlRoot->table as $node )
	{
		if( empty($node->name) )
		{
			throw new Exception('invalid data.xml. no name');
		}
		$tblName = strval($node->name);

		$tblInfo = array();
		if( !empty($node->partition) )
		{
			$tblInfo['partition'] = array(
					'key' => $node->partition->key,
					'method' => $node->partition->method,
					'value' => $node->partition->value,
			);
		}

		$arrTableInfo[$tblName] = $tblInfo;
	}

	return $arrTableInfo;
}

function table_is_exist($host, $db, $user, $password, $table)
{
	$mysql = new MysqlQuery();
	$mysql->setServerInfo($host, $db, $user, $password);
	$sql = sprintf("show tables like '%s%%'", $table);
	$query = $mysql->query($sql);
	return !empty($query);
}

function deal_t_travel_shop($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName)
{
	if (!table_is_exist($targetDbHost, $targetDbName, $dbUser, $dbPassword, 't_travel_shop')) 
	{
		return TRUE;
	}
	
	$now = time();
	
	$mysql = new MysqlQuery();
	$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
	$sql = sprintf("select * from t_travel_shop where id = 1");
	$query = $mysql->query($sql);
	if(!empty($query)) // 已经设置过
	{
		printf("already set num[%d] for t_travel_shop, ignore.\n", $query[0]['sum']);
	}
	else // 未设置过
	{
		// 从各个服取sum值，并且累加
		$sum = 0;
		foreach ($arrGameInfo as $gameId => $gameInfo)
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($gameInfo['dbHost'], $gameInfo['dbName'], $dbUser, $dbPassword);
			$sql = sprintf("select * from t_activity_conf where name = 'travelShop' order by version desc limit 1");
			$query = $mysql->query($sql);
				
			// 没开过这个活动，忽略
			if(empty($query))
			{
				continue;
			}
				
			// 活动已经结束，忽略
			$startTime = $query[0]['start_time'];
			$endTime = $query[0]['end_time'];
			if ($now < $startTime || $now > $endTime)
			{
				continue;
			}
				
			// 无购买总数记录，忽略
			$sql = sprintf("select * from t_travel_shop where id = 1");
			$query = $mysql->query($sql);
			if(empty($query))
			{
				continue;
			}
	
			// 上次刷新时间小于活动开始时间，忽略
			if ($query[0]['refresh_time'] < $startTime)
			{
				continue;
			}
				
			$sum += $query[0]['sum'];
			printf("get part of sum[%d] from game[%d], cur sum[%d]\n", $query[0]['sum'], $gameId, $sum);
		}
	
		// sum非0的话，需要插入db
		if (!empty($sum))
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
			$sql = sprintf("insert into t_travel_shop(id,sum,refresh_time) values(1,%d,%d)", $sum, $now);
			$mysql->query($sql);
			printf("set num[%d] for t_travel_shop\n", $sum);
		}
		else
		{
			printf("sum is 0, no need set num for t_travel_shop\n");
		}
	}
	
	return TRUE;
}

function deal_t_mission_inner_config($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName)
{	
	if (!table_is_exist($targetDbHost, $targetDbName, $dbUser, $dbPassword, 't_mission_inner_config'))
	{
		return TRUE;
	}
	
	$mysql = new MysqlQuery();
	$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
	$sql = sprintf("select * from t_mission_inner_config order by sess desc limit 1");
	$query = $mysql->query($sql);
	if(!empty($query)) // 已经设置过
	{
		printf("already set conf for sess[%d] for t_mission_inner_config\n", $query[0]['sess']);
	}
	else // 未设置过
	{
		// 遍历每一个服t_mission_inner_config表的最大一届配置
		$lastConfig = NULL;
		foreach ($arrGameInfo as $gameId => $gameInfo)
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($gameInfo['dbHost'], $gameInfo['dbName'], $dbUser, $dbPassword);
			$sql = sprintf("select * from t_mission_inner_config order by sess desc limit 1");
			$query = $mysql->query($sql);
			$curConfig = empty($query) ? array() : $query[0];
			if ($lastConfig == NULL) 
			{
				$lastConfig = $curConfig;
				printf("gameId[%d], set last config sess[%s]\n", $gameId, empty($lastConfig) ? "NULL" : $lastConfig['sess']);
			}
			else 
			{
				if (empty($curConfig) && !empty($lastConfig)) 
				{
					printf("gameId[%d] empty config, last config sess[%s]\n", $gameId, $lastConfig['sess']);
					return FALSE;
				}
				if (!empty($curConfig) && empty($lastConfig)) 
				{
					printf("gameId[%d] cur config sess[%s], last config empty\n", $gameId, $curConfig['sess']);
					return FALSE;
				}
				if ($curConfig['sess'] != $lastConfig['sess']) 
				{
					//这里先不判断啦，可能真的有活动开了，但没有一个人玩的情况
					//printf("gameId[%d] cur config sess[%s], last config sess[%s], diff\n", $gameId, $curConfig['sess'], $lastConfig['sess']);
					//return FALSE;
				}
				if ($curConfig['update_time'] > $lastConfig['update_time']) 
				{
					$lastConfig = $curConfig;
					printf("gameId[%d], set last config sess[%s], update time[%s]\n", $gameId, $lastConfig['sess'], strftime('%Y%m%d %H%M%S', $lastConfig['update_time']));
				}
				else 
				{
					printf("gameId[%d], old update time, ignore\n", $gameId);
				}
			}
		}
		
		if (!empty($lastConfig)) 
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
			$sql = sprintf("insert into t_mission_inner_config(sess, update_time, va_missconfig) values(%d, %d, UNHEX(\"%s\"))", $lastConfig['sess'], $lastConfig['update_time'], bin2hex(Util::amfEncode($lastConfig['va_missconfig'])));
			$mysql->query($sql);
			printf("insert db, config sess[%s]\n", $lastConfig['sess']);
		}
		else 
		{
			printf("empty config, no need insert db\n");
		}
	}
	
	return TRUE;
}

function deal_t_countrywar_inner_user($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName)
{
	if (!table_is_exist($targetDbHost, $targetDbName, $dbUser, $dbPassword, 't_countrywar_inner_user'))
	{
		return TRUE;
	}
	
	$mysql = new MysqlQuery();
	$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
	$sql = sprintf("select * from t_countrywar_inner_user limit 1");
	$query = $mysql->query($sql);
	if(!empty($query)) // 已经设置过
	{
		printf("already deal t_countrywar_inner_user\n");
		return TRUE;
	}
	
	foreach ($arrGameInfo as $gameId => $gameInfo)
	{
		printf("deal t_countrywar_inner_user for game[%s]\n", $gameId);
		$retainCount = 0;
		$ignoreCount = 0;
		$offset = 0;
		while(TRUE)
		{
			$mysql = new MysqlQuery();
			$mysql->setServerInfo($gameInfo['dbHost'], $gameInfo['dbName'], $dbUser, $dbPassword);
			$sql = sprintf("select * from t_countrywar_inner_user order by server_id,pid limit %d,100", $offset);
			$arrRecord = $mysql->query($sql);
			
			foreach ($arrRecord as $aRecord)
			{
				$mysql = new MysqlQuery();
				$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
				$sql = sprintf("select uid from t_user where server_id = %d and pid = %d", $aRecord['server_id'], $aRecord['pid']);
				$ret = $mysql->query($sql);
				$isRetain = !empty($ret[0]['uid']) ? TRUE : FALSE;
				
				if ($isRetain) 
				{
					$mysql = new MysqlQuery();
					$mysql->setServerInfo($targetDbHost, $targetDbName, $dbUser, $dbPassword);
					$sql = sprintf("insert into t_countrywar_inner_user(pid, server_id, support_pid, support_server_id, support_side, worship_time, audition_reward_time, support_reward_time, final_reward_time, update_time) values(%d, %d, %d, %d, %d, %d, %d, %d, %d, %d)"
							, $aRecord['pid'], $aRecord['server_id'], $aRecord['support_pid'], $aRecord['support_server_id'], $aRecord['support_side']
							, $aRecord['worship_time'], $aRecord['audition_reward_time'], $aRecord['support_reward_time'], $aRecord['final_reward_time'], $aRecord['update_time']);
					$mysql->query($sql);
					$retainCount++;
				}
				else 
				{
					$ignoreCount++;
				}
			}
			
			if (count($arrRecord) < 100) 
			{
				break;
			}
			else 
			{
				$offset += 100;
			}
			
			usleep(2000);
		}
		printf("deal t_countrywar_inner_user for game[%d] done! retain count[%d], ignore count[%d]\n", $gameId, $retainCount, $ignoreCount);
	}
	
	return TRUE;
}

function main()
{
	global $argc, $argv;

	$result = getopt('u:p:h:x:t:b:?', array('gl:'));

	$gameInfoList='';
	$dbUser = '';
	$dbPassword = '';
	$dataXmlPath = '';
	$targetDbHost = '';
	$targetDbName = '';

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'gl':
				$gameInfoList = strval($value);
				break;
			case 'u':
				$dbUser = strval($value);
				break;
			case 'p':
				$dbPassword = strval($value);
				break;
			case 'x':
				$dataXmlPath = strval($value);
				break;
			case 't':
				$targetDbHost = strval($value);
				break;
			case 'b':
				$targetDbName = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit(1);
		}
	}

	//检测是否存在目标服务器
	if ( empty($gameInfoList) )
	{
		fwrite(STDERR,"-gl should be set!\n");
		print_usage();
		exit(1);
	}

	//检测db的用户名是否被设置
	if ( empty($dbUser) )
	{
		fwrite(STDERR, "-u should be set!");
		print_usage();
		exit(1);
	}

	//检测db的密码是否被设置
	if ( empty($dbPassword) )
	{
		fwrite(STDERR, "-p should be set!");
		print_usage();
		exit(1);
	}
	
	//检测target的ip是否被设置
	if ( empty($targetDbHost) )
	{
		fwrite(STDERR, "-t should be set!");
		print_usage();
		exit(1);
	}
	
	//检测target的db是否被设置
	if ( empty($targetDbName) )
	{
		fwrite(STDERR, "-b should be set!");
		print_usage();
		exit(1);
	}

	//检查dataxml是否被设置
	if( empty($dataXmlPath) )
	{
		fwrite(STDERR, "-x should be set!");
		print_usage();
		exit(1);
	}

	$arrGameInfo = getGameInfo($gameInfoList);

	if( empty($arrGameInfo) )
	{
		throw new Exception('invalid gameInfoList:%s', $gameInfoList);
	}

	$arrTableInfo = getTableInfo($dataXmlPath);
	
	$now = time();
	
	// 1 处理云游商人的t_travel_shop表	
	if (!deal_t_travel_shop($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName)) 
	{
		printf("deal t_travel_shop error.\n");
		exit(1);
	}
	printf("deal_t_travel_shop done\n");
	
	// 2 处理悬赏榜的t_mission_inner_config表
	if (!deal_t_mission_inner_config($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName)) 
	{
		printf("deal t_mission_inner_config error.\n");
		exit(1);
	}
	printf("deal_t_mission_inner_config done\n");
	
	// 3 处理国战的t_countrywar_inner_user表
	if (!deal_t_countrywar_inner_user($arrGameInfo, $dbUser, $dbPassword, $arrTableInfo, $targetDbHost, $targetDbName))
	{
		printf("deal t_countrywar_inner_user error.\n");
		exit(1);
	}
	printf("deal_t_countrywar_inner_user done\n");
}

main ($argc, $argv);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */