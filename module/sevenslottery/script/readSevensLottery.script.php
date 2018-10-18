<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readSevensLottery.script.php 255800 2016-08-12 03:25:50Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/script/readSevensLottery.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-12 03:25:50 +0000 (Fri, 12 Aug 2016) $
 * @version $Revision: 255800 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/SevensLottery.def.php";

$inFileName = 'sevenstar_altar.csv';
$outFileName = 'SEVENS_LOTTERY';

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
		SevensLotteryDef::LOTTERY_ID 						=> $index++,				// id
		SevensLotteryDef::LOTTERY_LIMIT 					=> $index++,				// 每日最大抽取次数限制
		SevensLotteryDef::LOTTERY_COST						=> $index++,				// 每次抽取金币花费
		SevensLotteryDef::LOTTERY_POINT						=> $index++,				// 每次抽取获得积分
		SevensLotteryDef::LUCKY_MAX							=> $index++,				// 幸运值上限
		SevensLotteryDef::LUCKY_RANGE						=> $index++,				// 每次抽取获得幸运值范围
		SevensLotteryDef::PERIOD_TIME						=> $index++,				// 换届时间
		SevensLotteryDef::LOTTERY_DROP						=> ($index+=2)-1,			// 常规掉落ID
		SevensLotteryDef::LUCKY_REWARD						=> $index++,				// 幸运值满后奖励
		SevensLotteryDef::LOTTERY_ITEM						=> $index++,				// 优先消耗的物品
);

$arrKeyV1 = array(SevensLotteryDef::LUCKY_RANGE);
$arrKeyV2 = array(SevensLotteryDef::LUCKY_REWARD, SevensLotteryDef::LOTTERY_ITEM);

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
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach ($arr as $value)
				{
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = array('weight' => $ary[1]);
				}
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
						trigger_error( "star:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					if ($key == SevensLotteryDef::LOTTERY_ITEM) 
					{
						$conf[$key][$ary[1]] = $ary[2];
					}
					else 
					{
						$conf[$key][] = $ary;
					}
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