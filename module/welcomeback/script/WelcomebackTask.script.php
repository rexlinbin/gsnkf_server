<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackTask.script.php 258536 2016-08-26 05:49:57Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/script/WelcomebackTask.script.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-26 05:49:57 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258536 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Welcomeback.def.php";

$csvFile = 'return.csv';
$outFileName = 'WELCOMEBACK_TASK';

if ( isset($argv[1]) && $argv[1] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!resolver.csv output\n";
	trigger_error ("input error parameters.");
}

$index = 0;
$field_names = array(
		WelcomebackDef::DURING => ($index += 2),
		WelcomebackDef::LEVEL_LIMIT => ++$index,
		WelcomebackDef::SERVER_LIMIT => ++$index,
		WelcomebackDef::OFFLINE_LIMIT => ++$index,
		WelcomebackDef::VA_INFO_GIFT => ($index += 2),
		WelcomebackDef::VA_INFO_TASK => ($index += 2),
		WelcomebackDef::VA_INFO_RECHARGE => ($index += 2),
		WelcomebackDef::VA_INFO_SHOP => ($index += 2)
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( FALSE == $file )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();

while(true)
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}
	
	$confList[WelcomebackDef::DURING] = intval($data[$field_names[WelcomebackDef::DURING]]);
	$confList[WelcomebackDef::LEVEL_LIMIT] = intval($data[$field_names[WelcomebackDef::LEVEL_LIMIT]]);
	$confList[WelcomebackDef::SERVER_LIMIT] = intval($data[$field_names[WelcomebackDef::SERVER_LIMIT]]);
	$confList[WelcomebackDef::OFFLINE_LIMIT] = intval($data[$field_names[WelcomebackDef::OFFLINE_LIMIT]]);
	$confList[WelcomebackDef::VA_INFO_GIFT] = array_map('intval', str2Array($data[$field_names[WelcomebackDef::VA_INFO_GIFT]], '|'));
	$confList[WelcomebackDef::VA_INFO_TASK] = array_map('intval', str2Array($data[$field_names[WelcomebackDef::VA_INFO_TASK]], '|'));
	$confList[WelcomebackDef::VA_INFO_RECHARGE] = array_map('intval', str2Array($data[$field_names[WelcomebackDef::VA_INFO_RECHARGE]], '|'));
	$confList[WelcomebackDef::VA_INFO_SHOP] = array_map('intval', str2Array($data[$field_names[WelcomebackDef::VA_INFO_SHOP]], '|'));
}
fclose($file);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */