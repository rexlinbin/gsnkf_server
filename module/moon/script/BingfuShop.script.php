<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonShop.script.php 188200 2015-07-31 12:55:38Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/moon/script/MoonShop.script.php $
 * @author $Author: pengnana $(pengnana@babeltime.com)
 * @date $Date: 2015-12-30 14:25:00$
 * @version $Revision: 188200 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";

$csvFile = 'bingfu_shop_items.csv';
$outFileName = 'BINGFU_SHOP';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$incre = 0;
$field_names = array(
		'id' => 0,
		'item' => 1,
		'cost' => 2,
		'limitType' => 3,
		'baseNum' => 4,
		'weight' => 5,
		'isSold' => 6,
		'needLevel' => 7,
);

$config = array();
$file = fopen($argv[1] . "/$csvFile", 'r');
if (FALSE == $file)
{
	echo $argv[1] . "/{$csvFile} open failed! exit!\n";
	exit;
}

fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$line = fgetcsv($file);
	$mid = array();
	
	if (empty($line))
	{
		break;
	}	
	$id = intval($line[0]);
	foreach($field_names as $key => $v)
	{
		$itemArr =array();
		//无配置则设置为0
		if(empty($line[$v]))
		{
			$config[$id][$key] = 0;
			continue;
		}
		if($key == 'isSold' && $line[$v] == 0)//配置为0则不可出售商品,跳过此条配置
		{
			continue;
		}
		switch($key)
		{
			case 'cost':
				$itemArr = explode(',',$line[$v]);
				foreach($itemArr as $item)
				{
					$mid = array_map('intval', explode('|',$item));
					switch ($mid[0])
					{
						case RewardConfType::GOLD:
							$config[$id][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] = intval($mid[2]);
							break;
						case RewardConfType::SILVER:
							$config[$id][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] = intval($mid[2]);
							break;
						case RewardConfType::TALLY_POINT:
							$config[$id][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][RewardConfType::TALLY_POINT] = intval($mid[2]);
							break;
						default:								
					}
				}
				break;
			case 'item':
				$itemArr = array_map('intval', explode('|',$line[$v]));
				switch ($itemArr[0])
				{
					case RewardConfType::ITEM_MULTI:
						$config[$id][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][intval($itemArr[1])]=intval($itemArr[2]);
						break;
					default:
				}	
				break;
			case 'limitType':
				$config[$id][MallDef::MALL_EXCHANGE_TYPE] = intval($line[$v]);
				break;
			case 'baseNum':
				$config[$id][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = intval($line[$v]);
				break;
			case 'needLevel':
				$config[$id][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = intval($line[$v]);
				break;
			default:
				$config[$id][$key] = intval($line[$v]);
		}
	}
/**
 * config{
 * 			'id' => int,
 * 			'type'=>int  商品刷新类型
 * 			'req'{
 * 					'silver'=>int
 * 					'gold'=>int
 * 					'extra'=>'tally_point'=>int
 * 					'num' =>int
 * 					'level'=>int
 * 				}
 * 			'acq'{
 * 					'item'=>tpl_id=>num
 * 				}
 * 			'weight'=>int
 * 		}		
 * */
}
fclose($file);
var_dump($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */