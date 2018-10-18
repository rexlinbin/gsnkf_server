<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: mysmerchant.script.php 99250 2014-04-11 08:16:55Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/script/mysmerchant.script.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-04-11 08:16:55 +0000 (Fri, 11 Apr 2014) $$
 * @version $$Revision: 99250 $$
 * @brief 
 *  
 **/
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";


$csvFile = 'copy_mysticalshop.csv';
$outFileName = 'MYSMERCHANT';

if( isset($argv[1]) && $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if( $argc < 3)
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}


$ZERO = 0;
$field_names = array(
        'id' => $ZERO, //id
        'disappear_cd_time' => ++$ZERO, //神秘商人消失CD时间
        'refresh_cd_time' => ++$ZERO,  //商品刷新时间
        'refresh_gold_base' => ++$ZERO, //刷新金币基础值
        'refresh_gold_inc' => ++$ZERO, //刷新金币地增值
        'refresh_item' => ++$ZERO, //刷新需要物品ID
        'refresh_goods_num' => ++$ZERO, //每一组刷新出的物品数量
        'team1' => ++$ZERO,  //第一组
        'team2' => ++$ZERO,  //第二组
        'team3' => ++$ZERO,
        'team4' => ++$ZERO,
        'team5' => ++$ZERO,
        'team6' => ++$ZERO,
        'team7' => ++$ZERO,
        'team8' => ++$ZERO,
        'team9' => ++$ZERO,
        'team10' => ++$ZERO
);

//读取 —— 副本神秘商店表
$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
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
    $conf = array();
    foreach($field_names as $key => $val)
    {
        switch($key)
        {
            case 'refresh_item':
                $conf[$key] = array_map('intval', str2Array($line[$val], '|'));
                break;
            case 'refresh_goods_num':
                $conf[$key] = array_map('intval', str2Array($line[$val], ','));
                break;
            case 'team1':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team2':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team3':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team4':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team5':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team6':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team7':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team8':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team9':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            case 'team10':
                $conf[$key] = array_fill_keys(array_map('intval', str2Array($line[$val], ',')), array('refresh_weight'=>1000));
                break;
            default:
                $conf[$key] = intval($line[$val]);
        }

    }
    $arrConf[] = $conf;
}
if(count($arrConf) > 1)
{
    trigger_error('conf line num is bigger than 1');
}
else if(count($arrConf) < 1)
{
    trigger_error('conf line num is smaller than 1');
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf[0]));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */