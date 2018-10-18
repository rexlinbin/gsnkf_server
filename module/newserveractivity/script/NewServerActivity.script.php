<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivity.script.php 242177 2016-05-11 10:56:34Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/script/NewServerActivity.script.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-11 10:56:34 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242177 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/NewServerActivity.def.php";

/**
 * {
 * 		'OPENDAY' => {$taskId => $openDate:int},
 * 		'GOODS' => { $day => {'itemArr' => 奖励三元组的数组, 'currentPrice' => int, 'limitNum' => int, 'limitRatio' => int}, }
 * 		'DEADLINE' => int,
 * 		'CLOSEDAY' => int,
 * 		'minOpenDay' =>int,
 * }
 * */

$csvFile = 'open_seven_act.csv';
$outFileName = 'NEW_SERVER_ACT';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments\n";
	trigger_error ("input error parameters.");
}

$taskId = array(
		'duty1' => 2,
		'duty2' => 4,
		'duty3' => 6,
);
$goodsArr = array(
		NewServerActivityCsvDef::PRICE,
		NewServerActivityCsvDef::LIMITNUM,
);
$index = 0;

$field_names = array(
		NewServerActivityCsvDef::OPENDAY => $index,
// 		NewServerActivityCsvDef::DEADLINE => $index++,
// 		NewServerActivityCsvDef::CLOSEDAY => $index,
		NewServerActivityCsvDef::TASKID => $taskId,
		NewServerActivityCsvDef::ITEMS => $index = ($index + 7),
		NewServerActivityCsvDef::PRICE => $index = ($index + 2),
		NewServerActivityCsvDef::LIMITNUM => ++$index,

);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();

while(TRUE)
{
	$conf = array();
	$tempArr = array();
	$arrDutyId = array();
	$line = fgetcsv($file);
	if(empty($line) || empty($line[0]))
	{
		break;
	}
	foreach($field_names as $key => $value)
	{
		switch($key)
		{
			case NewServerActivityCsvDef::TASKID:
				foreach ($value as $v)
				{
					$arrTempTaskId = array2Int(str2Array($line[$v], '|'));
					foreach ($arrTempTaskId as $taskId)
					{
						$arrDutyId[] = $taskId;
					}
				}
				break;
			case NewServerActivityCsvDef::ITEMS:
				$itemArr = str2Array($line[$value], ',');
				foreach ($itemArr as $k => $v)
				{
					$itemArr[$k] = array2Int(str2Array($v, '|'));
				}
				$goodsTempConf[$key] = $itemArr;
				break;
			case in_array($key, $goodsArr):
				$goodsTempConf[$key] = intval($line[$value]);
				break;
			default:
				$conf[$key] = intval($line[$value]);
				break;
		}
	}
	
	$conf[NewServerActivityCsvDef::TASKID] = $arrDutyId;
	$arrConf[$conf[NewServerActivityCsvDef::OPENDAY]] = $conf;
	$goodsConf[NewServerActivityCsvDef::GOODS][$conf[NewServerActivityCsvDef::OPENDAY]] = $goodsTempConf;
}
fclose($file);
$confArr = array();
$dutyIdTimeArr = array();

foreach ($arrConf as $day => $value)
{
	foreach ($value[NewServerActivityCsvDef::TASKID] as $dutyId)
	{
		// 得到任务对应的活动开始天数
		$dutyIdTimeArr[NewServerActivityCsvDef::OPENDAY][$dutyId] = $value[NewServerActivityCsvDef::OPENDAY];
	}
}
// 得到任务对应的活动结束天数
$dutyIdTimeArr[NewServerActivityCsvDef::DEADLINE] = 7;
// 得到任务对应的领奖结束天数
$dutyIdTimeArr[NewServerActivityCsvDef::CLOSEDAY] = 10;

reset($dutyIdTimeArr[NewServerActivityCsvDef::OPENDAY]);
$dutyIdTimeArr['minOpenDay'] = current($dutyIdTimeArr[NewServerActivityCsvDef::OPENDAY]);
$dutyIdTimeArr = array_merge($goodsConf, $dutyIdTimeArr);
print_r($dutyIdTimeArr);	//打印解析的格式


// 将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($dutyIdTimeArr));

fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */