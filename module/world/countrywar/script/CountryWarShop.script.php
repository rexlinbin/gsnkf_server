<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarShop.script.php 213786 2015-12-02 10:49:58Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/script/CountryWarShop.script.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-02 10:49:58 +0000 (Wed, 02 Dec 2015) $
 * @version $Revision: 213786 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/CountryWar.def.php";


$csvFile = 'national_war_shop.csv';
$outFileName = 'COUNTRY_WAR_SHOP';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:! national_war_shop.csv output\n";
	trigger_error ("input error parameters.");
}

$pos = 0;
$field_names = array(
		CountryWarCsvField::GODID => $pos++,
		CountryWarCsvField::ITEMARR => $pos++,
		CountryWarCsvField::PRICE => $pos++,
		CountryWarCsvField::GODTYPE => ($pos += 2) - 1,
		CountryWarCsvField::MAX_BUY_NUM => $pos++,
		CountryWarCsvField::NEED_LEVEL => $pos,
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
		switch ($key)
		{
			case CountryWarCsvField::ITEMARR:
				$itemArr = explode(',', $line[$v]);
				foreach ( $itemArr as $index => $val )
				{
					$tempItemConf = array_map('intval', explode('|', $val));
					$conf[CountryWarShopDef::ACQ][$key][$tempItemConf[1]] = $tempItemConf[2];
				}
				break;
			case CountryWarCsvField::GODTYPE:
				$conf[$key] = intval($line[$v]);
				break;
			case CountryWarCsvField::GODID:
				$conf[$key] = intval($line[$v]);
				break;
			default:
				$tempConf = intval($line[$v]);
				$conf[CountryWarShopDef::REQ][$key] = $tempConf;
				break;
		}
	}
	$confList[$conf[CountryWarCsvField::GODID]] = $conf;
	unset($confList[$conf[CountryWarCsvField::GODID]][CountryWarCsvField::GODID]);
}
fclose($file);
//将内容写入文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */