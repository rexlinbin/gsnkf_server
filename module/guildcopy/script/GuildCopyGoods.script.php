<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyGoods.script.php 188882 2015-08-04 13:05:49Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/script/GuildCopyGoods.script.php $
 * @author $Author: JiexinLin $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-04 13:05:49 +0000 (Tue, 04 Aug 2015) $
 * @version $Revision: 188882 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'groupCopy_shop.csv';
$outFileName = 'GUILD_COPY_GOODS';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$tag = array
(
		'id' => 0,
		'acq' => 1,
		'req' => 2,
		'type' => 3,
		'level' => 4,
		'copy' => 5,
		'num' => 6,
		'level_num' =>7,
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
	
	// id字段
	$id = intval($data[$tag['id']]);
	
	// 购买获得的商品
	$acq = array2Int(str2Array($data[$tag['acq']], '|'));
	if (count($acq) != 3)
	{
		trigger_error(sprintf("id:%d invalid acq:%s\n", $id, $data[$tag['acq']]));
	}
	switch ($acq[0])
	{
		case RewardConfType::SILVER:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SILVER] = $acq[2];
			break;
		case RewardConfType::SOUL:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SOUL] = $acq[2];
			break;
		case RewardConfType::GOLD:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_GOLD] = $acq[2];
			break;
		case RewardConfType::ITEM_MULTI:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array($acq[1]=>$acq[2]);
			break;
		case RewardConfType::HERO_MULTI:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = array($acq[1]=>$acq[2]);
			break;
		case RewardConfType::TREASURE_FRAG_MULTI:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_TREASFRAG] = array($acq[1]=>$acq[2]);
			break;
		case RewardConfType::GRAIN:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA][GuildDef::GUILD_BARN_SHOP_GRAIN] = $acq[2];
			break;
		default:
			trigger_error(sprintf("invalid goods acq type[%d]\n", $acq[0]));
			break;
	}
	
	// 消耗的东西，这里暂时只支持4种，金币，银币，物品，战功
	$arrReq = str2Array($data[$tag['req']], ',');
	foreach ($arrReq as $aReq)
	{
		$detail = array2Int(str2Array($aReq, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("id:%d invalid req:%s\n", $id, $data[$tag['req']]));
		}
		
		switch ($detail[0])
		{
			case RewardConfType::SILVER:
				$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] = $detail[2];
				break;
			case RewardConfType::GOLD:
				$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] = $detail[2];
				break;
			case RewardConfType::ITEM_MULTI:
				$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_ITEM][$detail[1]] = $detail[2];
				break;
			case RewardConfType::ZG:
				$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['zg'] = $detail[2];
				break;
			default:
				trigger_error(sprintf("invalid goods acq type[%d]\n", $detail[0]));
				break;
		}
	}
	
	// 购买类型和次数
	$conf[MallDef::MALL_EXCHANGE_TYPE] = intval($data[$tag['type']]);
	$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = intval($data[$tag['num']]);
	if (empty($conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM]))
	{
		$arrLevelNum = str2Array($data[$tag['level_num']], ',');
		foreach ($arrLevelNum as $arr)
		{
			$detail = array2Int(str2Array($arr, '|'));
			$conf['level_num'][$detail[0]] = $detail[1];
		}
		ksort($conf['level_num']);
		$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM] = $conf['level_num'];
		unset($conf['level_num']);
	}
	
	// 购买商品需要的等级
	$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = $data[$tag['level']];
	if (empty($conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL])) 
	{
		$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = 1;
	}
	
	// 购买需要的等级
	$conf['copy'] = intval($data[$tag['copy']]);

	$config[$id] = $conf;
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