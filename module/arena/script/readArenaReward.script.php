<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: readArenaReward.script.php 78631 2013-12-04 07:53:34Z MingTian $$
 * 
 **************************************************************************/

/**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/script/readArenaReward.script.php $$
 * @author $$Author: MingTian $$(lanhongyu@babeltime.com)
 * @date $$Date: 2013-12-04 07:53:34 +0000 (Wed, 04 Dec 2013) $$
 * @version $$Revision: 78631 $$
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$inFileName = 'arena_reward.csv';
$outFileName = 'ARENA_REWARD';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $inFileName $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$inputDir = $argv[1];
$outputDir = $argv[2];

$index = 0;

//对应配置表键名
$confKey = array (
		'position' => $index,
		'soul' => ++$index,
		'silver' => ++$index,
		'items' => ++$index,
		'prestige' => ++$index,
);

$arrKeyV2 = array('items');

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ($confKey as $key => $index)
	{
		if( in_array($key, $arrKeyV2, true) )
		{
			$arr = str2array($data[$index]);
			$conf[$key] = array();
			foreach( $arr as $value )
			{
				if(!strpos($value, '|'))
				{
					trigger_error( "arena:$data[0] invalid $key, need v2\n" );
				}
				$ary = array2Int(str2Array($value, '|'));
				$conf[$key][$ary[0]] = $ary[1];
			}
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$confList[$conf['position']] = $conf;
}
fclose($file);

//print_r($confList);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
