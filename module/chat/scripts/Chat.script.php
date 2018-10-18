<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Chat.script.php 65282 2013-09-18 03:20:05Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/scripts/Chat.script.php $
 * @author $Author: ShiyuZhang $(jiangzhichao@babeltime.com)
 * @date $Date: 2013-09-18 03:20:05 +0000 (Wed, 18 Sep 2013) $
 * @version $Revision: 65282 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'chat_interface.csv';
$outFileName = 'CHAT';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:!chat_interface.csv output\n" );	
}

$index = 0;
//数据对应表
$name = array (
	'id' => $index,						// ID
	'need_level' => ++$index,			//需要等级
	'need_vip' => ++$index,				//需要vip等级
	'need_gold' => ++$index,			//消耗金币
	'count_limit' => ++$index,			// 信息可以输入的文字数量
	'interval_time' => ++$index,		// 信息显示的时间（只有广播有）
	'cost_item' => ++$index,			// 发送广播消耗的物品和数量
);

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$ret = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data) || empty( $data[0] ))
		break;

	$array = array();
	foreach ( $name as $key => $v )
	{
		if($key == 'cost_item')
		{
			if( !empty( $data[ $v ] ) )
			{
				$ary = array_map( 'intval' , explode("|", $data[$v]) );
				$array[$key] = array( $ary[0] => $ary[1] );
			}
			else 
			{
				$array[$key] = array();
			}
		}
		else 
		{
			$array[$key] = intval($data[$v]);
		}
	}
	$ret[ intval( $data[ 0 ] ) ] = $array;
}
print_r($ret);

fclose($file); //var_dump($salary);

$file = fopen($argv[2].'/'.$outFileName, "w");
fwrite($file, serialize($ret));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */