<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Break.script.php 161139 2015-03-12 03:39:46Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/script/Break.script.php $
 * @author $Author: TiantianZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-03-12 03:39:46 +0000 (Thu, 12 Mar 2015) $
 * @version $Revision: 161139 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'break.csv';
$outFileName = 'BREAKTBL';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}


$index = 0;
$arrConfKey = array(
        'id'=>$index++,        //突破id
        'minEvolveLv'    =>$index++,//主角突破需要的进阶次数
		'needDestinyId' => $index++,		//主角突破需要激活该天命id才可进行
		'transformToHtid'=>$index++,//主角突破后对应的武将的id
		'isDevelop'     => $index++,//是否为进化
		
);


$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	echo $argv[1]."/{$csvFile} open failed! exit!\n";
	exit;
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
	
	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
	    switch($key)
	    {
	        case 'transformToHtid':
	            $arrConf = str2Array($data[$index], ',');
	            if(count($arrConf) != 2)
	            {
	                trigger_error('transformToHtid conf should be two');
	            }
	            foreach($arrConf as $index => $htidConf)
	            {
	                $tmp = array_map('intval', str2Array($htidConf, '|'));
	                $conf[$key][$tmp[0]] = $tmp[1];
	            }	            
	            break;
	        default:
	            $conf[$key] = intval($data[$index]);
	            break;
	    }
	    
	}
	$confList[ $conf['id'] ] = $conf;
}
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */