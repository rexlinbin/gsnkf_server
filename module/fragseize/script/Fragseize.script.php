<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Fragseize.script.php 207554 2015-11-05 09:38:07Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/script/Fragseize.script.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-05 09:38:07 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207554 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'loot.csv';
$outFileName = 'LOOT';

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
		'id'			=> $index++,
		'levelOffset'	=> $index++,
		'reizeMaxRio'	=> $index++,
		'reizeMinRio'	=> $index++,
		'reizeLvRio'	=> $index++,
		'NPCLevels'		=> $index++,
		'NPCArmys'		=> $index++,
		'expBaseWin'	=> $index++,
		'expBaseLose'	=> $index++,
		'silverBaseWin'	=> $index++,
		'silverBaseLose'=> $index++,
		'stamina'		=> $index++,
		'insistTrea'	=> $index++,
		'flopId'		=> $index++,
		'whiteItem'		=> ($index+=3)-1,
		'whiteGold'		=> $index++,
		'whiteOnce'		=> $index++,
		'whiteLimit'	=> $index++,
		'peaceTime'		=> $index++,
		'winSilver'		=> $index++,//胜利获得银币上限
		'loseSilver'	=> $index++,//失败获得银币上限
);

$arrayOne = array( 'NPCLevels', 'insistTrea' );
$arrayTwo = array( 'whiteItem', 'peaceTime','NPCArmys' );

$confList = array();
$conf = array();
//只有一条记录
	$data = fgetcsv($file);
	
	if ( empty( $data )||empty( $data[ 0 ] ) )
	{
		break;
	}
	
	foreach ( $keyArr as $keyOne => $index )
	{
		if ( in_array( $keyOne , $arrayOne) )
		{
			$confList[ $keyOne ] = array_map( 'intval' , explode( ',' , $data[ $index ]));
		}
		elseif ( in_array( $keyOne , $arrayTwo) )
		{
			$tmpConf = array();
			$tmpConf = explode( ',' , $data[ $index ]);
			foreach ( $tmpConf as $keyTwo => $val )
			{
				if ( empty( $val ) )
				{
					$tmpConf[ $keyTwo ] = array();
				}
				elseif( $keyTwo == 'peaceTime' ) 
				{
					$tmpConf[ $keyTwo ] = array_map( 'strval' , explode( '|' , $val ));
				}
				else 
				{
					$tmpConf[ $keyTwo ] = array_map( 'intval' , explode( '|' , $val ));
				}
			}
			$confList[ $keyOne ] = $tmpConf;
		}
		else 
		{
			$confList[ $keyOne ] = intval( $data[ $index ] );
		}
	}
	
	foreach ( $confList[ 'NPCLevels' ] as $key => $level )
	{
		$confArmy[ $key ][ 'level' ] = $level;
		$confArmy[ $key ][ 'armys' ] = $confList['NPCArmys'][ $key ];
		$confList[ 'LvAndNpc' ][] = $confArmy[ $key ];
	}
	unset( $confList[ 'NPCLevels' ] );
	unset( $confList[ 'NPCArmys' ] );

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