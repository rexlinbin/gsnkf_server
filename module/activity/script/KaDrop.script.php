<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: KaDrop.script.php 94521 2014-03-20 10:35:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/script/KaDrop.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-20 10:35:06 +0000 (Thu, 20 Mar 2014) $
 * @version $Revision: 94521 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'ka_reward.csv';
$outFileName = 'KAREWARD';

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
$keyIndex = array(
	'rewardId' => $index++,
	'rewardInfo' => $index++,
	'dropId' => $index++,
	'weight' => $index++,
);


$index = 0;

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
	$conf = array();
	foreach ( $keyIndex as $key => $val )
	{
		if ( $key == 'rewardInfo' )
		{
			if (empty( $data[$val] ))
			{
				$conf[$key] = array();
			}
			else 
			{
				$tmp = explode( ',' , $data[$val]);
				foreach ( $tmp as $key2 => $val2 )
				{
					$tmp[$key2] = array_map('intval',explode( '|', $val2)  );
				}
				$conf[$key] = $tmp;
			}
		}
		else 
		{
			$conf[$key] = intval( $data[$val] );
		}
		
	}
	$confList[] = $conf;
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