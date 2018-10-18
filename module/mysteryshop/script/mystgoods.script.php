<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: mystgoods.script.php 114990 2014-06-17 08:37:39Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/script/mystgoods.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-17 08:37:39 +0000 (Tue, 17 Jun 2014) $
 * @version $Revision: 114990 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/MysteryShop.conf.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/MysteryShop.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Mall.def.php";

$csvFile = 'mystical_goods.csv';
$outFileName = 'MYSTERYGOODS';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}

$ZERO = 0;
$field_names = array(
        'goods_id' => $ZERO, //id
        'goods_array'=> ++$ZERO,//此商品表中的具体物品（如5个张辽或者10个名书表示5个张辽是一个商品）
        'cost_type' => ++$ZERO,//购买此物品花费的币种（如金币、银币、魂玉）
        'cost_num' =>  ++$ZERO,//购买此物品花费的数值
        MallDef::MALL_EXCHANGE_TYPE => ++$ZERO,//购买限制类型（1.次数每天重置 2.永久次数限制）
        MallDef::MALL_EXCHANGE_NUM => ++$ZERO,//初始购买次数
        MysteryShopConf::$MYSTERY_GOODS_BTSTORE_FIELD_WEIGHT => ++$ZERO,//刷新出此物品的权重
        'can_buy' => ++$ZERO,//是否可出售
        'need_level' => ++$ZERO,//主角等级
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
$conf = array();
while(TRUE)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf    =    array();
    foreach($field_names as $key => $v)
    {
        switch($key)
        {
                
            case 'goods_array':
                $conf[$key] = array_map('intval', str2Array($line[$v], '|'));
                break;
            default:
                $conf[$key] = intval($line[$v]);
        }
    }
    if($conf['can_buy'] == MysteryShopConf::$MYSTERY_GOODS_CANNOT_SELL)
    {
        continue;
    }
    $newConf = array();
    //acq字段
    if(count($conf['goods_array']) < 3)
    {
        trigger_error('goods_array should be a array that has 3 element.the conf is '.serialize($conf['goods_array']));
    }
    if($conf['goods_array'][0] == MysteryShopDef::MYSTERY_GOODS_TYPE_ITEM)
    {
        $newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = array($conf['goods_array'][1]=>$conf['goods_array'][2]); 
    }
    else if($conf['goods_array'][0] == MysteryShopDef::MYSTERY_GOODS_TYPE_HERO)
    {
        $newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = array($conf['goods_array'][1]=>$conf['goods_array'][2]);
    }
    else if($conf['goods_array'][0] == MysteryShopDef::MYSTERY_GOODS_TYPE_TREASFRAG)
    {
        $newConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_TREASFRAG] = array($conf['goods_array'][1]=>$conf['goods_array'][2]);
    }
    //type字段
    $newConf[MallDef::MALL_EXCHANGE_TYPE] = $conf[MallDef::MALL_EXCHANGE_TYPE];
    //req字段
    switch($conf['cost_type'])
    {
        case MysteryShopDef::MYSTERY_SPEND_TYPE_JEWEL:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_JEWEL] =  $conf['cost_num'];
            break;
        case MysteryShopDef::MYSTERY_SPEND_TYPE_GOLD:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] =  $conf['cost_num'];
            break;
        case MysteryShopDef::MYSTERY_SPEND_TYPE_SILVER:
            $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER] =  $conf['cost_num'];
            break;
    }
    $newConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $conf[MallDef::MALL_EXCHANGE_NUM];
    //weight字段
    $newConf[MysteryShopConf::$MYSTERY_GOODS_BTSTORE_FIELD_WEIGHT] = $conf[MysteryShopConf::$MYSTERY_GOODS_BTSTORE_FIELD_WEIGHT];
    $goodsId = $conf['goods_id'];
    $newConf['need_level'] = $conf['need_level'];
    if(empty($newConf['need_level']))
    {
        $newConf['need_level'] = 1;
    }
    $arrConf[$goodsId] = $newConf;
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */