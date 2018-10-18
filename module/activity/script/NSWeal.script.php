<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NSWeal.script.php 188680 2015-08-04 05:02:15Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/NSWeal.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-08-04 05:02:15 +0000 (Tue, 04 Aug 2015) $
 * @version $Revision: 188680 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Activity.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Logger.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Exception.class.php";
require_once dirname ( dirname ( __FILE__ ) ). "/weal/EnWeal.class.php";

$csvFile = 'wealActivity_kaifu.csv';
$outFile = 'NSWEAL';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFile \n" );
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

$confList = array();
$arrData = array();

$data = fgetcsv($file);
$data = fgetcsv($file);

while (TRUE)
{
	$data = fgetcsv($file);
	
	if (empty($data) || empty($data[0]))
	{
		break;
	}
	
	$arrData[] = $data;
}

$confList = EnWeal::readWealCSV($arrData);

$confList = array(
		WealDef::NS_WEAL_ID => $confList[WealDef::NS_WEAL_ID],
);

var_dump( $confList );
//输出文件
$file = fopen($argv[2].'/'.$outFile, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFile. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */