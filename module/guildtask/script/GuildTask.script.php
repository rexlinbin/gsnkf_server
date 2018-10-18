<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTask.script.php 117003 2014-06-24 09:35:40Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/script/GuildTask.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-24 09:35:40 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 117003 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GuildTask.def.php";

$csvFile = 'corps_quest.csv';
$outFileName = 'GUILD_TASK';

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
		GuildTaskDef::BTS_TASKID => 0,
		GuildTaskDef::BTS_TYPE => ($index+=2) -1,
		GuildTaskDef::BTS_STAR => ++$index,
		GuildTaskDef::BTS_WEIGHT => ++$index,
		GuildTaskDef::BTS_NEED_BUILDLV => ++$index,
		GuildTaskDef::BTS_FINISH_COND => ($index += 2)-1,
		GuildTaskDef::BTS_REWARD => ++$index,
		GuildTaskDef::BTS_RIT_FINISH_GOLD => ++$index,
		
		GuildTaskDef::BTS_NEED_CITY => ($index+=5),
		GuildTaskDef::BTS_NEED_EXE => ++$index,
);


$arrOne = array(GuildTaskDef::BTS_FINISH_COND,);
$arrTwo = array(GuildTaskDef::BTS_REWARD,);

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
	$confList[ $conf[ GuildTaskDef::BTS_TASKID ] ] = $conf;
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