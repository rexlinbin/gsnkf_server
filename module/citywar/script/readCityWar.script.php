<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readCityWar.script.php 115830 2014-06-19 10:11:48Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/script/readCityWar.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-19 10:11:48 +0000 (Thu, 19 Jun 2014) $
 * @version $Revision: 115830 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/CityWar.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$inFileName = 'city.csv';
$outFileName = 'CITY_WAR';

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

$index = 0;
//对应配置表键名
$arrConfKey = array (
		CityWarDef::CITY_LEVEL 						=> ($index+=4)-1,			// 城池等级
		CityWarDef::GUILD_LEVEL						=> $index++, 				// 需要军团等级
		CityWarDef::CITY_REWARD						=> $index++,				// 城池奖励
		CityWarDef::CITY_EFFECT						=> $index++,				// 城池效果
		CityWarDef::CITY_GUARD						=> $index++,				// 城池守卫者
		CityWarDef::DEFENCE_DEFAULT					=> $index++,				// 城防默认值
		CityWarDef::DEFENCE_DECREASE				=> $index++,				// 城防下降值
		CityWarDef::RUIN_GUARD						=> ($index+=2)-1,			// 破坏城池守卫军
		CityWarDef::MEND_GUARD						=> $index++,				// 修复城池守卫军
);

$arrKeyV1 = array(
		CityWarDef::RUIN_GUARD,
		CityWarDef::MEND_GUARD,
);

$arrKeyV2 = array(
		CityWarDef::CITY_REWARD, 
		CityWarDef::CITY_EFFECT
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
				$conf[$key] = array2Int( str2array($data[$index]) );
			}
		}
		elseif( in_array($key, $arrKeyV2, true) )
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
					$conf[$key][] = array2Int(str2Array($value, '|'));
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	//整理效果信息
	$effect = array();
	foreach ($conf[CityWarDef::CITY_EFFECT] as $key => $value)
	{
		$effect[$value[0]] = $value[1];
	}
	$conf[CityWarDef::CITY_EFFECT] = $effect;

	$confList[$data[0]] = $conf;
}
fclose($file);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */