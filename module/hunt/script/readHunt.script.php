<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readHunt.script.php 195591 2015-08-31 02:51:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/script/readHunt.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-08-31 02:51:22 +0000 (Mon, 31 Aug 2015) $
 * @version $Revision: 195591 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Hunt.def.php";

$inFileName = 'huntsoul.csv';
$outFileName = 'HUNT';

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
		HuntDef::HUNT_NEXT_RATE 					=> ($index+=3)-1,			// 开启下一场景概率
		HuntDef::HUNT_NEXT_PLACE 					=> $index++,				// 开启下一场景id
		HuntDef::HUNT_PLACE_COST					=> $index++,				// 场景消耗银币
		HuntDef::HUNT_PLACE_DROP					=> $index++,				// 场景对应掉落表
		HuntDef::HUNT_PLACE_POINT					=> $index++,				// 场景对应积分
		HuntDef::HUNT_SPECIAL_SERIAL				=> $index++,				// 场景累积变更积分序列
		HuntDef::HUNT_SPECIAL_DROP					=> $index++,				// 场景累积掉落表
);

$arrKeyV1 = array(HuntDef::HUNT_SPECIAL_SERIAL);
$arrKeyV2 = array(HuntDef::HUNT_PLACE_DROP, HuntDef::HUNT_SPECIAL_DROP);

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
			$conf[$key] = array2Int( str2array($data[$index]) );
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
						trigger_error( "hunt:$data[0] invalid key:$key, value:$value need v2\n" );
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
	
	//处理下特殊次数的序列
	if (!empty($conf[HuntDef::HUNT_SPECIAL_SERIAL]))
	{
		$sum = 0;
		foreach ($conf[HuntDef::HUNT_SPECIAL_SERIAL] as $key => $value)
		{
			$sum += $value;
			$conf[HuntDef::HUNT_SPECIAL_SERIAL][$key] = $sum;
		}
	}
	
	if (empty($conf[HuntDef::HUNT_NEXT_PLACE]))
	{
		trigger_error("hunt:$data[0] open next place id is empty!\n");
	}
	
	if (empty($conf[HuntDef::HUNT_PLACE_DROP]))
	{
		trigger_error("hunt:$data[0] drop id is empty!\n");
	}

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