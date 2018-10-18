<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: Tree.script.php 218789 2015-12-30 13:55:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/script/Tree.script.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-12-30 13:55:17 +0000 (Wed, 30 Dec 2015) $$
 * @version $$Revision: 218789 $$
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Athena.def.php";

$csvFile = "tree.csv";
$outFileName = "ATHENA_TREE";

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}

$index = 0;
$field_names = array(
    AthenaCsvDef::ID => $index++,
    AthenaCsvDef::AFFIX => ($index+=2) - 1,
    AthenaCsvDef::SPECIAL_ATTR_ID => $index++,
    AthenaCsvDef::OPEN_NEED => ($index+=3)-1,
    AthenaCsvDef::TYPE => $index++,
    AthenaCsvDef::NEXT_TREE => $index++,
    AthenaCsvDef::NEXT_NEED_LEVEL => ($index+=3)-1,
    AthenaCsvDef::AWAKE_ABILITY_ID => ($index+=2)-1,
);
//读tree表
$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
while(true)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($field_names as $key => $index)
    {
        switch($key)
        {
            case AthenaCsvDef::AFFIX:
                $conf[$key] = array2Int(str2Array($line[$index], '|'));
                break;
            case AthenaCsvDef::OPEN_NEED:
                $arrTmp = str2Array($line[$index]);
                if(empty($arrTmp))
                {
                    $conf[$key] = array();
                }
                else
                {
                    foreach($arrTmp as $k => $v)
                    {
                        $conf[$key][$k] = array2Int(str2Array($v, '|'));
                    }
                }
                break;
            case AthenaCsvDef::SPECIAL_ATTR_ID:
                $arrTmp = str2Array($line[$index]);
                if(empty($arrTmp))
                {
                    $conf[$key] = array();
                }
                else
                {
                    foreach($arrTmp as $v)
                    {
                        $tmpX = array2Int(str2Array($v, '|'));
                        $conf[$key][$tmpX[0]] = array($tmpX[1], $tmpX[2]);
                    }
                }
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }
    }
    $arrConf[$conf[AthenaCsvDef::ID]] = $conf;
}
fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */