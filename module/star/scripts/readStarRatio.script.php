<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readStarRatio.script.php 72475 2013-11-04 07:36:16Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/scripts/readStarRatio.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-04 07:36:16 +0000 (Mon, 04 Nov 2013) $
 * @version $Revision: 72475 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Star.def.php";

$inFileName = 'star_ratio.csv';
$outFileName = 'STAR_RATIO';

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
		StarDef::STAR_FAVOR_RATIO 					=> $index+=2					// 名将好感度升级暴击表
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
		if ($index == 2)
		{
			for ($i = 0; $i < (count($data)-2); $i++)
			{
				$conf[$key][$i] = intval($data[$index + $i]);
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