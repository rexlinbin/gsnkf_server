<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerCompensation.script.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/script/MergeServerCompensation.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/MergeServer.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'hefu_reward.csv';
$outFileName = 'MERGESERVER_COMPENSATION';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$tag = array(
		MergeServerDef::MSERVER_TYPE_COMPENSATION => array(
				"base_index" => 1,
				"coef_index" => 2,
				"max_day_index" => 3,
				"fix_index" => 4,
		),
);

// 读取合服补偿配置
$compensationConfig = array();
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

	$baseIndex = $tag[MergeServerDef::MSERVER_TYPE_COMPENSATION]["base_index"];
	$coefIndex = $tag[MergeServerDef::MSERVER_TYPE_COMPENSATION]["coef_index"];
	$maxDayIndex = $tag[MergeServerDef::MSERVER_TYPE_COMPENSATION]["max_day_index"];
	$fixIndex = $tag[MergeServerDef::MSERVER_TYPE_COMPENSATION]["fix_index"];
	
	$base = $data[$baseIndex];
	$coef = $data[$coefIndex];
	$maxDay = $data[$maxDayIndex];
	$fix = $data[$fixIndex];

	$rewards = array();
	$tmp = explode(',', $base);
	foreach ($tmp as $rwd)
	{
		$detail = explode('|', $rwd);
		switch ($detail[0])
		{
			case RewardConfType::SILVER:
			case RewardConfType::SOUL:
			case RewardConfType::GOLD:
			case RewardConfType::EXECUTION:
			case RewardConfType::STAMINA:
			case RewardConfType::ITEM:
			case RewardConfType::SILVER_MUL_LEVEL:
			case RewardConfType::SOUL_MUL_LEVEL:
			case RewardConfType::EXP_MUL_LEVEL:
			case RewardConfType::HERO:
			case RewardConfType::JEWEL:
			case RewardConfType::PRESTIGE:
			case RewardConfType::GUILD_CONTRI:
			case RewardConfType::GUILD_EXP:
			case RewardConfType::HORNOR:
				$rewards[] = array(
				'type' => $detail[0],
				'val' => intval($detail[2]),
				);
				break;
			case RewardConfType::ITEM_MULTI:
			case RewardConfType::HERO_MULTI:
			case RewardConfType::TREASURE_FRAG_MULTI:
				$rewards[] = array(
				'type' => $detail[0],
				'val' => array(
				array(
				$detail[1],
				intval($detail[2]),),
				),
				);
				break;
			/*default:
				trigger_error('invalid prize type:%s', $detail[0]);*/
		}
	}
	$compensationConfig['base'] = $rewards;
	
	$rewards = array();
	$tmp = explode(',', trim($fix));
	foreach ($tmp as $rwd)
	{
		$detail = explode('|', $rwd);
		switch ($detail[0])
		{
			case RewardConfType::SILVER:
			case RewardConfType::SOUL:
			case RewardConfType::GOLD:
			case RewardConfType::EXECUTION:
			case RewardConfType::STAMINA:
			case RewardConfType::ITEM:
			case RewardConfType::SILVER_MUL_LEVEL:
			case RewardConfType::SOUL_MUL_LEVEL:
			case RewardConfType::EXP_MUL_LEVEL:
			case RewardConfType::HERO:
			case RewardConfType::JEWEL:
			case RewardConfType::PRESTIGE:
			case RewardConfType::GUILD_CONTRI:
			case RewardConfType::GUILD_EXP:
			case RewardConfType::HORNOR:
				$rewards[] = array(
				'type' => $detail[0],
				'val' => intval($detail[2]),
				);
				break;
			case RewardConfType::ITEM_MULTI:
			case RewardConfType::HERO_MULTI:
			case RewardConfType::TREASURE_FRAG_MULTI:
				$rewards[] = array(
				'type' => $detail[0],
				'val' => array(
				array(
				$detail[1],
				intval($detail[2]),),
				),
				);
				break;
			/*default:
				trigger_error('invalid prize type:%d', $detail[0]);*/
		}
	}
	
	$compensationConfig['fix'] = $rewards;
	$compensationConfig['coef'] = $coef;
	$compensationConfig['max'] = $maxDay;
}
fclose($file);
print_r($compensationConfig);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($compensationConfig));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */