<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteGoods.script.php 245032 2016-06-01 10:47:36Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/script/WorldCompeteGoods.script.php $
 * @author $Author: QingYao $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-01 10:47:36 +0000 (Wed, 01 Jun 2016) $
 * @version $Revision: 245032 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Reward.def.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Mall.def.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";

$inFileName = 'kuafu_contest_shop.csv';	
$outFileName = 'WORLD_COMPETE_GOODS';

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

//数据对应表
$index = 0;
$arrConfKey = array (
		MallDef::MALL_EXCHANGE_ITEM => ++$index,
		MallDef::MALL_EXCHANGE_GOLD => ++$index,
		MallDef::MALL_EXCHANGE_TYPE => ++$index,
		MallDef::MALL_EXCHANGE_NUM => ++$index,
		'is_sold'=>($index+4),
);

$arrKeyV2 = array(
		MallDef::MALL_EXCHANGE_ITEM, 
		MallDef::MALL_EXCHANGE_GOLD,
);

//暂时只支持金币银币声望跨服荣誉
$exchangeReq = array(
		MallDef::MALL_EXCHANGE_GOLD,
		MallDef::MALL_EXCHANGE_SILVER,
		MallDef::MALL_EXCHANGE_PRESTIGE,
		MallDef::MALL_EXCHANGE_EXTRA,
		MallDef::MALL_EXCHANGE_NUM,
		MallDef::MALL_EXCHANGE_JH,
);

$exchangeAcq = array(
		MallDef::MALL_EXCHANGE_ITEM,
		MallDef::MALL_EXCHANGE_EXTRA,
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
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
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
				foreach( $arr as $value )
				{
					$ary = array2Int(str2Array($value, '|'));
					if ($key == MallDef::MALL_EXCHANGE_ITEM) 
					{
						if ($ary[0] == RewardConfType::ITEM_MULTI) 
						{
							$conf[MallDef::MALL_EXCHANGE_ACQ][$key] = array($ary[1] => $ary[2]);
						}
						elseif ($ary[0] == RewardConfType::CROSS_HONOR)
						{
							$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA] = $ary[2];
						}
						else 
						{
							trigger_error("invalid goods acq type:$ary[0]");
						}
					}
					else 
					{
						switch ($ary[0])
						{
							case RewardConfType::SILVER:
								$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] = $ary[2];
								break;
							case RewardConfType::GOLD:
								$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] = $ary[2];
								break;
							case RewardConfType::PRESTIGE:
								$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_PRESTIGE] = $ary[2];
								break;
							case RewardConfType::CROSS_HONOR:
								$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA] = $ary[2];
								break;
							case RewardConfType::JH:
								$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_JH] = $ary[2];
								break;
							default:
								trigger_error("invalid goods req type:$ary[0]");
						}
					}
				}
			}	
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	if ($conf['is_sold']==0)
	{
		continue;
	}
	unset($conf['is_sold']);
	$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $conf[MallDef::MALL_EXCHANGE_NUM];
	unset($conf[MallDef::MALL_EXCHANGE_NUM]);
	
	if (empty($exchangeReq) && empty($exchangeAcq))
	{
		trigger_error("goods:$data[0] both exchangeReq: $exchangeReq, exchangeAcq: $exchangeAcq is empty!\n");
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