<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WarcraftUnion.script.php 144097 2014-12-04 02:45:35Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/WarcraftUnion.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-04 02:45:35 +0000 (Thu, 04 Dec 2014) $
 * @version $Revision: 144097 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'method_attex.csv';
$outFileName = 'WARCRAFT_UNION';

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
		'id'	=> 0,
		'numAndLevel'=> 1,
		'profit'	=> 2,
		'allCraftRatio' => 5,
);

$arrayOne = array();
$arrayTwo = array( 'profit' );
$arrayThree = array( 'numAndLevel' );

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
				
		}
		elseif ( in_array( $keyOne , $arrayThree) ) 
		{
			$conf[ $keyOne ] = array_map( 'intval' , explode( '|' , $data[ $index ]));
		}
		else
		{
			$conf[ $keyOne ] = intval( $data[ $index ] );
		}
	}

	$confList[ $conf[ 'id' ] ] = $conf;
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