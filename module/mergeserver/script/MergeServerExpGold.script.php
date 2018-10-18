<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerExpGold.script.php 135595 2014-10-10 05:00:49Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/script/MergeServerExpGold.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-10 05:00:49 +0000 (Fri, 10 Oct 2014) $
 * @version $Revision: 135595 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/MergeServer.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'hefu_exp_gold.csv';
$outFileName = 'MERGESERVER_EXP_GOLD';

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

// 读取经验宝物摇钱树活动配置
$expGoldConfig = array();
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

	$openIndex = $tag[MergeServerDef::MSERVER_TYPE_EXP_GOLD]["open_index"];
	$rewardIndex = $tag[MergeServerDef::MSERVER_TYPE_EXP_GOLD]["reward_index"];

	if (1 !== intval($data[$openIndex]))
	{
		continue;
	}

	$rewards = explode(',', $data[$rewardIndex]);
	foreach ($rewards as $reward)
	{
		$tmp = explode('|', $reward);
		$expGoldConfig[$tmp[0]] = $tmp[1];
	}

	break;
}
fclose($file);
print_r($expGoldConfig);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($expGoldConfig));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */