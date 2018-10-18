<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildBarn.script.php 227684 2016-02-16 08:56:47Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildBarn.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-02-16 08:56:47 +0000 (Tue, 16 Feb 2016) $
 * @version $Revision: 227684 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion_granary.csv';
$outFileName = 'GUILD_BARN';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $inFileName $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$inputDir = $argv[1];
$outputDir = $argv[2];

$index = 1;
//对应配置表键名
$arrConfKey = array (
		GuildDef::GUILD_BARN_OPEN					=> $index++,				//军团粮仓开启需要大厅等级
		GuildDef::GUILD_EXP_ID						=> $index++,				//军团粮仓经验表ID
		GuildDef::GUILD_LEVEL_RATIO					=> $index++,				//军团粮仓等级系数
		GuildDef::GUILD_FIELD_NUM					=> $index++,				//粮田开启数量及开启等级
		GuildDef::GUILD_HARVEST_GRAIN				=> $index++,				//银币采集粮田产量
		GuildDef::GUILD_HARVEST_SILVER				=> ($index+=10)-1,			//银币采集粮田花费
		GuildDef::GUILD_HARVEST_NUM					=> $index++,				//每个粮田采集次数
		GuildDef::GUILD_REFRESH_OWN					=> $index++,				//每天可刷新粮田次数
		GuildDef::GUILD_REFRESH_BASE				=> $index++,				//每次刷新粮田花费金币
		GuildDef::GUILD_REFRESH_ADD					=> $index++,				//刷新粮田递增的金币
		GuildDef::GUILD_CHALLENGE_COST				=> $index++,				//购买战书花费
		GuildDef::GUILD_CHALLENGE_FREE				=> $index++,				//每日免费战书次数
		GuildDef::GUILD_SHARE_CD					=> $index++,				//分粮冷却时间
		GuildDef::GUILD_GRAIN_CAPACITY				=> $index++,				//粮仓粮草每级上限
		GuildDef::GUILD_HARVEST_EXP					=> $index++,				//粮田每次采集获得经验
		GuildDef::GUILD_SHARE_COEF					=> $index++,				//分粮系数
		GuildDef::GUILD_REFRESH_ALL_BYGOLD			=> ($index+=2)-1,			//每日军团全部刷新次数
		GuildDef::GUILD_FIELD_LEVEL					=> $index++,				//粮田等级上限
		GuildDef::GUILD_FIGHTBOOK_LIMIT             => $index++,				//挑战书携带上限
        GuildDef::GUILD_REFRESH_ALL_BYGUILDEXP      => $index++,				//贡献度军团全部刷新次数
        GuildDef::GUILD_RFRALL_BYEXP_COST           => $index++,				//军团刷新贡献度消耗
        GuildDef::MAX_HARVEST_NUM                   => $index++, 				//粮田采集次数累加上限
        GuildDef::GUILD_HARVEST_EXTRA				=> $index++,				//采集1次额外获得物品
);

$arrKeyV1 = array(
        GuildDef::GUILD_REFRESH_ALL_BYGOLD,
        GuildDef::GUILD_REFRESH_ALL_BYGUILDEXP,
		GuildDef::GUILD_RFRALL_BYEXP_COST
);

$arrKeyV2 = array(
		GuildDef::GUILD_FIELD_NUM, 
		GuildDef::GUILD_HARVEST_NUM,
		GuildDef::GUILD_GRAIN_CAPACITY,
		GuildDef::GUILD_SHARE_COEF,
		GuildDef::GUILD_FIELD_LEVEL,
        GuildDef::GUILD_REFRESH_BASE,
        GuildDef::GUILD_REFRESH_ADD,
        GuildDef::GUILD_BARN_OPEN,
);

//a|b|c,d|e|f;g|h|i => (((a,b,c),(d,e,f)),((g,h,j)))
$arrKeyV3 = array(
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ($arrConfKey as $key => $index)
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array2Int(str2array($data[$index]));
			}
		}
		elseif ( in_array($key, $arrKeyV2, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "star:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = $ary[1];
				}
			}
		}
		elseif (in_array($key, $arrKeyV3, true))
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arrs = str2array($data[$index], ';');
				$conf[$key] = array();
				$i = 0;
				foreach ($arrs as $arr)
				{
					if (empty($arr))
					{
						$conf[$key][$i] = array();
					}
					else
					{
						$arr = str2array($arr);
						foreach ($arr as $value)
						{
							$conf[$key][$i][] = array2Int(str2Array($value, '|'));
						}
					}
					$i++;
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$key1 = GuildDef::GUILD_HARVEST_GRAIN;
	$key2 = GuildDef::GUILD_FIELD_EXPID;
	$index = $arrConfKey[$key1];
	$conf[$key1] = array();
	$conf[$key2] = array();
	for ($i = 0; $i < 5; $i++)
	{
		$arr = str2array($data[$index + 2 * $i]);
		foreach ($arr as $value)
		{
			$ary = array2Int(str2Array($value, '|'));
			$conf[$key1][$i + 1][$ary[0]] = array($ary[1], $ary[2]);
		}
		$conf[$key2][$i + 1] = intval($data[$index + 2 * $i + 1]);
	}

	$confList[] = $conf;
}
fclose($file);

print_r($confList[0]);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList[0]));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */