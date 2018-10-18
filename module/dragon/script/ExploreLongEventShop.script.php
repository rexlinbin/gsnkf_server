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
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Dragon.def.php";

$csvFile = 'explore_long_event_shop.csv';
$outFileName = 'DRAGON_EVENT_SHOP';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$ZERO = 0;
$fields = array(
    DragonEventShopCsvDef::GOODID => $ZERO++,
    DragonEventShopCsvDef::ITEM => $ZERO++,
    DragonEventShopCsvDef::EACHPOINT => $ZERO++,
    DragonEventShopCsvDef::ORIGINALCOST => $ZERO++,
    DragonEventShopCsvDef::NOWCOST => $ZERO++,
    DragonEventShopCsvDef::ADD => $ZERO++,
);

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$dragonEventShop = array();
while(true)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($fields as $key => $val)
    {
        switch($key)
        {
            case DragonEventShopCsvDef::GOODID:
            case DragonEventShopCsvDef::EACHPOINT:
            case DragonEventShopCsvDef::ORIGINALCOST:
            case DragonEventShopCsvDef::NOWCOST:
            case DragonEventShopCsvDef::ADD:
                $conf[$key] = intval($line[$val]);
                break;
            case DragonEventShopCsvDef::ITEM:
                $conf[$key] = array2Int(str2Array($line[$val], '|'));
                break;
        }
    }
    $dragonEventShop[$conf[DragonEventShopCsvDef::GOODID]] = $conf;
}

fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($dragonEventShop));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */