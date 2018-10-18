<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassBase.script.php 149420 2014-12-26 14:36:44Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/script/PassBase.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-26 14:36:44 +0000 (Fri, 26 Dec 2014) $
 * @version $Revision: 149420 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'overcome.csv';
$outFileName = 'PASS_BASE';

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
		'baseId'			=> $index++,
	//	'nextBaseId'		=> $index++,
		'gradeArr'		=> $index++,
		'basePassRewardArr' => $index++,
		'opponentRangeArr'	=> $index++,
		'simpleAdaptArr'	=> $index++,
		'normalAdaptArr'	=> $index++,
		'difficultAdaptArr'	=> $index++,
		'permenentRewardId'	=> $index++,
		'freeChestNumArr'	=> $index++,
		'freeChestRewardArr' => $index++,
		'goldChestNumArr'	=> $index++,
		'goldChestRewardArr' => $index++,
		'chestNeedGoldArr'	=> $index++,
		'buffArr'			=> $index++,
		'recoverHpRageArr'	=> $index++,
		'switch'			=> $index++,
					
);

$arrayOne = array( 'permenentRewardId', 'freeChestNumArr', 'goldChestNumArr' );
$arrayTwo = array( 'recoverHpRageArr', );
$arrayThree = array( 
		'gradeArr', 'basePassRewardArr', 'opponentRangeArr', 'simpleAdaptArr',
		'normalAdaptArr', 'difficultAdaptArr', 'chestNeedGoldArr', 'buffArr', 
		'freeChestRewardArr', 'goldChestRewardArr',
);

$confList = array();
while (true)
{
	$conf = array();
	$data = fgetcsv($file);
	if( empty( $data ) || empty( $data[0] ) )
	{
		break;
	}
	foreach ( $keyArr as $keyOne => $index )
	{
		if ( in_array( $keyOne , $arrayOne) )
		{
			if( empty( $data[ $index ] ) )
			{
				$conf[ $keyOne ] = array();
			}
			else 
			{
				$conf[ $keyOne ] = array_map( 'intval' , explode( ',' , $data[ $index ]));
			}
			
		}
		elseif( in_array( $keyOne , $arrayTwo) )
		{
			if( empty( $data[ $index ] ) )
			{
				$conf[ $keyOne ] = array();
			}
			else
			{
				$conf[ $keyOne ] = array_map( 'intval' , explode( '|' , $data[ $index ]));
			}
			
		}
		elseif ( in_array( $keyOne , $arrayThree) )
		{
			if( empty( $data[ $index ] ) )
			{
				$conf[ $keyOne ] = array();
			}
			else 
			{
				$tmpConf = explode( ',' , $data[ $index ]);
				foreach ( $tmpConf as $keyTwo => $val )
				{
					if ( empty( $val ) )
					{
						$tmpConf[ $keyTwo ] = array();
					}
					else
					{
						$tmpConf[ $keyTwo ] = array_map( 'intval' , explode( '|' , $val ));
					}
				}
				$conf[ $keyOne ] = $tmpConf;
			}
			
		}
		else
		{
			$conf[ $keyOne ] = intval( $data[ $index ] );
		}
	}

	$confList[ $conf[ 'baseId' ] ] = $conf;
}

var_dump($confList);
foreach ( $confList as $baseId => $baseInfo )
{
	foreach ( $baseInfo as $key => $oneKeyInfo )
	{
		switch ( $key )
		{
			case 'gradeArr' :
				$tmp = array();
				foreach ( $oneKeyInfo as $oneInfo1 )
				{
					$tmp[$oneInfo1[0]] = array( $oneInfo1[1], $oneInfo1[2] );
				}
				$confList[$baseId][$key] = $tmp;
				break;
			case 'basePassRewardArr' :
			case 'opponentRangeArr' :
				$tmp = array();
				foreach ( $oneKeyInfo as $oneInfo2 )
				{
					$tmp[$oneInfo2[0]][] = $oneInfo2[1];
					$tmp[$oneInfo2[0]][] = $oneInfo2[2];
				}
				$confList[$baseId][$key] = $tmp;
				break;
			case 'chestNeedGoldArr' :
				$tmp = array();
				foreach ( $oneKeyInfo as $oneInfo3 )
				{
					$tmp[$oneInfo3[0]] = $oneInfo3[1];
				}
				$confList[$baseId][$key] = $tmp;
				break;
		}
		
	}
	
	$tmp = array();
	$tmp[1] = $baseInfo['simpleAdaptArr'];
	$tmp[2] = $baseInfo['normalAdaptArr'];
	$tmp[3] = $baseInfo['difficultAdaptArr'];
	unset( $confList[$baseId]['simpleAdaptArr'] );
	unset( $confList[$baseId]['normalAdaptArr'] );
	unset( $confList[$baseId]['difficultAdaptArr'] );
	foreach ( $tmp as $degree => $adptArr )
	{
		$tmptmp = array();
		foreach ( $adptArr as $one => $oneInfo )
		{
			if( !isset( $tmptmp[$oneInfo[0]] ) )
			{
				$tmptmp[$oneInfo[0]] = 0;
			}
			$tmptmp[$oneInfo[0]] += $oneInfo[1];
		}
		$tmp[$degree] = $tmptmp;
	}
	$confList[$baseId]['adaptArr'] = $tmp;
	
	$tmp = array();
	foreach ( $baseInfo['freeChestNumArr'] as $numIndex => $num )
	{
		var_dump('now next');
		var_dump($baseId);
		var_dump($baseInfo);
		$tmp[$numIndex] = array( $num, $baseInfo['freeChestRewardArr'][$numIndex] );
	}
	$confList[$baseId]['freeChestArr'] = $tmp;
	
	$tmp = array();
	foreach ( $baseInfo['goldChestNumArr'] as $numIndex => $num )
	{
		$tmp[$numIndex] = array( $num, $baseInfo['goldChestRewardArr'][$numIndex] );
	}
	$confList[$baseId]['goldChestArr'] = $tmp;
	
	unset( $confList[$baseId]['freeChestNumArr'] );
	unset( $confList[$baseId]['goldChestNumArr'] );
	unset( $confList[$baseId]['freeChestRewardArr'] );
	unset( $confList[$baseId]['goldChestRewardArr'] );
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