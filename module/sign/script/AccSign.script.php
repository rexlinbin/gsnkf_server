<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AccSign.script.php 64563 2013-09-13 07:24:02Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/script/AccSign.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-13 07:24:02 +0000 (Fri, 13 Sep 2013) $
 * @version $Revision: 64563 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once ( '/home/pirate/rpcfw/def/Reward.def.php' );

$csvFile = 'accumulate_sign.csv';
$outFileName = 'SIGN_ACC';

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
$test =0 ;
while ( true )
{
	$data = fgetcsv( $file );
	if ( empty( $data ) || empty( $data[ 0 ] ))
	{
		break;
	}
	$signStep = intval( $data[ 0 ] );
	$signDays = intval( $data[ 2 ] );
	$rewardNum = intval ( $data[ 3 ] );//没用到，前提是物品配的是从前往后，不要空
	$index = 4;
	$rewardConf = array();
	for( $i=1; $i <= $rewardNum; $i++ )
	{
		if ( empty( $data[ $index ] ) )
		{
			break;
		}
		if ( intval ( $data[ $index ] ) == RewardConfType::ITEM_MULTI )
		{
			$itemArr = explode( ',' , $data[ $index + 2 ]);
			foreach ( $itemArr as $key => $val )
			{
				$itemArr[ $key ] = explode( '|' , $val );
				$itemArr[ $key ][ 0 ] = intval( $itemArr[ $key ][ 0 ] );
				$itemArr[ $key ][ 1 ] = intval( $itemArr[ $key ][ 1 ] );
			}
			$rewardConf [] = array(
					'type' =>  intval ( $data[ $index ] ) ,
					'val' => $itemArr,
			);
		}
		else
		{
			$rewardConf[] = array(
					'type' => intval ( $data[ $index ] ),
					'val' => intval ( $data[ $index + 2 ] ),
			);
		}
		$index += 4;
	}
	$confList [ $signStep ][ 'needDays' ] = $signDays;
	$confList [ $signStep ][ 'rewardArr' ] = $rewardConf;
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