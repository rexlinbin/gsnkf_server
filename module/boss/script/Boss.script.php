<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Boss.script.php 178606 2015-06-12 08:57:46Z ShiyuZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/script/Boss.script.php $
 * 
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 *         @date $Date: 2015-06-12 08:57:46 +0000 (Fri, 12 Jun 2015) $
 * @version $Revision: 178606 $
 *          @brief
 *         
 *         
 */
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Boss.def.php";

$csvFile = 'worldboss.csv';
$outFileName = 'BOSS';

if (isset ( $argv [1] ) && $argv [1] == '-h') {
	exit ( "usage: $csvFile $outFileName \n" );
}

if ($argc < 2) {
	echo "Please input enough arguments:!{$csvFile}\n";
	exit ();
}

$file = fopen ( $argv [1] . "/$csvFile", 'r' );
if ($file == FALSE) {
	echo $argv [1] . "/{$csvFile} open failed! exit!\n";
	exit ();
}

$data = fgetcsv ( $file );
$data = fgetcsv ( $file );

$index = 0;
$name = array (
		BossDef::BOSS_ID => $index,
		BossDef::BASE_ID => ($index += 4) - 1,
		BossDef::BOSS_INIT_LEVEL => $index ++,
		BossDef::BOSS_MIN_LEVEL => $index ++,
		BossDef::BOSS_MAX_LEVEL => $index ++,
		BossDef::REWARD_ID => $index ++,
		BossDef::REWARD_SILVER_BASIC => $index ++,
		BossDef::REWARD_PRESTIGE_BASIC => $index ++,
		
		BossDef::SUPERHERO_GOOD => $index ++,
		BossDef::SUPERHERO_BETTER => $index ++,
		BossDef::SUPERHERO_BEST => $index ++,
		
		BossDef::BOSS_START_TIME => $index ++,
		BossDef::BOSS_END_TIME => $index ++,
		BossDef::BOSS_DAY_LIST => $index ++,
		BossDef::BOSS_WEEK_LIST => $index ++,
		BossDef::BOSS_DAY_START_TIMES => $index ++,
		BossDef::BOSS_DAY_END_TIMES => $index ++,
		BossDef::SUPERHERO_NUM_ARR => $index ++,
		BossDef::NEWBOSS_DAY => ($index += 3)-1,
		BossDef::NEWBASE_ID => $index ++,
		BossDef::NEWREWARD_ID => $index ++,
		BossDef::NEWBOSS_NEEDLV => ($index += 6) -1,
		BossDef::LV_TIME => $index ++,
		BossDef::CHANGE_REWARD1 => $index++,
		BossDef::CHANGE_REWARD2 => $index++,
);

$arrayOne = array (
		BossDef::BOSS_DAY_START_TIMES,
		BossDef::BOSS_DAY_END_TIMES,
		BossDef::BOSS_DAY_LIST,
		BossDef::BOSS_WEEK_LIST,
		BossDef::NEWBOSS_DAY,
		BossDef::CHANGE_REWARD1,
		BossDef::CHANGE_REWARD2,
);
$arrayTwo = array (
		BossDef::SUPERHERO_NUM_ARR ,
);

$conflist = array ();
while ( true ) {
	$data = fgetcsv ( $file );
	if (empty ( $data ) || empty ( $data [0] ))
		break;
		
		// 一条数据
	$conf = array ();
	foreach ( $name as $key => $val ) 
	{
		if (in_array ( $key, $arrayOne )) 
		{
			if (empty ( $data [$val] )) 
			{
				$conf [$key] = array ();
			} else {
				$conf [$key] = array_map ( 'intval', explode ( ',', $data [$val] ) );
			}
		} elseif (in_array ( $key, $arrayTwo )) 
		{
			if (empty ( $data [$val] )) 
			{
				$conf [$key] = array ();
			} else {
				$conf [$key] = explode ( ',', $data [$val] );
				foreach ( $conf [$key] as $innerKey => $innerVal ) 
				{
					$conf [$key] [$innerKey] = array_map ( 'intval', explode ( '|', $innerVal ) );
				}
			}
		} else {
			$conf [$key] = intval ( $data [$val] );
		}
	}
	
	$conf [BossDef::BOSS_START_TIME] = strtotime ( $conf [BossDef::BOSS_START_TIME] );
	$conf [BossDef::BOSS_END_TIME] = strtotime ( $conf [BossDef::BOSS_END_TIME] );
	
	if (empty ( $conf [BossDef::BOSS_DAY_START_TIMES] )) {
		// trigger_error( 'bossId: %d, day start time is empty' ,$conf[BossDef::BOSS_ID] );
	} else {
		foreach ( $conf [BossDef::BOSS_DAY_START_TIMES] as $oneStartKey => $oneStartTime ) {
			$conf [BossDef::BOSS_DAY_START_TIMES] [$oneStartKey] = strtotime ( $oneStartTime ) - mktime ( 0, 0, 0 );
		}
	}
	if (empty ( $conf [BossDef::BOSS_DAY_END_TIMES] )) {
		// trigger_error( 'bossId: %d, day end time is empty' ,$conf[BossDef::BOSS_ID] );
	} else {
		foreach ( $conf [BossDef::BOSS_DAY_END_TIMES] as $oneEndKey => $oneEndTime ) {
			$conf [BossDef::BOSS_DAY_END_TIMES] [$oneEndKey] = strtotime ( $oneEndTime ) - mktime ( 0, 0, 0 );
		}
	}
	if (count ( $conf [BossDef::BOSS_DAY_START_TIMES] ) != count ( $conf [BossDef::BOSS_DAY_END_TIMES] )) {
		trigger_error ( 'BOSS_DAY_START_TIMES BOSS_DAY_END_TIMES not equal' );
	}
	
	$conf[BossDef::SUPERHERO][] = $conf[BossDef::SUPERHERO_GOOD];
	$conf[BossDef::SUPERHERO][] = $conf[BossDef::SUPERHERO_BETTER];
	$conf[BossDef::SUPERHERO][] = $conf[BossDef::SUPERHERO_BEST];
	
	unset( $conf[BossDef::SUPERHERO_GOOD] );
	unset( $conf[BossDef::SUPERHERO_BETTER] );
	unset( $conf[BossDef::SUPERHERO_BEST] );
	
	// 所有数据
	$conflist [$conf [BossDef::BOSS_ID]] = $conf;
}
var_dump ( $conflist );
fclose ( $file );

// 输出文件
$file = fopen ( $argv [2] . '/' . $outFileName, "w" );
if ($file == FALSE) {
	echo $argv [2] . '/' . $outFileName . " open failed! exit!\n";
	exit ();
}
fwrite ( $file, serialize ( $conflist ) );
fclose ( $file );
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */