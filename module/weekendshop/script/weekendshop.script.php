<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/WeekendShop.def.php";

$csvFile = 'weekendshop.csv';
$outFileName = 'WEEKENDSHOP';

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
    WeekendShopCsvDef::ID => $ZERO++,
    WeekendShopCsvDef::CIRCLE_ID => $ZERO++,
    WeekendShopCsvDef::GOLD_BASE => $ZERO++,
    WeekendShopCsvDef::GOLD_GROW => $ZERO++,
    WeekendShopCsvDef::GOLD_MAX => $ZERO++,
    WeekendShopCsvDef::LAST_TIME => $ZERO++,
    WeekendShopCsvDef::SHOW_ITEMS => $ZERO++,
    WeekendShopCsvDef::COST_ITEM => $ZERO++,
    WeekendShopCsvDef::TEAM_NUMS => $ZERO++,
    WeekendShopCsvDef::TEAM1 => $ZERO++,
    WeekendShopCsvDef::TEAM2 => $ZERO++,
    WeekendShopCsvDef::TEAM3 => $ZERO++,
    WeekendShopCsvDef::TEAM4 => $ZERO++,
    WeekendShopCsvDef::TEAM5 => $ZERO++,

    WeekendShopCsvDef::TEAM6 => $ZERO++,
    WeekendShopCsvDef::TEAM7 => $ZERO++,
    WeekendShopCsvDef::TEAM8 => $ZERO++,
    WeekendShopCsvDef::TEAM9 => $ZERO++,
    WeekendShopCsvDef::TEAM10 => $ZERO++,
);
//读取 周末商店表
$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
while (true)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach ($field_names as $key => $val)
    {
        switch ($key)
        {
            case WeekendShopCsvDef::SHOW_ITEMS:
                break;
            case WeekendShopCsvDef::CIRCLE_ID:
            case WeekendShopCsvDef::TEAM_NUMS:
            case WeekendShopCsvDef::TEAM1:
            case WeekendShopCsvDef::TEAM2:
            case WeekendShopCsvDef::TEAM3:
            case WeekendShopCsvDef::TEAM4:
            case WeekendShopCsvDef::TEAM5:
            case WeekendShopCsvDef::TEAM6:
            case WeekendShopCsvDef::TEAM7:
            case WeekendShopCsvDef::TEAM8:
            case WeekendShopCsvDef::TEAM9:
            case WeekendShopCsvDef::TEAM10:
                $conf[$key] = array2Int(str2Array($line[$val]));
                break;
            case WeekendShopCsvDef::LAST_TIME:
                $tmp = str2Array($line[$val]);
                foreach ($tmp as $k => $v)
                {
                    $conf[$key][$k] = str2Array($v, '|');
                    $conf[$key][$k][0] = intval($conf[$key][$k][0]);
                    $hms = $conf[$key][$k][1];
                    $s = intval(substr($hms, -2));
                    $m = intval(substr($hms, -4, 2));
                    $h = intval(substr($hms, 0, 2));
                    $conf[$key][$k][1] = $s + $m * 60 + $h * 3600;
                }

                break;
            case WeekendShopCsvDef::COST_ITEM:
                $tmp = str2Array($line[$val]);
                foreach ($tmp as $k => $v)
                {
                    $conf[$key][$k] = array2Int(str2Array($v, '|'));
                }
                break;
            default:
                $conf[$key] = intval($line[$val]);
                break;
        }
    }
    $arrConf[$conf[WeekendShopCsvDef::ID]] = $conf;
}
var_dump($arrConf);
fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */