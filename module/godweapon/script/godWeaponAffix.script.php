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
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GodWeapon.def.php";

$csvFile = "godarm_affix.csv";
$outFileName = "GOD_WEAPON_AFFIX";

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
    GodWeaponDef::GOD_WEAPON_WASH_ID => $index++,
    GodWeaponDef::GOD_WEAPON_WASH_ATTR => ($index+=4)-1,
    GodWeaponDef::GOD_WEAPON_WASH_WEIGHT => $index++,
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
            case GodWeaponDef::GOD_WEAPON_WASH_ATTR:
                $arrAim = array();
                $arrTmp = str2Array($line[$index]);
                foreach($arrTmp as $k => $v)
                {
                    $tmp = array2Int(str2Array($v, '|'));
                    $arrAim[$tmp[0]] = $tmp[1];
                }
                $conf[$key] = $arrAim;
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }
    }
    $arrConf[$conf[GodWeaponDef::GOD_WEAPON_WASH_ID]] = $conf;
}
fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */