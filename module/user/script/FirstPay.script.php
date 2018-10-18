<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FirstPay.script.php 245878 2016-06-08 04:02:42Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/script/FirstPay.script.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-08 04:02:42 +0000 (Wed, 08 Jun 2016) $
 * @version $Revision: 245878 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";


$csvFile = 'first_gift.csv';
$outFileName = 'FIRSTPAY_REWARD';


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
        'platformIndex' => ++$ZERO,
        'startTime' => ++$ZERO,
        'endTime' => ++$ZERO,
        'platformName' => ++$ZERO,
        'profile'=>++$ZERO,
        'img'=>++$ZERO,
        'charge_rmg'=>++$ZERO,
        'charge_gold'=>++$ZERO,
        'back_gold'=>++$ZERO,
        'reward_silver'=>++$ZERO,
        'reward_item'=>++$ZERO,
        'reward_hero'=>++$ZERO,
        'type' => ($ZERO += 3),
        );


$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$arrConf = array();
while ( true )
{
    $data = fgetcsv($file);
    if ( empty($data) )
    {
        break;
    }
    $conf = array();
    foreach($arrField as $key => $v)
    {
        switch($key)
        {
            case 'id':
            case 'profile':
            case 'img':
            case 'charge_rmg':
                break;
            case 'charge_gold':
            case 'back_gold':
                $conf[$key] = array2Int(str2Array($data[$v], ','));
                break;
            case 'reward_silver':
                $conf['reward'][RewardType::SILVER] = intval($data[$v]);
                break;
            case 'reward_item':
                $rewards = str2Array($data[$v], ',');
                $itemReward = array();
                foreach($rewards as $index => $reward)
                {
                    $itemReward[] = array2Int(str2Array($reward, '|'));
                }
                foreach($itemReward as $index => $rewardInfo)
                {
                    $conf['reward'][RewardType::ARR_ITEM_TPL][$rewardInfo[0]] = $rewardInfo[1];
                }
                break;
            case 'reward_hero':
                $rewards = str2Array($data[$v], ',');
                $heroReward = array();
                foreach($rewards as $index => $reward)
                {
                    $heroReward[] = array2Int(str2Array($reward, '|'));
                }
                foreach($heroReward as $index => $rewardInfo)
                {
                    $conf['reward'][RewardType::ARR_HERO_TPL][$rewardInfo[0]] = $rewardInfo[1];
                }
                break;
            case 'platformIndex':
            case 'type':
                $conf[$key] = intval($data[$v]);
                break;
            case 'startTime':
            case 'endTime':
                $conf[$key] = intval(strtotime($data[$v]));
                break;
        }
    }
    if(count($conf['charge_gold']) != count($conf['back_gold']))
    {
        trigger_error('charge_gold num is not equal to back_gold num.'.var_export($conf,true));
    }
    foreach($conf['charge_gold'] as $index => $gold)
    {
        $conf['pay_back'][$gold] = $conf['back_gold'][$index];
    }
    unset($conf['charge_gold']);
    unset($conf['back_gold']);
    $arrConf[$conf['platformIndex']][] = $conf;
}
fclose($file);
var_dump($arrConf);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */