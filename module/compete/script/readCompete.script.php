<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readCompete.script.php 117369 2014-06-26 07:11:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/script/readCompete.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-26 07:11:25 +0000 (Thu, 26 Jun 2014) $
 * @version $Revision: 117369 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Compete.def.php";

$inFileName = 'contest.csv';
$outFileName = 'COMPETE';

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

$index = 0;
//对应配置表键名
$arrConfKey = array (
		CompeteDef::COMPETE_TEMPLATE_ID 			=> $index++,				// 比武id
		CompeteDef::COMPETE_POINT_GROUP 			=> $index++,				// 比武积分分组
		CompeteDef::COMPETE_SUC_POINT 				=> $index++,				// 比武胜利积分
		CompeteDef::COMPETE_FAIL_POINT				=> $index++,				// 比武失败积分
		CompeteDef::COMPETE_MAX_POINT				=> $index++,				// 比武积分最大值
		CompeteDef::COMPETE_SUC_RATE				=> $index++,				// 比武胜利积分比率
		CompeteDef::COMPETE_FAIL_RATE				=> $index++,				// 比武失败积分比率
		CompeteDef::COMPETE_COST_STAMINA			=> $index++, 				// 比武消耗耐力
		CompeteDef::COMPETE_SUC_EXP					=> $index++,				// 比武胜利经验
		CompeteDef::COMPETE_FAIL_EXP				=> $index++,				// 比武失败经验
		CompeteDef::COMPETE_SUC_FLOP				=> $index++,				// 比武胜利翻牌id
		CompeteDef::COMPETE_LAST_TIME   			=> ($index+=3)-1, 			// 比武持续时间组
		CompeteDef::COMPETE_REST_TIME				=> $index++,				// 比武休息时间组
		CompeteDef::COMPETE_REFRESH_TIME			=> $index++,				// 比武刷新冷却时间
		CompeteDef::COMPETE_INIT_POINT				=> ($index+=3)-1,			// 比武初始积分
		CompeteDef::COMPETE_ADD_HONOR				=> $index++,				// 比武加荣誉值
);

$arrKeyV1 = array(
		CompeteDef::COMPETE_POINT_GROUP, 
		CompeteDef::COMPETE_LAST_TIME,
		CompeteDef::COMPETE_REST_TIME,
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
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ($arrConfKey as $key => $index)
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			$conf[$key] = array2Int( str2array($data[$index]) );
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	if($conf[CompeteDef::COMPETE_POINT_GROUP][0] > 0)
	{
		$conf[CompeteDef::COMPETE_POINT_GROUP] = array_merge(array(-1),$conf[CompeteDef::COMPETE_POINT_GROUP]);
	}
	
	if (count($conf[CompeteDef::COMPETE_POINT_GROUP]) < 3)
	{
		trigger_error('point group acount is less than 3');
	}

	$confList[$data[0]] = $conf;
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