<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Online.script.php 62593 2013-09-03 03:24:44Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/script/Online.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-03 03:24:44 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62593 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'online_reward.csv';
$outFileName = 'ONLINE_GIFT';

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

$muliItem = 7;
$confList = array();
while ( true )
{
	$data = fgetcsv( $file );
	if ( empty( $data[ 0 ] )|| empty( $data ))
	{
		break;
	}
	$onlineKey = intval( $data[ 0 ] );
	$needOnlineTime = intval( $data[ 1 ] ) ;//时间
	//跳过奖励数目，没用到 
	$index = 3;
	$rewardConf = array();
	while ( true )
	{
		if ( empty( $data[ $index ] ) || empty( $data[ 0 ] ) )
		{
			break;
		}
		if ( intval ( $data[ $index ] ) == $muliItem )
		{
			$itemArr = explode( ',' , $data[ $index + 2 ]);
			foreach ( $itemArr as $key => $val )
			{
				$itemArr[ $key ] = array_map('intval', explode( '|' , $val ));
			}
			$rewardConf [] = array( 
					'type' => intval ( $data[ $index ] ),
					'val'  => $itemArr,
			 ) ;
		}
		else
		{
			$rewardConf[]= array( 
					'type' => intval ( $data[ $index ] ),
					'val'  => intval ( $data[ $index + 2 ] ),
			 ) ;
		}
		$index += 4;
	}

	$confList [ $onlineKey ][ 'needTime' ] = $needOnlineTime;
	$confList [ $onlineKey ][ 'rewardArr' ] = $rewardConf;
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