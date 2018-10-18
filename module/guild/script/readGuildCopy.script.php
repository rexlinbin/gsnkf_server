<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildCopy.script.php 103080 2014-04-23 06:40:26Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildCopy.script.php $
 * @author $Author: TiantianZhang $(tianming@babeltime.com)
 * @date $Date: 2014-04-23 06:40:26 +0000 (Wed, 23 Apr 2014) $
 * @version $Revision: 103080 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion_copy.csv';
$outFileName = 'GUILD_COPY';

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
		GuildDef::GUILD_COPY_ARR					=> $index++,				//副本数组
		GuildDef::GUILD_COPY_ADD					=> $index++,				//副本每天增加攻击次数
		GuildDef::GUILD_COPY_LIMIT					=> $index++,				//副本最大累积攻击次数
		GuildDef::GUILD_HIT_ROUNDS					=> $index++,				//副本每次撞击的回合数
		GuildDef::GUILD_HELP_NUM					=> $index++,				//副本每日协助次数
//         'begin_talk'                                => $index++,                //上场对话
//         'win_talk'                                  => $index++,                //连胜对话
        GuildDef::GUILD_SILVER_ADDTION              => $index+=2,                //军团组队银币加成      
);

$arrKeyV2 = array(GuildDef::GUILD_COPY_ARR);

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
		if( in_array($key, $arrKeyV2, true) )
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
					$conf[$key][$ary[1]] = $ary[0];
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}

	$confList[] = $conf;
}
fclose($file);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList[0]));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */