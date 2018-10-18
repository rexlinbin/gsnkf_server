<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipWeekGift.script.php 237822 2016-04-12 09:28:18Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/script/VipWeekGift.script.php $
 * @author $Author: MingTian $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-04-12 09:28:18 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237822 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'vip_weekgift.csv';
$outFileName = 'VIP_WEEKGIFT';

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
	if (empty( $data ) || !isset( $data[0] ))
	{
		break;
	}
	
	$conf = array('reward' => array(), 'cost' => 0);
	$vip = intval($data[1]);
	if (!empty($data[3]))
	{
		$reward = explode( ',' , $data[3]);
		foreach ($reward as $key1 => $val1)
		{
			$reward[$key1] = array_map( 'intval',explode( '|' , $val1) );
		}
		$conf['reward'] = $reward;
	}
	if (!empty($data[5])) 
	{
		$conf['cost'] = intval($data[5]);
	}

	$confList[$vip] = $conf;
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