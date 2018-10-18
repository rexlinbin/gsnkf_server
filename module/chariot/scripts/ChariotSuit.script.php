<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotSuit.script.php 252423 2016-07-19 08:49:40Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/scripts/ChariotSuit.script.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-19 08:49:40 +0000 (Tue, 19 Jul 2016) $
 * @version $Revision: 252423 $
 * @brief 
 *  
 **/
$inFileName = 'suit_warcar.csv';
$outFileName = 'CHARIOTSUIT';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $inFileName $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$inputDir = $argv[1];
$outputDir = $argv[2];

$NUM = 0;

$arrConfKey = array (
		'id'=>$NUM,
		'suit_items'=>++$NUM,
		'suit_attr'=>++$NUM,
);


$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if ($key=='suit_items')
		{
			$suitItemArr=array();
			$tmp=explode(',', $data[$index]);
			foreach ($tmp as $id)
			{
				$suitItemArr[]=intval($id);
			}
			$conf[$key]=$suitItemArr;
		}
		elseif ($key=='suit_attr')
		{
			$suitAttr=array();
			$tmp=explode(',', $data[$index]);
			foreach ($tmp as $value)
			{
				$nextTmp=explode('|', $value);
				$suitAttr[intval($nextTmp[0])]=intval($nextTmp[1]);
			}
			$conf[$key]=$suitAttr;
		}
		else 
		{
			$conf[$key]=intval($data[$index]);
		}
	}

	$confList[$conf['id']] = $conf;
}

fclose($file);

print_r($confList);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */