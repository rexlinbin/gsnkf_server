<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionShop.script.php 196432 2015-09-02 11:42:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/script/MissionShop.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-02 11:42:06 +0000 (Wed, 02 Sep 2015) $
 * @version $Revision: 196432 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Mission.def.php";

$csvFile = 'bounty_shop.csv';
$outFileName = 'MISSION_SHOP';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!bounty_shop.csv output\n";
	trigger_error ("input error parameters.");
}

$pos = 0;
$field_names = array(
		MissionCsvField::GODID => $pos++,
		MissionCsvField::ITEMARR => $pos++,
		MissionCsvField::PRICE => $pos++,
		MissionCsvField::GODTYPE => $pos++,
		MissionCsvField::MAX_BUY_NUM => $pos++,
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
		if( $key ==  MissionCsvField::ITEMARR )
		{
			$conf[$key] = explode( ',' , $line[$v] );
			foreach ( $conf[$key] as $k2 => $v2 )
			{
				$conf[$key][$k2] = array_map( 'intval' , explode( '|' , $v2));
			}
		}
		else
		{
			$conf[$key] = intval( $line[$v] );
		}
	}
	$confList[$conf[MissionCsvField::GODID]] = $conf;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */