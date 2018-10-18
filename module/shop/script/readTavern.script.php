<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readTavern.script.php 114800 2014-06-17 03:12:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/script/readTavern.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-17 03:12:46 +0000 (Tue, 17 Jun 2014) $
 * @version $Revision: 114800 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Shop.def.php";

$inFileName = 'tavern.csv';
$outFileName = 'SHOP';

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
$confKey = array (
		ShopDef::RECRUIT_TYPE_ID => $index,
		ShopDef::RECRUIT_CD_TIME => $index+=5,
		ShopDef::RECRUIT_COST_GOLD => ++$index,
		ShopDef::RECRUIT_COST_ITEM => ++$index,
		ShopDef::RECRUIT_POINT_BASE => $index+=2,
		ShopDef::RECRUIT_GOLD_DROP => $index+=2,
		ShopDef::RECRUIT_FREE_DROP => ++$index,
		ShopDef::RECRUIT_DEFAULT_GOLD => ++$index,
		ShopDef::RECRUIT_DEFAULT_FREE => ++$index,
		ShopDef::RECRUIT_SPECIAL_NUM => $index+=2,
		ShopDef::RECRUIT_SPECIAL_DROP => ++$index,
		ShopDef::RECRUIT_EXTRA_DROP => ++$index,
		ShopDef::RECRUIT_MULTI_COST	=> ++$index,
		ShopDef::RECRUIT_SPECIAL_SERIAL => ++$index,
		ShopDef::RECRUIT_ANOTHER_SERIAL => ++$index,
		ShopDef::RECRUIT_ANOTHER_DROP => ++$index,
);

$arrKeyV1 = array(
		ShopDef::RECRUIT_GOLD_DROP,
		ShopDef::RECRUIT_FREE_DROP,
		ShopDef::RECRUIT_DEFAULT_GOLD,
		ShopDef::RECRUIT_DEFAULT_FREE,
		ShopDef::RECRUIT_SPECIAL_NUM,
		ShopDef::RECRUIT_SPECIAL_DROP,
		ShopDef::RECRUIT_SPECIAL_SERIAL,
);

$arrKeyV2 = array(
		ShopDef::RECRUIT_COST_ITEM,	
		ShopDef::RECRUIT_MULTI_COST,
		ShopDef::RECRUIT_ANOTHER_SERIAL,
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
	foreach ($confKey as $key => $index)
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
						trigger_error( "shop:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					if ($key == ShopDef::RECRUIT_ANOTHER_SERIAL) 
					{
						$conf[$key][] = $ary;
					}
					else 
					{
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);		
		}
	}
	
	//处理下特殊次数的序列1
	if (!empty($conf[ShopDef::RECRUIT_SPECIAL_NUM]))
	{
		$sum = 0;
		foreach ($conf[ShopDef::RECRUIT_SPECIAL_NUM] as $key => $value)
		{
			$sum += $value;
			$conf[ShopDef::RECRUIT_SPECIAL_NUM][$key] = $sum;
		}
	}
	//处理下特殊次数的序列2
	if (!empty($conf[ShopDef::RECRUIT_SPECIAL_SERIAL])) 
	{
		$sum = 0;
		foreach ($conf[ShopDef::RECRUIT_SPECIAL_SERIAL] as $key => $value)
		{
			$sum += $value;
			$conf[ShopDef::RECRUIT_SPECIAL_SERIAL][$key] = $sum; 
		}
	}
	
	//配置检查
	if (!empty($conf[ShopDef::RECRUIT_SPECIAL_NUM])) 
	{
		if (count($conf[ShopDef::RECRUIT_SPECIAL_NUM]) < 2) 
		{
			trigger_error("shop:$data[0] invalid special num arr");
		}
		if (count($conf[ShopDef::RECRUIT_SPECIAL_DROP]) < 2) 
		{
			trigger_error("shop:$data[0] invalid special drop arr");
		}
	}
	
	if (!empty($conf[ShopDef::RECRUIT_SPECIAL_SERIAL])) 
	{
		if (count($conf[ShopDef::RECRUIT_SPECIAL_DROP]) < 2) 
		{
			trigger_error("shop:$data[0] invalid special drop arr");
		}
	}
	
	//配置检查
	if ($conf[ShopDef::RECRUIT_TYPE_ID] == ShopDef::RECRUIT_TYPE_SILVER
	|| $conf[ShopDef::RECRUIT_TYPE_ID] == ShopDef::RECRUIT_TYPE_GOLD) 
	{
		if (count($conf[ShopDef::RECRUIT_GOLD_DROP]) < 2) 
		{
			trigger_error("shop:$data[0] invalid gold drop arr");
		}
		if (count($conf[ShopDef::RECRUIT_FREE_DROP]) < 2)
		{
			trigger_error("shop:$data[0] invalid free drop arr");
		}
		if (count($conf[ShopDef::RECRUIT_DEFAULT_GOLD]) < 2)
		{
			trigger_error("shop:$data[0] invalid default gold drop arr");
		}
		if (count($conf[ShopDef::RECRUIT_DEFAULT_FREE]) < 2)
		{
			trigger_error("shop:$data[0] invalid default free drop arr");
		}
	}
	
	$confList[$conf[ShopDef::RECRUIT_TYPE_ID]] = $conf;
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