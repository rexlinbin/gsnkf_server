<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Dragon.def.php";

$csvFile = 'explore_long.csv';
$outFileName = 'DRAGON';

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
$field = array(
    DragonCsvDef::ID => $ZERO,  //层
    DragonCsvDef::INITACT => ++$ZERO,//初始行动力
    DragonCsvDef::RESETPAY =>  ++$ZERO,//重置花费
    DragonCsvDef::ITEMPANDECT  =>  ++$ZERO,//宝物总览（前端用 不解析）
    DragonCsvDef::RENEWNUM  => ++$ZERO,//每日免费重置次数

    DragonCsvDef::ADDPAY => ++$ZERO, //重置递增上限组
    DragonCsvDef::ACTPAY => ++$ZERO,   //购买行动力价格
    DragonCsvDef::ADDACT => ++$ZERO, //行动力递增上限组
    DragonCsvDef::INITHP => ++$ZERO, //初始血池血量
    DragonCsvDef::HPPAY => ++$ZERO, //血槽购买花费

    DragonCsvDef::ADDHP => ++$ZERO,

    DragonCsvDef::HEIGHT => ++$ZERO, //寻龙探宝高度
    DragonCsvDef::WIDTH => ++$ZERO,  //寻龙探宝宽度
    DragonCsvDef::ACTERPOS => ++$ZERO, //主角出现位置
    DragonCsvDef::BOX1POS => ++$ZERO, //宝箱
    DragonCsvDef::BOX2POS => ++$ZERO, //宝箱
    DragonCsvDef::BOX3POS => ++$ZERO, //宝箱
    DragonCsvDef::BOX4POS => ++$ZERO, //宝箱

    DragonCsvDef::BOX5POS => ++$ZERO,
    DragonCsvDef::BOX6POS => ++$ZERO,
    DragonCsvDef::BOX7POS => ++$ZERO,
    DragonCsvDef::BOX8POS => ++$ZERO,
    DragonCsvDef::BOX9POS => ++$ZERO,

    DragonCsvDef::BOX10POS => ++$ZERO,
    DragonCsvDef::BOX11POS => ++$ZERO,

    DragonCsvDef::POS1EVENT => ++$ZERO, //事件
    DragonCsvDef::POS2EVENT => ++$ZERO, //事件
    DragonCsvDef::POS3EVENT => ++$ZERO, //事件
    DragonCsvDef::POS4EVENT => ++$ZERO, //事件
    DragonCsvDef::POS5EVENT => ++$ZERO, //事件
    DragonCsvDef::POS6EVENT => ++$ZERO, //事件
    DragonCsvDef::POS7EVENT => ++$ZERO, //事件

    DragonCsvDef::POS8EVENT => ++$ZERO, //事件
    DragonCsvDef::POS9EVENT => ++$ZERO, //事件
    DragonCsvDef::POS10EVENT => ++$ZERO, //事件
    DragonCsvDef::POS11EVENT => ++$ZERO, //事件
    DragonCsvDef::POS12EVENT => ++$ZERO, //事件
    DragonCsvDef::POS13EVENT => ++$ZERO, //事件
    DragonCsvDef::POS14EVENT => ++$ZERO, //事件

    DragonCsvDef::POS15EVENT => ++$ZERO, //事件
    DragonCsvDef::POS16EVENT => ++$ZERO, //事件
    DragonCsvDef::POS17EVENT => ++$ZERO, //事件
    DragonCsvDef::POS18EVENT => ++$ZERO, //事件
    DragonCsvDef::POS19EVENT => ++$ZERO, //事件
    DragonCsvDef::POS20EVENT => ++$ZERO, //事件
    DragonCsvDef::POS21EVENT => ++$ZERO, //事件

    DragonCsvDef::POS22EVENT => ++$ZERO, //事件
    DragonCsvDef::POS23EVENT => ++$ZERO, //事件
    DragonCsvDef::POS24EVENT => ++$ZERO, //事件
    DragonCsvDef::POS25EVENT => ++$ZERO, //事件
    DragonCsvDef::POS26EVENT => ++$ZERO, //事件
    DragonCsvDef::POS27EVENT => ++$ZERO, //事件
    DragonCsvDef::POS28EVENT => ++$ZERO, //事件

    DragonCsvDef::POS29EVENT => ++$ZERO, //事件
    DragonCsvDef::POS30EVENT => ++$ZERO, //事件
    DragonCsvDef::POS31EVENT => ++$ZERO, //事件
    DragonCsvDef::POS32EVENT => ++$ZERO, //事件
    DragonCsvDef::POS33EVENT => ++$ZERO, //事件
    DragonCsvDef::POS34EVENT => ++$ZERO, //事件
    DragonCsvDef::POS35EVENT => ++$ZERO, //事件

    DragonCsvDef::POS36EVENT => ++$ZERO, //事件
    DragonCsvDef::POS37EVENT => ++$ZERO, //事件
    DragonCsvDef::POS38EVENT => ++$ZERO, //事件
    DragonCsvDef::POS39EVENT => ++$ZERO, //事件
    DragonCsvDef::POS40EVENT => ++$ZERO, //事件
    DragonCsvDef::POS41EVENT => ++$ZERO, //事件
    DragonCsvDef::POS42EVENT => ++$ZERO, //事件

    DragonCsvDef::POS43EVENT => ++$ZERO, //事件
    DragonCsvDef::POS44EVENT => ++$ZERO, //事件
    DragonCsvDef::POS45EVENT => ++$ZERO, //事件
    DragonCsvDef::POS46EVENT => ++$ZERO, //事件
    DragonCsvDef::POS47EVENT => ++$ZERO, //事件
    DragonCsvDef::POS48EVENT => ++$ZERO, //事件
    DragonCsvDef::POS49EVENT => ++$ZERO, //事件

    DragonCsvDef::POS50EVENT => ++$ZERO, //事件
    DragonCsvDef::POS51EVENT => ++$ZERO, //事件
    DragonCsvDef::POS52EVENT => ++$ZERO, //事件
    DragonCsvDef::POS53EVENT => ++$ZERO, //事件
    DragonCsvDef::POS54EVENT => ++$ZERO, //事件
    DragonCsvDef::POS55EVENT => ++$ZERO, //事件
    DragonCsvDef::POS56EVENT => ++$ZERO, //事件

    DragonCsvDef::POS57EVENT => ++$ZERO, //事件
    DragonCsvDef::POS58EVENT => ++$ZERO, //事件
    DragonCsvDef::POS59EVENT => ++$ZERO, //事件
    DragonCsvDef::POS60EVENT => ++$ZERO, //事件
    DragonCsvDef::POS61EVENT => ++$ZERO, //事件
    DragonCsvDef::POS62EVENT => ++$ZERO, //事件
    DragonCsvDef::POS63EVENT => ++$ZERO, //事件

    DragonCsvDef::POS64EVENT => ++$ZERO, //事件
    DragonCsvDef::POS65EVENT => ++$ZERO, //事件
    DragonCsvDef::POS66EVENT => ++$ZERO, //事件
    DragonCsvDef::POS67EVENT => ++$ZERO, //事件
    DragonCsvDef::POS68EVENT => ++$ZERO, //事件
    DragonCsvDef::POS69EVENT => ++$ZERO, //事件
    DragonCsvDef::POS70EVENT => ++$ZERO, //事件

    DragonCsvDef::POS71EVENT => ++$ZERO, //事件
    DragonCsvDef::POS72EVENT => ++$ZERO, //事件
    DragonCsvDef::POS73EVENT => ++$ZERO, //事件
    DragonCsvDef::POS74EVENT => ++$ZERO, //事件
    DragonCsvDef::POS75EVENT => ++$ZERO, //事件
    DragonCsvDef::POS76EVENT => ++$ZERO, //事件
    DragonCsvDef::POS77EVENT => ++$ZERO, //事件

    DragonCsvDef::POS78EVENT => ++$ZERO, //事件
    DragonCsvDef::POS79EVENT => ++$ZERO, //事件
    DragonCsvDef::POS80EVENT => ++$ZERO, //事件
    DragonCsvDef::POS81EVENT => ++$ZERO, //事件
    DragonCsvDef::POS82EVENT => ++$ZERO, //事件
    DragonCsvDef::POS83EVENT => ++$ZERO, //事件
    DragonCsvDef::POS84EVENT => ++$ZERO, //事件

    DragonCsvDef::EXPLOREMUSIC => ++$ZERO,
    DragonCsvDef::AIEXPLORECOSTACT => ++$ZERO,
    DragonCsvDef::AIEXPLOREEVENT => ++$ZERO, //自动探宝事件集
    DragonCsvDef::AIEXPLOREREWARD => ++$ZERO, //自动探宝奖励
    DragonCsvDef::AIEXPLORETIPS => ++$ZERO,
    DragonCsvDef::AIEXPLOREPAY => ++$ZERO,
    DragonCsvDef::AIEXPLOREREWARDPOINT => ++$ZERO,

    DragonCsvDef::AIEXTRAEVENT => ++$ZERO,
);

/**
 * 事件解析后结构array(array('id'=>int事件id, 'w'=>int权重)...)
 */

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);

$dragon = array();
while(TRUE)
{
    $line = fgetcsv($file);
    if(empty($line))
    {
        break;
    }
    $conf = array();
    foreach($field as $key => $val)
    {
        switch($key)
        {
            case DragonCsvDef::ITEMPANDECT:
            case DragonCsvDef::EXPLOREMUSIC:
            case DragonCsvDef::AIEXPLORETIPS:
                break;
            case DragonCsvDef::ID:
            case DragonCsvDef::INITACT:
            case DragonCsvDef::RESETPAY:
            case DragonCsvDef::RENEWNUM:
            case DragonCsvDef::INITHP:
            case DragonCsvDef::HEIGHT:
            case DragonCsvDef::WIDTH:
            case DragonCsvDef::AIEXPLOREPAY:
                $conf[$key] = intval($line[$val]);
                break;
            case DragonCsvDef::ADDPAY:
            case DragonCsvDef::ADDACT:
            case DragonCsvDef::ADDHP:
            case DragonCsvDef::AIEXPLORECOSTACT:
            case DragonCsvDef::AIEXPLOREREWARD:
            case DragonCsvDef::AIEXPLOREREWARDPOINT:
                $conf[$key] = array2Int(str2Array($line[$val], '|'));
                break;
            case DragonCsvDef::ACTPAY:
            case DragonCsvDef::HPPAY:
            case DragonCsvDef::AIEXTRAEVENT:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $conf[$key][$k] = array2Int(str2Array($v, '|'));
                }
                break;
            case DragonCsvDef::ACTERPOS:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $tmp2 = array2Int(str2Array($v, '|'));
                    $conf[$key][$k] = array('id'=>$tmp2[0], 'w'=>$tmp2[1]);
                }
                break;
            case DragonCsvDef::BOX1POS:
            case DragonCsvDef::BOX2POS:
            case DragonCsvDef::BOX3POS:
            case DragonCsvDef::BOX4POS:
            case DragonCsvDef::BOX5POS:
            case DragonCsvDef::BOX6POS:
            case DragonCsvDef::BOX7POS:
            case DragonCsvDef::BOX8POS:
            case DragonCsvDef::BOX9POS:
            case DragonCsvDef::BOX10POS:
            case DragonCsvDef::BOX11POS:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $tmp2 = array2Int(str2Array($v, '|'));
                    $conf[$key][$k] = array('id'=>$tmp2[0], 'w'=>$tmp2[1]);
                }
                break;
            default:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $tmp2 = array2Int(str2Array($v, '|'));
                    $conf[$key][$k] = array('id'=>$tmp2[0], 'w'=>$tmp2[1]);
                }
                break;
        }
    }
    $dragon[$conf[DragonCsvDef::ID]] = $conf;

}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($dragon));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */