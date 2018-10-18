<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassGoods.script.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/script/PassGoods.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Pass.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'overcomeshop_items.csv';
$outFileName = 'PASS_GOODS';

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
		PassGoodsCsvTag::GOODS_ID => $incre++,
		PassGoodsCsvTag::GOODS_ITEM => $incre++,
		PassGoodsCsvTag::COST_TYPE => $incre++,
		PassGoodsCsvTag::COST_NUM => $incre++,
		PassGoodsCsvTag::LIMIT_TYPE => $incre++,
		PassGoodsCsvTag::LIMIT_NUM => $incre++,
		PassGoodsCsvTag::GOODS_WEIGHT => $incre++,
		PassGoodsCsvTag::IS_SOLD => $incre++,
		PassGoodsCsvTag::NEED_LEVEL => $incre++,
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
	
	$conf = array();
    foreach($tag as $k => $v)
    {
        switch($k) 
        {
            case PassGoodsCsvTag::GOODS_ITEM:
                $conf[$k] = array_map('intval', str2Array($data[$v], '|'));
                break;
            default:
                $conf[$k] = intval($data[$v]);
        }
    }
    
    if(0 == $conf[PassGoodsCsvTag::IS_SOLD])
    {
        continue;
    }
    
    $newConf = array();
    
    //type字段
    $newConf[MallDef::MALL_EXCHANGE_TYPE] = $conf[PassGoodsCsvTag::LIMIT_TYPE];
    
    //acq字段		目前神兵商店商品支持的类型包括以下几种，1,2,3,7,8,9,11,12,13,14,19，其他不支持，也没必要支持
    if(count($conf[PassGoodsCsvTag::GOODS_ITEM]) < 3)
    {
        trigger_error('goods_items should be a array that has 3 element.the conf is ' . serialize($conf[PassGoodsCsvTag::GOODS_ITEM]));
    }
    
    switch ($conf[PassGoodsCsvTag::GOODS_ITEM][0])
    {
    	case RewardConfType::SILVER:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SILVER] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::SOUL:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SOUL] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::GOLD:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_GOLD] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::ITEM_MULTI:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array($conf[PassGoodsCsvTag::GOODS_ITEM][1]=>$conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::SILVER_MUL_LEVEL:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA]['mul_silver'] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::SOUL_MUL_LEVEL:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA]['mul_soul'] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::EXP_MUL_LEVEL:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA]['mul_exp'] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::JEWEL:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_JEWEL] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::PRESTIGE:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_PRESTIGE] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::HERO_MULTI:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = array($conf[PassGoodsCsvTag::GOODS_ITEM][1]=>$conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::TREASURE_FRAG_MULTI:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_TREASFRAG] = array($conf[PassGoodsCsvTag::GOODS_ITEM][1]=>$conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	case RewardConfType::COIN:
    		$newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA]['coin'] = intval($conf[PassGoodsCsvTag::GOODS_ITEM][2]);
    		break;
    	default:
    		trigger_error('invalid goods acq type[%d]' . $conf[PassGoodsCsvTag::GOODS_ITEM][0]);
    		break;
    }
    
    //req字段
    switch($conf[PassGoodsCsvTag::COST_TYPE])
    {
        case PassDef::PASSSHOP_SPEND_TYPE_WEAPON_COIN:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['coin'] =  $conf[PassGoodsCsvTag::COST_NUM];
            break;
        case PassDef::PASSSHOP_SPEND_TYPE_GOLD:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] =  $conf[PassGoodsCsvTag::COST_NUM];
            break;
        case PassDef::PASSSHOP_SPEND_TYPE_SILVER:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] =  $conf[PassGoodsCsvTag::COST_NUM];
            break;
    }
    $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $conf[PassGoodsCsvTag::LIMIT_NUM];
    $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = $conf[PassGoodsCsvTag::NEED_LEVEL];
    if (empty($newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL])) 
    {
    	$newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = 1;
    }
    
    //weight字段
    $newConf[PassGoodsCsvTag::GOODS_WEIGHT] = $conf[PassGoodsCsvTag::GOODS_WEIGHT];
    $goodsId = $conf[PassGoodsCsvTag::GOODS_ID];

    $config[$goodsId] = $newConf;
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