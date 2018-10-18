<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: hell_tower_level.script.php 254629 2016-08-03 13:14:35Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/script/hell_tower_level.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-08-03 13:14:35 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254629 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Util.class.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Tower.def.php";

$csvFile = 'nightmare_layer.csv';
$outFileName = 'HELL_TOWER_LEVEL';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!TOWER.csv output\n";
    trigger_error ("input error parameters.");
}

$ZERO = 0;
$confIndex = array(
    HellTowerLevelDef::ID => $ZERO,
    HellTowerLevelDef::REWARD => ( $ZERO += 3 ),
    HellTowerLevelDef::PASS_OPEN => ++$ZERO,
    HellTowerLevelDef::NEED_LEVEL => ++$ZERO,
    HellTowerLevelDef::BASE_ID => ++$ZERO,
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();

while ( TRUE )
{
    $data = fgetcsv($file);
    
    if ( empty( $data ) || empty( $data[0] ) )
    {
        break;
    }
    
    $conf = array();
    
    foreach ( $confIndex as $key => $index )
    {
        switch ( $key )
        {
            case HellTowerLevelDef::REWARD:
                $conf[$key] = array();
                
                $arrTmp = Util::str2Array( $data[$index], ',' );
                foreach ( $arrTmp as $value )
                {
                    $conf[$key][] = array_map('intval', Util::str2Array($value, '|'));
                }
                break;
            default:
                $conf[$key] = intval( $data[$index] );
                break;
        }
        
        $confList[$conf[HellTowerLevelDef::ID]] = $conf;
    }
}

fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */