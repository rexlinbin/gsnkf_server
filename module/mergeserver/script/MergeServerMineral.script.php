<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerMineral.script.php 178105 2015-06-11 07:10:00Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/script/MergeServerMineral.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-11 07:10:00 +0000 (Thu, 11 Jun 2015) $
 * @version $Revision: 178105 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/MergeServer.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'hefu_mine.csv';
$outFileName = 'MERGESERVER_MINERAL';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$tag = array(
		MergeServerDef::MSERVER_TYPE_MINERAL => array(
				"open_index" => 8,
				"reward_index" => 22,
		),
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

	$openIndex = $tag[MergeServerDef::MSERVER_TYPE_MINERAL]["open_index"];
	$rewardIndex = $tag[MergeServerDef::MSERVER_TYPE_MINERAL]["reward_index"];

	if (1 !== intval($data[$openIndex]))
	{
		continue;
	}
	
	$config['rate'] = intval($data[$rewardIndex]);

	break;
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