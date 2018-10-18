<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AttrExtra.script.php 214645 2015-12-09 02:43:08Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/scripts/AttrExtra.script.php $
 * @author $Author: ShijieHan $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-12-09 02:43:08 +0000 (Wed, 09 Dec 2015) $
 * @version $Revision: 214645 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'secondfriends.csv';
$outFileName = 'SECOND_FRIEND';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$tag = array
(
		'id' => 0,
		'cost' => 1,
		'attr' => 2,
        'maxLv' => 8, //助战位最大强化等级
        'upAttr' => 9, //提升属性
        'extraAttr' => 10, //解锁属性
);

$config = array();
$file = fopen($argv[1] . "/$csvFile", 'r');
if (FALSE == $file)
{
	echo $argv[1] . "/{$csvFile} open failed! exit!\n";
	exit;
}

fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;

	$id = intval($data[$tag['id']]);
	
	$arrCost = array();
	foreach (explode(',', $data[$tag['cost']]) as $cost)
	{
		$detail = array_map('intval', explode('|', $cost));
		if (count($detail) != 3) 
		{
			trigger_error("error cost conf, count != 3\n");
		}
		
		$arrCost[] = $detail;
	}
	$config[$id]['cost'] = $arrCost;

    foreach(array('attr', 'upAttr') as $value)
    {
        $arrAddAttr = array();
        foreach (explode(',', $data[$tag[$value]]) as $attr)
        {
            $detail = array_map('intval', explode('|', $attr));
            if (count($detail) != 3)
            {
                trigger_error("error attr conf, count != 3\n");
            }
            if (!isset(PropertyKey::$MAP_CONF[$detail[1]]))
            {
                trigger_error("error attr conf, no map info of id" . $detail[1] . "\n");
            }
            $detail[1] = PropertyKey::$MAP_CONF[$detail[1]];
            $arrAddAttr[] = $detail;
        }
        $config[$id][$value] = $arrAddAttr;
    }

    $config[$id]['maxLv'] = intval($data[$tag['maxLv']]);

    $arrExtraAttr = array(); //解锁属性
    foreach(explode(',', $data[$tag['extraAttr']]) as $attr)
    {
        $detail = array_map('intval', explode('|', $attr));
        if (count($detail) != 3)
        {
            trigger_error("error attr conf, count != 3\n");
        }
        if (!isset(PropertyKey::$MAP_CONF[$detail[1]]))
        {
            trigger_error("error attr conf, no map info of id" . $detail[1] . "\n");
        }
        $detail[1] = PropertyKey::$MAP_CONF[$detail[1]];
        if(isset($arrExtraAttr[$detail[0]][$detail[1]]))
        {
            $arrExtraAttr[$detail[0]][$detail[1]] += $detail[2];
        }
        else
        {
            $arrExtraAttr[$detail[0]][$detail[1]] = $detail[2];
        }
    }
    ksort($arrExtraAttr);
    $config[$id]['extraAttr'] = $arrExtraAttr;

}
fclose($file);
var_dump($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */