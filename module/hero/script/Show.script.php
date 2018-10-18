<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Show.script.php 219644 2016-01-06 03:56:35Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/script/Show.script.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-01-06 03:56:35 +0000 (Wed, 06 Jan 2016) $
 * @version $Revision: 219644 $
 * @brief 
 *  
 **/


require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Show.def.php";

$csvFile = 'show.csv';
$outFileName = 'SHOWS';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}


$index = 1;
$arrConfKey = array(
        'id'                                         =>          $index++,//id
        'arrShow'                                  =>          $index++,//图鉴id组
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);
$armBookIdBase = 100;
$treasureBookIdBase = 200;
$godWeaponBookIdBase = 300;
$tallyBookIdBase = 400;
$books = array();
$heroShow = array();
$armShow = array();
$treasShow = array();
$godWeaponShow = array();
$tallyShow = array();
while ( true )
{
    $data = fgetcsv($file);
    if ( empty($data) || empty($data[0]) )
    {
        break;
    }
    $id = intval($data[0]);
    $shows = array2Int(str2Array($data[1], ','));
    if(in_array($id, ShowDef::$ARR_HERO_SHOW_ID))
    {
        $heroShow = array_merge($heroShow,$shows);
    }
    else if(in_array($id, ShowDef::$ARR_ARM_SHOW_ID))
    {
        $armShow = array_merge($armShow,$shows);
    }
    else if(in_array($id, ShowDef::$ARR_TREASURE_SHOW_ID))
    {
        $treasShow = array_merge($treasShow,$shows);
    }
    else if(in_array($id, ShowDef::$ARR_GODWEAPON_SHOW_ID))
    {
    	$godWeaponShow = array_merge($godWeaponShow,$shows);
    }
    else if(in_array($id, ShowDef::$ARR_TALLY_SHOW_ID))
    {
    	$tallyShow = array_merge($tallyShow,$shows);
    }
}
$books[ShowDef::HERO_SHOW] = $heroShow;
$books[ShowDef::ARM_SHOW] = $armShow;
$books[ShowDef::TREASURE_SHOW] = $treasShow;
$books[ShowDef::GODWEAPON_SHOW] = $godWeaponShow;
$books[ShowDef::TALLY_SHOW] = $tallyShow;
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($books));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */