<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArmResolve.script.php 84160 2014-01-01 09:48:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/ArmResolve.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-01-01 09:48:26 +0000 (Wed, 01 Jan 2014) $
 * @version $Revision: 84160 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Arm.def.php";

$inFileName = 'armresolve.csv';
$outFileName = 'ARM_RESOLVE';

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
$index = 2;
$arrConfKey = array (
		ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_VALUE			=> $index++,
		ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_ARGS				=> $index++,
		ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_NUM				=> ($index+=3)-1,
		ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_DROPS			=> $index++,
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

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

	//分解参数数组
	$conf[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_ARGS] = array();
	for ( $i = 0; $i < 3; $i++ )
	{
		$index = $arrConfKey[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_ARGS] + $i;
		$conf[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_ARGS][] = intval($data[$index]);
	}

	//分解掉落表数组
	$conf[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_DROPS] = array();
	for ( $i = 0; $i < $conf[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_NUM]; $i++ )
	{
		$index = $arrConfKey[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_DROPS] + $i;
		if (empty($data[$index])) 
		{
			trigger_error("arm resolve:$data[0] drop:$i is empty\n" );
		}
		$conf[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_DROPS][] = intval($data[$index]);
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