<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AttrExtraTemplete.script.php 161727 2015-03-16 09:23:00Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/AttrExtraTemplete.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-16 09:23:00 +0000 (Mon, 16 Mar 2015) $
 * @version $Revision: 161727 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'affix_count.csv';
$outFileName = 'SECOND_FRIEND_TEMPLETE';

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
		'formula_id' => 1,
		'base' => 2,
		'add' => 3,
		'final' => 4,
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

	$id = intval($data[$tag['id']]);
	$formulaId = intval($data[$tag['formula_id']]);
	if ($formulaId != 1 && $formulaId != 2) 
	{
		trigger_error("error formula id " . $formulaId. "\n");
	}
	
	$base = intval($data[$tag['base']]);
	if ($base > 0 && !isset(PropertyKey::$MAP_CONF[$base]))
	{
		trigger_error("error attr conf, no map info of id " . $base . "\n");
	}
	if ($base > 0) 
	{
		$base = PropertyKey::$MAP_CONF[$base];
	}
	
	$add = intval($data[$tag['add']]);
	if ($add > 0 && !isset(PropertyKey::$MAP_CONF[$add]))
	{
		trigger_error("error attr conf, no map info of id " . $add . "\n");
	}
	if ($add > 0)
	{
		$add = PropertyKey::$MAP_CONF[$add];
	}
	
	$final = intval($data[$tag['final']]);
	if ($final > 0 && !isset(PropertyKey::$MAP_CONF[$final]))
	{
		trigger_error("error attr conf, no map info of id " . $final . "\n");
	}
	if ($final > 0)
	{
		$final = PropertyKey::$MAP_CONF[$final];
	}

	$config[$id] = array('formula' => $formulaId, 'base' => $base, 'add' => $add, 'final' => $final);
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