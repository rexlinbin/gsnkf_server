<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 


$csvFile = 'elitecopy.csv';
$outFileName = 'ELITECOPY';


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
/**
 * 精英副本ID 
精英副本名称 
精英副本介绍 
精英副本图片 
通关某副本开启该精英副本 
通关奖励银币 
通关奖励将魂 
通关奖励经验 
通关消耗体力 
通关掉落物品数组 
据点ID组 
通关该副本开启下一个精英副本

 */
		/**
		 精英副本ID
		精英副本名称
		精英副本介绍
		精英副本图片
		通关某副本开启该精英副本
		通关奖励银币
		通关奖励将魂
		通关奖励经验
		通关消耗体力
		通关掉落物品数组
		据点id
		通关该副本开启下一个精英副本
		 */
$field_names = array(
		'id'=>$ZERO,
		'name'=>++$ZERO,
		'profile'=>++$ZERO,
		'img'=>++$ZERO,
		'pre_open_copy'=>++$ZERO,//通关某副本开启该精英副本
		'reward_silver'=>++$ZERO,
		'reward_soul'=>++$ZERO,
		'reward_exp'=>++$ZERO,
		'need_power'=>++$ZERO,
		'drop_tbl_ids'=>++$ZERO,//通关副本掉落表数组 15312,15313
		'base_id'=>++$ZERO, //对应的据点id 如1
		'pass_open_next'=>++$ZERO
		);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$copies = array();
$copy = array();
while(TRUE)
{
	$copy = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{		
		switch($key)
		{
			case 'name':
			case 'profile':
				break;
			case 'drop_tbl_ids':
				$copy[$key] = array_map('intval', str2Array($line[$v], ','));
				break;
			default:
				$copy[$key] = intval($line[$v]);
		}
	}
	$copies[$copy['id']] = $copy;
}
$preCopy = 0;
$newCopies    =    array();
foreach($copies   as $copyId => $copyInfo)
{
    $copyInfo['pre_copy']=    $preCopy;
    $newCopies[$copyId]    =    $copyInfo;
    $preCopy    =    $copyId;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($newCopies));
fclose($file);