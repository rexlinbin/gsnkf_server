<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: GenerateGameConf.php 103337 2014-04-23 12:45:06Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/script/GenerateGameConf.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-04-23 20:45:06 +0800 (星期三, 23 四月 2014) $
 * @version $Revision: 103337 $
 * @brief
 *
 **/

/**
 *
 * 产生新的Game.cfg.php
 *
 * @author pkujhd
 */

function print_usage()
{
	echo	"Usage:php ExportPokerUpdate.php [options]:
				-p		server game config path, eg:/home/pirate/
				-s		server list string, eg:20001;20002;20003 ;
				-h		help list;
				-?		help list!\n";
}

const FILE_CONJ = "/";
const DEFAULT_GAME_CONF_NAME = "Game.cfg.php";
const CONF_CLASS_PREFIX = "GameConf";
const LINE_END = "\n";

const MERGE_DATE_KEY_SELF = 'self';

const HEADER_GAME_CONF =
"<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * \$Id: Game.cfg.php 19974 2012-05-08 12:19:43Z HongyuLan \$
 *
 **************************************************************************/

/**
 * @file \$HeadURL: svn://192.168.1.80:3698/C/tags/pirate/rpcfw/rpcfw_1-0-0-46/conf/gsc/game001/Game.cfg.php \$
 * @author \$Author: jiahaidong \$(jhd@babeltime.com)
 * @date \$Date: 2012-05-08 00:00:00 +0800 (Tue, 08 May 2012)\$
 * @version \$Revision: 1 \$
 * @brief
 *
 **/

class GameConf
{
";
const COMMENT_MERGEDATE =
"
	/**
	 * 上次合服时间
	 * @var string
	 */
";
const COMMENT_MERGE_SERVER_DATASETTING =
"
	/**
	 * 合服补偿, 两个服的开服时间
	 */
";
const COMMENT_MERGE_DATES =
"
	/**
	 * 合服日期，用于记录多次合服的数据
	 */
";
const COMMENT_MERGE_LY_SERVER_ID =
"
	/**
	 * 联运和服server id 数字，
	 * @var array
	 */
";
const COMMENT_OPEN_DATE =
"
	/**
	 * 开服年月日
	 * @var string
	 */
";
const COMMENT_OPEN_TIME =
"
	/**
	 * 开服时分秒
	 * @var string
	 */
";
const COMMENT_BOSS_OFFSET =
"
	/**
	 * boss 错峰时间偏移
	 * @var int
	 */
";
const COMMENT_LY_KEY =
"
	/**
	 * 与联运平台的key系统
	 */
";
const COMMENT_LY_SERVER_ID =
"
	/**
	 * 联运平台的server_id
	 */
";
const COMMENT_NEWER_CARD =
"
	/**
	 * 新手卡，值的意义具体看平台接口
	 */
    public static \$LY_BEGINNER_REWARD =  array(
		array('item_id' => 0, 'item_num' => 50000, 'item_type' => 1),
		array('item_id' => 0, 'item_num' => 50, 'item_type' => 3),
		array('item_id' => 0, 'item_num' => 20000, 'item_type' => 2)
	);
";
const ENDDER_GAME_CONF =
"
}

/**
 * 如果需要修改竞技场持续天数，
 * 应该也同时修改竞技场开始日期为当前日期
 *
 * @author idyll
 *
 */
class ArenaDateConf
{
	//持续天数
	const LAST_DAYS = 1;

	//锁定时间
	const LOCK_START_TIME = \"22:00:00\";

	//锁定结束时间
	const LOCK_END_TIME = \"22:30:00\";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
";

function minserver($serverlist)
{
	$min_server_id = "";
	$min = 2000000000;
	foreach ( $serverlist as $serverid )
	{
		$list = getgroupids($serverid);
		foreach ( $list as $value )
		{
			if ( $value < $min )
			{
				$min = $value;
				$min_server_id = $serverid;
			}
		}
	}
	return $min_server_id;
}

function getgroupids($serverid)
{
	if ( empty($serverid) )
	{
		return array();
	}
	$serverids = explode("_", $serverid);
	if ( count($serverids) == 1 )
	{
		return $serverids;
	}
	else
	{
		for ( $i = 1; $i < count($serverids); $i++ )
		{
			$serverids[$i] = $serverids[$i] + $serverids[0];
		}
		unset($serverids[0]);
		return $serverids;
	}
}

function serverids($serverlist)
{
	$return = array();
	foreach ( $serverlist as $serverid )
	{
		$return = array_merge($return , getserverid($serverid));
	}
	return $return;
}

function getserverid($serverid)
{
	if ( empty($serverid) )
	{
		return array();
	}
	$serverids = explode("_", $serverid);
	if ( count($serverids) == 1 )
	{
		$serverids[0] = $serverids[0] % 1000;
 		return $serverids;
	}
	else
	{
		unset($serverids[0]);
		return $serverids;
	}
}

function genMergeDateStr($arrMergeDate, $linePre = "\t", $lineEnd = "\n")
{
	$str = sprintf("array(%s", $lineEnd);

	foreach($arrMergeDate as $serverId => $mergeDate)
	{
		if( is_array($mergeDate))
		{
			$str .= sprintf("\t%s'%s' => %s,%s", $linePre, $serverId, genMergeDateStr($mergeDate, $linePre."\t", $lineEnd), $lineEnd);
		}
		else
		{
			$str .= sprintf("\t%s'%s' => '%s',%s", $linePre, $serverId, $mergeDate, $lineEnd);
		}
	}
	$str .= sprintf("%s)",$linePre);

	return $str;
}

function main()
{
	global $argc, $argv;

	$result = getopt('s:p:h:t:?');

	$path = '';
	$serverstr = '';
	$target = '';

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 's':
				$serverstr = strval($value);
				break;
			case 'p':
				$path = strval($value);
				break;
			case 't':
				$target = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit;
		}
	}

	if  ( empty($path) )
	{
		fwrite(STDERR,"-u should be set!\n");
		print_usage();
		exit;
	}

	$serverlist=explode(";", $serverstr);
	if  ( empty($serverstr) || count($serverlist) <= 1 )
	{
		fwrite(STDERR,"-p should be set!\n");
		print_usage();
		exit;
	}
	$minserver=minserver($serverlist);
	$serverids=serverids($serverlist);

	$mergeinfo = array();
	$opendate = "";
	$opentime = "";
	$bossoffset =0;
	$ly_key = "";
	$ly_server_id = "";

	foreach ( $serverlist as $server )
	{
		$filename=$path. FILE_CONJ . "game$server". FILE_CONJ . DEFAULT_GAME_CONF_NAME;
		if ( !file_exists($filename) )
		{
			fwrite(STDERR,"$filename is not exist!".LINE_END);
			exit(1);
		}
		@include_once($filename);
		$classname = CONF_CLASS_PREFIX.$server;
		if ( !class_exists($classname) )
		{
			fwrite(STDERR,"class $classname is not exist!".LINE_END);
			exit(1);
		}
		if ( isset($classname::$MERGE_SERVER_DATASETTING) &&
			defined("$classname::MERGE_SERVER_OPEN_DATE") )
		{
			$__date = date("Ymd", strtotime(trim($classname::MERGE_SERVER_OPEN_DATE)));
			//想让self在前面
			$mergeinfo[$server] = array(MERGE_DATE_KEY_SELF => $__date) + $classname::$MERGE_SERVER_DATASETTING;
		}
		else if ( !isset($classname::$MERGE_SERVER_DATASETTING) &&
			!defined("$classname::MERGE_SERVER_OPEN_DATE") )
		{
			$mergeinfo[$server] = $classname::SERVER_OPEN_YMD;
		}
		else
		{
			fwrite(STDERR,"server $server config has error!".LINE_END);
			exit(1);
		}
		if ( $server == $minserver )
		{
			$opendate = $classname::SERVER_OPEN_YMD;
			if ( defined("$classname::SERVER_OPEN_TIME") )
			{
				$opentime = $classname::SERVER_OPEN_TIME;
			}
			$bossoffset = $classname::BOSS_OFFSET;
			/*
			if ( defined("$classname::LY_KEY") )
			{
				$ly_key = $classname::LY_KEY;
			}
			if ( defined("$classname::LY_SERVER_ID") )
			{
				$ly_server_id = $classname::LY_SERVER_ID;
			}
			*/
		}
	}

	$file = fopen($target, "w");
	fwrite($file, HEADER_GAME_CONF);
	fwrite($file, COMMENT_MERGEDATE);
	$mergeServerTime = date("Ymd", time()) . "100000";
	if ( strtotime($mergeServerTime) > time() )
	{
		$mergeServerTime = date("Ymd", time()-86400) . "100000";
	}
	
	fwrite($file, "\tconst MERGE_SERVER_OPEN_DATE = '" . $mergeServerTime . "';".LINE_END);
	fwrite($file, LINE_END);

	fwrite($file, COMMENT_MERGE_SERVER_DATASETTING);
	fwrite($file, "\tpublic static \$MERGE_SERVER_DATASETTING = ");
	
	$mergeDateStr = genMergeDateStr($mergeinfo, "\t", LINE_END);
	fwrite($file, $mergeDateStr);
	
	fwrite($file, ";".LINE_END);
	fwrite($file, LINE_END);

	/*
	// write merge lianyun server id
	if ( !empty($ly_server_id) )
	{
		fwrite($file, COMMENT_LY_SERVER_ID);
		fwrite($file, "\tpublic static \$MERGE_LY_SERVER_ID = array(".LINE_END);
		foreach($serverids as $serverid)
		{
			fwrite($file, "\t\t'S$serverid',".LINE_END);
		}
		fwrite($file, "\t);".LINE_END);
	}
	*/

	fwrite($file, COMMENT_OPEN_DATE);
	fwrite($file, "\tconst SERVER_OPEN_YMD = '$opendate';".LINE_END);
	fwrite($file, LINE_END);
	if ( !empty($opentime) )
	{
		fwrite($file, COMMENT_OPEN_TIME);
		fwrite($file, "\tconst SERVER_OPEN_TIME = '$opentime';" . LINE_END);
		fwrite($file, LINE_END);
	}

	fwrite($file, COMMENT_BOSS_OFFSET);
	fwrite($file, "\tconst BOSS_OFFSET = $bossoffset;".LINE_END);
	fwrite($file, LINE_END);

	/*
	if ( !empty($ly_key) || !empty($ly_server_id) )
	{
		fwrite($file, COMMENT_LY_KEY);
		fwrite($file, "\tconst LY_KEY = '$ly_key';".LINE_END);
		fwrite($file, LINE_END);

		fwrite($file, COMMENT_LY_SERVER_ID);
		fwrite($file, "\tconst LY_SERVER_ID = '$ly_server_id';".LINE_END);
		fwrite($file, LINE_END);

		fwrite($file, COMMENT_NEWER_CARD);
	}
	*/

	fwrite($file, ENDDER_GAME_CONF);
	fclose($file);
}

main ($argc, $argv);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
