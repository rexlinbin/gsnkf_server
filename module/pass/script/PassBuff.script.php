<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassBuff.script.php 146290 2014-12-16 02:51:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/script/PassBuff.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-16 02:51:41 +0000 (Tue, 16 Dec 2014) $
 * @version $Revision: 146290 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'overcome_buff.csv';
$outFileName = 'PASS_BUFF';

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
$keyArr = array(
		'buffId'		=> $index++,
		'buffArr'		=> $index++,
		'weight'		=> $index++,
		'needStarNum' 	=> $index++,
);

$arrayOne = array();
$arrayTwo = array();
$arrayThree = array('buffArr',);

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
		elseif( in_array( $keyOne , $arrayTwo) )
		{
			$conf[ $keyOne ] = array_map( 'intval' , explode( '|' , $data[ $index ]));
		}
		elseif ( in_array( $keyOne , $arrayThree) )
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
		else
		{
			$conf[ $keyOne ] = intval( $data[ $index ] );
		}
	}
	$confList[ $conf[ 'buffId' ] ] = $conf;
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