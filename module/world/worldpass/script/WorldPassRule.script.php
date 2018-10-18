<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassRule.script.php 227723 2016-02-16 13:17:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/script/WorldPassRule.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-02-16 13:17:53 +0000 (Tue, 16 Feb 2016) $
 * @version $Revision: 227723 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Property.def.php";

$csvFile = 'lianyutiaozhan_rule.csv';
$outFileName = 'WORLD_PASS_RULE';

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
		'time' => $incre++,
		'default_atk_num' => $incre++,
		'buy_cost' => $incre++,
		'point_coef' => $incre++,
		'hell_point_coef' => $incre++,
		'call_cost' => $incre++,
		'all_hero' => $incre++,
		'five_star_attr' => $incre++,
		'six_star_attr' => $incre++,
		'need_open_days' => $incre++,
		'refresh_item_id' => $incre++,
		'seven_star_attr' => $incre++,
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

	// id
	$config['id'] = intval($data[$tag['id']]);
	
	// 活动每周开始的时间和每周结束的时间
	$arrTime = str2Array($data[$tag['time']], ',');
	if (count($arrTime) != 2) 
	{
		trigger_error(sprintf("invalid time config:%s\n", $arrTime));
	}
	$arrBeginTime = array2Int(str2Array($arrTime[0], '|'));
	if (count($arrBeginTime) != 2)
	{
		trigger_error(sprintf("invalid begin time:%s\n", $arrBeginTime));
	}
	$config['begin_time'] = ($arrBeginTime[0] - 1) * 86400 + strtotime(date('Ymd') . sprintf("%06d", $arrBeginTime[1])) - strtotime(date('Ymd') . "000000");
	$arrEndTime = array2Int(str2Array($arrTime[1], '|'));
	if (count($arrEndTime) != 2)
	{
		trigger_error(sprintf("invalid end time:%s\n", $arrEndTime));
	}
	$config['end_time'] = ($arrEndTime[0] - 1) * 86400 + strtotime(date('Ymd') . sprintf("%06d", $arrEndTime[1])) - strtotime(date('Ymd') . "000000");

	// 默认攻打次数
	$config['default_atk_num'] = intval($data[$tag['default_atk_num']]);

	// 购买攻击次数价格
	$arrBuyCost = str2Array($data[$tag['buy_cost']], ',');
	foreach ($arrBuyCost as $aCost)
	{
		$detail = array2Int(str2Array($aCost, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid buy cost:%d\n", count($detail)));
		}
		$config['buy_cost'][$detail[0]] = $detail[1];
	}

	// 积分计算系数
	$config['point_coef'] = array2Int(str2Array($data[$tag['point_coef']], '|'));

	// 炼狱积分计算系数
	$config['hell_point_coef'] = intval($data[$tag['hell_point_coef']]);
	
	// 召唤武将花费
	$arrCallCost = str2Array($data[$tag['call_cost']], ',');
	foreach ($arrCallCost as $aCost)
	{
		$detail = array2Int(str2Array($aCost, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid call cost:%d\n", count($detail)));
		}
		$config['call_cost'][$detail[0]] = $detail[1];
	}
	
	// 召唤的所有武将
	$arrAllHero = str2Array($data[$tag['all_hero']], ',');
	foreach ($arrAllHero as $aHero)
	{
		$detail = array2Int(str2Array($aHero, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid hero config:%d\n", count($detail)));
		}
		$config['all_hero'][$detail[0]] = array('weight' => $detail[1]);
	}
	
	// 五星武将属性
	$arrFiveStarAttr = str2Array($data[$tag['five_star_attr']], ',');
	foreach ($arrFiveStarAttr as $aAttr)
	{
		$detail = array2Int(str2Array($aAttr, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid five star attr:%d\n", count($detail)));
		}
		if (!isset(PropertyKey::$MAP_CONF[$detail[0]])) 
		{
			trigger_error(sprintf("invalid attr id:%d\n", $detail[0]));
		}
		$config['five_star_attr'][$detail[0]] = $detail[1];
	}
	
	// 六星武将属性
	$arrSixStarAttr = str2Array($data[$tag['six_star_attr']], ',');
	foreach ($arrSixStarAttr as $aAttr)
	{
		$detail = array2Int(str2Array($aAttr, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid six star attr:%d\n", count($detail)));
		}
		if (!isset(PropertyKey::$MAP_CONF[$detail[0]]))
		{
			trigger_error(sprintf("invalid attr id:%d\n", $detail[0]));
		}
		$config['six_star_attr'][$detail[0]] = $detail[1];
	}
	
	// 七星武将属性
	$arrSevenStarAttr = str2Array($data[$tag['seven_star_attr']], ',');
	foreach ($arrSevenStarAttr as $aAttr)
	{
		$detail = array2Int(str2Array($aAttr, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid seven star attr:%d\n", count($detail)));
		}
		if (!isset(PropertyKey::$MAP_CONF[$detail[0]]))
		{
			trigger_error(sprintf("invalid attr id:%d\n", $detail[0]));
		}
		$config['seven_star_attr'][$detail[0]] = $detail[1];
	}
	
	// 自动分组需要服务器最低开服天数
	$config['need_open_days'] = intval($data[$tag['need_open_days']]);
	
	// 刷新武将道具id
	$config['refresh_item_id'] = intval($data[$tag['refresh_item_id']]);

	break;
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