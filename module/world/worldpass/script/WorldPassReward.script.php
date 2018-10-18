<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassReward.script.php 175402 2015-05-28 08:50:11Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/script/WorldPassReward.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-28 08:50:11 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175402 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";

$csvFile = 'lianyutiaozhan_reward.csv';
$outFileName = 'WORLD_PASS_REWARD';

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
		'desc' => $incre++,
		'min' => $incre++,
		'max' => $incre++,
		'reward' => $incre++,
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

	// 可以领取这个奖励的最大名次
	$max = intval($data[$tag['max']]);

	// 奖励内容
	$reward = array();
	$arrReward = str2Array($data[$tag['reward']], ',');
	foreach ($arrReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3) 
		{
			trigger_error(sprintf("invalid reward:%d\n", count($detail)));
		}
		$reward[] = $detail;
	}

	if (empty($max) || empty($reward))
	{
		continue;
	}

	$config[$max] = $reward;
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