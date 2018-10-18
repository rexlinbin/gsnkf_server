<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readStarLevel.script.php 85167 2014-01-07 07:21:52Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/scripts/readStarLevel.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-01-07 07:21:52 +0000 (Tue, 07 Jan 2014) $
 * @version $Revision: 85167 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Star.def.php";

$inFileName = 'star_level.csv';
$outFileName = 'STAR_LEVEL';

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
// 对应配置表键名
$arrConfKey = array (
		StarDef::STAR_MAX_LEVEL						=> $index+=2,				// 名将等级上限
		StarDef::STAR_FAVOR_LEVEL 					=> ++$index,				// 名将好感度等级表
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
		if ($key == StarDef::STAR_FAVOR_LEVEL) 
		{
			$sum = 0;
			//等级0对应经验0
			$conf[$key][0] = $sum;
			for ($i = 0; $i < (count($data)-3); $i++)
			{
				$sum += intval($data[$index + $i]);
				$conf[$key][$i + 1] = $sum;
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	if ($conf[StarDef::STAR_MAX_LEVEL] > count($conf[StarDef::STAR_FAVOR_LEVEL])) 
	{
		trigger_error( "star:$data[0] max level is not configed!\n" );
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