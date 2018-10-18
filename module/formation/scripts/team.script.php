<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: team.script.php 62600 2013-09-03 03:27:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/team.script.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-03 03:27:14 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62600 $
 * @brief 
 *  
 **/


require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
$csvFile = 'team.csv';
$outFileName = 'TEAM';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFileName\n");
}
if ( $argc < 3 )
{
	echo "Please input enough arguments:!team.csv output\n";
	trigger_error( "Please input enough arguments:!{$csvFile}\n" );	
}

$ZERO = 0;
/**
 * 
 * 	怪物小队ID 
	怪物小队模板名称 
	怪物小队显示名称 
	怪物小队显示等级 
	怪物小队显示阵型 
	怪物小队类型 
	阵型ID 
	阵型等级 
	位置怪物ID 
	bossID

 */
$field_names = array(
		'aid' => $ZERO,  				//teamid
		't_name' => ++$ZERO, 			//怪物小队模板名称
		'display_name' => ++$ZERO, 		//怪物小队显示名称 
		'display_lv'=> ++$ZERO, 		//怪物小队显示等级
		'fmt_name' => ++$ZERO,			// 怪物小队显示阵型
		'type' => ++$ZERO,				// 怪物小队类型（1:怪物；2：NPC。）
		'fid' => ++$ZERO,				// 阵型ID 
		'fmtLevel' => ++$ZERO,			// 阵型等级
		'fmt' =>	++$ZERO,			// 位置怪物ID
		'boss_id'=> ++$ZERO				//bossID
);


$file = fopen($argv[1].'/'.$csvFile, 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$teams = array();
$team = array();
while(TRUE)
{
	$team = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{
		switch($key)
		{
			case 't_name':
			case 'display_name':
			case 'fmt_name':
				break;
			case 'aid':
			case 'display_lv':
			case 'type':
			case 'fid':
			case 'fmtLevel':
			case 'boss_id':
				$team[$key] = intval($line[$v]);
				break;
			case 'fmt':				
				//去掉换行
				echo $line[$v]."\n";
				$str = str_replace(array("\r", "\n"), "", $line[$v]);
				//去掉末尾字符","
				if ($str[strlen($str)-1] == ',')
				{
					$str = substr($str, 0, strlen($str)-1);
				}				
				$fmt = explode(',', $str);
				if(count($fmt) < 6)
				{
					echo "error fmt data,it must have 6 positon.";
					trigger_error( "error fmt data:{$fmt}\n" );
				}
				$team['fmt']	=	$fmt;
		}
	}
	$teams[$team['aid']] = $team;
}
fclose($file);
//将内容写入TEAM文件中
$file = fopen($argv[2].'/'.$outFileName, 'w');
fwrite($file, serialize($teams));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */