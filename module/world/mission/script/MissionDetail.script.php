<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionDetail.script.php 196772 2015-09-07 02:18:58Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/script/MissionDetail.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-07 02:18:58 +0000 (Mon, 07 Sep 2015) $
 * @version $Revision: 196772 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Mission.def.php";


$csvFile = 'bounty_task.csv';
$outFileName = 'MISSION_DETAIL';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:! bounty_task.csv output\n";
	trigger_error ("input error parameters.");
}

$pos = 0;
$field_names = array(
		MissionCsvField::MISID => $pos++,
		MissionCsvField::MAX_NUM => ($pos+=5)-1,
		MissionCsvField::FAME_RECEIVE => $pos++,
		//MissionCsvField::MIS_TYPE => $pos++,
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
		$conf[$key] = intval( $line[$v] );
	}
	$confList[$conf[MissionCsvField::MISID]] = $conf;
}
fclose($file);
//将内容写入文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */