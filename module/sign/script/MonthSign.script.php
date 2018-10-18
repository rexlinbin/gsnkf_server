<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthSign.script.php 136659 2014-10-17 11:09:05Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/script/MonthSign.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-17 11:09:05 +0000 (Fri, 17 Oct 2014) $
 * @version $Revision: 136659 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'month_sign.csv';
$outFileName = 'SIGN_MONTH';

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

$keyIndex = 0;
$key2Index = array(
		'id' => $keyIndex,
		'arrCyc' => $keyIndex += 2,
		'arrDoublePrize' => ++$keyIndex,
		'prizeNum' => ++$keyIndex,
		'arrDayPrize' => ++$keyIndex,
);

$arrOne = array('arrCyc');
$arrTwo = array('arrDoublePrize');
$special = array('arrDayPrize');

$data = fgetcsv($file);
$data = fgetcsv($file);
$confList = array();

while ( true )
{
	$data = fgetcsv( $file );
	if ( empty( $data )|| empty( $data[ 0 ] ))
	{
		break;
	}
	
	$conf = array();
	
	foreach ( $key2Index as $key => $index )
	{
		if( in_array( $key , $arrTwo) )
		{
			if( empty( $data[$index] ) )
			{
				$conf[$key] = array();
			}
			else
			{
				$arrTwoTmp = explode( ',' , $data[$index]);
				foreach ( $arrTwoTmp as $key1 => $info1 )
				{
					$arrTwoTmp[$key1] = array_map( 'intval' , explode( '|' , $info1));
				}
				if(  $key == 'arrDoublePrize'  )
				{
					$arrTwoTmpTmp = array();
					foreach ( $arrTwoTmp as $key2 => $info2 )
					{
						$arrTwoTmpTmp[ $info2[0] ] = $info2[1];
					}
					$arrTwoTmp = $arrTwoTmpTmp;
				}
				$conf[$key] = $arrTwoTmp;
			}
			
		}
		elseif( in_array( $key , $arrOne) )
		{
			if( empty( $data[$index] ) )
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array_map( 'intval', explode( ',' , $data[$index]));
			}
		}
		elseif( in_array( $key , $special) )
		{
			$specalConf = array();
			if( $key == 'arrDayPrize' )
			{
				if( !isset( $conf['prizeNum'] ) )
				{
					trigger_error( 'impossible, shit' );
				}
				for ( $day = 1; $day <= $conf['prizeNum']; $day++  )
				{
					if( !isset( $data[$day -1] ) || empty( $data[$day - 1 ] ) )
					{
						trigger_error( 'lack some prize' );
					}
					
					$oneDayPrizeArr = explode( ',' , $data[$index + $day -1]);
					foreach ( $oneDayPrizeArr as $onePiece => $onePiecePrize )
					{
						$oneDayPrizeArr[$onePiece] = array_map( 'intval' ,explode( '|' , $onePiecePrize) );
					}
					$specalConf[$day] = $oneDayPrizeArr;
				}
			}
			$conf[$key] = $specalConf;
		}
		else 
		{
			$conf[$key] = intval( $data[$index] );
		}
	}
	
	$arrCyc = $conf['arrCyc'];
	unset( $conf['arrCyc'] );
	$confList[$conf['id']] = $conf;
}

$confList['arrCyc'] = $arrCyc;

var_dump( $confList );
fclose($file);

//输出天数及奖励文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */