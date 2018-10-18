<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerRecharge.script.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/script/MergeServerRecharge.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/MergeServer.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'hefu_recharge_back.csv';
$outFileName = 'MERGESERVER_RECHARGE';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$tag = array(
		MergeServerDef::MSERVER_TYPE_LOGIN => array(
				"day_index" => 3,
				"reward_index" => 4,
		),
		MergeServerDef::MSERVER_TYPE_RECHARGE => array(
				"expense_index" => 2,
				"reward_index" => 3,
		),
		MergeServerDef::MSERVER_TYPE_EXP_GOLD => array(
				"open_index" => 8,
				"reward_index" => 9,
		),
);

// 读取充值返还配置
$rechargeConfig = array();
$file = fopen($argv[1] . "/$csvFile", 'r');
if (FALSE == $file)
{
	echo $argv[1] . "/{$csvFile} open failed! exit!\n";
	exit;
}

fgetcsv($file);
fgetcsv($file);
$index = 0;
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;

	$expenseIndex = $tag[MergeServerDef::MSERVER_TYPE_RECHARGE]["expense_index"];
	$rewardIndex = $tag[MergeServerDef::MSERVER_TYPE_RECHARGE]["reward_index"];
	$expense = $data[$expenseIndex];
	$reward = $data[$rewardIndex];

	$rewards = array();
	$tmp = explode(',', $reward);
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
			default:
				trigger_error('invalid prize type:%d', $detail[0]);
		}
	}
	$rechargeConfig[++$index] = array(
			'expense' => $expense,
			'reward' => $rewards,
	);
}
fclose($file);
print_r($rechargeConfig);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($rechargeConfig));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */