<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildSkill.script.php 230587 2016-03-02 10:15:19Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildSkill.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:15:19 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230587 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'gruopTech_skill.csv';
$outFileName = 'GUILD_SKILL';

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
		GuildDef::GUILD_SKILL_TYPE					=> $index++,				//技能类型
		GuildDef::GUILD_SKILL_ATTR					=> ($index+=3)-1,			//每级成长
		GuildDef::GUILD_MEMBER_COST					=> $index++,				//个人消耗
		GuildDef::GUILD_MANAGER_COST				=> $index++,				//军团消耗
);

$arrKeyV3 = array(GuildDef::GUILD_MEMBER_COST, GuildDef::GUILD_MANAGER_COST);

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
		if( GuildDef::GUILD_SKILL_ATTR == $key )
		{
			if (is_numeric($data[$index]) || empty($data[$index])) 
			{
				$conf[$key] = intval($data[$index]);
			}
			else 
			{
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = $ary[1];
				}
			}
		}
		else if( in_array($key, $arrKeyV3, true) )
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
					$conf[$key][] = array2Int(str2Array($value, '|'));
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	$confList[$data[0]] = $conf;
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