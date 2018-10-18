<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GrowUp.script.php 67334 2013-09-30 03:45:51Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/GrowUp.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-30 03:45:51 +0000 (Mon, 30 Sep 2013) $
 * @version $Revision: 67334 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'growth_fund.csv';
$outFileName = 'GROWUP_FUND';

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
	if ( empty( $data )||empty( $data[0] ) )
		{
			break;
		}
		$index = 2;
		$confList[ 'needVip' ] = intval( $data[ $index++ ] );
		$confList[ 'lvAndGold' ] = $data[ $index++ ];
		$confList[ 'needGold' ] = intval( $data[ $index++ ] );
		$confList[ 'timeLast' ] = intval( $data[ $index++ ] ) * 86400;
			
		$Conf = str2Array( $confList[ 'lvAndGold' ] );
		foreach ( $Conf as $key => $val )
		{
			$Conf[ $key ] = array2Int( str2Array( $val , '|') );
		}
		$standarConf = array();
		foreach ( $Conf as $key => $val )
		{
			$standarConf[ $key ][ 'needLevel' ] = $val[ 0 ];
			$standarConf[ $key ][ 'fundGold' ] = $val[ 1 ];
		}
		$confList[ 'lvAndGold' ] = $standarConf;
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