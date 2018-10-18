<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildStage.script.php 140795 2014-11-19 09:01:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildStage.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-19 09:01:45 +0000 (Wed, 19 Nov 2014) $
 * @version $Revision: 140795 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion_chest.csv';
$outFileName = 'GUILD_LOTTERY';

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
		GuildDef::GUILD_LOTTERY_NUM					=> $index++,				//开启军团宝箱次数
		GuildDef::GUILD_LOTTERY_DROP				=> ($index+=2)-1,			//掉落表ID
		GuildDef::GUILD_LOTTERY_COST				=> $index++,				//开启军团宝箱消耗功勋值
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