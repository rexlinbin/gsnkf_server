<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteRule.script.php 203113 2015-10-19 07:05:12Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/script/WorldCompeteRule.script.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-19 07:05:12 +0000 (Mon, 19 Oct 2015) $
 * @version $Revision: 203113 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/conf/WorldCompete.cfg.php";

$inFileName = 'kuafu_contest.csv';	
$outFileName = 'WORLD_COMPETE_RULE';

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

//数据对应表
$index = 0;
$arrConfKey = array (
		'time' => ++$index,
		'refresh_default' => ++$index,
		'refresh_cost' => ++$index,
		'atk_default' => ++$index,
		'buy_cost' => ++$index,
		'buy_limit' => ++$index,
		'crazy_attr' => ++$index,
		'crazy_cost' => ++$index,
		'worship_reward1' => ++$index,
		'worship_reward2' => ++$index,
		'worship_con2' => ++$index,
		'suc_honor' => $index+=2,
		'fail_honor' => ++$index,
		'rival_range' => ++$index,
		'need_open_days' => ++$index,
);

$arrKeyV1 = array(
		'worship_con2',
);

$arrKeyV2 = array(	 
		'buy_cost',
		'crazy_attr'
);

$arrKeyV3 = array(
		'worship_reward1',
		'worship_reward2',
);

$arrKeyV4 = array(
		'time',
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if (in_array($key, $arrKeyV1, true))
		{
			$conf[$key] = array2Int(str2array($data[$index], '|'));
		}
		elseif (in_array($key, $arrKeyV2, true))
		{
			$conf[$key] = array();
			$arr = str2array($data[$index]);
			foreach ($arr as $value)
			{
				$ary = array2Int(str2Array($value, '|'));
				$conf[$key][$ary[0]] = $ary[1];
			}
		}
		elseif( in_array($key, $arrKeyV3, true) )
		{
			$conf[$key] = array();
			$arr = str2array($data[$index]);
			foreach( $arr as $value )
			{
				$conf[$key][] = array2Int(str2Array($value, '|'));
			}
		}
		elseif( in_array($key, $arrKeyV4, true) )
		{
			$arr = str2array($data[$index]);
			if (count($arr) != 2)
			{
				trigger_error("invalid time conf:$arr");
			}
			$ary = array2Int(str2Array($arr[0], '|'));
			if (count($ary) != 2)
			{
				trigger_error("invalid begin time conf:$ary");
			}
			$conf['begin_time'] = ($ary[0] - 1) * 86400 + strtotime(date('Ymd') . sprintf("%06d", $ary[1])) - strtotime(date('Ymd') . "000000");
			$ary = array2Int(str2Array($arr[1], '|'));
			if (count($ary) != 2)
			{
				trigger_error("invalid end time conf:$ary");
			}
			$conf['end_time'] = ($ary[0] - 1) * 86400 + strtotime(date('Ymd') . sprintf("%06d", $ary[1])) - strtotime(date('Ymd') . "000000");
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	ksort($conf['buy_cost']);
	
	if ($conf['rival_range'] < WorldCompeteConf::RIVAL_COUNT) 
	{
		trigger_error("invalid rival range!");
	}

	$confList = $conf;
}
fclose($file);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */