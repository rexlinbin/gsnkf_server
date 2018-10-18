<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Dragon.def.php";

$csvFile = 'explore_long_answer.csv';
$outFileName = 'DRAGONANSWER';

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
    DragonAnswerCsvDef::ID => $ZERO,
    DragonAnswerCsvDef::QUESTION => ++$ZERO,
    DragonAnswerCsvDef::ANSWERA => ++$ZERO,
    DragonAnswerCsvDef::ANSWERB => ++$ZERO,
    DragonAnswerCsvDef::ANSWER  => ++$ZERO,
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$dragonanswer = array();
while(true)
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
            case DragonAnswerCsvDef::ID:
            case DragonAnswerCsvDef::ANSWER:
                $conf[$key] = intval($line[$val]);
                break;
            case DragonAnswerCsvDef::QUESTION:
            case DragonAnswerCsvDef::ANSWERA:
            case DragonAnswerCsvDef::ANSWERB:
                break;
        }
    }
    $dragonanswer[$conf[DragonAnswerCsvDef::ID]] = $conf;
}

fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($dragonanswer));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */