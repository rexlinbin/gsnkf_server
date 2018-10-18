<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyInfo.script.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/script/GuildCopyInfo.script.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/GuildCopy.cfg.php";

$csvFile = 'groupCopy.csv';
$outFileName = 'GUILD_COPY_INFO';

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
		'next' => 1,
		'base' => 2,
		'box_reward' => 6,
		'pass_reward' => 7,
		'attack_reward' => 8,
		'kill_reward' => 9,
		'extra_reward' => 10,
		'box_reward_indicate' => 13,
		'box_reward_2' => 14,
		'boss_id' => 17,
		'boss_reward' => 18,
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
	
	// next字段，开启的下一个copy
	$next = intval($data[$tag['next']]);
	if (!empty($next) && $next != $id + 1) 
	{
		trigger_error(sprintf("next[%d] is not just after id[%d]\n", $next, $id));
	}
	$config[$id]['next'] = $next;

	// 该副本包含的所有base
	$arrBase = array2Int(str2Array($data[$tag['base']], ','));
	if (count($arrBase) != GuildCopyCfg::BASE_COUNT) 
	{
		trigger_error(sprintf("curr base[%d], need count[%d]\n", count($arrBase), GuildCopyCfg::BASE_COUNT));
	}
	$config[$id]['base'] = $arrBase;
	
	// 宝箱奖励1
	$arrBoxReward = str2Array($data[$tag['box_reward']], ',');
	foreach ($arrBoxReward as $aBox)
	{
		$detail = array2Int(str2Array($aBox, '|'));
		if (count($detail) != 4)
		{
			trigger_error(sprintf("id:%d invalid box reward:%d\n",$id, count($detail)));
		}
		$config[$id]['box_reward'][] = array('reward' => array($detail[0], $detail[1], $detail[2]), 'num' => $detail[3]); 
	}
	
	// 宝箱奖励2
	$arrBoxReward = str2Array($data[$tag['box_reward_2']], ',');
	foreach ($arrBoxReward as $aBox)
	{
		$detail = array2Int(str2Array($aBox, '|'));
		if (count($detail) != 4)
		{
			trigger_error(sprintf("id:%d invalid box reward 2:%d\n",$id, count($detail)));
		}
		$config[$id]['box_reward_2'][] = array('reward' => array($detail[0], $detail[1], $detail[2]), 'num' => $detail[3]); 
	}
	
	// 从什么时间开始，使用哪个宝箱奖励，便于宝箱奖励升级
	list($beginDate, $boxIndex) = array2Int(str2Array($data[$tag['box_reward_indicate']], '|'));
	if ($boxIndex != 1 && $boxIndex != 2) 
	{
		trigger_error(sprintf("invalid box index:%d\n", $boxIndex));
	}
	$config[$id]['box_reward_indicate'] = array($beginDate, $boxIndex);
	
	// 通关奖励
	$arrPassReward = str2Array($data[$tag['pass_reward']], ',');
	foreach ($arrPassReward as $aPassReward)
	{
		$detail = array2Int(str2Array($aPassReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("id:%d invalid pass reward:%d\n",$id, count($detail)));
		}
		$config[$id]['pass_reward'][] = $detail;
	}
	
	// 攻击奖励
	$arrAttackReward = str2Array($data[$tag['attack_reward']], ',');
	foreach ($arrAttackReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid attack reward:%d\n", count($detail)));
		}
		$config[$id]['attack_reward'][] = $detail;
	}
	
	// 击杀奖励
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
	
	// 额外奖励
	$arrExtraReward = str2Array($data[$tag['extra_reward']], ',');
	foreach ($arrExtraReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid extra reward:%d\n", count($detail)));
		}
		$config[$id]['extra_reward'][] = $detail;
	}
	
	// BossId
	$bossId = intval($data[$tag['boss_id']]);
	if (empty($bossId)) 
	{
		trigger_error(sprintf("error boss id %d", $bossId));
	}
	$config[$id]['boss_id'] = $bossId;
	// Boss奖励
	$arrBossReward = str2Array($data[$tag['boss_reward']], ',');
	foreach ($arrBossReward as $aReward)
	{
		$detail = array2Int(str2Array($aReward, '|'));
		if (count($detail) != 3)
		{
			trigger_error(sprintf("invalid extra reward:%d\n", count($detail)));
		}
		$config[$id]['boss_reward'][] = $detail;
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