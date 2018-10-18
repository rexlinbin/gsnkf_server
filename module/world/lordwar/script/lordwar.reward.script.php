<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: lordwar.reward.script.php 127740 2014-08-18 11:39:48Z HaidongJia $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/script/lordwar.reward.script.php $
 * @author $Author: HaidongJia $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-18 11:39:48 +0000 (Mon, 18 Aug 2014) $
 * @version $Revision: 127740 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php"; 

$csvFile = 'kuafu_challengereward.csv';
$outFileName = 'LORDWAR_REWARD';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!kuafu_challengereward.csv output\n";
	trigger_error ("input error parameters.");	
}

$ZERO = 0;
$field_names = array(
		'id'=>$ZERO,
		'reward'=>$ZERO+3
);

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$lordwar_reward = array();
while(TRUE)
{
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{
	    $reward[$key] = intval($line[$v]);
	    if ( $key == 'reward' )
	    {
	    	$reward[$key] = array();
	    	$datas = explode(",", $line[$v]);
	    	foreach ( $datas as $data)
	    	{
	    		$array = explode("|", $data);
				foreach ( $array as $array_key => $array_value )
				{
					$array[$array_key] = intval($array_value);
				}
	    		$reward[$key][] = $array;	
	    	}
	    }
	}
	$lordwar_reward[$reward['id']] = $reward;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($lordwar_reward));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */