<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Achieve.script.php 145552 2014-12-11 10:41:38Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/script/Achieve.script.php $
 * @author $Author: ShijieHan $(wuqilin@babeltime.com)
 * @date $Date: 2014-12-11 10:41:38 +0000 (Thu, 11 Dec 2014) $
 * @version $Revision: 145552 $
 * @brief 
 *  
 **/



require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";


$csvFile = 'achieve.csv';
$outFileName = 'ACHIEVE';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error("Please input enough arguments:!{$csvFile}\n" );
}

$index = 5;
$arrConfKey = array(
		'type' => $index++,			//分类
		'arrCond' => $index++,		//完成条件
		'arrAddAttr' => $index++,	//属性加成
		'staminaMaxNum' => ($index+=8)-1,//增加耐力上限
        'executionMaxNum' => $index++,
);

$arrKeyV1 = array('arrCond');
$arrKeyV2 = array('arrAddAttr');

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
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$id = $data[0];
	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if( in_array($key, $arrKeyV1, TRUE)  )
		{
			$conf[$key] = array2Int( str2array($data[$index],'|') );
		}
		else if( in_array($key, $arrKeyV2, TRUE)  )
		{
			$arrV2 = str2array($data[$index],',');
			$conf[$key] = array();
			foreach ($arrV2 as $value)
			{
				$arrV1 = array2Int(str2Array($value, '|'));
				if(empty($arrV1))
				{
					continue;
				}
				if(count($arrV1) != 2)
				{
					trigger_error("invalid config. id:$id, key:$key, index:$index");
				}
				$conf[$key][ $arrV1[0] ] = $arrV1[1];
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}

	$confList[$id] = $conf;
}
fclose($file);

var_dump($confList);


//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */