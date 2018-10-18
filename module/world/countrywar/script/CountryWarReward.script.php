<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarReward.script.php 216467 2015-12-18 14:35:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/script/CountryWarReward.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-18 14:35:30 +0000 (Fri, 18 Dec 2015) $
 * @version $Revision: 216467 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/CountryWar.def.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/conf/CountryWar.cfg.php";

$csvFile = 'national_war_reward.csv';
$outFileName = 'COUNTRY_WAR_REWARD';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:! national_war_reward.csv output\n";
	trigger_error ("input error parameters.");
}

$pos = 0;
$field_names = array(
		CountryWarCsvField::REWARD_ID => $pos++,
		CountryWarCsvField::RANK_MIN => ($pos+=2)-1,
		CountryWarCsvField::RANK_MAX => $pos++,
		CountryWarCsvField::REWARD_ARR => $pos++,
		CountryWarCsvField::STAGE => $pos++,
);

$arrOne = array();
$arrTwo = array(
		CountryWarCsvField::REWARD_ARR
);
$arrThree = array();

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$confList = array();
while(TRUE)
{
$conf = array();
$line = fgetcsv($file);
if(empty($line) || empty( $line[0] ))
{
break;
}
foreach($field_names as $key => $v)
{
if( in_array( $key , $arrOne) )
{
$conf[$key] = array_map( 'intval' , explode( '|' , $line[$v]));
}
elseif (in_array($key, $arrTwo))
{
	$conf[$key] = explode(',', $line[$v]);
	foreach ( $conf[$key] as $index => $val )
	{
		$conf[$key][$index] = array_map('intval', explode('|', $val));
	}
}
elseif(in_array($key, $arrThree))
{
	$conf[$key] = explode(',', $line[$v]);
	foreach ( $conf[$key] as $index => $val )
	{
		$conf[$key][$index] = explode(';', $val);
		foreach ( $conf[$key][$index] as $index2 => $val2 )
		{
			$conf[$key][$index][$index2] = array_map('intval', explode('|', $val2));
		}
	}
}
else
{
	$conf[$key] = intval( $line[$v] );
}
}

$confList[$conf[CountryWarCsvField::REWARD_ID]] = $conf;
}
$tmpList = array();
foreach ( $confList as $id => $oneRewardInfo )
{
	if( $oneRewardInfo[CountryWarCsvField::STAGE] == 1 )
	{
		$stage = CountryWarStage::AUDITION;
	}
	else
	{
		$stage = CountryWarStage::FINALTION;
	}
	$tmpList[$stage][] = $oneRewardInfo;
}
fclose($file);
//将内容写入文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($tmpList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */