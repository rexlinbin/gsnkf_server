<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Levelfund.script.php 62678 2013-09-03 06:30:29Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/Levelfund.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-03 06:30:29 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62678 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'level_reward.csv';
$outFileName = 'LEVEL_FUND';

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
	$data = fgetcsv( $file );
	if ( empty( $data ) || empty( $data[ 0 ] ) )
	{
		break;
	}
	$index = 3;
	$rewardConf = array();
	while ( true )
	{
		if ( empty( $data[ $index ] )  )
		{
			break;
		}
		if ( intval ( $data[ $index ] ) == RewardConfType::ITEM_MULTI )
		{
			if ( empty( $data[ $index + 2 ]  ))
			{
				$rewardConf [] = array();
			}
			else
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
	$actual = count( $rewardConf );
	if ( count( $rewardConf ) != intval( $data[ 2 ]  ) )
	{
		trigger_error("$data[0] jiang li shu bu dui, $data[2] , $actual ");
	}
	$confList[ intval( $data[ 0 ] ) ][ 'needLevel' ] = intval( $data[ 1 ] );
	$confList[ intval( $data[ 0 ] ) ][ 'rewardArr' ] = $rewardConf;
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