#!/usr/bin/env php
<?php
/***************************************************************************
 *
 * Copyright (c) 2014- babeltime.com, Inc. All Rights Reserved
 * $Id: readherocopy.script.php 103795 2014-04-24 11:28:19Z QiangHuang $
 * Created Time: 2014年04月22日 星期二 22时15分28秒
 *
 ***************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/script/readherocopy.script.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-04-24 11:28:19 +0000 (Thu, 24 Apr 2014) $
 * @version $Revision: 103795 $
 * @brief
 *
 **/
require_once('/home/pirate/rpcfw/def/HCopy.def.php');
/*
require_once("confparser.php");

$scheme = array(
    "objects" => array(
        "HeroCopy" => array(
            array("name" => "id", "type" => "int"),
            array("name" => "require_htid", "type" => "int"),
            array("name" => "ignore1", "type" => "string", "ignore" => "true"),
            array("name" => "ignore2", "type" => "string", "ignore" => "true"),
            array("name" => "ignore3", "type" => "string", "ignore" => "true"),
            array("name" => "arr_baseid", "type" => "vector", "value" => "int", "read_size" => "true"),
            array("name" => "ignore4", "type" => "string", "ignore" => "true"),
            array("name" => "ignore5", "type" => "string", "ignore" => "true"),
        ),
    ),
);

try {
    if($argc != 2) {
        print_r("usage: php testdata_transfer.php [csv data file]\n");
        print_r("e.g:   php testdata_transfer.php data.cvs\n");
        exit(-1);
    }
    $s = new QParser($scheme, true);
    $s->load_config($argv[1], 2);
    $d = $s->read_type("entry", "id", "HeroCopy");
    $d[1]["max_finish_num"] = 1;
    $f = fopen("HEROCOPY", "w");
    fwrite($f, serialize($d));
    fclose($f);
    var_dump($d);
}
catch(Exception $e) {
    print_r("####### error ######\n");
    print_r($e->getMessage());
}
 */

$inFileName = "hero_copy.csv";
$outFilename = "HEROCOPY";

if(isset($argv[1]) && $argv[1] == '-h')
{
    exit("usage: $inFileName $outFilename\n");
}

if($argc < 3)
{
    trigger_error("Please input enough arguments:inputDir && outputDir!\n");
}

$inDir = $argv[1];
$outDir = $argv[2];

$f = fopen("$inDir/$inFileName", "r");
if(!$f)
{
    print_r("open fail\n");
    exit(-1);
}
fgetcsv($f);
fgetcsv($f);

$copys = array();
while(true)
{
    $line = fgetcsv($f);
    if(empty($line)) break;
    $copy = array(
        HCopyDef::ID => intval($line[0]),
        HCopyDef::REQUIRE_HTID => intval($line[1]),
        HCopyDef::ARR_BASEID => array(),
        HCopyDef::MAX_FINISH_NUM => intval($line[18]),
    );
    $BASEID_OFFSET = 6;
    for($i = 0 ; $i < intval($line[$BASEID_OFFSET - 1]) ; $i++)
    {
        array_push($copy[HCopyDef::ARR_BASEID], intval($line[$BASEID_OFFSET + $i]));
    }
    $copys[$copy[HCopyDef::ID]] = $copy;
}
//var_dump($copys);

$outf = fopen("$outDir/$outFilename", "w");
fwrite($outf, serialize($copys));
fclose($outf);

