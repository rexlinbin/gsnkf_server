<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Dragon.def.php";

$csvFile = 'secondfriends_levelup.csv';
$outFileName = 'SECOND_FRIENDS_LVUP';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$ZERO = 0;
$fields = array(
    'id' => $ZERO++,
    'costSilver' => $ZERO++,
    'costItem' => $ZERO++,
);

$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$output = array();

while(true)
{
    $line = fgetcsv($file);
    if(empty($line) || empty($line[0]))
    {
        break;
    }

    $conf = array();
    foreach($fields as $key => $val)
    {
        switch($key)
        {
            case 'costItem':
                foreach(str2Array($line[$val], ',') as $k => $v)
                {
                    $tmp = array2Int(str2Array($v, '|'));
                    $conf[$key][$tmp[0]] = $tmp[1];
                }
                break;
            default:
                $conf[$key] = intval($line[$val]);
                break;
        }
    }
    $output[$conf['id']] = $conf;
}

fclose($file);
//将内容写入文件中
$file = fopen($argv[2]. "/$outFileName", 'w');
fwrite($file, serialize($output));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */