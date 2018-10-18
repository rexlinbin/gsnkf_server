<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalSign.script.php 65363 2013-09-18 05:45:14Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/script/NormalSign.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-18 05:45:14 +0000 (Wed, 18 Sep 2013) $
 * @version $Revision: 65363 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once ( '/home/pirate/rpcfw/lib/Logger.class.php');
require_once ( '/home/pirate/rpcfw/def/Reward.def.php' );

$csvFile = 'normal_sign.csv';
$outFileName = 'SIGN_NORMAL';

$outFileNameTwo = 'SIGN';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFileName $outFileNameTwo \n" );
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
$upgradeConfList = array();

while ( true )
{
	$data = fgetcsv( $file );
	if ( empty( $data )|| empty( $data[ 0 ] ))
	{
		break;
	}
	//第一列id没用
	if ( !isset( $upgradeConfList[ $data[ 1 ] ] ) )
	{
		$upgradeConfList[ $data [ 1 ] ] [ 'needUserLevel' ] = intval( $data[ 2 ] );
		$upgradeConfList[ $data [ 1 ] ] [ 'beginTime' ] = strtotime ( $data [ 3 ] ) ;
		$upgradeConfList[ $data [ 1 ] ] [ 'endTime' ] = strtotime ( $data[ 4 ] ) ;
	}
	
	$rewardNum = intval ( $data[ 6 ] );
	$index = 7;
	$rewardConf = array();
	for ( $i=1; $i <= $rewardNum; $i++ )
	{
		if ( empty( $data[ $index ] ) || empty( $data ) )
		{
			trigger_error( "reward num: $i err" );
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
			$rewardConf[] = array( 
					'type' => intval ( $data[ $index ] ),
					'val' =>  $itemArr
			 );
		}
		else
		{
			$rewardConf[] = array( 
					'type' => intval ( $data[ $index ] ),
					'val' =>  intval ( $data[ $index + 2 ] ),
			 ); 
				
		}
		$index += 4;
		
	}
	//某一级别某一天的奖励数组
	$confList[ $data[ 1 ] ][ $data [ 5 ] ] = $rewardConf;
}
var_dump( $upgradeConfList );
var_dump( $confList );
fclose($file);

//输出天数及奖励文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($confList));
fclose($file);

//输出升级配置文件，为了自己方便，将一个文件读成两个文件
$file = fopen($argv[2].'/'.$outFileNameTwo, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize( $upgradeConfList ));
fclose( $file );



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */