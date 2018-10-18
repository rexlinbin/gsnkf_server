<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildStore.script.php 88291 2014-01-22 04:15:00Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildStore.script.php $
 * @author $Author: wuqilin $(tianming@babeltime.com)
 * @date $Date: 2014-01-22 04:15:00 +0000 (Wed, 22 Jan 2014) $
 * @version $Revision: 88291 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Define.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion_shop.csv';
$outFileName = 'GUILD_STORE';

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
		GuildDef::GUILD_EXP_ID						=> $index++,				//升级经验表id
		GuildDef::GUILD_LEVEL_RATIO					=> $index++,				//等级系数
		GuildDef::GUILD_NORMAL_GOODS				=> $index++,				//普通道具类商品ID组
		GuildDef::GUILD_SPECIAL_GOODS				=> $index++, 				//珍品类商品ID组
		GuildDef::GUILD_SPECIAL_NUM					=> $index++,				//每日珍品类商品数量
		GuildDef::GUILD_SPECIAL_CD					=> $index++,				//珍品类商品刷新CD
);

$arrKeyV1 = array(GuildDef::GUILD_NORMAL_GOODS, GuildDef::GUILD_SPECIAL_GOODS);

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
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	if (SECONDS_OF_DAY % $conf[GuildDef::GUILD_SPECIAL_CD] != 0) 
	{
		trigger_error("guild store:$data[0] special cd can not be divided by 86400!\n");
	}

	$confList = $conf;
}
fclose($file);

print_r($confList);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */