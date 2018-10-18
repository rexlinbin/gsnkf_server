<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWar.script.php 212975 2015-11-27 08:04:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/script/CountryWar.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-27 08:04:52 +0000 (Fri, 27 Nov 2015) $
 * @version $Revision: 212975 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/CountryWar.def.php";


$csvFile = 'national_war.csv';
$outFileName = 'COUNTRY_WAR';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:! national_war.csv output\n";
	trigger_error ("input error parameters.");
}

$pos = 0;
$field_names = array(
	CountryWarCsvField::ID => $pos++,
	CountryWarCsvField::BATTLE_PREPARE_SECONDS => $pos++,
	CountryWarCsvField::REQ_LEVEL => $pos++,
	CountryWarCsvField::REQ_OPEN_DAYS => $pos++,
	CountryWarCsvField::BATTLE_MAX_NUM => $pos++,
	CountryWarCsvField::COUNTRY_ADDITION_ARR => $pos++,
	CountryWarCsvField::SIGN_REWARD_ARR => $pos++,
	CountryWarCsvField::INSPIRE_REQ_COCOIN => $pos++,
	CountryWarCsvField::INSPIRE_ADDITION_ARR => $pos++,
	CountryWarCsvField::INSPIRE_LIMIT => $pos++,
	CountryWarCsvField::COUNTRY_SUPPORT_REWARD => $pos++,
	CountryWarCsvField::MEMBER_SUPPORT_REWARD => $pos++,
	CountryWarCsvField::RANDOM_COUNTRY_RATIO => $pos++,
	CountryWarCsvField::MANUAL_COUNTRY_RATIO_ARR => $pos++,
	CountryWarCsvField::COUNTRY_FINAL_MEMBERNUM => $pos++,
	CountryWarCsvField::FINAL_INIT_RESOURCE => $pos++,
	CountryWarCsvField::TOUCHDOWN_ROB_RESOURCE => $pos++,
	CountryWarCsvField::JOIN_POINT => $pos++,
	CountryWarCsvField::KILL_POINT_ARR => $pos++,
	CountryWarCsvField::TOUCH_DOWN_POINT => $pos++,
	CountryWarCsvField::TERMINAL_KILL_POINT_ARR => $pos++,
	CountryWarCsvField::RECOVER_REQ_COCOIN => ($pos+=2)-1,
	CountryWarCsvField::OPEN_TRANSFER_REQ_NUM => $pos++,
	CountryWarCsvField::JOIN_CD => $pos++,
	CountryWarCsvField::CLEAR_JOIN_CD_REQ_COCOIN => $pos++,
	CountryWarCsvField::RECOVER_RANGE_ARR => $pos++,
	CountryWarCsvField::WORSHIP_REWARD_ARR => $pos++,
	CountryWarCsvField::EXCHANGE_RATIO => $pos++,
	CountryWarCsvField::ROAD_ARR => $pos++,
	CountryWarCsvField::COCOIN_MAX => $pos++,
	CountryWarCsvField::WINSIDE_REWARD_ARR => $pos++,
);

$arrOne = array(
		CountryWarCsvField::RECOVER_RANGE_ARR,
		
);
$arrTwo = array(
		CountryWarCsvField::COUNTRY_ADDITION_ARR,
		CountryWarCsvField::SIGN_REWARD_ARR,
		CountryWarCsvField::COUNTRY_SUPPORT_REWARD,
		CountryWarCsvField::MEMBER_SUPPORT_REWARD,
		CountryWarCsvField::MANUAL_COUNTRY_RATIO_ARR,
		CountryWarCsvField::KILL_POINT_ARR,
		CountryWarCsvField::TERMINAL_KILL_POINT_ARR,
		CountryWarCsvField::ROAD_ARR,
		CountryWarCsvField::WORSHIP_REWARD_ARR,
		CountryWarCsvField::WINSIDE_REWARD_ARR
);
$arrThree = array(
		CountryWarCsvField::INSPIRE_ADDITION_ARR,
);


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
	
	$confList = $conf;
}
fclose($file);
//将内容写入文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */