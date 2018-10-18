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
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/def/Pill.def.php";

$csvFile = 'pill.csv';
$outFileName = 'PILL';

if( isset($argv[1]) && $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if( $argv < 3 )
{
    trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}

$index = 0;
$arrColumn = array(
    PillDef::ID => $index++,
    PillDef::ED_NUMBER => ($index+=4)-1,
    PillDef::PILL_ID => $index++,
    PillDef::PILL_ATTOP => $index++,
    PillDef::PILL_NUM => $index++,
    PillDef::PILL_TYPE => $index++,
);

$file = fopen($argv[1]."/$csvFile", 'r');
if( $file == false )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);
$arrConf = array();
while(true)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($arrColumn as $key => $index)
    {
        switch ($key)
        {
            case PillDef::PILL_ATTOP:
                $arrTmp = str2Array($line[$index]);
                $aimC = array();
                $aimD = array();
                foreach($arrTmp as $k => $v)
                {
                    $tmp2 = array2Int(str2Array($v, '|'));
                    $aimC[$tmp2[0]] = $tmp2[1];
                    $aimD[$tmp2[0]] = $tmp2[2];
                }
                $conf[$key] = $aimC;
                $conf[PillDef::PILL_ATTED] = $aimD;
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }
    }
    $arrConf[$conf[PillDef::ID]] = $conf;
}
fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */