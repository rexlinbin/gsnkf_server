<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readStarTeach.script.php 128361 2014-08-21 06:18:10Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/scripts/readStarTeach.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-21 06:18:10 +0000 (Thu, 21 Aug 2014) $
 * @version $Revision: 128361 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Star.def.php";

$inFileName = 'teach.csv';
$outFileName = 'STAR_TEACH';

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
		StarDef::STAR_DRAW_FREE						=> $index++,				// 每日免费可翻牌次数
		StarDef::STAR_CHALLENGE_FREE				=> $index++,				// 每日免费可挑战次数
		StarDef::STAR_DRAW_COST						=> $index++,				// 购买翻牌次数金币组
		StarDef::STAR_CHALLENGE_COST				=> $index++,				// 购买挑战次数金币组
		StarDef::STAR_DRAW_COMBINATION				=> $index++,				// 翻牌组合类型名称表
		StarDef::STAR_DRAW_DROP						=> $index++,				// 翻牌掉落武将ID组
		StarDef::STAR_SHUFFLE_COST					=> $index++,				// 洗牌需要金币
		StarDef::STAR_CHALLENGE_FEEL				=> $index++,				// 挑战增加感悟值组
		StarDef::STAR_SPECIAL_COST					=> $index++,				// 一键最大金币
);

$arrKeyV1 = array(StarDef::STAR_DRAW_DROP, StarDef::STAR_DRAW_COST, StarDef::STAR_CHALLENGE_COST);
$arrKeyV2 = array(StarDef::STAR_SHUFFLE_COST, StarDef::STAR_CHALLENGE_FEEL);
$arrKeyV3 = array(StarDef::STAR_DRAW_COMBINATION);

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
				if ($key == StarDef::STAR_DRAW_DROP) 
				{
					$conf[$key] = array2Int(str2array($data[$index]));
				}
				else 
				{
					$conf[$key] = array2Int(str2array($data[$index], '|'));
				}
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
						trigger_error( "invalid $key, need v2\n" );
					}
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
					if(!strpos($value, '|'))
					{
						trigger_error( "star:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = array('feel' => $ary[2], 'weight' => $ary[3]);
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
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