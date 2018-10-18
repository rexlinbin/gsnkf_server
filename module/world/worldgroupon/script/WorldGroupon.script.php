<?php

require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) ) . "/def/WorldGroupon.def.php";

$csvFile = "kufu_groupPurchase.csv";
$outFileName = "WORLDGROUPON";

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!resolver.csv output\n";
    trigger_error ("input error parameters.");
}
/*
$index = 0;
$field_names = array(
    WorldGrouponCsvDef::ID => $index++,
    WorldGrouponCsvDef::DAY => $index++,
    WorldGrouponCsvDef::ITEM => $index++,
    WorldGrouponCsvDef::PRICE => $index++,
    WorldGrouponCsvDef::DISCOUNT => $index++,
    WorldGrouponCsvDef::NUM => $index++,
    WorldGrouponCsvDef::RETURN_RATE => $index++,
    WorldGrouponCsvDef::POINT_REWARD => $index++,
    WorldGrouponCsvDef::TIME_CFG => $index++,
    WorldGrouponCsvDef::COUPON_USE_RATE => $index++,
);
//读表
$file = fopen($argv[1]."/$csvFile", 'r');
//略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrConf = array();
$extra = array();
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
            case WorldGrouponCsvDef::DAY:
                $conf[$key] = array2Int(str2Array($line[$index], ','));
                break;
            case WorldGrouponCsvDef::ITEM:
                $conf[$key] = array2Int(str2Array($line[$index], '|'));
                break;
            case WorldGrouponCsvDef::DISCOUNT:
            case WorldGrouponCsvDef::POINT_REWARD:
                $arrTmp = str2Array($line[$index], ',');
                if(empty($arrTmp))
                {
                    $conf[$key] = array();
                }
                else
                {
                    foreach($arrTmp as $k => $v)
                    {
                        $tmp = array2Int(str2Array($v, '|'));
                        if($key == WorldGrouponCsvDef::POINT_REWARD)
                        {
                            $conf[$key][$tmp[0]][] = array($tmp[1], $tmp[2], $tmp[3]);
                        }
                        else
                        {
                            $conf[$key][$tmp[0]] = $tmp[1];
                        }
                    }
                }
                break;
            case WorldGrouponCsvDef::TIME_CFG:
                $conf[$key] = array2Int(str2Array($line[$index], '|'));
                break;
            default:
                $conf[$key] = intval($line[$index]);
                break;
        }
    }
    foreach($conf[WorldGrouponCsvDef::DAY] as $day)
    {
        $extra[WorldGrouponCsvDef::DAY][$day][] = $conf[WorldGrouponCsvDef::ID];
    }
    unset($conf[WorldGrouponCsvDef::DAY]);
    if(!empty($conf[WorldGrouponCsvDef::POINT_REWARD]))
    {
        $extra[WorldGrouponCsvDef::POINT_REWARD] = $conf[WorldGrouponCsvDef::POINT_REWARD];
        unset($conf[WorldGrouponCsvDef::POINT_REWARD]);
    }
    if(!empty($conf[WorldGrouponCsvDef::TIME_CFG]))
    {
        $extra[WorldGrouponCsvDef::TIME_CFG] = $conf[WorldGrouponCsvDef::TIME_CFG];
        unset($conf[WorldGrouponCsvDef::TIME_CFG]);
    }
    $arrConf[WorldGrouponCsvDef::EXTRA] = $extra;
    $arrConf[WorldGrouponCsvDef::ARR_GOOD][$conf[WorldGrouponCsvDef::ID]] = $conf;
}*/

/*fclose($file);
//将内容写入文件
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrConf));
fclose($file);*/

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */