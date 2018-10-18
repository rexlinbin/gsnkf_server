<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Ka.script.php 94521 2014-03-20 10:35:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/Ka.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-20 10:35:06 +0000 (Thu, 20 Mar 2014) $
 * @version $Revision: 94521 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'weal_point.csv';
$outFileName = 'KAPOINT';

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

$index = 0;
// $actions = array(
// 	KaDef::FRAGSEIZE => $index++,
// 	KaDef::COMPETE => $index++,
// 	KaDef::DIVINE => $index++,
// 	KaDef::NCOPY => $index++,
// 	KaDef::ECOPY => $index++,
// 	KaDef::ACOPY => $index++,
// 	KaDef::GCOPY => $index++,
// 	KaDef::BOSS => $index++,
// 	KaDef::ARENA => $index++,	 
// );



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
	
	$confList[intval( $data[0] )] = intval( $data[2] );
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