<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: resolver.script.php 77671 2013-11-28 08:25:16Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/script/resolver.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-28 08:25:16 +0000 (Thu, 28 Nov 2013) $
 * @version $Revision: 77671 $
 * @brief 
 *  
 **/
/**
 * 分解表
 */
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";


$csvFile = 'resolve.csv';
$outFileName = 'RESOLVER';

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
        'type' => $ZERO, //分解类型ID
        'silver_ratio' => ++$ZERO,//获得银币系数
        'soul_ratio' =>  ++$ZERO,//获得将魂系数
        );

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
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
    $conf    =    array();
    foreach($field_names as $key => $v)
    {
        $conf[$key] = intval($line[$v]);
    }
    $arrConf[$conf['type']] = $conf;
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */