<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuild.script.php 230585 2016-03-02 10:13:55Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuild.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:13:55 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230585 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";

$inFileName = 'legion.csv';
$outFileName = 'GUILD';

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
		GuildDef::GUILD_USER_LEVEL					=> $index++,				//军团开启所需人物等级
		GuildDef::GUILD_SILVER_CREATE				=> $index++,				//创建军团所需银币
		GuildDef::GUILD_GOLD_CREATE					=> $index++,				//创建军团所需金币
		GuildDef::GUILD_REJOIN_CD					=> $index++, 				//再次加入军团CD时间（秒）
		GuildDef::GUILD_CAPACITY_BASE				=> $index++,				//军团人数基础值
		GuildDef::GUILD_CAPACITY_LIMIT				=> $index++,				//军团等级最大人数
		GuildDef::GUILD_MAX_LEVEL					=> $index++,				//军团最大等级
		GuildDef::GUILD_EXP_ID						=> $index++,				//军团等级经验表ID
		GuildDef::GUILD_CONTRI_ARR					=> $index++, 				//军团捐献方式数组
		GuildDef::GUILD_VP_NUM						=> ($index+=5)-1,			//军团等级与副军团长人数数组
		GuildDef::GUILD_JOIN_EXTRA					=> $index++,				//每天加入成员额外个数限制
		GuildDef::GUILD_IMPEACH_GOLD				=> $index++,				//弹劾团长花费金币
		GuildDef::GUILD_TECH_OPEN					=> $index++,				//科技开启条件
);

$arrKeyV2 = array(
		GuildDef::GUILD_CAPACITY_BASE, 
		GuildDef::GUILD_VP_NUM,
		GuildDef::GUILD_TECH_OPEN,
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
		
		if( GuildDef::GUILD_CONTRI_ARR == $key )
		{
			for ($i = 0; $i < 5; $i++)
			{
				if (empty($data[$index + $i])) 
				{
					break;
				}
				$ary = array2Int( str2array($data[$index + $i], '|') );
				if (count($ary) != 5) 
				{
					trigger_error("guild:$data[0] contri arr count is not 5");
				}
				$conf[$key][$i + 1] = array(
					'silver' => $ary[0],
					'gold' => $ary[1],
					'exp' => $ary[2],
					'point' => $ary[3],
					'vip' => $ary[4],
				);
			}
		}
		else if( in_array($key, $arrKeyV2, true) )
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
		else
		{
			$conf[$key] = intval($data[$index]);
		}
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