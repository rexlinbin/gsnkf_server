<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ZKManager.php 97547 2014-04-03 13:38:31Z HaidongJia $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/script/ZKManager.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-04-03 21:38:31 +0800 (星期四, 03 四月 2014) $
 * @version $Revision: 97547 $
 * @brief
 *
 **/
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/Util.class.php';
require_once dirname ( dirname ( __FILE__ ) ) . '/lib/ZK.class.php';

function print_usage()
{
	echo	"Usage:php ZKManager.php [options]:
				-o	operand,
						eg:	getMainIP;
							getMainRefer;
							getMDBIP;
							getSDBIP;
							getDataproxyIP;
							getLogicIP;
							get;
							dump;
							set;
							nodes;
							create;
							delete;
				-p	zookeeper path, eg:/pirate/dataproxy;
				-g	group, eg:192.168.1.1;
				-d	dir, eg:/home/pirate/dataproxy;
				-s	server id, eg:30001
				-z	zkhost, eg:30002;
				-h	help list;
				-?	help list!\n";
}

function getMDBIP($path, $zkhost, $group)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path.$group, "");
	if ( isset($return["mdb_host"]) )
	{
		return trim($return["mdb_host"]);
	}
	else
	{
		return FALSE;
	}
}

function getDataproxyIP($path, $zkhost, $serverid)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path.$serverid, "");
	if ( isset($return["host"]) )
	{
		return trim($return["host"]);
	}
	else
	{
		return FALSE;
	}
}

function getSDBIP($path, $zkhost, $serverid)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path.$serverid, "");
	if ( isset($return["sdb_host"]) )
	{
		return trim($return["sdb_host"]);
	}
	else if ( isset($return["host"]) )
	{
		return trim($return["host"]);
	}
	else
	{
		return FALSE;
	}
}

function getLogicIP($path, $zkhost)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path, "");
	$string = FALSE;
	if ( $return !== FALSE )
	{
		foreach ( $return as $key => $value )
		{
			$string .= trim($value['host']) . "\n";
		}
	}
	return $string;
}

function getLogic($path, $zkhost)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path, "");
	$string = FALSE;
	if ( $return !== FALSE )
	{
		foreach ( $return as $key => $value )
		{
			$string .= trim($value['host']) . "\n";
		}
	}
	return $string;
}

function getMainIP($path, $zkhost, $serverid)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path.$serverid, "");
	if ( isset($return["host"]) )
	{
		return trim($return["host"]);
	}
	else
	{
		return FALSE;
	}
}

function getMainRefer($path, $zkhost, $serverid)
{
	$zk = new ZK($zkhost);
	$return = $zk->execute('get', $path.$serverid, "");
	if ( isset($return["refer"]) )
	{
		$has_refer = preg_match('/[0-9\_]+/', $return["refer"], $matches);
		if ( isset($has_refer) )
		{
			return $matches[0];
		}
		else
		{
			return FALSE;
		}
	}
	else
	{
		return FALSE;
	}
}

function main()
{
	global $argc, $argv;

	$result = getopt('p:d:o:z:s:g:h:?');

	$operand = "";
	$path = "";
	$dir = "";
	$zkhost = "";
	$group = "";
	$serverid = "";
	$map = array(
		'getMainIP',
		'getMainRefer',
		'getSDBIP',
		'getDataproxyIP',
		'getMDBIP',
		'getLogicIPBySid',
	);
	$logic_map=array(
		'getLogic',
		'getLogicIP',
	);
	$nodes_map=array(
		'create',
		'delete',
		'nodes'
	);

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'o':
				$operand = strval($value);
				break;
			case 'p':
				$path = strval($value);
				break;
			case 'd':
				$dir = strval($value);
				break;
			case 'z':
				$zkhost = strval($value);
				break;
			case 's':
				$serverid = strval($value);
				break;
			case 'h':
			case '?':
			default:
				print_usage();
				exit(1);
		}
	}

	if  ( empty($operand) )
	{
		fwrite(STDERR,"-o should be set!\n");
		print_usage();
		exit(1);
	}

	if  ( empty($path) )
	{
		fwrite(STDERR,"-p should be set!\n");
		print_usage();
		exit(1);
	}

	if ( empty($zkhost) )
	{
		fwrite(STDERR,"-z should be set!\n");
		print_usage();
		exit(1);
	}

	if ( in_array($operand, $map) )
	{
		if ( empty($serverid) )
		{
			fwrite(STDERR,"if -o = $operand -s should be set!\n");
			print_usage();
			exit(1);
		}
		$return = $operand($path, $zkhost, $serverid);
	}
	else if ( in_array($operand, $logic_map) )
	{
		$return = $operand($path, $zkhost);
	}
	else if ( in_array($operand, $nodes_map) )
	{
		$zk = new ZK($zkhost);
		$return = $zk->execute($operand, $path, "");
	}
	else
	{
		if ( empty($dir) )
		{
			fwrite(STDERR,"if -o = $operand -d should be set!\n");
			print_usage();
			exit(1);
		}
		$zk = new ZK($zkhost);
		$return = $zk->execute($operand, $path, $dir);
	}

	if ( $return == FALSE )
	{
		exit(1);
	}
	else
	{
		echo $return;
		exit(0);
	}
}

main ($argc, $argv);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */