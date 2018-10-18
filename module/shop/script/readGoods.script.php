<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGoods.script.php 75145 2013-11-15 11:18:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/script/readGoods.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-15 11:18:14 +0000 (Fri, 15 Nov 2013) $
 * @version $Revision: 75145 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$inFileName = 'goods.csv';		//普通商品表, 策划说暂时没有限购数量
$outFileName = 'GOODS';

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
$index = 1;
$arrConfKey = array (
		MallDef::MALL_EXCHANGE_GOLD => $index += 5,
		MallDef::MALL_EXCHANGE_VIP => ++$index,
		MallDef::MALL_EXCHANGE_LEVEL => ++$index,
		MallDef::MALL_EXCHANGE_DISCOUNT => ++$index,
		MallDef::MALL_EXCHANGE_NUM => ++$index,
		MallDef::MALL_EXCHANGE_ITEM => ++$index,
		MallDef::MALL_EXCHANGE_HERO => ++$index,
		MallDef::MALL_EXCHANGE_DROP => ++$index,
		MallDef::MALL_EXCHANGE_SILVER => ++$index,
		MallDef::MALL_EXCHANGE_SOUL => ++$index,
		MallDef::MALL_EXCHANGE_INCRE => ++$index
);

$arrKeyV2 = array(
		MallDef::MALL_EXCHANGE_DISCOUNT, 
		MallDef::MALL_EXCHANGE_INCRE
);

$exchangeReq = array(
		MallDef::MALL_EXCHANGE_GOLD,
		MallDef::MALL_EXCHANGE_VIP,
		MallDef::MALL_EXCHANGE_LEVEL,
		MallDef::MALL_EXCHANGE_DISCOUNT,
		MallDef::MALL_EXCHANGE_NUM,
		MallDef::MALL_EXCHANGE_INCRE
);

$exchangeAcq = array(
		MallDef::MALL_EXCHANGE_ITEM,
		MallDef::MALL_EXCHANGE_HERO,
		MallDef::MALL_EXCHANGE_DROP,
		MallDef::MALL_EXCHANGE_SILVER,
		MallDef::MALL_EXCHANGE_SOUL
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
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "goods:$data[0] invalid key:$key, value:$value need v2\n" );
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
	
	//把获得物品信息整理一下
	if( !empty($conf[MallDef::MALL_EXCHANGE_ITEM]) )
	{
		$items = array($conf[MallDef::MALL_EXCHANGE_ITEM] => 1);
		$conf[MallDef::MALL_EXCHANGE_ITEM] = $items;
	}
	
	//把获得武将信息整理一下
	if( !empty($conf[MallDef::MALL_EXCHANGE_HERO]) )
	{
		$heros = array($conf[MallDef::MALL_EXCHANGE_HERO] => 1);
		$conf[MallDef::MALL_EXCHANGE_HERO] = $heros;
	}
	
	$conf[MallDef::MALL_EXCHANGE_REQ] = array();
	foreach ( $exchangeReq as $attr )
	{
		if ( !empty($conf[$attr]) )
		{
			$conf[MallDef::MALL_EXCHANGE_REQ][$attr] = $conf[$attr];
		}
		unset($conf[$attr]);
	}
	
	$conf[MallDef::MALL_EXCHANGE_ACQ] = array();
	foreach ( $exchangeAcq as $attr )
	{
		if ( !empty($conf[$attr]) )
		{
			$conf[MallDef::MALL_EXCHANGE_ACQ][$attr] = $conf[$attr];
		}
		unset($conf[$attr]);
	}
	
	if (empty($exchangeReq) && empty($exchangeAcq))
	{
		trigger_error("goods:$data[0] both exchangeReq: $exchangeReq, exchangeAcq: $exchangeAcq is empty!\n");
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