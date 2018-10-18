<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SuitItem.script.php 66556 2013-09-26 08:45:00Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/SuitItem.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-26 08:45:00 +0000 (Thu, 26 Sep 2013) $
 * @version $Revision: 66556 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Arm.def.php";

$inFileName = 'suit.csv';
$outFileName = 'SUIT_ITEM';

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
		ArmDef::ITEM_ATTR_NAME_ARM_SUIT_NUM 		=> $index++,				// 套装数量总数
		ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ATTR		=> $index++,				// 套装属性加成数组
		ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ITEMS		=> $index++					// 套装物品id组
);

$arrKeyV1 = array(ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ITEMS);

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
	foreach ( $arrConfKey as $key => $index )
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index]))
			{
				trigger_error("suit:$data[0] item array is empty!");
			}
			else
			{
				$conf[$key] = array2Int( str2array($data[$index]) );
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		} 
		
		//整理套装属性数组
		if ($key == ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ATTR) 
		{
			$conf[$key] = array();
			for ($i = 0; $i < $data[$index]; $i++)
			{
				$armNum = $data[$index + $i * 2 + 2];
				if ($armNum > $conf[ArmDef::ITEM_ATTR_NAME_ARM_SUIT_NUM]) 
				{
					trigger_error("suit:$data[0] arm num:$armNum > max num:$data[1]!\n");
				}
				$arr = str2array($data[$index + $i * 2 + 3]);
				$conf[$key][$armNum] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "invalid $key, $value need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$armNum][$ary[0]] = $ary[1];
				}
			}
		}	
	}
	
	if (count($conf[ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ITEMS]) != $conf[ArmDef::ITEM_ATTR_NAME_ARM_SUIT_NUM])
	{
		trigger_error("suit:$data[0] suit num is not equal with suit items array acount!");
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