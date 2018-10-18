<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonCopy.script.php 219276 2016-01-05 05:58:07Z NanaPeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/script/MoonCopy.script.php $
 * @author $Author: NanaPeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-05 05:58:07 +0000 (Tue, 05 Jan 2016) $
 * @version $Revision: 219276 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Moon.cfg.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Moon.def.php";

$csvFile = 'treasure_smallcopy.csv';
$outFileName = 'MOON_COPY';

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
		'grid' => 3,
		'boss' => 4,
		'default_open_grid' => 5,
		'kill_reward' => 6,
		'drop' => 7,
		//梦魇模式增加新字段
		'strengthen' => 10,     //梦魇属性增强
		'drop_nightmare' => 11,  //梦魇掉落
		'nightmare_reward' => 13,//梦魇boss固定掉落
		
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
$lastId = 0;
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
	if ($id != ++$lastId)
	{
		trigger_error(sprintf("id not continus, id[%d], last[%d]\n", $id, --$lastId));
	}

	// 格子的信息
	$arrGridInfo = str2Array($data[$tag['grid']], ',');
	if (count($arrGridInfo) != MoonConf::MAX_GRID_NUM)
	{
		trigger_error(sprintf("curr grid count[%d], need count[%d]\n", count($arrGridInfo), MoonConf::MAX_GRID_NUM));
	}
	$gridId = 0;
	foreach ($arrGridInfo as $aGrid)
	{
		$detail = array2Int(str2Array($aGrid, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("id:%d invalid grid config:%d\n", $id, count($detail)));
		}
		
		$gridInfo = array();
		if (1 == $detail[0]) // 怪
		{
			$gridInfo['type'] = MoonGridType::MONSTER;
			$gridInfo['baseId'] = $detail[1];
		}
		else if (2 == $detail[0]) // 宝箱
		{
			$gridInfo['type'] = MoonGridType::BOX;
			$gridInfo['boxId'] = $detail[1];
		}
		else 
		{
			trigger_error(sprintf("id:%d invalid grid type:%d\n", $id, $detail[0]));
		}
		
		$config[$id]['grid'][++$gridId] = $gridInfo;
	}

	// boss的armyId
	$config[$id]['boss'] = intval($data[$tag['boss']]);
	
	// 默认开启的格子id
	$defaultOpenGrid = intval($data[$tag['default_open_grid']]);
	if ($defaultOpenGrid <= 0 || $defaultOpenGrid > MoonConf::MAX_GRID_NUM) 
	{
		trigger_error(sprintf("id:%d invalid default open grid id:%d\n", $id, $defaultOpenGrid));
	}
	$config[$id]['default_open_grid'] = $defaultOpenGrid;
	
	// 击杀boss奖励
	$arrKillReward = str2Array($data[$tag['kill_reward']], ',');
	foreach ($arrKillReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid kill reward:%d\n", count($detail)));
		}
		$config[$id]['kill_reward'][] = $detail;
	}
	
	// drop id
	$config[$id]['drop'] = intval($data[$tag['drop']]);
	
	//梦魇属性增强
	$strengthenAttr = str2Array($data[$tag['strengthen']], ',');
	foreach($strengthenAttr as $attrs)
	{
		$detailAttr = array2Int(str2Array($attrs, '|'));
		$config[$id]['strengthen'][$detailAttr[0]] = $detailAttr[1];
	}
	
	//梦魇掉落
	$config[$id]['drop_nightmare'] = intval($data[$tag['drop_nightmare']]);
	
	//梦魇boss固定奖励
	$arrNightmareKillReward = str2Array($data[$tag['nightmare_reward']], ',');
	foreach ($arrNightmareKillReward as $nReward)
	{
		$ndetail = array2Int(str2Array($nReward, '|'));
		if (count($ndetail) != 3)
		{
			trigger_error(sprintf("invalid kill reward:%d\n", count($ndetail)));
		}
		$config[$id]['nightmare_reward'][] = $ndetail;
	}
	/**
	 *  id   int
	 *  {
	 *  	'grid'  					  格子的信息
	 *  	{
	 *  		gridId int
	 *  		[
	 *  			type                 monster/box
	 *  			baseId/boxId		 armyId/宝箱Id
	 *  		]   
	 *  	}	
	 *	    'boss'=> int                 boss的armyId
	 *		'default_open_grid'=>int     默认开启的格子id
	 *		'kill_reward'                击杀boss奖励
	 *		[
	 *			[type,tpl_id,num]        奖励三元组int
	 *		]
	 *		'drop'=>int                  掉落id
	 *		'strengthen'                 梦魇属性增强
	 *		[
	 *			属性id => 增加比例
	 *		]
	 *		'drop_nightmare'=> int       梦魇掉落
	 *      'nightmare_reward'
	 *      [
	 *      	[type,tpl_id,num]        梦魇奖励三元组
	 *      ]
	 *  }
	 */
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