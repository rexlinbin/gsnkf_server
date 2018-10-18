<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: hell_tower_goods.script.php 254657 2016-08-04 02:14:29Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/script/hell_tower_goods.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-08-04 02:14:29 +0000 (Thu, 04 Aug 2016) $
 * @version $Revision: 254657 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Util.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Tower.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$csvFile = 'nightmare_shop.csv';
$outFileName = 'HELL_TOWER_GOODS';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!TOWER.csv output\n";
    trigger_error ("input error parameters.");
}

$ZERO = 0;
$confIndex = array(
    HellTowerGoodsDef::ID => $ZERO,
    HellTowerGoodsDef::ITEMS => ++$ZERO,
    HellTowerGoodsDef::PRICE => ++$ZERO,
    HellTowerGoodsDef::TYPE => ( $ZERO += 2 ),
    HellTowerGoodsDef::NUM => ++$ZERO,
    HellTowerGoodsDef::LEVEL => ( $ZERO += 2 ),
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();

while ( TRUE )
{
    $data = fgetcsv($file);
    
    if ( empty( $data ) || empty( $data[0] ) )
    {
        break;
    }
    
    if ( empty( $data[6] ) )
    {
        continue;
    }
    
    $conf = array();
    foreach ( $confIndex as $key => $index )
    {
        switch ( $key )
        {
            case HellTowerGoodsDef::ITEMS:
                $conf[$key] = array();
                $arrTmp = Util::str2Array($data[$index], ',');
                foreach ( $arrTmp as $value )
                {
                    $conf[$key][] = array_map('intval', Util::str2Array($value, '|'));
                }
                break;
            default:
                $conf[$key] = intval( $data[$index] );
                break;
        }
    }
    
    $goods = array();
    foreach ( $conf as $key => $value )
    {
        switch ( $key )
        {
            case HellTowerGoodsDef::ITEMS:
                if ( !isset( $goods[MallDef::MALL_EXCHANGE_ACQ] ) )
                {
                    $goods[MallDef::MALL_EXCHANGE_ACQ] = array();
                }
                foreach ( $value as $acq )
                {
                    switch ( $acq[0] )
                    {
                        case RewardConfType::ITEM_MULTI:
                            if ( !isset( $goods[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] ) )
                            {
                                $goods[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array();
                            }
                            if ( !isset( $goods[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][$acq[1]] ) )
                            {
                                $goods[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][$acq[1]] = 0;
                            }
                            $goods[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][$acq[1]] += $acq[2];
                            break;
                        default:
                            break;
                    }
                }
                break;
            case HellTowerGoodsDef::PRICE:
                $goods[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['tower'] = $value;
                break;
            case HellTowerGoodsDef::TYPE:
                $goods[MallDef::MALL_EXCHANGE_TYPE] = $value;
                break;
            case HellTowerGoodsDef::NUM:
                $goods[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $value;
                break;
            case HellTowerGoodsDef::LEVEL:
                $goods[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL] = $value;
                break;
            default:
                break;
        }
    }
    
    $confList[$conf[HellTowerGoodsDef::ID]] = $goods;
}

fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */