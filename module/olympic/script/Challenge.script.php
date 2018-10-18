<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Olympic.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'challenge.csv';
$outFileName = 'CHALLENGE';

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
    ChallengeCsvDef::ID => $ZERO,
    ChallengeCsvDef::START_TIME => ++$ZERO,
    ChallengeCsvDef::CHALLENGE_EVENT => ++$ZERO,
    ChallengeCsvDef::LAST_TIME_ARR => ++$ZERO,
    ChallengeCsvDef::PRIZE_ID => ++$ZERO,
    ChallengeCsvDef::PRIZE_POINT => ++$ZERO,

    ChallengeCsvDef::CDTIME => ++$ZERO,
    ChallengeCsvDef::CLEAR_CD_COST_GOLD => ++$ZERO,
    ChallengeCsvDef::JOIN_COST_BELLY => ++$ZERO,
    ChallengeCsvDef::INFO_LEN => ++$ZERO,
    ChallengeCsvDef::CHEER_COST_BELLY => ++$ZERO,

    ChallengeCsvDef::CHEER_PRIZE_ID => ++$ZERO,
    ChallengeCsvDef::POINT_EXCHANGE => ++$ZERO,
    ChallengeCsvDef::LUCKY_NUM => ++$ZERO,
    ChallengeCsvDef::CHEER_LUCKY_POINT => ++$ZERO,
    ChallengeCsvDef::CHEER_LUCKY_PRIZE_ID => ++$ZERO,

    ChallengeCsvDef::FINAL_PRIZE_POINT => ++$ZERO,
    ChallengeCsvDef::FINAL_PRIZE_ID => ++$ZERO,
    ChallengeCsvDef::MIN_PRIZE => ++$ZERO,
    ChallengeCsvDef::MAX_PRIZE => ++$ZERO,
    ChallengeCsvDef::CONTINUE_REWARD => ++$ZERO,

    ChallengeCsvDef::CHALLENGE_COST => ++$ZERO,

    ChallengeCsvDef::EFFECTIVE_CHANGE => ++$ZERO,
    ChallengeCsvDef::REDUCE_EFFECTIVE => ++$ZERO,
    ChallengeCsvDef::CHAMPION_RATE => ++$ZERO,
    ChallengeCsvDef::TERMINATOR_RATE => ++$ZERO,
    ChallengeCsvDef::OTHER_RATE => ++$ZERO,
    ChallengeCsvDef::CHEER_MULTIPLE => ++$ZERO,

);


// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);

$challenge = array();
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
            case ChallengeCsvDef::CHALLENGE_EVENT:
                $arrTmp = array2Int(str2Array($line[$val], ','));
                $allStatusIsCompete = TRUE;
                foreach($arrTmp as $index => $event)
                {
                    $conf[$key][$index+1] = $event;
                    if($event != OlympicWeekStatus::COMPETE_DAY)
                    {
                        $allStatusIsCompete = FALSE;
                    }
                }
                if($allStatusIsCompete)
                {
                    trigger_error('config error.');
                }
                break;
            case ChallengeCsvDef::LAST_TIME_ARR:
            case ChallengeCsvDef::PRIZE_ID:
            case ChallengeCsvDef::PRIZE_POINT:
            case ChallengeCsvDef::REDUCE_EFFECTIVE:
            case ChallengeCsvDef::CHAMPION_RATE:
            case ChallengeCsvDef::TERMINATOR_RATE:
            case ChallengeCsvDef::OTHER_RATE:
                $conf[$key] = array2Int(str2Array($line[$val], ','));
                break;
            case ChallengeCsvDef::EFFECTIVE_CHANGE:
                $arrTmp = array2Int(str2Array($line[$val], ','));
                foreach($arrTmp as $index => $attrId)
                {
                    $attrName = PropertyKey::$MAP_CONF[$attrId];
                    $conf[$key][$index] = $attrName;
                }
                break;
            case ChallengeCsvDef::CONTINUE_REWARD:
                $tmp = str2Array($line[$val], ',');
                foreach($tmp as $k => $v)
                {
                    $conf[$key][$k] = array2Int(str2Array($v, '|'));
                }
                break;
            default:
                $conf[$key] = intval($line[$val]);
                break;
        }
    }
    $challenge[$conf[ChallengeCsvDef::ID]] = $conf;
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($challenge));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */