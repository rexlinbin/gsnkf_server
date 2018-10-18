<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTaskLimit.script.php 116952 2014-06-24 08:15:12Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/script/GuildTaskLimit.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-24 08:15:12 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 116952 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GuildTask.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Guild.def.php";


$csvFile = 'corps_quest_config.csv';
$outFileName = 'GUILD_TASK_LIMIT';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 2 )
{
	echo "Please input enough arguments:!{$csvFile}\n";
	exit;
}

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	echo $argv[1]."/{$csvFile} open failed! exit!\n";
	exit;
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$index = 0;
$keyArr = array(
		GuildTaskDef::BTS_LIMITID => 0,
		GuildTaskDef::BTS_MAXNUM => ++$index,
		GuildTaskDef::BTS_FORGIVE_CD => ++$index,
		GuildTaskDef::BTS_REF_GOLD => ++$index,
		GuildTaskDef::BTS_INCREF_GOLD => ++$index,
		GuildTaskDef::BTS_REF_TASKARR => ++$index,
		GuildDef::GUILD_EXP_ID => ++$index,
		GuildDef::GUILD_LEVEL_RATIO => ++$index,
		
		GuildTaskDef::BTS_GUILD_LV => ++$index,
		GuildTaskDef::BTS_USER_LV => ++$index,
);

$arrOne = array(GuildTaskDef::BTS_INCREF_GOLD,);
$arrTwo = array(GuildTaskDef::BTS_REF_TASKARR,);

$confList = array();
while ( true )
{
	$conf  = array();

	$data = fgetcsv($file);
	if ( empty( $data )||empty( $data[ 0 ] ) )
	{
		break;
	}

	foreach ( $keyArr as $key => $index )
	{
		if ( in_array( $key , $arrOne) )
		{
			if ( empty( $data[$index] ) )
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array_map( 'intval' , explode( ',' , $data[ $index ]));
			}
		}
		else if ( in_array( $key, $arrTwo ) )
		{
			if (empty( $data[$index] ))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = explode( ',' , $data[$index]);
				foreach ( $conf[$key] as $key2 => $val2 )
				{
					$conf[$key][$key2] = array_map( 'intval' , explode( '|' , $val2));
				}
			}
		}
		else
		{
			$conf[$key] = intval( $data[$index] );
		}
	}
	$confList = $conf;
}

var_dump($confList);
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */