<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDartItems.script.php 242142 2016-05-11 10:06:33Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/scripts/ChargeDartItems.script.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-11 10:06:33 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242142 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/ChargeDart.def.php";

$inFileName = 'mnlm_items.csv';
$outFileName = 'CHARGEDART_ITEMS';

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
    ChargeDartDef::CSV_CAR_ID => $NUM,
    ChargeDartDef::CSV_QUALITY => $NUM+=3,
    ChargeDartDef::CSV_REWARD => ++$NUM,
    ChargeDartDef::CSV_ROB_REWARD => ++$NUM,
    ChargeDartDef::CSV_ASSIT_REWARD => ++$NUM,
	ChargeDartDef::CSV_SPECIAL_REWARD => ++$NUM,
	ChargeDartDef::CSV_SPECIAL_ONCE_REWARD => ++$NUM,
);

//特殊处理的部分
$special_array = array(
    ChargeDartDef::CSV_REWARD,
	ChargeDartDef::CSV_SPECIAL_REWARD,
	ChargeDartDef::CSV_SPECIAL_ONCE_REWARD,
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
        if ( in_array($key, $special_array) )
        {
            $conf[$key] = array();
            $info = explode ( ';', $data [$index] );
            foreach ($info as $k => $v)
            {
                $info2 = explode ( ',', $v );
                $level = 0;
                foreach ($info2 as $k2 => $v2)
                {
                    if($k2 == 0)
                    {
                        $level = intval($v2);
                        $conf[$key][$level] = array();
                    }
                    else{
                        $conf[$key][$level][] =  explode('|', $v2);
                    }
                }
                /*//三元组
                $conf[$key][intval($info2[0])][] = array(intval($info2[1]),intval($info2[2]),intval($info2[3]));
                */
            }
        }
        else {
            $conf[$key] = intval($data [$index]);
        }
    }
    
    $confList[$conf[ChargeDartDef::CSV_CAR_ID]] = $conf;
}

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