<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipDailyBonud.script.php 97357 2014-04-03 05:44:49Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/script/VipDailyBonud.script.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-04-03 05:44:49 +0000 (Thu, 03 Apr 2014) $
 * @version $Revision: 97357 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'vipsalary.csv';
$outFileName = 'VIP_DAILYBONUS';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 2 )
{
	echo "Please input enough arguments:!{$csvFile}\n";
	exit;
}

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	echo $argv[1]."/{$csvFile} open failed! exit!\n";
	exit;
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while ( true )
{
	$data = fgetcsv($file);
	if (empty( $data ) || !isset( $data[0] ))
	{
		break;
	}
	$vipConf = array();
	
	$vip = intval( $data[0] );
	if ( !empty( $data[1] ) )
	{
		$vipConf = explode( ',' , $data[1]);
		foreach ( $vipConf as $key1 => $val1 )
		{
			$vipConf[$key1] = array_map( 'intval',explode( '|' , $val1) );
		}
	}

	$confList[$vip] = $vipConf;
}

var_dump( $confList );

fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */