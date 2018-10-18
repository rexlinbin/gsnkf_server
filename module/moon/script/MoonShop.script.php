<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonShop.script.php 188200 2015-07-31 12:55:38Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/script/MoonShop.script.php $
 * @author $Author: JiexinLin $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-31 12:55:38 +0000 (Fri, 31 Jul 2015) $
 * @version $Revision: 188200 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'treasure_copymall.csv';
$outFileName = 'MOON_SHOP';

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
		'sys_refresh_interval' => $incre++,
		'usr_refresh_cost' => $incre++,
		'rand_num' => $incre++,
		'all_goods' => $incre++,
		//'refresh_limit' => $incre++,
		'free_refresh_num' => ++$incre,
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
	
	// 系统刷新时间
	$sysRefreshArr = str2Array($data[$tag['sys_refresh_interval']], ',');
	foreach ($sysRefreshArr as $aTime)
	{
		$config['sys_refresh_interval'][] = strtotime(date('Ymd') . $aTime) - strtotime(date('Ymd') . '000000');
	}
	sort($config['sys_refresh_interval']);
	if (empty($config['sys_refresh_interval'])) 
	{
		trigger_error(sprintf("empty sys_refresh_interval\n"));
	}
	
	// 玩家刷新花费金币
	$arrCost = str2Array($data[$tag['usr_refresh_cost']], ',');
	foreach ($arrCost as $aCost)
	{
		$detail = array2Int(str2Array($aCost, '|'));
		$config['usr_refresh_cost'][$detail[0]] = $detail[1];
	}
	
	// 商品列表个数
	$config['rand_num'] = intval($data[$tag['rand_num']]);
	if ($config['rand_num'] <= 0) 
	{
		trigger_error(sprintf("invalid rand num[%d]\n", $config['rand_num']));
	}
	
	// 所有商品id
	$config['all_goods'] = array2Int(str2Array($data[$tag['all_goods']], ','));
	if (count($config['all_goods']) < $config['rand_num']) 
	{
		trigger_error(sprintf("all goods count[%d] less than rand num[%d]\n", count($config['all_goods']), $config['rand_num']));
	}
	
	// 每天给玩家免费刷新的次数
	$config['free_refresh_num'] = intval($data[$tag['free_refresh_num']]);
	if ($config['free_refresh_num'] <= 0)
	{
		trigger_error(sprintf("invalid free refresh num[%d]\n", $config['free_refresh_num']));
	}
	
	// 商品列表刷新上限，改为从vip表读上限次数啦
	//$config['refresh_limit'] = intval($data[$tag['refresh_limit']]);
    
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