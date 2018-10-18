<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Divine.script.php 258547 2016-08-26 06:31:49Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/script/Divine.script.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-26 06:31:49 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258547 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Divine.cfg.php";

$csvFile = 'astrology.csv';
$outFileName = 'DIVI_PRIZE';

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

$arrOne = array( 'integ_arr' );
$arrTwo = array( 'prize_arr', 'tar_aster_arr' );

$confList = array();
$oneClickLevel = 9999;
while ( true )
{
	$conf = array();
	$data = fgetcsv($file);
	if ( empty( $data )||empty( $data[ 0 ] ) )
	{
		break;
	}
	
	// 读取一键占星的等级限制，在第4行的最后一列（18列）
	if(count($data) == 18)
	{
		$oneClickLevel = intval($data[17]);
	}
	
	$index = 1;
	$conf[ 'need_level' ] 		= intval( $data[ $index++ ] );
	$conf[ 'integ_arr' ] 		= $data[ $index++ ];
	$conf[ 'prize_arr' ] 		= $data[ $index++ ];
	$conf[ 'max_integral' ] 	= intval( $data[ $index++ ] );
	$conf[ 'tar_aster_arr' ] 	= $data[ $index++ ];
	
	foreach ( $conf as $key => $val )
	{
		if ( in_array($key, $arrOne) )
		{
			$conf[ $key ] = array_map( 'intval' , explode( ',' , $val ) );
		}
		else if ( in_array($key, $arrTwo) )
		{
			$conf[ $key ]= explode( ',' , $val );
			foreach ( $conf[ $key ] as $lowerKey => $val )
			{
				$conf[ $key ][ $lowerKey ] = array_map ( 'intval' , explode( '|' , $val ));
			}
		}
	}
	
	foreach ( $conf[ 'integ_arr' ] as $key => $val )
	{
		if ( !isset($conf[ 'prize_arr' ][ $key ]) )
		{
			trigger_error( 'no prize for this integral: %d' ,$val );
		}
	}

	$newRewardArr = array();
	while ( count( $newRewardArr ) < count( $conf['integ_arr'] ) )
	{
		echo "now index $index \n";
		if ( $data[0] == 1 )
		{
			break;
		}
		
		if ( empty( $data[$index] ) )
		{
			trigger_error( 'invalid num of rand reward' );
		}
		$newOne = explode( ',' ,$data[$index] );
		foreach ( $newOne as $keyOne => $valOne )
		{		
			$tmp = array_map( 'intval' , explode( '|' , $valOne));
			var_dump( $valOne );
			$newOne[$keyOne] = array(
						'type' => $tmp[0],
						'val'  => $tmp[1],
						'num'  => $tmp[2],
						'weight' => $tmp[3],
				);
			
		}
		
		$newRewardArr[] = $newOne;
		$index++;
	}
	
	$conf['newReward'] = $newRewardArr;
	
	$confList[ $data[ 0 ] ] = $conf; 
}

$confList['oneclick_level'] = $oneClickLevel;
var_dump($confList);
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