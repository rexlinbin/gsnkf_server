<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Switch.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Fragseize.cfg.php";
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: switch.script.php 75439 2013-11-18 12:00:18Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/script/switch.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-18 12:00:18 +0000 (Mon, 18 Nov 2013) $
 * @version $Revision: 75439 $
 * @brief 
 *  
 **/

$csvFile = 'switch.csv';
$outFileName1 = 'SWITCH';
$outFileName2 = 'SWITCHBASE';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName1 $outFileName2\n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!COPY.csv output\n";
    trigger_error ("input error parameters.");
}

$ZERO = 0;
/**
 id
 开启等级
 开启需击败据点ID
 开启功能
 功能对应数字
 */
$field_names = array(
        BtTblSwitchField::ID    => $ZERO,
        BtTblSwitchField::OPENLV=>++$ZERO,
        BtTblSwitchField::OPEN_NEED_BASE=>++$ZERO,
        BtTblSwitchField::OPEN_SWITCH=>++$ZERO,
        BtTblSwitchField::SWITCH_INDEX=>++$ZERO,
);

$file = $argv[1] . "/$csvFile";
$handle = fopen($file, "r");

$line = fgetcsv($handle);
$line = fgetcsv($handle);

$switches = array();
$switchBases    =    array();
while (TRUE)
{
    $line = fgetcsv($handle);
    // 	var_dump($line);
    if(empty($line))
    {
        break;
    }
    $switch = array();
    foreach($field_names as $key => $v)
    {
        switch($key)
        {
            case BtTblSwitchField::OPEN_SWITCH:
                break;
            default:
                $switch[$key] = intval($line[$v]);
        }
    }
    if(!empty($switch[BtTblSwitchField::OPEN_NEED_BASE]))
    {
        $switchBases[]    =    $switch[BtTblSwitchField::OPEN_NEED_BASE];
    }
    $switches[$switch[BtTblSwitchField::ID]] = $switch;
}
$fragSeize = $switches[SwitchDef::ROBTREASURE];
if(!empty($fragSeize['openLv']) && ($fragSeize['openLv'] > FragseizeConf::GOD_PROTECT_LEVEL ))
{
    trigger_error('switch fragseize open level '.$fragSeize['openLv'] .' is bigger than treasure protect level '.FragseizeConf::GOD_PROTECT_LEVEL);
}
fclose($handle);
$outputFile = $argv[2] . "/$outFileName1";
$handle = fopen($outputFile, "w");
fwrite($handle, serialize($switches));
fclose($handle);
$handle = fopen($argv[2] . "/$outFileName2","w");
fwrite($handle, serialize($switchBases));
fclose($handle);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */