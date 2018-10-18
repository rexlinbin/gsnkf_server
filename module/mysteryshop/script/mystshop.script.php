<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: mystshop.script.php 77635 2013-11-28 07:31:10Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/script/mystshop.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-28 07:31:10 +0000 (Thu, 28 Nov 2013) $
 * @version $Revision: 77635 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";


$csvFile = 'mystical_shop.csv';
$outFileName = 'MYSTERYSHOP';

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
        'id' => $ZERO, //id
        'refresh_cd_time'=> ++$ZERO,//刷新CD时间
        'refresh_goods_num' => ++$ZERO,//刷新出的物品数量
        'refresh_gold_base' =>  ++$ZERO,//刷新金币基础值
        'refresh_gold_inc' => ++$ZERO,//刷新金币递增值
        'refresh_item' => ++$ZERO,//刷新需要物品ID
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
    $arrConf[] = $conf;
}
if(count($arrConf) > 1)
{
    trigger_error('conf line num is bigger than 1');
}
else if(count($arrConf) < 1)
{
    trigger_error('conf line num is smaller than 1');
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf[0]));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */