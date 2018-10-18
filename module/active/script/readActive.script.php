<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readActive.script.php 228099 2016-02-18 07:15:00Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/script/readActive.script.php $
 * @author $Author: BaoguoMeng $(tianming@babeltime.com)
 * @date $Date: 2016-02-18 07:15:00 +0000 (Thu, 18 Feb 2016) $
 * @version $Revision: 228099 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Active.def.php";

$inFileName = 'daytask.csv';
$outFileName = 'ACTIVE';

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

$index = 5;
//对应配置表键名
$arrConfKey = array (
		ActiveDef::ACTIVE_NUM 						=> $index++,				// 需要次数
		ActiveDef::ACTIVE_POINT						=> $index++,				// 积分数
		ActiveDef::ACTIVE_TYPE						=> $index++,				// 任务类型
		ActiveDef::ACTIVE_REWARD					=> ($index + 3),
		ActiveDef::ACTIVE_OPEN_LIMIT				=> ($index + 5),
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
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ($arrConfKey as $key => $index)
	{
		switch ($key)
		{
			case ActiveDef::ACTIVE_REWARD:
				$tempArr = str2Array($data[$index], ',');
				foreach ($tempArr as $k => $v)
				{
					if(!strpos($v, '|'))
					{
						trigger_error( "task:$data[0] invalid $key, need check\n" );
					}
					//TODO
					$conf[$key][] = array2Int(str2Array($v, '|'));
				}
				break;
			default:
				$conf[$key] = intval($data[$index]);
				break;
		}
	}

	$confList[$data[0]] = $conf;
}
fclose($file);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */