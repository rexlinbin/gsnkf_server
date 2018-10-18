<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SkillItem.script.php 62661 2013-09-03 06:14:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/SkillItem.script.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2013-09-03 06:14:26 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62661 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$inFileName = 'skill_itembook.csv';
$outFileName = 'SKILL_ITEM';

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
$index = 1;
$arrConfKey = array (
		PropertyKey::ARR_IMMUNED_BUFF 		=> $index++,
		PropertyKey::PARRY_SKILL			=> $index++,
		PropertyKey::DODGE_SKILL			=> $index++,
		PropertyKey::DEATH_SKILL	 		=> $index++,
		PropertyKey::ARR_ATTACK_SKILL 		=> $index++,
		PropertyKey::ARR_RAGE_SKILL 		=> $index++,
		PropertyKey::ARR_PARRY_SKILL 		=> $index++,
		PropertyKey::ARR_DODGE_SKILL 		=> $index++,
		PropertyKey::ARR_DEATH_SKILL 		=> $index++,
		PropertyKey::ARR_ATTACK_BUFF 		=> $index++,
		PropertyKey::ARR_RAGE_BUFF 			=> $index++,
		PropertyKey::ARR_DEATH_BUFF 		=> $index++,
		PropertyKey::ARR_PARRY_BUFF 		=> $index++,
		PropertyKey::ARR_DODGE_BUFF			=> $index++	
);

$arrKeyV1 = array(
		PropertyKey::ARR_IMMUNED_BUFF,
		PropertyKey::ARR_ATTACK_SKILL,
		PropertyKey::ARR_RAGE_SKILL,	
		PropertyKey::ARR_PARRY_SKILL,
		PropertyKey::ARR_DODGE_SKILL,
		PropertyKey::ARR_DEATH_SKILL,
		PropertyKey::ARR_ATTACK_BUFF,
		PropertyKey::ARR_RAGE_BUFF,
		PropertyKey::ARR_DEATH_BUFF,
		PropertyKey::ARR_PARRY_BUFF,
		PropertyKey::ARR_DODGE_BUFF,
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) )
	{
		break;
	}

	$id = intval($data[0]);
	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			$conf[$key] =  array2Int( str2Array($data[$index]) );
			continue;
		}
		$conf[$key] = intval($data[$index]);
	}
	$confList[$id] = $conf;
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