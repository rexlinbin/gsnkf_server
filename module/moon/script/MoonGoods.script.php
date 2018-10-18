<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonGoods.script.php 172959 2015-05-14 05:19:33Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/script/MoonGoods.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-14 05:19:33 +0000 (Thu, 14 May 2015) $
 * @version $Revision: 172959 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'treasure_copyitem.csv';
$outFileName = 'MOON_GOODS';

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
		'goods_id' => $incre++,
		'goods_item' => $incre++,
		'cost_type' => $incre++,
		'cost_num' => $incre++,
		'limit_type' => $incre++,
		'limit_num' => $incre++,
		'goods_weight' => $incre++,
		'is_sold' => $incre++,
		'need_level' => $incre++,
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
	
	$conf = array();
	foreach($tag as $k => $v)
	{
		switch($k)
		{
			case 'goods_item':
				$conf[$k] = array_map('intval', str2Array($data[$v], '|'));
				break;
			default:
				$conf[$k] = intval($data[$v]);
		}
	}

	if(0 == $conf['is_sold'])
	{
		continue;
	}

	$newConf = array();

	//type字段
	$newConf[MallDef::MALL_EXCHANGE_TYPE] = $conf['limit_type'];

	//acq字段		目前天工阁商店商品支持的类型包括以下几种：7 其他不支持，也没必要支持
	if(count($conf['goods_item']) < 3)
	{
		trigger_error('goods_items should be a array that has 3 element.the conf is ' . serialize($conf['goods_item']));
	}

	switch ($conf['goods_item'][0])
	{
		case RewardConfType::ITEM_MULTI:
			$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array($conf['goods_item'][1]=>$conf['goods_item'][2]);
			break;
		case RewardConfType::TG:
			$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA]['tg'] = $conf['goods_item'][2];
			break;
		default:
			trigger_error(sprintf("invalid goods acq type[%d]\n", $conf['goods_item'][0]));
			break;
	}

	//req字段
	switch($conf['cost_type'])
	{
		case 1:
			$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['tg'] =  $conf['cost_num'];
			break;
		case 2:
			$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] =  $conf['cost_num'];
			break;
		case 3:
			$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] =  $conf['cost_num'];
			break;
		default:
			trigger_error(sprintf("invalid cost type[%d]\n", $conf['cost_type']));
			break;
	}
	$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $conf['limit_num'];
	$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = $conf['need_level'];
	if (empty($newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL]))
	{
		$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = 1;
	}

	//weight字段
	$newConf['goods_weight'] = $conf['goods_weight'];
	$goodsId = $conf['goods_id'];

	$config[$goodsId] = $newConf;
}
fclose($file);
print_r($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */