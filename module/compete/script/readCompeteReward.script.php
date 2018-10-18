<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readCompeteReward.script.php 114229 2014-06-13 10:02:31Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/script/readCompeteReward.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-13 10:02:31 +0000 (Fri, 13 Jun 2014) $
 * @version $Revision: 114229 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Compete.def.php";

$inFileName = 'contest_reward.csv';
$outFileName = 'COMPETE_REWARD';

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
		CompeteDef::COMPETE_REWARD_ID 				=> $index++,				// 比武奖励id
		CompeteDef::COMPETE_REWARD_MIN 				=> ($index+=2)-1,			// 奖励最小排名
		CompeteDef::COMPETE_REWARD_MAX 				=> $index++,				// 奖励最大排名
		CompeteDef::COMPETE_REWARD_SILVER			=> $index++,				// 奖励银币
		CompeteDef::COMPETE_REWARD_SOUL				=> $index++,				// 奖励将魂
		CompeteDef::COMPETE_REWARD_GOLD				=> $index++,				// 奖励金币
		CompeteDef::COMPETE_REWARD_ITEM				=> $index++,				// 奖励物品
		CompeteDef::COMPETE_REWARD_HONOR			=> $index++,				
);

$arrKeyV2 = array(CompeteDef::COMPETE_REWARD_ITEM);

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
		if( in_array($key, $arrKeyV2, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "star:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = $ary[1];
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}

	$confList[$data[0]] = $conf;
}
fclose($file);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */