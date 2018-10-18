<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Formation.script.php 159587 2015-02-28 11:10:58Z BaoguoMeng $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/Formation.script.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-02-28 11:10:58 +0000 (Sat, 28 Feb 2015) $
 * @version $Revision: 159587 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";


$csvFile = 'formation.csv';
$outFileName = 'FORMATION';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error("Please input enough arguments:!{$csvFile}\n" );
}

$index = 1;
$arrConfKey = array(
		'arrOpenSeq' => $index++,		//阵型开启顺序
		'arrOpenNeedLevel' => $index++,	//阵型位置所需主角等级
		'initPos' => $index++,			//主角初始位置
		'arrNumNeedLevel' => $index++,  //等级对应阵容数量,二维
		'arrExtraNeedLevel' => $index++,//等级对应小伙伴个数
		'arrExtraNeedGold' => $index++,//开启小伙伴需要金币
		'arrExtraNeedCraft' => $index++, //开启小伙伴需要阵法
		'arrAttrExtraNeedCraft' => $index++, //开启属性小伙伴需要的等级
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );	
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) )
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if(preg_match( '/^arr[a-zA-Z]*$/' ,$key ))
		{
			if(empty($data[$index]))
			{
				$conf[$key] = array();
				continue;
			}
			$arr = str2array($data[$index]);
			if(is_numeric($arr[0]))
			{
				$conf[$key] = array2Int($arr);
			}
			else
			{
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					$ary = array2Int( str2array($value, '|') );
					if( $key == 'arrExtraNeedCraft' || $key == 'arrAttrExtraNeedCraft')
					{
						$conf[$key][$ary[0]] = $ary;
					}
					else 
					{
						$conf[$key][$ary[0]] = $ary[1];
					}
					
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}		
	}

	$confList[] = $conf;
}
fclose($file);

$confList = $confList[0];
print_r($confList);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */