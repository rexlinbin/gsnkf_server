<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Convert.script.php 86486 2014-01-13 13:17:35Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/script/Convert.script.php $
 * @author $Author: TiantianZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-01-13 13:17:35 +0000 (Mon, 13 Jan 2014) $
 * @version $Revision: 86486 $
 * @brief 
 *  
 **/



require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'hero_transfer.csv';
$outFileName = 'HERO_CONVERT';

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
        'evolveTblId'=>$index++,
        'tmplId'    =>$index++,
		'fromHtid' => $index++,		//进阶需要英雄ID
		'needLevel' => $index++,	//进阶需要英雄等级
		'toHtid' => $index++,		//进阶后英雄ID
		'needSilver' => $index++,		//进阶花费银币
		'arrNeedItem' => $index++,		//进阶需要物品ID和数量组
		'arrNeedHero' => $index++,		//进阶需要卡牌等级和ID
		'needBeauty' => $index++,		//主角卡牌进阶需要红颜数量
		'needMasterLv'=>$index++,        //进阶需要主角武将等级
			
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
		if(preg_match( '/^arr[a-zA-Z]*$/' ,$key ))
		{
			if(empty($data[$index]))
			{
				$conf[$key] = array();
				continue;
			}
			$arr = str2Array($data[$index], ',');
			$conf[$key] = array();
			foreach( $arr as $value )
			{
				$conf[$key][] = array2Int( str2Array($value, '|') );
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	    switch($key)
	    {
	        case 'arrNeedItem':
	            $arrItem = $conf[$key];
	            $arrNewItem = array();
	            foreach($arrItem as $index => $itemConf)
	            {
	                $itemTmplId = $itemConf[0];
	                $itemNum = $itemConf[1];
	                if(!isset($arrNewItem[$itemTmplId]))
	                {
	                    $arrNewItem[$itemTmplId] = 0;
	                }
	                $arrNewItem[$itemTmplId] += $itemNum;
	            }
	            unset($conf[$key]);
	            $conf[$key] = $arrNewItem;
	            break;
	    }
	}
    //判断arrNeedHero有没有重复的htid
    $arrNeedHero    =    $conf['arrNeedHero'];
    for($i=0;$i<count($arrNeedHero)-1;$i++)
    {
        for($j=$i+1;$j<count($arrNeedHero);$j++)
        {
            if($arrNeedHero[$i][0] == $arrNeedHero[$j][0])
            {
                trigger_error('need two hero with same htid.confinfo %s.'.$conf['fromHtid']);
            }
        }
    }
	$confList[ $conf['evolveTblId'] ] = $conf;
}
fclose($file);


var_dump($confList);


//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */