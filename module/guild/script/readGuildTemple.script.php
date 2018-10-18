<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildTemple.script.php 82268 2013-12-20 13:41:15Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildTemple.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-12-20 13:41:15 +0000 (Fri, 20 Dec 2013) $
 * @version $Revision: 82268 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion_feast.csv';
$outFileName = 'GUILD_TEMPLE';

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
		GuildDef::GUILD_EXECUTION_BASE				=> $index++,				//体力基础值
		GuildDef::GUILD_EXECUTION_INCRE				=> $index++, 				//体力成长值
		GuildDef::GUILD_STAMINA_BASE				=> $index++,				//耐力基础值
		GuildDef::GUILD_STAMINA_INCRE				=> $index++,				//耐力成长值
		GuildDef::GUILD_PRESTIGE_BASE				=> $index++,				//声望基础值
		GuildDef::GUILD_PRESTIGE_INCRE				=> $index++,				//声望成长值
		GuildDef::GUILD_SOUL_BASE					=> $index++,				//将魂基础值
		GuildDef::GUILD_SOUL_INCRE					=> $index++, 				//将魂成长值
		GuildDef::GUILD_SILVER_BASE					=> $index++,				//银币基础值
		GuildDef::GUILD_SILVER_INCRE				=> $index++,				//银币成长值	
		GuildDef::GUILD_GOLD_BASE					=> $index++,				//金币基础值
		GuildDef::GUILD_GOLD_INCRE					=> $index++,				//金币成长值
		GuildDef::GUILD_REWARD_START				=> $index++,				//领奖开始时间
		GuildDef::GUILD_REWARD_END					=> $index++,				//领奖结束时间
		GuildDef::GUILD_REWARD_COST					=> $index++,				//领奖花费贡献值
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
		$conf[$key] = intval($data[$index]);
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