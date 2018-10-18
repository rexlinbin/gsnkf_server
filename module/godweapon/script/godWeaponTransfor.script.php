<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: godWeaponTransfor.script.php 149260 2014-12-26 07:26:58Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/godweapon/script/godWeaponTransfor.script.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-12-26 07:26:58 +0000 (Fri, 26 Dec 2014) $$
 * @version $$Revision: 149260 $$
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GodWeapon.def.php";

$csvFile = "godarm_transfer.csv";
$outFileName = "GOD_WEAPON_TRANSFER";

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}

$index = 0;
$field_names = array(
    GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ID => $index++,
    GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_RESOLVE_GOD_LEVEL => $index++,
    GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_ACTOR_LV => $index++,
    GodWeaponDef::GOD_WEAPON_TRANSFER_COST_SILVER => $index++,
    GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY => $index++,
    GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID => $index++,
);
//读取神兵进化表
$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
while(true)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($field_names as $key => $index)
    {
        switch($key)
        {
            case GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY:
                $arrTmp = str2Array($line[$index]);
                if(empty($arrTmp))
                {
                    $conf[$key] = array();
                }
                else
                {
                    foreach($arrTmp as $k => $v)
                    {
                        $arrTmp = array2Int(str2Array($v, '|'));
                        $conf[$key][$k] = $arrTmp;
                    }
                }
                break;
            case GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID:
                $arrYes = array();
                $arrTmp = str2Array($line[$index]);
                foreach($arrTmp as $k => $v)
                {
                    $tmp = array2Int(str2Array($v, '|'));
                    $arrYes[$tmp[0]] = $tmp[1];
                }
                $conf[$key] = $arrYes;
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }
    }
    $arrConf[$conf[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ID]] = $conf;
}
fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */