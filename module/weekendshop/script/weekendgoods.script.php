<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 *
 **************************************************************************/

/**
 * @file $HeadURL$
 * @author $Author$(zhengguohao@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief
 *
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/WeekendShop.def.php";

$csvFile = 'weekendshop_goods.csv';
$outFileName = 'WEEKENDGOODS';

if ( isset($argv[1]) && $argv[1] == '-h' )
{
    exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}

//数据对应表
$index = 0;
$field_names = array(
    WeekendGoodsCsvDef::ID => $index++,
    WeekendGoodsCsvDef::ITEMS => $index++,
    WeekendGoodsCsvDef::COST_TYPE => $index++,
    WeekendGoodsCsvDef::COST_NUM => $index++,
    MallDef::MALL_EXCHANGE_TYPE => $index++,
    MallDef::MALL_EXCHANGE_NUM => $index++,
    WeekendGoodsCsvDef::WEIGHT => $index++,
    WeekendGoodsCsvDef::ISSOLD => $index++,
    WeekendGoodsCsvDef::LEVEL_LIMIT => $index++,
    WeekendGoodsCsvDef::ISHOT => $index++,
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( FALSE == $file )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
$arrConfList = array();

while (true)
{
    $data = fgetcsv($file);
    if (empty($data) || empty($data[0]))
    {
        break;
    }

    $confList = array();

    foreach ($field_names as $key => $v)
    {
        switch ($key)
        {
            case WeekendGoodsCsvDef::ITEMS:
                $confList[$key] = array_map('intval', str2Array($data[$v], '|'));
                break;
            default:
                $confList[$key] = intval($data[$v]);
                break;
        }
    }

    if (WeekendGoodsCsvDef::NOSOLD == $confList[WeekendGoodsCsvDef::ISSOLD])
    {
        continue;
    }

    $newConfList = array();
    //acq
    if ($confList[WeekendGoodsCsvDef::ITEMS] < 3)
    {
        trigger_error('items should be a array that has 3 element.the conf is '.serialize($confList['goods_array']));
    }

    if (WeekendGoodsCsvDef::ACQ_TYPE_ITEM == $confList[WeekendGoodsCsvDef::ITEMS][0])
    {
        $newConfList[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array(
            $confList[WeekendGoodsCsvDef::ITEMS][1] => $confList[WeekendGoodsCsvDef::ITEMS][2]
        );
    }
    elseif (WeekendGoodsCsvDef::ACQ_TYPE_HERO == $confList[WeekendGoodsCsvDef::ITEMS][0])
    {
        $newConfList[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = array(
            $confList[WeekendGoodsCsvDef::ITEMS][1] => $confList[WeekendGoodsCsvDef::ITEMS][2]
        );
    }

    //req
    switch ($confList[WeekendGoodsCsvDef::COST_TYPE])
    {
        case WeekendGoodsCsvDef::REQ_TYPE_JEWEL:
            $newConfList[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_JEWEL] = $confList[WeekendGoodsCsvDef::COST_NUM];
            break;
        case WeekendGoodsCsvDef::REQ_TYPE_GOLD:
            $newConfList[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] = $confList[WeekendGoodsCsvDef::COST_NUM];
            break;
        case WeekendGoodsCsvDef::REQ_TYPE_SILVER:
            $newConfList[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] = $confList[WeekendGoodsCsvDef::COST_NUM];
            break;
        default:
            break;
    }
    //限购次数
    $newConfList[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $confList[MallDef::MALL_EXCHANGE_NUM];
    //type
    $newConfList[MallDef::MALL_EXCHANGE_TYPE] = $confList[MallDef::MALL_EXCHANGE_TYPE];
    //refresh_weight
    $newConfList[WeekendGoodsCsvDef::WEIGHT] = $confList[WeekendGoodsCsvDef::WEIGHT];
    //level
    $newConfList[WeekendGoodsCsvDef::LEVEL_LIMIT] = $confList[WeekendGoodsCsvDef::LEVEL_LIMIT];
    if (empty($newConfList[WeekendGoodsCsvDef::LEVEL_LIMIT]))
    {
        $newConfList[WeekendGoodsCsvDef::LEVEL_LIMIT] = 0;
    }
    //is_hot
    $newConfList[WeekendGoodsCsvDef::ISHOT] = $confList[WeekendGoodsCsvDef::ISHOT];

    $arrConfList[$confList[WeekendGoodsCsvDef::ID]] = $newConfList;
}

fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConfList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */