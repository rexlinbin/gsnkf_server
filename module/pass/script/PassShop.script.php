<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassShop.script.php 258594 2016-08-26 08:38:43Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/script/PassShop.script.php $
 * @author $Author: MingmingZhu $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-26 08:38:43 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258594 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Pass.def.php";

$csvFile = 'overcomeshop.csv';
$outFileName = 'PASS_SHOP';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$incre = 0;
$tag = array
(
		PassShopCsvTag::ID => $incre++,
		PassShopCsvTag::SYS_REFRESH_INTERVAL => $incre++,
		PassShopCsvTag::USR_REFRESH_COST => $incre++,
		PassShopCsvTag::GOODS_NUM => $incre++,
		PassShopCsvTag::GOODS_ARRAY => $incre++,
		PassShopCsvTag::REFRESH_LIMIT => ($incre+=10)-1,
		PassShopCsvTag::FREE_REFRESH => $incre++,
		PassShopCsvTag::USR_REFRESH_STONE => $incre,
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
	$data = fgetcsv($file);
	if (empty($data))
		break;
	
	$config[PassShopCsvTag::ID] = intval($data[$tag[PassShopCsvTag::ID]]);
	
	$sysRefreshArr = explode(',', $data[$tag[PassShopCsvTag::SYS_REFRESH_INTERVAL]]);
	foreach ($sysRefreshArr as $aTime)
	{
		$config[PassShopCsvTag::SYS_REFRESH_INTERVAL][] = strtotime(date('Ymd') . $aTime) - strtotime(date('Ymd') . '000000'); 
	}
	sort($config[PassShopCsvTag::SYS_REFRESH_INTERVAL]);
	
	$arrCost = explode(',', $data[$tag[PassShopCsvTag::USR_REFRESH_COST]]);
	foreach ($arrCost as $aCost)
	{
		$detail = explode('|', $aCost);
		$config[PassShopCsvTag::USR_REFRESH_COST][intval($detail[0])] = intval($detail[1]);
	}
	$config[PassShopCsvTag::REFRESH_LIMIT] = intval($data[$tag[PassShopCsvTag::REFRESH_LIMIT]]);
	$config[PassShopCsvTag::GOODS_NUM] = array_map('intval', explode(',', $data[$tag[PassShopCsvTag::GOODS_NUM]]));
	
	for ($i = $tag[PassShopCsvTag::GOODS_ARRAY]; $i < PassDef::PASS_SHOP_GOODS_ARRAY_NUM + $tag[PassShopCsvTag::GOODS_ARRAY]; ++$i)
	{
		if (isset($data[$i]) && !empty($data[$i])) 
		{
			$config[PassShopCsvTag::GOODS_ARRAY][] = array_map('intval', explode(',', $data[$i]));
		}
		else 
		{
			$config[PassShopCsvTag::GOODS_ARRAY][] = array();
		}
	}
	
	if (count($config[PassShopCsvTag::GOODS_NUM]) > count($config[PassShopCsvTag::GOODS_ARRAY])) 
	{
		trigger_error('err PASS_SHOP, count of goods num bigger than count goods array');
	}
	
	$config[PassShopCsvTag::FREE_REFRESH] = intval($data[$tag[PassShopCsvTag::FREE_REFRESH]]);
	
	// 读取“神兵刷新石”
	$config[PassShopCsvTag::USR_REFRESH_STONE] = array();
	$arrStone = explode('|', $data[$tag[PassShopCsvTag::USR_REFRESH_STONE]]);
	$config[PassShopCsvTag::USR_REFRESH_STONE]['templ_id'] = intval($arrStone[0]);
	$config[PassShopCsvTag::USR_REFRESH_STONE]['cost_num'] = intval($arrStone[1]);
	break;
}
fclose($file);
print_r($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */