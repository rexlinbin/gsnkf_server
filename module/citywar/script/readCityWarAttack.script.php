<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readCityWarAttack.script.php 137963 2014-10-30 02:24:44Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/script/readCityWarAttack.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-30 02:24:44 +0000 (Thu, 30 Oct 2014) $
 * @version $Revision: 137963 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/CityWar.def.php";

$inFileName = 'legion_citybattle.csv';
$outFileName = 'CITY_WAR_ATTACK';

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
		CityWarDef::SIGNUP_LIMIT						=> ($index+=9)-1,			// 城池报名上限
		CityWarDef::ATTACK_LIMIT						=> $index++,				// 参战城池上限
		CityWarDef::DEFENCE_PARAM						=> $index++,				// 城防攻防系数
		CityWarDef::JOIN_LIMIT							=> $index++,				// 军团参战上限
		CityWarDef::INSPIRE_ATTACK						=> $index++,				// 鼓舞增加攻击
		CityWarDef::INSPIRE_DEFEND						=> $index++,				// 鼓舞增加防御
		CityWarDef::INSPIRE_LIMIT						=> $index++,				// 攻防等级上限
		CityWarDef::INSPIRE_BASERATE					=> $index++,				// 鼓舞基础概率
		CityWarDef::INSPIRE_SUCPARAM					=> $index++,				// 鼓舞成功系数
		CityWarDef::INSPIRE_SILVER						=> $index++,				// 鼓舞花费银币
		CityWarDef::INSPIRE_GOLD						=> $index++,				// 鼓舞花费金币
		CityWarDef::INSPIRE_CD							=> $index++,				// 鼓舞冷却时间
		CityWarDef::WIN_DEFAULT							=> $index++,				// 默认连胜次数
		CityWarDef::WIN_GOLD							=> $index++,				// 金币连胜次数	
		CityWarDef::REWARD_PARAM						=> $index++,				// 职位收益系数
		CityWarDef::DEFENCE_MIN							=> $index++,				// 城防最小值
		CityWarDef::DEFENCE_MAX							=> $index++,				// 城防最大值
		CityWarDef::CONTRI_WIN							=> $index++,				// 胜利贡献值
		CityWarDef::CONTRI_FAIL							=> $index++,				// 失败贡献值
		CityWarDef::CONTRI_ADD							=> $index++,				// 贡献加成值
		CityWarDef::GUILD_LEVEL							=> $index++,				// 军团等级
		CityWarDef::USER_LEVEL							=> $index++,				// 用户等级
		CityWarDef::CD_CLEAR							=> $index++,				// 修复CD消耗金币
		CityWarDef::CD_TIME							    => $index++,				// 修复CD
);

$arrKeyV1 = array(
		CityWarDef::REWARD_PARAM,
);

$arrKeyV2 = array(
		CityWarDef::INSPIRE_ATTACK,
		CityWarDef::INSPIRE_DEFEND,
		CityWarDef::WIN_GOLD,
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
						trigger_error( "city war:$data[0] invalid $key, need v2\n" );
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
	
	//整理鼓舞增加攻击信息
	$attack = array();
	foreach ($conf[CityWarDef::INSPIRE_ATTACK] as $key => $value)
	{
		$attack[$value[0]] = $value[1];
	}
	$conf[CityWarDef::INSPIRE_ATTACK] = $attack;
	
	//整理鼓舞增加防御信息
	$defend = array();
	foreach ($conf[CityWarDef::INSPIRE_DEFEND] as $key => $value)
	{
		$defend[$value[0]] = $value[1];
	}
	$conf[CityWarDef::INSPIRE_DEFEND] = $defend;

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