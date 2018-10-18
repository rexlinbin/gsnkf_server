<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonRule.script.php 219277 2016-01-05 05:58:12Z NanaPeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/script/MoonRule.script.php $
 * @author $Author: NanaPeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-05 05:58:12 +0000 (Tue, 05 Jan 2016) $
 * @version $Revision: 219277 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'treasure_copy.csv';
$outFileName = 'MOON_RULE';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$incre = 0;
$tag = array
(
		'id' => $incre++,
		'default_atk_num' => $incre++,
		'buy_cost' => $incre++,
		'box_cost' => $incre++,
		'box_drop' => $incre++,
		//梦魇新增2015-12-29
		'nightmare_num' =>$incre++,//每日梦魇次数
		'nightmare_price'=>$incre++,//金币购买梦魇次数及价格
		
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
	$data = fgetcsv($file);
	if (empty($data))
		break;

	// id
	$config['id'] = intval($data[$tag['id']]);

	// 默认攻打次数
	$config['default_atk_num'] = intval($data[$tag['default_atk_num']]);

	// 购买攻击次数价格
	$arrBuyCost = str2Array($data[$tag['buy_cost']], ',');
	foreach ($arrBuyCost as $aCost)
	{
		$detail = array2Int(str2Array($aCost, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid buy cost:%d\n", count($detail)));
		}
		$config['buy_cost'][$detail[0]] = $detail[1];
	}
	
	// 购买宝箱价格
	$arrBoxCost = str2Array($data[$tag['box_cost']], ',');
	foreach ($arrBoxCost as $aCost)
	{
		$detail = array2Int(str2Array($aCost, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid buy cost:%d\n", count($detail)));
		}
		$config['box_cost'][$detail[0]] = $detail[1];
	}
	
	// 宝箱掉落
	$config['box_drop'] = intval($data[$tag['box_drop']]);
	
	//每日梦魇次数
	$config['nightmare_num'] = intval($data[$tag['nightmare_num']]);
	
	//金币购买梦魇次数及价格
	$arrNightmarePrice = str2Array($data[$tag['nightmare_price']], ',');
	foreach ($arrNightmarePrice as $nCost)
	{
		$cost = array2Int(str2Array($nCost, '|'));
		if (count($cost) != 2)
		{
			trigger_error(sprintf("invalid nightmare num buy cost:%d\n", count($cost)));
		}
		$config['nightmare_price'][$cost[0]] = $cost[1];
	}
	break;
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