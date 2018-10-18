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
	echo	"Usage:php CheckGame.php [options]:
				-gl		game info list. eg: 30001|192.168.3.26|30001;30002|192.168.3.26|30002;
				-u		db user, eg:admin
				-p		db password, eg:BabelTime
				-x		path of data.xml
				-h		help list;
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


function main()
{
	global $argc, $argv;

	$result = getopt('u:p:h:x:?', array('gl:'));

	$gameInfoList='';
	$dbUser = '';
	$dbPassword = '';
	$dataXmlPath = '';

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

	//1. 检查一下是否有不认识的表
	list($gameId, $gameInfo) = each($arrGameInfo);
	$mysql = new MysqlQuery();
	$dbHost = $gameInfo['dbHost'];
	$dbName = $gameInfo['dbName'];
	$mysql->setServerInfo($dbHost, $dbName, $dbUser, $dbPassword);
	
	$query = $mysql->query("show  tables;");
	if( empty($query) )
	{
		fwrite(STDERR, "not found any tables");
		exit(1);
	}

	$arrDealTable = SQLTableConf::$SQLDELETE;
	foreach( SQLTableConf::$SQLMODIFYTABLE as $value )
	{
		$arrDealTable = array_merge($arrDealTable, array_keys($value));
	}
	$arrDealTable = array_merge($arrDealTable, array('t_item', 't_tmp_guild', 't_tmp_user', 't_tmp_id_info', 't_tmp_id_proto', 't_tmp_item_id', 't_tmp_slim_user')); 
	
	
	$arrNoDealTable = array();
	foreach( $query as $value )
	{
		$tblName = current($value);
		if( preg_match('/([a-z\_]+)_([0-9]+)/', $tblName, $arr) )
		{
			$tblName = $arr[1];
		}
		if( !in_array($tblName, $arrDealTable) )
		{
			$arrNoDealTable[] = $tblName;
		}
	}
	if( !empty($arrNoDealTable) )
	{
		printf("some table not deal:%s\n", var_export($arrNoDealTable, true));
		exit(1);
	} 
	
	
	//2. 检查一下活动配置，是否有什么活动和合服冲突
	/*
	 	此处检查过严
	 	1）活动没有检查开服时间限制。一般被合的服都是老服，都应该在活动开服时间要求内
	 	2）跨服战没有检查是否在分组内
	 */
	$now = time();
	$inActivity = false;
	//$arrNeedCheckActivity = array( 'groupon', 'lordwar' );
	$arrNeedCheckActivity = array( 'lordwar', 'guildwar' );
	foreach ($arrGameInfo as $gameId => $gameInfo)
	{
		$mysql = new MysqlQuery();
		$dbHost = $gameInfo['dbHost'];
		$dbName = $gameInfo['dbName'];
		$mysql->setServerInfo($dbHost, $dbName, $dbUser, $dbPassword);
		
		foreach( $arrNeedCheckActivity as $activityName )
		{
			$sql = sprintf("select * from t_activity_conf where name = '%s' order by version desc limit 1 ", $activityName);
			$query = $mysql->query($sql);
			if(empty($query))
			{
				continue;
			}
			$startTime = $query[0]['start_time'];
			$endTime = $query[0]['end_time'];
			
			if($now >= $startTime && $now <= $endTime )
			{
				if( $activityName == 'lordwar' )
				{
					$conf = $query[0]['va_data'][1];
					$arrRoundStartTime = $conf['startTimeArr'];
					$lastRoundTimeConf = end($arrRoundStartTime);
					$lastRoundStartTime = $startTime + $lastRoundTimeConf[0]*86400 + $lastRoundTimeConf[1];
					$lastRoundEndTime = $lastRoundStartTime + ($conf['loseNumArr'][1]*2-1) * $conf['subRoundGapCross'];
					//跨服决赛打完之后2个小时，才允许合服
					if( $now < $lastRoundEndTime + 3600*2 )
					{
						printf("game:%s in %s activity. start:%s end:%s, lastRoundEnd:%s\n",
						$gameId, $activityName, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', $endTime), date('Y-m-d H:i:s', $lastRoundEndTime));
						$inActivity = true;
					}
				}
				else if( $activityName == 'guildwar' )
				{
					$conf = $query[0]['va_data'][1];
					$arrRoundTimeConf = $conf['time_config'];
					$lastRoundTimeConf = end($arrRoundTimeConf);
					$lastRoundEndTime = $startTime + $lastRoundTimeConf[1];
					//跨服决赛打完之后12个小时，才允许合服
					if( $now < $lastRoundEndTime + 3600*12 )
					{
						printf("game:%s in %s activity. start:%s end:%s, lastRoundEnd:%s\n",
						$gameId, $activityName, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', $endTime), date('Y-m-d H:i:s', $lastRoundEndTime));
						$inActivity = true;
					}
				}
				else
				{
					printf("game:%s in %s activity. start:%s end:%s\n",
						$gameId, $activityName, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', $endTime));
					$inActivity = true;
				}
				
			}
		}
		
		
		
	}

	
	if($inActivity)
	{
		exit(1);
	}
	
}

main ($argc, $argv);



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */