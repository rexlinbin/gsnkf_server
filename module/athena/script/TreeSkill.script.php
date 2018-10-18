<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: TreeSkill.script.php 164520 2015-03-31 09:16:43Z ShijieHan $$
 *
 **************************************************************************/

/**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/script/TreeSkill.script.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-03-31 09:16:43 +0000 (Tue, 31 Mar 2015) $$
 * @version $$Revision: 164520 $$
 * @brief
 *
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Athena.def.php";

$csvFile = "tree_skill.csv";
$outFileName = "ATHENA_TREE_SKILL";

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
    AthenaCsvDef::AFFIX_GROW => ($index+=3) - 1,
    AthenaCsvDef::EX_SKILL => ($index+=2) - 1,
    AthenaCsvDef::MAX_LEVEL => ($index+=3) - 1,
    AthenaCsvDef::UP_COST1 => $index++,
    AthenaCsvDef::UP_COST2 => $index++,
    AthenaCsvDef::UP_COST3 => $index++,
    AthenaCsvDef::UP_COST4 => $index++,
    AthenaCsvDef::UP_COST5 => $index++,
    AthenaCsvDef::UP_COST6 => $index++,
    AthenaCsvDef::UP_COST7 => $index++,
    AthenaCsvDef::UP_COST8 => $index++,
    AthenaCsvDef::UP_COST9 => $index++,
    AthenaCsvDef::UP_COST10 => $index++,
    AthenaCsvDef::UP_COST11 => $index++,
    AthenaCsvDef::UP_COST12 => $index++,
    AthenaCsvDef::UP_COST13 => $index++,
    AthenaCsvDef::UP_COST14 => $index++,
    AthenaCsvDef::UP_COST15 => $index++,
    AthenaCsvDef::UP_COST16 => $index++,
    AthenaCsvDef::UP_COST17 => $index++,
    AthenaCsvDef::UP_COST18 => $index++,
    AthenaCsvDef::UP_COST19 => $index++,
    AthenaCsvDef::UP_COST20 => $index++,
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
            case AthenaCsvDef::EX_SKILL:
                $conf[$key] = array2Int(str2Array($line[$index], '|'));
                break;
            case AthenaCsvDef::AFFIX_GROW:
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
            case AthenaCsvDef::UP_COST1:
            case AthenaCsvDef::UP_COST2:
            case AthenaCsvDef::UP_COST3:
            case AthenaCsvDef::UP_COST4:
            case AthenaCsvDef::UP_COST5:
            case AthenaCsvDef::UP_COST6:
            case AthenaCsvDef::UP_COST7:
            case AthenaCsvDef::UP_COST8:
            case AthenaCsvDef::UP_COST9:
            case AthenaCsvDef::UP_COST10:
            case AthenaCsvDef::UP_COST11:
            case AthenaCsvDef::UP_COST12:
            case AthenaCsvDef::UP_COST13:
            case AthenaCsvDef::UP_COST14:
            case AthenaCsvDef::UP_COST15:
            case AthenaCsvDef::UP_COST16:
            case AthenaCsvDef::UP_COST17:
            case AthenaCsvDef::UP_COST18:
            case AthenaCsvDef::UP_COST19:
            case AthenaCsvDef::UP_COST20:
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
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }

        if(strncmp($key, AthenaCsvDef::UP_COST, strlen(AthenaCsvDef::UP_COST)) == 0)
        {
            $lastV = substr($key, strlen(AthenaCsvDef::UP_COST), strlen($key) - strlen(AthenaCsvDef::UP_COST));
            $conf[AthenaCsvDef::UP_COST][intval($lastV) - 1] = $conf[$key];
            unset($conf[$key]);
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