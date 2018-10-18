<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: tower.script.php 119026 2014-07-07 09:46:01Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/script/tower.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-07 09:46:01 +0000 (Mon, 07 Jul 2014) $
 * @version $Revision: 119026 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 

$csvFile = 'tower.csv';
$outFileName = 'TOWER';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!TOWER.csv output\n";
	trigger_error ("input error parameters.");	
}

$ZERO = 0;
$field_names = array(
		'id'=>$ZERO,
		'daily_atk_num'=>++$ZERO,//每天攻击次数
		'fail_num'=>++$ZERO,//每层失败次数
		'sweep_need_time'=>++$ZERO,//每层扫荡需要时间
		'buy_gold_init' => ++$ZERO,
        'buy_gold_inc' => ++$ZERO,//
        'buy_gold_limit'=> ++$ZERO,//购买失败次数金币上限
        'special_lv_atk_num'=>++$ZERO,//隐藏关可攻击次数
        'special_lv_duration'=>++$ZERO,//隐藏关持续时间
        'sweep_need_gold'=>++$ZERO,//扫荡每层需要金币
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$tower = array();
while(TRUE)
{
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{
	    $tower[$key] = intval($line[$v]);
	}	
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($tower));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */