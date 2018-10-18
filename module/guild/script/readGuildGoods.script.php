<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readGuildGoods.script.php 236894 2016-04-07 06:26:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/script/readGuildGoods.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-04-07 06:26:30 +0000 (Thu, 07 Apr 2016) $
 * @version $Revision: 236894 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Guild.cfg.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$inFileName = 'legion_goods.csv';
$outFileName = 'GUILD_GOODS';

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
		'goods' => ++$index,
		MallDef::MALL_EXCHANGE_EXTRA => ++$index,
		GuildDef::GUILD_GOODS_TYPE => $index+=2,
		MallDef::MALL_EXCHANGE_TYPE => ++$index,
		GuildDef::GUILD_STORE_LEVEL => ++$index,
		GuildDef::GUILD_GOODS_LIMIT => ++$index,
		MallDef::MALL_EXCHANGE_NUM => ++$index,
		'sell' => ++$index,
		GuildDef::GUILD_GOODS_WEIGHT => ++$index,
		MallDef::MALL_EXCHANGE_LEVEL => ++$index,
		MallDef::MALL_EXCHANGE_GOLD => ++$index,
);

$arrKeyV2 = array('goods');

$exchangeReq = array(
		MallDef::MALL_EXCHANGE_EXTRA,
		MallDef::MALL_EXCHANGE_NUM,
		MallDef::MALL_EXCHANGE_LEVEL,
		MallDef::MALL_EXCHANGE_GOLD,
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
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if ($key == MallDef::MALL_EXCHANGE_GOLD) 
		{
			if (!empty($data[$index])) 
			{
				$ary = array2Int(str2Array($data[$index], '|'));
				if ($ary[0] != 3) 
				{
					trigger_error( "guild goods:$data[0] cost is not gold type 3\n" );
				}
				$conf[$key] = $ary[2];
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
						trigger_error( "guild goods:$data[0] invalid key:$key, value:$value need v2\n" );
					}
					$conf[$key]= array2Int(str2Array($value, '|'));
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
	
	//珍品类商品需要中午12点重置购买次数
	if ($conf[GuildDef::GUILD_GOODS_TYPE] == 1) 
	{
		$conf[MallDef::MALL_EXCHANGE_OFFSET] = GuildConf::SPECIAL_GOODS_OFFSET;
	}
	
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
			$conf[MallDef::MALL_EXCHANGE_ACQ][$attr] = $conf[$attr];
		}
		unset($conf[$attr]);
	}
	
	$req = $conf[MallDef::MALL_EXCHANGE_REQ];
	$acq = $conf[MallDef::MALL_EXCHANGE_ACQ];
	if (empty($req) && empty($acq))
	{
		trigger_error("guild goods:$data[0] both exchangeReq: $req, exchangeAcq: $acq is empty!\n");
	}
	
	if ($conf[GuildDef::GUILD_GOODS_LIMIT] < $conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM]) 
	{
		trigger_error("guild goods:$data[0] goods limit is smaller than exchange num!\n");
	}

	$confList[$data[0]] = $conf;
}
fclose($file);

$specialGoods = array();
foreach ($confList as $goodsId => $conf)
{
	if (GuildDef::SPECIAL == $conf[GuildDef::GUILD_GOODS_TYPE]
	&& $conf[GuildDef::GUILD_STORE_LEVEL] == 0) 
	{
		$specialGoods[] = $goodsId;
	}
}

if (count($specialGoods) < 2) 
{
	trigger_error("guild with store level 0 can buy special goods num is less than 2!\n");
}

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