<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonReward.script.php 169123 2015-04-22 13:22:24Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/script/MoonReward.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-04-22 13:22:24 +0000 (Wed, 22 Apr 2015) $
 * @version $Revision: 169123 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Moon.cfg.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Moon.def.php";

$csvFile = 'treasure_copygift.csv';
$outFileName = 'MOON_REWARD';

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
		'reward' => 1,
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

	// id字段，当前copy
	$id = intval($data[$tag['id']]);
	if ($id == 0) 
	{
		continue;
	}

	// 奖励内容
	$arrReward = str2Array($data[$tag['reward']], ',');
	foreach ($arrReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid reward:%d\n", count($detail)));
		}
		$config[$id][] = $detail;
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