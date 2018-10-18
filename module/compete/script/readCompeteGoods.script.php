<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readCompeteGoods.script.php 190157 2015-08-11 07:51:54Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/script/readCompeteGoods.script.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2015-08-11 07:51:54 +0000 (Tue, 11 Aug 2015) $
 * @version $Revision: 190157 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$inFileName = 'contest_shop.csv';
$outFileName = 'COMPETE_GOODS';

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

$arrConfKey = array (
		'goods' => ++$index,
		MallDef::MALL_EXCHANGE_EXTRA => ++$index,
		MallDef::MALL_EXCHANGE_TYPE => $index+=2,
		MallDef::MALL_EXCHANGE_NUM => ++$index,
		'sell' => ++$index,
		MallDef::MALL_EXCHANGE_LEVEL => ++$index,
		MallDef::MALL_EXCHANGE_LEVEL_NUM => ++$index,
);

$arrKeyV2 = array('goods', MallDef::MALL_EXCHANGE_LEVEL_NUM);  //有数组形式的单独解析方式

$exchangeReq = array(
		MallDef::MALL_EXCHANGE_EXTRA,
		MallDef::MALL_EXCHANGE_NUM,
		MallDef::MALL_EXCHANGE_LEVEL,
		MallDef::MALL_EXCHANGE_LEVEL_NUM,
);

$exchangeAcq = array(
		MallDef::MALL_EXCHANGE_ITEM,
		MallDef::MALL_EXCHANGE_HERO,
		MallDef::MALL_EXCHANGE_TREASFRAG,
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
		//有数组方式的解析
		if ( in_array($key, $arrKeyV2, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arr = str2Array($data[$index], ',');
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "contest_goods:$data[0] invalid key:$key, value:$value need v2\n" );
					}
					if ( 'goods' == $key )
					{
						//物品id组那一列的解析
						$conf[$key]= array2Int(str2Array($value, '|'));
					}
					else
					{
						//限制方式为玩家等级时，等级兑换次数那一列的解析
						$arrLevelNum = array2Int(str2Array($value, '|'));
						$conf[$key][$arrLevelNum[0]] = $arrLevelNum[1];
					}
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	//不出售的商品直接跳过
	if ($conf['sell'] == 0)
	{
		continue;
	}
	unset($conf['sell']);

	//把获得物品信息整理一下
	if( !empty($conf['goods']) )
	{
		if ($conf['goods'][0] == 1)
		{
			$items = array($conf['goods'][1] => $conf['goods'][2]);
			$conf[MallDef::MALL_EXCHANGE_ITEM] = $items;
		}
		if ($conf['goods'][0] == 2)
		{
			$heros = array($conf['goods'][1] => $conf['goods'][2]);
			$conf[MallDef::MALL_EXCHANGE_HERO] = $heros;
		}
		if ($conf['goods'][0] == 3)
		{
			$treasFrags = array($conf['goods'][1] => $conf['goods'][2]);
			$conf[MallDef::MALL_EXCHANGE_TREASFRAG] = $treasFrags;
		}
	}
	unset($conf['goods']);

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
			if (MallDef::MALL_EXCHANGE_LEVEL_NUM == $attr)
			{
				ksort($conf[$attr]);
			}
			$conf[MallDef::MALL_EXCHANGE_ACQ][$attr] = $conf[$attr];
		}
		unset($conf[$attr]);
	}

	$req = $conf[MallDef::MALL_EXCHANGE_REQ];
	$acq = $conf[MallDef::MALL_EXCHANGE_ACQ];
	if (empty($req) && empty($acq))
	{
		trigger_error("contest goods:$data[0] both exchangeReq: $req, exchangeAcq: $acq is empty!\n");
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