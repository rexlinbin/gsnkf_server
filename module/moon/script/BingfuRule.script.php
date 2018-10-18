<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonShop.script.php 188200 2015-07-31 12:55:38Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/moon/script/MoonShop.script.php $
 * @author $Author: pengnana $(pengnana@babeltime.com)
 * @date $Date: 2015-12-30 14:25:00$
 * @version $Revision: 188200 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'bingfu_shop.csv';
$outFileName = 'BINGFU_RULE';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$incre = 0;
$field_names = array(
		'id' => $incre++,
		'cd' => $incre++,
		'goldGost' => $incre++,
		'itemTeamNum' => $incre++,
		'itemTeam1' => $incre++,
		'itemTeam2' => $incre++,
		'itemTeam3' => $incre++,
		'itemTeam4' => $incre++,
		'itemTeam5' => $incre++,
		'itemTeam6' => $incre++,
		'itemTeam7' => $incre++,
		'itemTeam8' => $incre++,
		'itemTeam9' => $incre++,
		'itemTeam10' => $incre++,
		'refreshNum' => $incre++,
		'freeRefreshNum'=> $incre++,
);

$config = array();
$file = fopen($argv[1] . "/$csvFile", 'r');
if (FALSE == $file)
{
	echo $argv[1] . "/{$csvFile} open failed! exit!\n";
	exit;
}

fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$line = fgetcsv($file);
	$mid = array();
	
	if (empty($line))
	{
		break;
	}	
	//$id = intval($line[0]);
	foreach($field_names as $key => $v)
	{
		$itemArr =array();
		if(empty($line[$v]) && $key!= 'cd')
		{
			$config[$key] = 0;
			continue;
		}
		switch($key)
		{
			case 'cd':
				$sysRefreshArr = explode(',',$line[$v]);
				foreach ($sysRefreshArr as $aTime)
				{
					$config[$key][] = strtotime(date('Ymd') . $aTime) - strtotime(date('Ymd') . '000000');
				}
				sort($config[$key]);
				break;
			case 'goldGost':
				$itemArr = explode(',',$line[$v]);
				foreach($itemArr as $item)
				{
					$mid = array_map('intval', explode('|',$item));
					$config[$key][$mid[0]] = $mid[1];
				}
				ksort($config[$key]);
				break;
			case 'itemTeamNum':
				$config[$key]= array_map('intval', explode(',',$line[$v]));
				if(count($config[$key]) > 10)
				{
					trigger_error('bingfu_shop teamNum > allNum.');
				}
				break;
			case 'refreshNum':
				$config[$key] = intval($line[$v]);
				break;
			case 'freeRefreshNum':
				$config[$key] = intval($line[$v]);
				break;
			default:
				$config[$key]= array_map('intval', explode(',',$line[$v]));
		}
	}
/**
 * config{
 * 			'cd' => array(100000,190000)
 * 			'goldGost'=> array(5=>20,....)
 * 			'itemTeamNum' => array(1,2,3,4...)
 * 			'itemTeam1' => array(1,2,3,4...)
 * 				.
 * 				.
 * 			'refreshNum'=>int
 * 			'freeRefreshNum'=>int
 * 		}		
 * */
}
fclose($file);
var_dump($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */