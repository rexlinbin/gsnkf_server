<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Destiny.script.php 82637 2013-12-23 14:40:06Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/script/Destiny.script.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-12-23 14:40:06 +0000 (Mon, 23 Dec 2013) $
 * @version $Revision: 82637 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'destiny.csv';
$outFileName = 'DESTINY';

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
        'id'=>$index++,                  //天命Id
        'name'    =>$index++,            //天命名称
		'addAttr' => $index++,		     //天命附加属性
		'needCopyScore'=>$index++,       //消耗副本得分
		'preId' => $index++,            //前置天命Id
		'afterId' => $index++,            //后置天命Id
		'breakId' => $index++,            //突破表Id
		'destinyColor'=>$index++,         //天命颜色
		'spendSilver'=>$index++,          //激活消耗银币   
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
$firstConst = 0;
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
	        case 'name':
	            break;
	        case 'addAttr':
	        	$conf[$key] = array();
	            $arrAttr = str2Array($data[$index], ',');
	            foreach($arrAttr as $index => $attrStr)
	            {
	                $attr = array2Int(str2Array($attrStr, '|'));
	                if(count($attr) != 2)
	                {
	                    trigger_error('error config in column addattr.');
	                }
	                if(!isset(PropertyKey::$MAP_CONF[$attr[0]]))
	                {
	                    trigger_error('can not add this attr index '.$attr[0]);
	                }
	                $conf[$key][PropertyKey::$MAP_CONF[$attr[0]]] = $attr[1];
	            }
	            break;
	        default:
	            $conf[$key] = intval($data[$index]);
	    }
	}
	if(isset($confList[ $conf['id'] ]))
	{
	    trigger_error("duplicate id ".$conf['id']);
	}
	$confList[ $conf['id'] ] = $conf;
	if(empty($conf['preId']))
	{
	    $firstConst = $conf['id'];
	}
}
fclose($file);
$curId = $firstConst;
$curScore = 0;
$num = 0;
while(TRUE)
{
    $curScore += $confList[$curId]['needCopyScore'];
    $confList[$curId]['needCopyScore'] = $curScore;
    $num ++;
    if(!empty($confList[$curId]['afterId']))
    {
        $curId = $confList[$curId]['afterId'];
    }
    else
    {
        break;
    }    
}
if(count($confList) != $num)
{
    trigger_error('the list is broken');
}
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */