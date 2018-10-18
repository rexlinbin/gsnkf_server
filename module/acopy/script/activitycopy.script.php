<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 


$csvFile = 'activitycopy.csv';
$outFileName = 'ACTIVITYCOPY';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
	echo "Please input enough arguments:!activitycopy.csv output\n";
	trigger_error ("input error params.");	
}

$ZERO = 0;
		/**
			活动副本ID
			活动副本名称
			活动副本介绍
			活动副本图片
			活动类型
			通关某据点后开启该活动副本
			活动副本开启等级限制
			活动开启后可通关次数
			活动开启后可攻击次数
			奖励银币
			奖励将魂
			奖励经验		
			通关消耗体力
			攻击消耗体力
			活动对应据点ID组
			活动开启时间
			活动结束时间
		 */
$field_names = array(
		'id'=>$ZERO,
		'name'=>++$ZERO,
		'profile'=>++$ZERO,
		'img'=>++$ZERO,
		'type'=>++$ZERO,//活动的类型如活动据点，摇钱树，
		'pre_pass_ncopy'=>++$ZERO,//通关某普通副本后开启该活动副本
		'need_level'=>++$ZERO,//开启该副本的等级限制
		'pass_num'=>++$ZERO,//可通关次数
		'attack_num'=>++$ZERO,//可攻击次数		
		'reward_silver'=>++$ZERO,
		'reward_soul'=>++$ZERO,
		'reward_exp'=>++$ZERO,
		'pass_need_power'=>++$ZERO,//通关消耗体力
		'attack_need_power'=>++$ZERO,//攻击消耗体力
		'base_id'=>++$ZERO, //副本对应的据点id   1
		'start_time'=>++$ZERO,
		'end_time'=>++$ZERO,
        'copy_img'=>++$ZERO,
        'drop_id'=>++$ZERO,
		);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$copies = array();
$copy = array();
while(TRUE)
{
	$copy = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{		
		switch($key)
		{
			case 'name':
			case 'profile':
			case 'img':
				break;
			case 'start_time':
			case 'end_time':
				$tmp = str2Array($line[$v], ',');
				$copy[$key] = array();
				foreach($tmp as $arrDate)
				{
				    $dateTime = array2Int(str2Array($arrDate, '|'));
				    if(count($dateTime) != 2)
				    {
				        trigger_error('error config for '.$key.'.date is '.$line[$v]);
				    }
				    $copy[$key][$dateTime[0]] = $dateTime[1];
				}
				break;
			default:
				$copy[$key] = intval($line[$v]);
		}
	}
	if(count($copy['start_time']) != count($copy['end_time']))
	{
	    trigger_error('copy '.$copy['id'].' conf error.start_time num not equal to end_time num');
	}
	var_dump($copy);
	$copy['open_time'] = array();
	foreach($copy['start_time'] as $week => $startTime)
	{
	    if(!isset($copy['end_time'][$week]))
	    {
	        trigger_error('copy '.$copy['id'].' conf error.week '.$week.' in start_time,not in end_time');
	    }
	    $copy['open_time'][$week] = array(
	            'start_time' => $startTime,
	            'end_time' => $copy['end_time'][$week],
	            );
	}
	unset($copy['start_time']);
	unset($copy['end_time']);
	$copies[$copy['id']] = $copy;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($copies));
fclose($file);
