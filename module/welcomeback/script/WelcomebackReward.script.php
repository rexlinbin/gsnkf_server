<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackReward.script.php 258539 2016-08-26 06:03:30Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/script/WelcomebackReward.script.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-26 06:03:30 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258539 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Welcomeback.def.php";

$csvFile = 'return_reward.csv';
$outFileName = 'WELCOMEBACK_REWARD';

if ( isset($argv[1]) && $argv[1] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!resolver.csv output\n";
	trigger_error ("input error parameters.");
}

$index = 0;
$field_names = array(
		WelcomebackDef::ID => $index,
		WelcomebackDef::TYPE => ++$index,
		WelcomebackDef::LEVEL_LIMITS => ($index += 2),
		WelcomebackDef::TYPE_ID => ++$index,
		WelcomebackDef::FINISH => ++$index,
		WelcomebackDef::REWARD => ++$index,
		WelcomebackDef::ONE_FROM_N => ++$index,
		WelcomebackDef::DISCOUNT_ITEM => ++$index,
		WelcomebackDef::COST => ++$index,
		WelcomebackDef::BUY_TIMES => ++$index,
		WelcomebackDef::GOLD => ($index += 2),
		WelcomebackDef::RECHARGE_TIMES => ++$index
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( FALSE == $file )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while(true)
{
	$conf =array();
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$conf[WelcomebackDef::TYPE] = intval($data[$field_names[WelcomebackDef::TYPE]]);
	
	if ($conf[WelcomebackDef::TYPE] == WelcomebackDef::TYPE_GIFT) 
	{
		$temp = str2Array($data[$field_names[WelcomebackDef::REWARD]], ',');
		foreach ($temp as $value)
		{
			$conf[WelcomebackDef::REWARD][] = array_map('intval', str2Array($value, '|'));
		}
	}
	else if ($conf[WelcomebackDef::TYPE] == WelcomebackDef::TYPE_TASK)
	{
		$conf[WelcomebackDef::TYPE_ID] = intval($data[$field_names[WelcomebackDef::TYPE_ID]]);
		$conf[WelcomebackDef::LEVEL_LIMITS] = intval($data[$field_names[WelcomebackDef::LEVEL_LIMITS]]);
		$conf[WelcomebackDef::FINISH] = intval($data[$field_names[WelcomebackDef::FINISH]]);
		$conf[WelcomebackDef::ONE_FROM_N] = intval($data[$field_names[WelcomebackDef::ONE_FROM_N]]);
		
		$temp = str2Array($data[$field_names[WelcomebackDef::REWARD]], ',');
		foreach ($temp as $value)
		{
			$conf[WelcomebackDef::REWARD][] = array_map('intval', str2Array($value, '|'));
		}
	}
	else if ($conf[WelcomebackDef::TYPE] == WelcomebackDef::TYPE_RECHARGE)
	{
		$conf[WelcomebackDef::GOLD] = intval($data[$field_names[WelcomebackDef::GOLD]]);
		$conf[WelcomebackDef::RECHARGE_TIMES] = intval($data[$field_names[WelcomebackDef::RECHARGE_TIMES]]);
		$conf[WelcomebackDef::ONE_FROM_N] = intval($data[$field_names[WelcomebackDef::ONE_FROM_N]]);
		
		$temp = str2Array($data[$field_names[WelcomebackDef::REWARD]], ',');
		foreach ($temp as $value)
		{
			$conf[WelcomebackDef::REWARD][] = array_map('intval', str2Array($value, '|'));
		}
	}
	else if($conf[WelcomebackDef::TYPE] == WelcomebackDef::TYPE_SHOP)
	{
		$conf[WelcomebackDef::COST] = array_map('intval', str2Array($data[$field_names[WelcomebackDef::COST]], '|'));
		$conf[WelcomebackDef::BUY_TIMES] = intval($data[$field_names[WelcomebackDef::BUY_TIMES]]);
		
		$temp = str2Array($data[$field_names[WelcomebackDef::DISCOUNT_ITEM]], ',');
		foreach ($temp as $value)
		{
			$conf[WelcomebackDef::DISCOUNT_ITEM][] = array_map('intval', str2Array($value, '|'));
		}
	}
	else
		trigger_error('return_reward.csv err task type: '.$conf[WelcomebackDef::TYPE]);
	
	if (isset($confList[$data[$field_names[WelcomebackDef::ID]]]))
		trigger_error('return_reward.csv err same id '.$data[$field_names[WelcomebackDef::ID]]);
	else 
		$confList[$data[$field_names[WelcomebackDef::ID]]] = $conf;
}
fclose($file);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */