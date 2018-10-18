<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PetSkill.script.php 99672 2014-04-14 02:56:35Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/script/PetSkill.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-04-14 02:56:35 +0000 (Mon, 14 Apr 2014) $
 * @version $Revision: 99672 $
 * @brief 
 *  
 **/


$csvFile = 'pet_skill.csv';
$outFileName = 'PETSKILL';

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
		'skillId' => $index,
		'skillType' => ($index += 5),
		'specialNeed' => ++$index,
		'specialNeedCond' => ++$index,
		'skillValueIncArr' => ++$index,//TODO 文策划
		'productSkillArr' => ++$index,
		'productSkillCdArr' => ++$index,
		'normalSkillLvInc' => ++$index,
		'productSkillLvInc' => ++$index,
		'skillFight' => ++$index,
		'skillQuality' => ++$index,
		'skillWeight' => ++$index,
);

$arrOne = array( 'specialNeedCond' );
$arrTwo = array(  'skillValueIncArr','productSkillArr', 'productSkillCdArr');



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
		if ( in_array( $key , $arrOne ) )
		{
			if ( empty( $data[ $index ] ) )
			{
				$conf[ $key ] = array();
			}
			else 
			{
				$conf[ $key ] = array_map( 'intval' , explode( ',' , $data[ $index ] ));
			}
		}
		elseif( in_array( $key , $arrTwo ) )
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
				}
				if ( $key == 'productSkillCdArr' )
                {
                    $cdArrAfter = array();
                    foreach ( $conf[ $key ] as $oneIndex => $cdInfo )
                    {
                        $cdArrAfter[$cdInfo[0]] = $cdInfo[1];
                    }
					$conf[ $key ] = $cdArrAfter;
                }
			}

		}
		else
		{
			$conf[ $key ] = intval( $data[ $index ] );
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
