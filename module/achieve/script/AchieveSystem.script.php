<?php
/***************************************************************************
 * 
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: AchieveSystem.script.php 109731 2014-05-21 04:01:29Z QiangHuang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/script/AchieveSystem.script.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-21 04:01:29 +0000 (Wed, 21 May 2014) $
 * @version $Revision: 109731 $
 * @brief 
 *  
 **/
 

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Achieve.def.php";

$csvFile = 'achie_table.csv';
$outFileName = 'ACHIEVE_SYSTEM';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error("Please input enough arguments:!{$csvFile}\n" );
}

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$types = array();
$ids = array();

while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$id = intval($data[0]);
	$type = intval($data[2]);
	if(empty(AchieveDef::$ALL_TYPES[$type]))
	{
		trigger_error("invalid config: type:$type unknown. id:$id");
	}
	$conf = array( AchieveDef::CONF_ID => $id, AchieveDef::CONF_TYPE => $type);
	$conf[AchieveDef::CONF_MTYPE] = intval($data[1]);
	$arrV1 = array2Int(str2Array($data[7], '|'));
	if(sizeof($arrV1) == 1)
	{
		$conf[AchieveDef::CONF_FINISH_TYPE] = 0;
		$conf[AchieveDef::CONF_FINISH_NUM] = $arrV1[0];
	}
	else if(sizeof($arrV1) == 2)
	{
		$conf[AchieveDef::CONF_FINISH_TYPE] = $arrV1[0];
		$conf[AchieveDef::CONF_FINISH_NUM] = $arrV1[1];
	}
	else 
	{
		trigger_error("invalid config: finish_type is empty. id:$id");
	}


    $arrRewards = str2Array($data[8], ',');
    if(count($arrRewards) == 0)
    {
    	trigger_error("invalid config: reward is empty. id:$id");
    }
    
    $rewards = array();
    foreach($arrRewards as $index => $reward)
    {
    	$arrDatas = array2Int(str2Array($reward, '|'));
    	if(count($arrDatas) != 3)
    	{
    		trigger_error("invalid config: reward[$index] is empty. id:$id");
    	}
    	array_push($rewards, $arrDatas);
    }
    $conf[AchieveDef::CONF_REWARD] = $rewards;

    // 排名成就比较特殊,是向上兼容(其他都是向下兼容),故用了点技巧,把$finish_num -> MAX_BOSS_RANK - $finish_num
	// 这样就能利用现有的规则了
	if(in_array($type, AchieveDef::$DESC_TYPES))
		$conf[AchieveDef::CONF_FINISH_NUM] = AchieveDef::MAX_BOSS_RANK - $conf[AchieveDef::CONF_FINISH_NUM];
    if(!isset($types[$type]))
        $types[$type] = array();
	$types[$type][$id] = $conf;
	$ids[$id] = $conf;
}


foreach(AchieveDef::$ALL_TYPES as $type => $tname)
{
    if(!isset($types[$type]))
    {
		printf("[warn] type:$tname config is missing!\n");
		$types[$type] = array();
    }
}

$confs = array(
	AchieveDef::CONF_TYPES => $types,
	AchieveDef::CONF_IDS => $ids,
);
fclose($file);

var_dump($confs);


//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confs));
fclose($file);

 
