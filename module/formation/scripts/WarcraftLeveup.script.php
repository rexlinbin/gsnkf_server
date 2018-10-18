<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WarcraftLeveup.script.php 141861 2014-11-24 13:12:03Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/WarcraftLeveup.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-24 13:12:03 +0000 (Mon, 24 Nov 2014) $
 * @version $Revision: 141861 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'method_levelup.csv';
$outFileName = 'WARCRAFT_LEVELUP';

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

$keyArr = array(
		'up2Level'	=> 0,
		'needSilver'=> 1,
		'needItem'	=> 2,
);

$arrayOne = array();
$arrayTwo = array( 'needItem' );

$confList = array();
while (true)
{
	$conf = array();
	$data = fgetcsv($file);
	if( empty( $data ) || empty( $data[0] ) )
	{
		break;
	}
	foreach ( $keyArr as $keyOne => $index )
	{
		if ( in_array( $keyOne , $arrayOne) )
		{
			$conf[ $keyOne ] = array_map( 'intval' , explode( ',' , $data[ $index ]));
		}
		elseif ( in_array( $keyOne , $arrayTwo) )
		{
			$tmpConf = explode( ',' , $data[ $index ]);
			foreach ( $tmpConf as $keyTwo => $val )
			{
				if ( empty( $val ) )
				{
					$tmpConf[ $keyTwo ] = array();
				}
				else 
				{
					$tmpConf[ $keyTwo ] = array_map( 'intval' , explode( '|' , $val ));
				}
			}
			$conf[ $keyOne ] = $tmpConf;
			
			if( $keyOne == 'needItem' )
			{
				$tmp = array();
				foreach ( $tmpConf as $index => $itemInfo)
				{
					if( empty( $itemInfo ) )
					{
						continue;
					}
					if( isset( $tmp[ $itemInfo[0] ] ) )
					{
						trigger_error( 'item config twice in one blank' );
					}
					$tmp[ $itemInfo[0] ] = $itemInfo[1];
				}
				$conf[ $keyOne ] = $tmp;
			}
			
		}
		else
		{
			$conf[ $keyOne ] = intval( $data[ $index ] );
		}
	}
	
	$confList[ $conf[ 'up2Level' ] ] = $conf;
}

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