<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Dragon.def.php";


$csvFile = 'explore_long_event.csv';
$outFileName = 'DRAGONEVENT';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
    echo "Please input enough arguments:!COPY.csv output\n";
    trigger_error ("input error parameters.");
}

$ZERO = 0;
$fields = array(
    DragonEventCsvDef::ID => $ZERO,
    DragonEventCsvDef::TYPE => ++$ZERO,
    DragonEventCsvDef::CONDITION =>  ++$ZERO,
    DragonEventCsvDef::COSTACT  =>  ++$ZERO,//消耗探宝行动力

    DragonEventCsvDef::WEIGHT  => ++$ZERO,//权重
    DragonEventCsvDef::EXPLAIN => ++$ZERO, //描述
    DragonEventCsvDef::SHOW => ++$ZERO, //是否强制显示
    DragonEventCsvDef::ICON => ++$ZERO,

    DragonEventCsvDef::DOUBLEPAY => ++$ZERO, //双倍领取花费
    DragonEventCsvDef::ONKEYPAY => ++$ZERO, //快速完成花费
    DragonEventCsvDef::REWARD => ++$ZERO,   //奖励数组
    DragonEventCsvDef::POINT => ++$ZERO,    //获得积分

    DragonEventCsvDef::POINTTIPS => ++$ZERO,
    DragonEventCsvDef::AITIPS => ++$ZERO,
    DragonEventCsvDef::AIEXPLOREPOINT => ++$ZERO,

    //新寻龙试炼
    DragonEventCsvDef::GOODSID => ($ZERO += 2),
    DragonEventCsvDef::ARMYID => ++$ZERO,
    DragonEventCsvDef::BOSSCOSTACT => ++$ZERO,
    DragonEventCsvDef::BOSSSCORE => ++$ZERO,
    DragonEventCsvDef::BOSSDROP => ++$ZERO,
    DragonEventCsvDef::GOLDBOSS => ($ZERO += 2),
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$dragonevent = array();
while(TRUE)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($fields as $key => $val)
    {
        switch($key)
        {
            case DragonEventCsvDef::EXPLAIN:
            case DragonEventCsvDef::POINTTIPS:
            case DragonEventCsvDef::AITIPS:
                break;
            case DragonEventCsvDef::ID:
            case DragonEventCsvDef::TYPE:
            case DragonEventCsvDef::COSTACT:
            case DragonEventCsvDef::WEIGHT:
            case DragonEventCsvDef::SHOW:
            case DragonEventCsvDef::ICON:
            case DragonEventCsvDef::DOUBLEPAY:
            case DragonEventCsvDef::ONKEYPAY:
            case DragonEventCsvDef::AIEXPLOREPOINT:
                $conf[$key] = intval($line[$val]);
                break;
            case DragonEventCsvDef::CONDITION:
            case DragonEventCsvDef::GOODSID:
            case DragonEventCsvDef::ARMYID:
            case DragonEventCsvDef::BOSSCOSTACT:
            case DragonEventCsvDef::BOSSSCORE:
            case DragonEventCsvDef::BOSSDROP:
            case DragonEventCsvDef::GOLDBOSS:
                $conf[$key] = array2Int(str2Array($line[$val], '|'));
                break;
            case DragonEventCsvDef::REWARD:
                $conf[$key] = array2Int(str2Array($line[$val], '|'));
                break;
            case DragonEventCsvDef::POINT:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $conf[$key][$k] = array2Int(str2Array($v, '|'));
                }
                break;
        }
    }
    $dragonevent[$conf[DragonCsvDef::ID]] = $conf;

}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($dragonevent));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */