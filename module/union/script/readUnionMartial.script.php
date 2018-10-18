<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readUnionMartial.script.php 241891 2016-05-10 08:35:40Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/script/readUnionMartial.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 08:35:40 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241891 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Union.def.php";

$inFileName = 'hall_attr.csv';
$outFileName = 'UNION_MARTIAL';

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

$index = 2;
//对应配置表键名
$arrConfKey = array (
		UnionDef::NEED_LEVEL						=> $index++,				// 镶嵌等级
		UnionDef::ITEM_TPLID						=> ($index+=2)-1,			// 消耗物品ID
		UnionDef::GOLD_NUM							=> $index++,				// 1个消耗物对应金币
		UnionDef::ITEM_NUM_ARR						=> $index++,				// 不同资质武将对应消耗
		UnionDef::NEED_ARR							=> ($index+=2)-1,			// 镶嵌的武将及物品
		UnionDef::ADD_ATTR							=> $index++,				// 激活属性
);

$arrKeyV1 = array();
$arrKeyV2 = array(UnionDef::ITEM_NUM_ARR, UnionDef::NEED_ARR, UnionDef::ADD_ATTR);

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
		if (in_array($key, $arrKeyV2, true))
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array();
				$arr = str2array($data[$index]);
				foreach ($arr as $value)
				{
					if (!strpos($value, '|'))
					{
						trigger_error("union:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					if ($key == UnionDef::NEED_ARR) 
					{
						$conf[$key][] = $ary[1]; 
					}
					else 
					{
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
		}
		elseif ( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array2Int(str2array($data[$index]));
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