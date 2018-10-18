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
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/def/DressRoom.def.php";
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = "suit_dress.csv";
$outFileName = "SUIT_DRESS";

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
    SuitDressCsvDef::ID => $index++,
    SuitDressCsvDef::SUIT_ITEMS => $index++,
    SuitDressCsvDef::SUIT_ATTR => $index++
);

//读表
$file = fopen($argv[1]. "/$csvFile", "r");
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
while(true)
{
    $line = fgetcsv($file);
    if(empty($line) || empty($line[0]))
    {
        break;
    }

    $conf = array();
    foreach($field_names as $key => $index)
    {
        switch($key)
        {
            case SuitDressCsvDef::SUIT_ITEMS:
                $conf[$key] = array2Int(str2Array($line[$index], ','));
                break;
            case SuitDressCsvDef::SUIT_ATTR:
                $arrAttr = str2Array($line[$index], ',');
                foreach($arrAttr as $k => $v)
                {
                    $eachAttr = array2Int(str2Array($v, '|'));
                    $conf[$key][$k] = $eachAttr;
                }
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }

    }
    $arrConf[$conf[SuitDressCsvDef::ID]] = $conf;
}

fclose($file);
//将内容写入文件
$file = fopen($argv[2]. "/$outFileName", "w");
fwrite($file, serialize($arrConf));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */