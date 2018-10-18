<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyReward.script.php 164330 2015-03-30 13:00:47Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/script/GuildCopyReward.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-30 13:00:47 +0000 (Mon, 30 Mar 2015) $
 * @version $Revision: 164330 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'groupCopy_reward.csv';
$outFileName = 'GUILD_COPY_REWARD';

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
		'rank' => 3,
		'reward' => 4,
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

	// rank字段
	$rank = intval($data[$tag['rank']]);
	if (empty($rank)) 
	{
		$rank = 100000;
	}

	// 奖励数据
	$reward = array();
	$arrReward = str2Array($data[$tag['reward']], ',');
	foreach ($arrReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3) 
		{
			trigger_error(sprintf("rank:%d invalid reward:%d\n", $rank, count($detail)));
		}
		$config[$rank][] = $detail;
	}
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