<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: PotenceItem.script.php 84535 2014-01-02 12:40:59Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/PotenceItem.script.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-01-02 12:40:59 +0000 (Thu, 02 Jan 2014) $
 * @version $Revision: 84535 $
 * @brief
 *
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Potence.def.php";

$inFileName = 'potentiality.csv';
$outFileName = 'POTENCE_ITEM';

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

//数据对应表
$index = 0;
$arrConfKey = array (
		PotenceDef::POTENCE_ID						=> $index++,
		PotenceDef::POTENCE_TYPE_NUM_LIST			=> ($index+=3)-1,
		PotenceDef::POTENCE_VALUE_ADJUST			=> ($index+=5)-1,
		PotenceDef::POTENCE_REFRESH_TYPE			=> $index++,
		PotenceDef::POTENCE_LIST_NUM				=> ($index+=15)-1,
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

//潜能数量
$potenceNum = 5;
//洗练数量
$refreshNum = 5;

$confList = array();
while ( TRUE )
{
	$data = fgetcsv($file);
	if ( empty($data) )
	{
		break;
	}	

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		$conf[$key] = intval($data[$index]);
		if ( is_numeric($conf[$key]) || empty($conf[$key]) )
		{
			$conf[$key] = intval($conf[$key]);
		}
	}

	//潜能个数权重列表
	$conf[PotenceDef::POTENCE_TYPE_NUM_LIST] = array();
	$index = $arrConfKey[PotenceDef::POTENCE_TYPE_NUM_LIST];
	for ( $i = 1; $i <= $refreshNum; $i++ )
	{
		if (!empty($data[$index]))
		{
			$ary = array2Int(str2Array($data[$index++], '|'));
			foreach ($ary as $key => $value)
			{
				$ary[$key] = array(
						PotenceDef::POTENCE_TYPE_NUM => $key,
						PotenceDef::POTENCE_TYPE_WEIGHT => $value
				);
			}
			$conf[PotenceDef::POTENCE_TYPE_NUM_LIST][$i] = $ary;
		}
	}
	
	$conf[PotenceDef::POTENCE_REFRESH_TYPE] = array();	
	$index = $arrConfKey[PotenceDef::POTENCE_REFRESH_TYPE];
	for ( $i = 1; $i <= $refreshNum; $i++ )
	{
		if (empty($data[$index])) 
		{
			continue;
		}
		$list = array(
			PotenceDef::POTENCE_VALUE_ADD		=>	intval($data[$index++]),
			PotenceDef::POTENCE_VALUE_MODIFY	=>	intval($data[$index++]),
		);
		$cost = str2Array($data[$index++]);
		if(!empty($cost[0]) && !strpos($cost[0], '|'))
		{
			trigger_error("refresh type:$i invalid item array, need v2\n");
		}
		$ary = array2Int(str2Array($cost[0], '|'));
		$cost[0] = array($ary[0] => $ary[1]);
		$list[PotenceDef::POTENCE_VALUE_COST] = $cost;
		$conf[PotenceDef::POTENCE_REFRESH_TYPE][$i] = $list;
	}

	//潜能属性列表
	$conf[PotenceDef::POTENCE_LIST] = array();
	$index = $arrConfKey[PotenceDef::POTENCE_LIST_NUM] + 1;
	for ( $i = 0; $i < $conf[PotenceDef::POTENCE_LIST_NUM]; $i++ )
	{
		$list = array(
			PotenceDef::POTENCE_ATTR_ID		=>	intval($data[$index++]),
			PotenceDef::POTENCE_ATTR_WEIGHT	=>	intval($data[$index++]),
			PotenceDef::POTENCE_ATTR_VALUE	=>	intval($data[$index++]),
		);
		$conf[PotenceDef::POTENCE_LIST][$list[PotenceDef::POTENCE_ATTR_ID]] = $list;
	}
	
	$confList[$conf[PotenceDef::POTENCE_ID]] = $conf;
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