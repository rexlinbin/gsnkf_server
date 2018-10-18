<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 *
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(jinyang@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 数组，男主htid=>女主htid
 *
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'sex_change.csv';
$outFileName = 'SEX_CHANGE';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}
$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$arrConf = array();
while (true)
{
    $data = fgetcsv($file);
    if (empty($data) || empty($data[0]))
        break;

    //$id = $data[0];//id没啥用，不存到btstore。
    $arrConf[$data[1]] = $data[2];
}
fclose($file);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($arrConf));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */