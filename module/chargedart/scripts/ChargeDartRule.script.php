<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDartRule.script.php 241129 2016-05-05 08:15:49Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/scripts/ChargeDartRule.script.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-05 08:15:49 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241129 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/ChargeDart.def.php";

$inFileName = 'mnlm_rule.csv';
$outFileName = 'CHARGEDART_RULE';

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

$NUM = 0;

$arrConfKey = array (
    ChargeDartDef::CSV_ID => $NUM,
    ChargeDartDef::CSV_LEVEL => ++$NUM,
    ChargeDartDef::CSV_LAST_TIME => ++$NUM,
    ChargeDartDef::CSV_INTERVAL => ++$NUM,
    ChargeDartDef::CSV_DOUBLE_TIME => ++$NUM,
    ChargeDartDef::CSV_FREE_REFRESH => ++$NUM,
    ChargeDartDef::CSV_GOLD_REFRESH => ++$NUM,
    ChargeDartDef::CSV_COST_REFRESH => ++$NUM,
    ChargeDartDef::CSV_FIRST_REFRESH_PRO => ++$NUM,
    ChargeDartDef::CSV_REFRESH_UPGRADE_PRO => ++$NUM,
    ChargeDartDef::CSV_REFRESH_DARK_CHECK => ++$NUM,
    ChargeDartDef::CSV_CAN_ROB_NUM => ++$NUM,
    ChargeDartDef::CSV_FREE_SHIP_NUM => ++$NUM,
    ChargeDartDef::CSV_GOLD_SHIP_NUM => ++$NUM,
    ChargeDartDef::CSV_COST_SHIP_NUM => ++$NUM,
    ChargeDartDef::CSV_FREE_ROB_NUM => ++$NUM,
    ChargeDartDef::CSV_GOLD_ROB_NUM => ++$NUM,
    ChargeDartDef::CSV_COST_ROB_NUM => ++$NUM,
    ChargeDartDef::CSV_FREE_ASSIT_NUM => ++$NUM,
    ChargeDartDef::CSV_GOLD_ASSIT_NUM => ++$NUM,
    ChargeDartDef::CSV_COST_ASSIT_NUM => ++$NUM,
    ChargeDartDef::CSV_LEVEL_PROTECT => ++$NUM,
    ChargeDartDef::CSV_FAST_COST => ++$NUM,
    ChargeDartDef::CSV_RAGE_COST => ++$NUM,
    ChargeDartDef::CSV_RAGE_GROW => ++$NUM,
    ChargeDartDef::CSV_LOOK_COST => ++$NUM,
    ChargeDartDef::CSV_ALL_PAGE_NUM => ++$NUM,
    ChargeDartDef::CSV_ALL_ROAD_NUM => ++$NUM,
    ChargeDartDef::CSV_REFRESH_ITEM => ++$NUM,
    ChargeDartDef::CSV_SHIP_ITEM => ++$NUM,
);

//特殊处理的部分，一般有'|'和','，按照|前的=>|后面的
$special_array1 = array(
    ChargeDartDef::CSV_COST_REFRESH,
    ChargeDartDef::CSV_FIRST_REFRESH_PRO,
    ChargeDartDef::CSV_REFRESH_UPGRADE_PRO,
    ChargeDartDef::CSV_COST_SHIP_NUM,
    ChargeDartDef::CSV_COST_ROB_NUM,
    ChargeDartDef::CSV_COST_ASSIT_NUM,
    ChargeDartDef::CSV_LEVEL_PROTECT,
    ChargeDartDef::CSV_RAGE_GROW,
);

//特殊处理的部分，只有'|'
$special_array2 = array(
    ChargeDartDef::CSV_FAST_COST,
    ChargeDartDef::CSV_RAGE_COST,
    ChargeDartDef::CSV_REFRESH_ITEM,
    ChargeDartDef::CSV_SHIP_ITEM,
);

//特殊处理的部分，双倍时间，0=>array('时间1','时间2')，1=>....
$special_array3 = array(
    ChargeDartDef::CSV_DOUBLE_TIME,
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
    if ( empty($data) || empty($data[0]) )
    {
        break;
    }

    $conf = array();
    foreach ( $arrConfKey as $key => $index )
    {
        if ( in_array($key, $special_array1) )
        {
            $conf[$key] = array();
            $info = explode ( ',', $data [$index] );
            foreach ($info as $k => $v)
            {
                $info2 = explode ( '|', $v );
                $conf[$key][intval($info2[0])] = intval($info2[1]);
            }
        }
        elseif ( in_array($key, $special_array2) )
        {
            $conf[$key] = array();
            $info = explode ( '|', $data [$index] );
            foreach ($info as $k => $v)
            {
                $conf[$key][$k] = intval($v);
            }
        }
        elseif (in_array($key, $special_array3) )
        {
            $conf[$key] = array();
            $info = explode ( ',', $data [$index] );
            foreach ($info as $k => $v)
            {
                $conf[$key][$k] = array();
                $info2 = explode ( '|', $v );
                foreach ($info2 as $k2 => $v2)
                {
                    $conf[$key][$k][$k2] = intval($v2);
                }
            }
        }
        else
        {
            $conf[$key] = intval($data [$index]);
        }
    }
}

$confList = $conf;

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