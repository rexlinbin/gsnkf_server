<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readStarAll.script.php 119250 2014-07-08 10:29:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/scripts/readStarAll.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-08 10:29:30 +0000 (Tue, 08 Jul 2014) $
 * @version $Revision: 119250 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Star.def.php";

$inFileName = 'star_all.csv';
$outFileName = 'STAR_ALL';

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

$index = 1;
// 对应配置表键名
$arrConfKey = array (
		StarDef::STAR_GOLD_BASE 					=> ++$index,				// 赠送金币的基础值
		StarDef::STAR_GOLD_INCRE 					=> ++$index,				// 赠送金币的递增值
		StarDef::STAR_GOLD_FAVOR 					=> ++$index,				// 赠送金币的好感度
		StarDef::STAR_GOLD_MAX 						=> ++$index,				// 赠送金币的次数上限
		StarDef::STAR_RATIO_ONE 					=> ++$index,				// 暴击参数1
		StarDef::STAR_RATIO_TWO 					=> ++$index,				// 暴击参数2
		StarDef::STAR_SWAP_COST						=> ++$index,				// 好感互换花费
);

$arrKeyV2 = array(StarDef::STAR_SWAP_COST);

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
			$arr = str2array($data[$index]);
			$conf[$key] = array();
			foreach( $arr as $value )
			{
				if(!strpos($value, '|'))
				{
					trigger_error( "invalid $key, need v2\n" );
				}
				$ary = array2Int(str2Array($value, '|'));
				$conf[$key][$ary[0]] = $ary[1];
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$confList = $conf;
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