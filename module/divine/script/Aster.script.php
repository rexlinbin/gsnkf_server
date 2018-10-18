<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Aster.script.php 62581 2013-09-03 03:20:25Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/script/Aster.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-03 03:20:25 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62581 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'aster.csv';
$outFileName = 'DIVI_ASTER';

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
	$data = fgetcsv($file);
	if ( empty( $data )||empty( $data[ 0 ] ) )
	{
		break;
	}
	$index = 4;
	$confList[ intval( $data[ 0 ] ) ][ 'silver_num' ] = intval( $data[ $index++ ] );
	$confList[ intval( $data[ 0 ] ) ][ 'integral_num' ] = intval( $data[ $index++ ] );
	$confList[ intval( $data[ 0 ] ) ][ 'weight' ] = intval( $data[ $index++ ] );
	$oneSample = array();
	$oneSample['weight'] = $confList[ intval( $data[ 0 ] ) ][ 'weight' ];
	
	$confList[ 'sample_arr' ][ intval( $data[ 0 ] ) ] = $oneSample;
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