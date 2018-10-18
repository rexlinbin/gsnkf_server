<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NSRobtomb.script.php 116795 2014-06-24 03:12:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/NSRobtomb.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-24 03:12:06 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 116795 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname( dirname ( dirname ( __FILE__ ) ) ) . "/reward/RewardUtil.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Logger.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Exception.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Util.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/RobTomb.def.php";
require_once  dirname ( dirname ( __FILE__ ) ). "/robtomb/EnRobTomb.class.php";

$csvFile = 'ernie_kaifu.csv';
$outFileName = 'ERNIE';

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
$arrdata = array();
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

$confList = EnRobTomb::readRobTombCSV($arrdata);

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