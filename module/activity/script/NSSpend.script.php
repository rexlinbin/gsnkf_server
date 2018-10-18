<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NSSpend.script.php 117393 2014-06-26 08:12:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/NSSpend.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-26 08:12:46 +0000 (Thu, 26 Jun 2014) $
 * @version $Revision: 117393 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname( dirname ( dirname ( __FILE__ ) ) ) . "/reward/RewardUtil.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Logger.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Exception.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once  dirname ( dirname ( __FILE__ ) ). "/spend/EnSpend.class.php";

$csvFile = 'xiaofei_leiji_kaifu.csv';
$outFileName = 'SPENDACC';

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
$keyArr = array(
		'id' => $index++,
		'needSpend' => ($index+=2)-1,
		'rewardArr' => $index++,
);

$arrTwo = array( 'rewardArr' );
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while(true)
{
	$conf = array();
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}
	$arrdata[] = $data;
}
$confList = EnSpend::readSpendCSV( $arrdata );
var_dump( $confList );	
		//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */