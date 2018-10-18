<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Olympic.def.php";

$csvFile = 'challenge_reward.csv';
$outFileName = 'CHALLENGEREWARD';

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
    ChallengeRewardCsvDef::ID => $ZERO,
    ChallengeRewardCsvDef::TIPS => ++$ZERO,
    ChallengeRewardCsvDef::REWARD => ++$ZERO,
);


// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);

$challengeReward = array();
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
            case ChallengeRewardCsvDef::TIPS:
                break;
            case ChallengeRewardCsvDef::ID:
                $conf[$key] = intval($line[$val]);
                break;
            case ChallengeRewardCsvDef::REWARD:
                $arrTmp = str2Array($line[$val], ',');
                foreach($arrTmp as $rewardStr)
                {
                    $conf[$key][] = array2Int(str2Array($rewardStr, '|'));
                }
                break;
        }
    }
    $challengeReward[$conf[ChallengeRewardCsvDef::ID]] = $conf;
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($challengeReward));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */