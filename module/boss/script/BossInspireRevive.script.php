<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossInspireRevive.script.php 85184 2014-01-07 08:15:01Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/script/BossInspireRevive.script.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2014-01-07 08:15:01 +0000 (Tue, 07 Jan 2014) $
 * @version $Revision: 85184 $
 * @brief
 *
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Boss.def.php";

$csvFile = 'worldbossinspire.csv';
$outFileName = 'BOSS_INSPIRE_REVIVE';

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

$index = 0;
$name = array (
BossDef::IR_ID							=>$index,
BossDef::ADDITION_ARR						=>($index+=2)-1,
BossDef::INSPIRE_LIMIT				=>$index++,
BossDef::INSPIRE_BASE_RATIO					=>$index++,
BossDef::INSPIRE_SUCCESS_RATIO					=>$index++,
BossDef::INSPIRE_SILVER_CD						=>$index++,
BossDef::INSPIRE_NEED_SILVER			=>$index++,
BossDef::INSPIRE_NEED_GOLD				=>$index++,
BossDef::REBIRTH_GOLD_BASE				=>$index++,
BossDef::REBIRTH_GOLD_INC					=>$index++,
BossDef::ATK_CD					=>$index++,
);

$arrayOne = array();
$arrayTwo = array(BossDef::ADDITION_ARR,);

$conflist = array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) || empty( $data[0] ) )
		break;

	//一条数据
	$conf = array();
	foreach ( $name as $key => $val )
	{
		if ( in_array( $key , $arrayOne) )
		{
			if ( empty( $data[ $val ] ) )
			{
				$conf[ $key ] = array();
			}
			else
			{
				$conf[ $key ] = array_map( 'intval' , explode( ',' , $data[ $val ] ) );
			}
		}
		elseif ( in_array( $key , $arrayTwo) )
		{
			if ( empty( $data[$val] ) )
			{
				$conf[ $key ] = array();
			}
			else 
			{
				$conf[ $key ]= explode( ',' , $data[$val] );
				foreach ( $conf[ $key ] as $innerKey => $innerVal )
				{
					$conf[ $key ][ $innerKey ] = array_map ( 'intval' , explode( '|' , $innerVal ));
				}
			}
		}
		else 
		{
			$conf[ $key ] = intval( $data[$val] );
		}
	}
	
	//所有数据
	$conflist[ $conf[BossDef::IR_ID] ] = $conf;
}
var_dump($conflist);
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($conflist));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */