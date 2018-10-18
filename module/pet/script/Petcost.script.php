<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Petcost.script.php 241650 2016-05-09 09:22:24Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/script/Petcost.script.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-09 09:22:24 +0000 (Mon, 09 May 2016) $
 * @version $Revision: 241650 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'pet_cost.csv';
$outFileName = 'PET_COST';

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
		'itemFeedCriRto' => ++$index,
		'itemFeedWeightArr' => ++$index,
		'squandExp' => ++$index,
		'lockSkillCostArr' => ++$index,
		'squandSlotOpenArr' => ++$index,
		'initKeeperSlot' => ++$index,
		'keeperSlotNumPerOpen' => ++$index,
		'openKeeperGoldBase' => ++$index,
		'openKeeperGoldInc' => ++$index,
		'skillDarkCell' => $index += 2,
		'exchangeCostGold' => ++$index,
        'evolveFightForce' => ++$index,
        'potentialityFightForce' => ++$index,
		'openSquandSlotCostItems' => ++$index,
);
$arrTwo = array( 'itemFeedWeightArr' , 'lockSkillCostArr' , 'squandSlotOpenArr');
$arrOne = array('evolveFightForce','openSquandSlotCostItems');

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
		if( in_array( $key , $arrTwo ) )
		{
			if ( empty( $data[ $index ] ) )
			{
				$conf[ $key ] = array();
			}
			else
			{
				$conf[ $key ] = explode( ',' , $data[ $index ] );
				foreach ( $conf[ $key ] as $keyTwo => $val )
				{
					$conf[ $key ][ $keyTwo ] = array_map( 'intval' , explode( '|' , $val));
					if ($key == 'itemFeedWeightArr')
					{
						$arrNumWeight = array();
						$arrNumWeight[ 'ratio' ] = $conf[ $key ][ $keyTwo ][ 0 ];
						$arrNumWeight[ 'weight' ] = $conf[ $key ][ $keyTwo ][ 1 ];
							
						$conf[ $key ][ $keyTwo ] = $arrNumWeight;
					}
				}
			}
				
		}
        else if(in_array($key, $arrOne))
        {
            if(empty($data[$index]))
            {
                $conf[$key] = array();
            }
            else
            {
                $tmp = array_map('intval', explode('|', $data[$index]));
                ksort($tmp);
                $conf[ $key ] = $tmp;
            }
        }
		else
		{
			$conf[ $key ] = intval( $data[ $index ] );
		}
	}

	foreach ($conf as $keyafter => $valafter)
	{
		if ( $keyafter == 'lockSkillCostArr' )
		{   $tmp = array();
			foreach ( $valafter as $keyafter2 => $valafter2)
			{
				$tmp[$valafter2[0]] = $valafter2[1];
			}
			$conf[$keyafter] = $tmp; 
		}
	}
	$confList[ $data[ 0 ] ] = $conf;
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
