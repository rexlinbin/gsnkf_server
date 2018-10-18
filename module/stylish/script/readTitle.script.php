<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readTitle.script.php 241191 2016-05-06 02:56:36Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/script/readTitle.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-06 02:56:36 +0000 (Fri, 06 May 2016) $
 * @version $Revision: 241191 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Stylish.def.php";

$inFileName = 'sign.csv';
$outFileName = 'TITLE';

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

$index = 7;
//对应配置表键名
$arrConfKey = array (
		StylishDef::TITLE_EQUIP_TYPE 						=> $index++,				//装备属性类型
		StylishDef::TITLE_EQUIP_ATTR						=> $index++,				//装备属性
		StylishDef::TITLE_ACTIVE_ATTR						=> $index++,				//激活属性
		StylishDef::TITLE_LAST_TIME 						=> ($index+=2)-1,			//持续时间
		StylishDef::TITLE_COST_ITEM							=> $index++,				//消耗物品
);

$arrKeyV2 = array(StylishDef::TITLE_ACTIVE_ATTR, StylishDef::TITLE_EQUIP_ATTR);

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
						trigger_error( "active:$data[0] invalid $key, need v2\n" );
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
	
	//持续时间换算成秒
	$conf[StylishDef::TITLE_LAST_TIME] *= 3600;

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