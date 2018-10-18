<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompetePrize.script.php 202070 2015-10-14 06:29:42Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/script/WorldCompetePrize.script.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-14 06:29:42 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202070 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";

$csvFile = 'kuafu_contest_dayreward.csv';
$outFileName = 'WORLD_COMPETE_PRIZE';

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
		'num' => $incre++,
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

	// 可以领取这个奖励的胜利场次
	$num = intval($data[$tag['num']]);

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

	if (empty($num) || empty($reward))
	{
		continue;
	}

	$config[$num] = $reward;
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