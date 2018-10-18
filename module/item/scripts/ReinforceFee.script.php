<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReinforceFee.script.php 66445 2013-09-26 03:20:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/ReinforceFee.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-26 03:20:30 +0000 (Thu, 26 Sep 2013) $
 * @version $Revision: 66445 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Arm.def.php";

$inFileName = 'reinforce_fee.csv';
$outFileName = 'REINFORCE_FEE';

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
		ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS			=> ($index+=4)-1,
		ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER			=> ($index+=11)-1
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
	$sum = 0;
	for ( $i = 1; $i <= $conf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER]; $i++ )
	{
		$index = $arrConfKey[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER] + $i;
		$sum += intval($data[$index]);
		$conf[$i][ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER] = $sum;
	}
	unset($conf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER]);
	
	for ( $i = 1; $i <= $conf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS]; $i++ )
	{
		$index = $arrConfKey[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS] + $i;
		if ( !empty($data[$index]) )
		{
			$info = str2Array($data[$index]);
			if ( !isset($conf[$info[0]]))
			{
				trigger_error("invalid req item, $i!\n");
			}
			$conf[$info[0]][ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS] = array();
			for ( $k = 1; $k < count($info); $k += 2)
			{
				$conf[$info[0]][ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS][$info[k]] = $info[$k+1];
			}
		}
	}
	unset($conf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS]);
	
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