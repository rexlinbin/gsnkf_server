<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyRule.script.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/script/GuildCopyRule.script.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/GuildCopy.cfg.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'groupCopy_rule.csv';
$outFileName = 'GUILD_COPY_RULE';

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
		'build_cond' => $incre++,
		'default_atk_num' => $incre++,
		'max_buy_num' => $incre++,
		'buy_cost' => $incre++,
		'all_attack' => $incre++,
		'country_add' => $incre++,
		'immuned_buff' => $incre++,
		'boss_open' => $incre++,
		'bonus' => $incre++,
		'num' => $incre++,
		'price' => $incre++,
		'cd' => $incre++,
		'rise' => $incre++,
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
	
	// 开启军团副本条件
	$arrBuildCond = str2Array($data[$tag['build_cond']], ',');
	foreach ($arrBuildCond as $aCond)
	{
		$detail = array2Int(str2Array($aCond, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid build cond:%d\n", count($detail)));
		}
		$config['build_cond'][$detail[0]] = $detail[1];
	}
	
	// 默认攻打次数
	$config['default_atk_num'] = intval($data[$tag['default_atk_num']]);
	
	// 每天最高购买次数
	$config['max_buy_num'] = intval($data[$tag['max_buy_num']]);
	
	// 购买价格
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
	
	// 全团突击相关信息
	$allAttackInfo = array2Int(str2Array($data[$tag['all_attack']], '|'));
	if (count($allAttackInfo) != 3)
	{
		trigger_error(sprintf("invalid all attack info:%d\n", count($allAttackInfo)));
	}
	$config['all_attack_limit'] = $allAttackInfo[0];
	$config['all_attack_cost'] = $allAttackInfo[1];
	$config['all_attack_add_num'] = $allAttackInfo[2];
	
	// 国家加成
	$arrCountryAdd = array2Int(str2Array($data[$tag['country_add']], '|'));
	if (count($arrCountryAdd) != 2) 
	{
		trigger_error(sprintf("invalid country add info:%d", count($arrCountryAdd)));
	}
	if (!isset(PropertyKey::$MAP_CONF[$arrCountryAdd[0]])) 
	{
		trigger_error(sprintf("invalid country add prop:%d\n", $arrCountryAdd[0]));
	}
	$config['country_add'][PropertyKey::$MAP_CONF[$arrCountryAdd[0]]] = intval($arrCountryAdd[1]);
	
	// 免疫的buff
	if (isset($data[$tag['immuned_buff']])) 
	{
		$config['immuned_buff'] = array2Int(str2Array($data[$tag['immuned_buff']], ','));
	}
	else 
	{
		$config['immuned_buff'] = array();
	}
	// Boss开启条件
	$arrBossOpen = str2Array($data[$tag['boss_open']], ',');
	foreach ($arrBossOpen as $aCond)
	{
		$detail = array2Int(str2Array($aCond, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid build cond:%d\n", count($detail)));
		}
		$config['boss_open'][$detail[0]] = $detail[1];
	}
	
	// 击杀奖励
	$arrBonus = str2Array($data[$tag['bonus']], ',');
	foreach ($arrBonus as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid bonus reward:%d\n", count($detail)));
		}
		$config['bonus'][] = $detail;
	}
	
	// 每日免费次数
	$num = intval($data[$tag['num']]);
	$config['num'] = $num;
	
	// 购买次数花费金币
	$arrPrice = str2Array($data[$tag['price']], ',');
	foreach ($arrPrice as $aCond)
	{
		$detail = array2Int(str2Array($aCond, '|'));
		if (count($detail) != 2)
		{
			trigger_error(sprintf("invalid build cond:%d\n", count($detail)));
		}
		$config['price'][$detail[0]] = $detail[1];
	}
	
	// Boss 复活CD
	$num = intval($data[$tag['cd']]);
	$config['cd'] = $num;
	
	// Boss 血量提升
	$num = intval($data[$tag['rise']]);
	$config['rise'] = $num;
	
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