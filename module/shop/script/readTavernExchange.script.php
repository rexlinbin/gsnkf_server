<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readTavernExchange.script.php 62674 2013-09-03 06:23:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/script/readTavernExchange.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-03 06:23:43 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62674 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$inFileName = 'tavern_exchange.csv';
$outFileName = 'SHOP_EXCHANGE';

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
		MallDef::MALL_EXCHANGE_EXTRA => ++$index,
		MallDef::MALL_EXCHANGE_ITEM => ++$index,
);

$exchangeReq = array(MallDef::MALL_EXCHANGE_EXTRA);
$exchangeAcq = array(MallDef::MALL_EXCHANGE_ITEM);

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
		$conf[$key] = intval($data[$index]);
		if ( is_numeric($conf[$key]) || empty($conf[$key]) )
		{
			$conf[$key] = intval($conf[$key]);
		}
	}
	
	if (empty($conf[MallDef::MALL_EXCHANGE_ITEM])) 
	{
		trigger_error("shop exchange:$data[0] hero frag is empty!\n");
	}

	//把获得物品信息整理一下
	if( !empty($conf[MallDef::MALL_EXCHANGE_ITEM]) )
	{
		$items = array($conf[MallDef::MALL_EXCHANGE_ITEM] => 1);
		$conf[MallDef::MALL_EXCHANGE_ITEM] = $items;
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