<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LevelUpExp.script.php 66618 2013-09-26 12:29:09Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/script/LevelUpExp.script.php $
 * @author $Author: TiantianZhang $(wuqilin@babeltime.com)
 * @date $Date: 2013-09-26 12:29:09 +0000 (Thu, 26 Sep 2013) $
 * @version $Revision: 66618 $
 * @brief 
 *  
 **/


require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'level_up_exp.csv';
$outFileName = 'EXP_TBL';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}

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
	
	$id = $data[0];
	$sum = 0;
	//从第二列开始是升级到1级的配置
	for( $i = 2; $i < count($data); $i++)
	{
		$sum += intval($data[$i]);
		$conf[$i-1] = $sum;
	}
	$confList[$id] = $conf;

}
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */