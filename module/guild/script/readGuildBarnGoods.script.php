<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildBarnGoods.script.php 143913 2014-12-03 06:54:50Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildBarnGoods.script.php $
 * @author $Author: wuqilin $(tianming@babeltime.com)
 * @date $Date: 2014-12-03 06:54:50 +0000 (Wed, 03 Dec 2014) $
 * @version $Revision: 143913 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";


$inFileName = 'grain_shop.csv';
$outFileName = 'GUILD_BARN_GOODS';

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
		GuildDef::GUILD_BARN_LEVEL => ++$index,
		MallDef::MALL_EXCHANGE_ACQ => ++$index,
		GuildDef::GUILD_BARN_SHOP_GRAIN => ($index+=2),
		GuildDef::GUILD_BARN_SHOP_MERIT => ++$index,
		MallDef::MALL_EXCHANGE_TYPE => ++$index,
		MallDef::MALL_EXCHANGE_NUM => ++$index,
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
	foreach ( $arrConfKey as $key => $index )
	{
		if( $key == MallDef::MALL_EXCHANGE_ACQ )
		{
			$conf[$key] = array2Int(str2Array($data[$index], '|'));
			if( count($conf[$key]) != 3 )
			{
				trigger_error(sprintf("id:%d invalid acq:%s\n", $data[0], $data[$index]));
			}
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	//兑换需要的东西
	$conf[MallDef::MALL_EXCHANGE_REQ] = array(
			MallDef::MALL_EXCHANGE_NUM => $conf[MallDef::MALL_EXCHANGE_NUM],
			MallDef::MALL_EXCHANGE_EXTRA => array(),
	);
	if( empty( $conf[GuildDef::GUILD_BARN_SHOP_GRAIN] ) 
		&& empty($conf[GuildDef::GUILD_BARN_SHOP_MERIT])  )
	{
		trigger_error(sprintf("id:%d no req\n", $data[0]));
	}
	if ( !empty($conf[GuildDef::GUILD_BARN_SHOP_GRAIN] ) )
	{
		$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][GuildDef::GUILD_BARN_SHOP_GRAIN] = $conf[GuildDef::GUILD_BARN_SHOP_GRAIN];
	}
	if ( !empty($conf[GuildDef::GUILD_BARN_SHOP_MERIT] ) )
	{
		$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][GuildDef::GUILD_BARN_SHOP_MERIT] = $conf[GuildDef::GUILD_BARN_SHOP_MERIT];
	}
	
	unset($conf[MallDef::MALL_EXCHANGE_NUM]);
	unset($conf[GuildDef::GUILD_BARN_SHOP_GRAIN]);
	unset($conf[GuildDef::GUILD_BARN_SHOP_MERIT]);
	
	//兑换获得的东西
	if ( empty($conf['acq']) )
	{
		trigger_error(sprintf("id:%d no acq\n", $data[0]));
	}
	
	$acq = $conf[MallDef::MALL_EXCHANGE_ACQ];
	$conf[MallDef::MALL_EXCHANGE_ACQ] = array();
	switch ( $acq[0] )
	{
		case RewardConfType::ITEM_MULTI:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array($acq[1] => $acq[2]);
			break;
		case RewardConfType::GRAIN:
			$conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA][GuildDef::GUILD_BARN_SHOP_GRAIN] = $acq[2];
			break;
		default:
			trigger_error(sprintf("not support reward type:%d, id:%d \n", $acq[0], $data[0]));
			break;
	}

	
	$req = $conf[MallDef::MALL_EXCHANGE_REQ];
	$acq = $conf[MallDef::MALL_EXCHANGE_ACQ];
	if (empty($req) && empty($acq))
	{
		trigger_error("guild goods:$data[0] both exchangeReq: $req, exchangeAcq: $acq is empty!\n");
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