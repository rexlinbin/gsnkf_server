<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UnionProfit.script.php 160891 2015-03-10 07:10:56Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/UnionProfit.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-03-10 07:10:56 +0000 (Tue, 10 Mar 2015) $
 * @version $Revision: 160891 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$inFileName = 'union_profit.csv';
$outFileName = 'UNION_PROFIT';

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

//数据对应表
$index = 1;
$arrConfKey = array (
		'arrCond'			=> $index++,
		'arrAttr'			=> $index++,
		'arrValue'			=> $index++,
		'starLevel'			=> $index+=2
);

$arrKeyV1 = array('arrAttr', 'arrValue');
$arrKeyV2 = array('starLevel');

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
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
		if( in_array($key, $arrKeyV1, true) )
		{
 			$conf[$key] = array2Int( str2array($data[$index]) );
		}
		else if( in_array($key, $arrKeyV2, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "union profit:$data[0] invalid $key, $value need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[1]] = $ary[0];
				}
			}
		}
		else
		{
			$arrCondStr = str2array($data[$index]);
			$arrCond = array();
			foreach($arrCondStr as $condStr)
			{
				$cond = array2Int( str2Array($condStr, '|') );
				if(count($cond) != 2 || !in_array($cond[0], array(1,2,3,4,5))  )
				{
					trigger_error("invalid union profit:$data[0] $condStr\n");
					exit();
				}
				$arrCond[$cond[0]][] = $cond[1];
			}
			$conf[$key] = $arrCond;
		}
	}
	
	if (count($conf['arrAttr']) != count($conf['arrValue']))
	{
		trigger_error("union profit:$data[0] invalid array $data[2] and $data[3], num is not the same");
	}
	$conf['arrAttr'] = array_combine($conf['arrAttr'], $conf['arrValue']);
	unset($conf['arrValue']);
	$confList[$data[0]] = $conf;
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