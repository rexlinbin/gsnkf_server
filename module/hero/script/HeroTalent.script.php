<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroTalent.script.php 112296 2014-06-03 07:53:27Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/script/HeroTalent.script.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-03 07:53:27 +0000 (Tue, 03 Jun 2014) $
 * @version $Revision: 112296 $
 * @brief 
 *  
 **/


require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Show.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";


$csvFile = 'hero_refreshgift.csv';
$outFileName = 'HEROTALENT';

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
        'id'            =>          $index++,//id
        'templateId'    =>          $index++,//天赋模板id
        'name'          =>          $index++,//
        'profile'       =>            $index++ ,
        'addAttr'       =>            $index++ ,
        'addAwakeAbility'=>            $index++,
        'weight'         =>            $index++,//刷新权重
        'talentType'     =>        $index++,//能力类型
        'priority'       =>        $index++,//能力优先级
);

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrTalent = array();
$talent = array();
while(TRUE)
{
	$talent = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($arrConfKey as $key => $v)
	{
		switch($key)
		{		
		    case 'templateId':	
			case 'name':
			case 'profile':
				break;
			case 'addAttr':
			    $arrAttr = str2Array($line[$v], ',');
			    $talent[$key] = array();
			    foreach($arrAttr as $attr)
			    {
			        $attrInfo = array2Int(str2Array($attr, '|'));
			        if(count($attrInfo) != 2)
			        {
			            trigger_error('addattr config error.');
			        }
			        $talent[$key][PropertyKey::$MAP_CONF[$attrInfo[0]]] = $attrInfo[1];
			    }
			    break;
			case 'addAwakeAbility':
			    $talent[$key] = array2Int(str2Array($line[$v], '|'));
				break;
			default:
			    $talent[$key] = intval($line[$v]);
		}
	}
	
	$arrTalent[$talent['id']] = $talent;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrTalent));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */