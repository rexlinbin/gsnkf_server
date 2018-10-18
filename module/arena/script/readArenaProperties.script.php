<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readArenaProperties.script.php 207545 2015-11-05 09:11:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/script/readArenaProperties.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-05 09:11:59 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207545 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$inFileName = 'arena_properties.csv';
$outFileName = 'ARENA_PROPERTIES';

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

$index = 1;

//对应配置表键名
$confKey = array (
		'challenge_free_num' => $index,
		'fight_suc_silver' => ++$index,
		'fight_fail_silver' => ++$index,
		'fight_suc_soul' => ++$index,
		'fight_fail_soul' => ++$index,
		'fight_suc_exp' => ++$index,
		'fight_fail_exp' => ++$index,
		'fight_cost_stamina' => ++$index,
		'fight_suc_flop' => ++$index,
		'male_army_group' => ++$index,
		'female_army_group' => ++$index,
		'fight_suc_prestige' => ++$index,
		'fight_fail_prestige' => ++$index,
		'fight_suc_silver_max' => ++$index,
		'fight_fail_silver_max' => ++$index,
);

$arrKeyV1 = array('male_army_group', 'female_army_group');

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
		if( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array2Int( str2array($data[$index]) );
			}
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$confList[] = $conf;
}
fclose($file);

print_r($confList[0]);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList[0]));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */