<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassCopy.script.php 175402 2015-05-28 08:50:11Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/script/WorldPassCopy.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-28 08:50:11 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175402 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";

$csvFile = 'lianyutiaozhan_copy.csv';
$outFileName = 'WORLD_PASS_COPY';

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
		'name' => $incre++,
		'army_id' => $incre++,
		'picture' => $incre++,
		'music' => $incre++,
		'hero_num' => $incre++,
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

	// 关卡id
	$id = intval($data[$tag['id']]);

	// 每个关卡的备选armyId
	$arrArmy = array2Int(str2Array($data[$tag['army_id']], ','));
	
	if (empty($id) || empty($arrArmy)) 
	{
		continue;
	}
	$config[$id]['army'] = $arrArmy;
	
	// 每个关卡上阵人数上限
	$config[$id]['hero_num'] = intval($data[$tag['hero_num']]);
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