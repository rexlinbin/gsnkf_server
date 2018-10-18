<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DropItem.script.php 74568 2013-11-13 09:50:56Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/DropItem.script.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2013-11-13 09:50:56 +0000 (Wed, 13 Nov 2013) $
 * @version $Revision: 74568 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Drop.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Item.def.php";

$inFileName = 'drop.csv';
$outFileName = 'DROP_ITEM';

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
		DropDef::DROP_ID					=> $index++,
		DropDef::DROP_NUM_LIST				=> ($index+=3)-1,
		DropDef::DROP_RULE					=> ($index+=6)-1,
		DropDef::DROP_TYPE					=> $index++,
		DropDef::DROP_LIST_NUM				=> $index++
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

//掉落数量
$dropNum = 5;

$confList = array();
while ( true )
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
	
	if (!in_array($conf[DropDef::DROP_TYPE], DropDef::$DROP_VALID_TYPES)) 
	{
		trigger_error("drop:$data[0] drop type: is invalid!");
	}
	
	$conf[DropDef::DROP_NUM_LIST] = array();
	//个数权重列表
	for ( $i = 0; $i <= $dropNum; $i++ )
	{
		$index = $arrConfKey[DropDef::DROP_NUM_LIST] + $i;
		if ( intval($data[$index]) > 0 )
		{
			$conf[DropDef::DROP_NUM_LIST][$i] = array(
					DropDef::DROP_NUM => $i,
					DropDef::DROP_WEIGHT => intval($data[$index]),
			);
		}
	}
	
	//列表
	$conf[DropDef::DROP_LIST] = array();
	$index = $arrConfKey[DropDef::DROP_LIST_NUM] + 1;
	for ( $i = 0; $i < $conf[DropDef::DROP_LIST_NUM]; $i++ )
	{
		$list = array(
				DropDef::DROP_ITEM_TEMPLATE	=>	intval($data[$index++]),
				DropDef::DROP_WEIGHT		=>	intval($data[$index++]),
				DropDef::DROP_ITEM_NUM		=>	intval($data[$index++]),
		);
		$conf[DropDef::DROP_LIST][$i+1] = $list;
		if (empty($list[DropDef::DROP_ITEM_TEMPLATE])) 
		{
			trigger_error("drop:$data[0] the $i drop item template id is invalid!");
		}	
	}		
	
	$confList[$conf[DropDef::DROP_ID]] = $conf;
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