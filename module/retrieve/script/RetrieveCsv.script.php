<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RetrieveCsv.script.php 147216 2014-12-18 13:51:14Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/script/RetrieveCsv.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-18 13:51:14 +0000 (Thu, 18 Dec 2014) $
 * @version $Revision: 147216 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Retrieve.def.php";

$csvFile = 'resourceback.csv';
$outFileName = 'RETRIEVE';

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
		RetrieveCsvTag::TYPE => $incre++,
		RetrieveCsvTag::SILVER => ($incre+=3)-1,
		RetrieveCsvTag::GOLD => $incre++,
		RetrieveCsvTag::SILVER_REWARD => $incre++,
		RetrieveCsvTag::GOLD_REWARD => $incre++,
);

$arrTag = array
(
		RetrieveCsvTag::SILVER_REWARD,
		RetrieveCsvTag::GOLD_REWARD,
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

	foreach ($tag as $k => $v)
	{
		if ($k == RetrieveCsvTag::TYPE) 
		{
			$type = intval($data[$v]);
			$config[$type] = array();
			continue;
		}
		
		if ($k == RetrieveCsvTag::SILVER) 
		{
			$config[$type][$k] = array_map('intval', explode('|', $data[$v]));
			continue;
		}
		
		if (in_array($k, $arrTag)) 
		{
			foreach (explode(',', $data[$v]) as $detail)
			{
				$config[$type][$k][] = explode('|', $detail);
			}
		}
		else 
		{
			$config[$type][$k] = intval($data[$v]);
		}
	}
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