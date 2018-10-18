<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossRward.script.php 85184 2014-01-07 08:15:01Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/script/BossRward.script.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2014-01-07 08:15:01 +0000 (Tue, 07 Jan 2014) $
 * @version $Revision: 85184 $
 * @brief
 *
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Boss.def.php";

$csvFile = 'worldboss_reward.csv';
$outFileName = 'BOSS_REWARD';

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

//数据对应表
$name = array (
BossDef::REWARD_ID							=>		0,
BossDef::REWARD_ORDER_LIST_NUM				=>		1,
);

$attributes = array(
	BossDef::REWARD_ORDER_LOW,
	BossDef::REWARD_ORDER_UP,
	BossDef::REWARD_INFO,
	BossDef::REWARD_DROP_TEMPLATE_ID,
);
$attributes_num = count($attributes);


$boss_reward = array();
while ( TRUE )
{
	//得到数据
	$data = fgetcsv($file);
	if ( empty($data) || $data[0] === NULL )
		break;

	$array = array();
	foreach ( $name as $key => $v )
	{
		$array[$key] = $data[$v];
		//如果是数字,则intval
		if ( is_numeric($array[$key]) || empty($array[$key]) )
		{
			$array[$key] = intval($array[$key]);
		}
	}

	$order_list = array();
	for ( $i = 0; $i < $array[BossDef::REWARD_ORDER_LIST_NUM]; $i++ )
	{
		$order_data = array();

		for ( $k = 0; $k < $attributes_num; $k++ )
		{
			$order_data[$attributes[$k]] = intval($data[$name[BossDef::REWARD_ORDER_LIST_NUM]+$i*$attributes_num+$k+1]);
// 			if ( $attributes[$k] == BossDef::REWARD_DROP_TEMPLATE_ID )
// 			{
// 				if ( empty($data[$name[BossDef::REWARD_ORDER_LIST_NUM]+$i*$attributes_num+$k+1]) )
// 				{
// 					$order_data[$attributes[$k]] = array();
// 				}
// 				else
// 				{
// 					$order_data[$attributes[$k]] = explode('|', $data[$name[BossDef::REWARD_ORDER_LIST_NUM]+$i*$attributes_num+$k+1]);
// 				}
// 			}
			if ( $attributes[$k] == BossDef::REWARD_INFO )
			{
				if ( empty($data[$name[BossDef::REWARD_ORDER_LIST_NUM]+$i*$attributes_num+$k+1]) )
				{
					$order_data[$attributes[$k]] = array();
				}
				else 
				{
					$order_data[$attributes[$k]] = explode(',', $data[$name[BossDef::REWARD_ORDER_LIST_NUM]+$i*$attributes_num+$k+1]);
					if ( !empty( $order_data[$attributes[$k]] ) )
					{
						foreach ( $order_data[$attributes[$k]]  as $index => $val  )
						{
							$oneRewarArr= array_map( 'intval' , explode( '|' , $val ));
							
							if ( $oneRewarArr[1] == 0 )
							{
								$standardArr = array('type' =>$oneRewarArr[0], 'val' => $oneRewarArr[2] );
							}
							else 
							{
								$standardArr = array('type' => $oneRewarArr[0], 'val' => array( $oneRewarArr[1],$oneRewarArr[2] ));
							}
							$order_data[$attributes[$k]][$index] =  $standardArr;
						}
					}
				}
			} 
		}

		$order_list[] = $order_data;
	}

	$array[BossDef::REWARD_ORDER_LIST] = $order_list;

	$boss_reward[$array[BossDef::REWARD_ID]] = $array;
}
var_dump( $boss_reward );
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($boss_reward));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */