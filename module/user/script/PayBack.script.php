<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PayBack.script.php 81219 2013-12-17 03:44:26Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/script/PayBack.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-17 03:44:26 +0000 (Tue, 17 Dec 2013) $
 * @version $Revision: 81219 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'pay_list.csv';
$outFileName = 'PAY_BACK';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}
$ZERO = 0;
$arrField = array(
        'id'=>$ZERO,
        'platformIndex'=>++$ZERO,
        'platformName'=>++$ZERO,
        'rmb'=>++$ZERO,
        'charge_gold'=>++$ZERO,
        'back_gold'=>++$ZERO
        );
$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$conf = array();

while ( true )
{
    $data = fgetcsv($file);
    if ( empty($data) )
    {
        break;
    }
    $gold = 1;
    $back = 1;
    foreach($arrField as $key => $v)
    {
        switch($key)
        {
            case 'id':
            case 'rmb':
                break;
            case 'platformIndex':
                $platformIndex = intval($data[$v]);
                break;
            case 'charge_gold':
                $gold = intval($data[$v]);
                break;
            case 'back_gold':
                $back = intval($data[$v]);
                break;
        }
    }
    if(isset($conf[$platformIndex][$gold]))
    {
        trigger_error('error config.duplicate conf.');
    }
    $conf[$platformIndex][$gold] = $back;
}
fclose($file);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($conf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */