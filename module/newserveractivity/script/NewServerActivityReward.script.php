<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityReward.script.php 242177 2016-05-11 10:56:34Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/script/NewServerActivityReward.script.php $
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
 * 		'TASKID' => [$taskId]
 * 		'TYPE' => 
 * 		{
 * 			$taskType => 
 * 					{
 * 						$taskId => {
 * 										'TYPE'	=> int,
 * 										'REQUIRE' => int,
 * 										'REWARD' => 奖励三元组[0 => {'itemTid'=>int, 'itemId'=>int, 'itemNum'=>int}, 1 => {...}, ...]
 * 									}
 * 					}
 * 		}
 * }
 * */

$csvFile = 'open_seven_reward.csv';
$outFileName = 'NEW_SERVER_ACT_REWARD';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments\n";
	trigger_error ("input error parameters.");
}

$index = 2;
$field_names = array(
		NewServerActivityCsvDef::TYPE => $index++,
		NewServerActivityCsvDef::RQE => $index++,
		NewServerActivityCsvDef::REWARD => $index,
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
	$line = fgetcsv($file);
	if(empty($line) || empty($line[0]))
	{
		break;
	}
	foreach($field_names as $key => $value)
	{
		switch($key)
		{
			case NewServerActivityCsvDef::REWARD:
				$tmp = str2Array($line[$value], ',');
				foreach($tmp as $k => $v)
				{
					$conf[$key][$k] = array2Int(str2Array($v, '|'));
				}
				break;
			default:
			$conf[$key] = intval($line[$value]);
			break;
		}
	}
	$taskId = intval($line[0]);
	$arrConf[$taskId] = $conf;
}

foreach ($arrConf as $taskId => $info)
{
    $arrConf[NewServerActivityCsvDef::TASKID][] = $taskId;
	// 比较截取 任务id 的前3位  与 父类型  是否相等
	$cutOut = substr($taskId, 0, 3);
	if ($info[NewServerActivityCsvDef::TYPE] == $cutOut)
	{
		$arrConf[NewServerActivityCsvDef::TYPE][$cutOut][$taskId] = $arrConf[$taskId];
		unset($arrConf[$taskId]);
	}
}

fclose($file);
print_r($arrConf);	//打印解析的格式

// 将内容写入btstore文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */