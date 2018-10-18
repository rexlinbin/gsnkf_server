<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 

$csvFile = 'copy_team.csv';
$outFileName = 'COPYTEAM';

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

$field_names = array(
		'id' => $ZERO,  //组队副本id1
		'template_name' => ++$ZERO, //模板名称2
		'name'=>++$ZERO,		//战役部队的显示名称3		
		'profile' => ++$ZERO, //战役部队简介4
		'level' => ++$ZERO,// 战役部队显示等级   5
		'vectory_condition' => ++$ZERO,//胜利条件描述        显示用6
		'reward_profile' => ++$ZERO, //奖励描述            显示用7
		'army_template' => ++$ZERO, //战役部队显示模型8
        'army_img' => ++$ZERO, //战役部队头像图片9
        'team_type' => ++$ZERO, //组队类型10
        'battle_background' => ++$ZERO, //战役背景11
        'team_limit' => ++$ZERO, // 组队限制12     1.代表可以选择无限制。2.表示可以选择同一阵营。3.代表可以选择同一军团。
        'max_win_num' => ++$ZERO, //最大连胜次数13        本场战役战斗中每个部队的最大连胜次数。        
        'min_member_num' => ++$ZERO, //最少参加人数14
        'max_member_num' => ++$ZERO, //最大参加人数15
        'need_level' => ++$ZERO,//副本开启等级限制16
        'enemy_army_num' => ++$ZERO, //敌方怪物小队的怪物总数量。 17       
        'base_id' => ++$ZERO, //对应据点ID18
        'reward_exp' => ++$ZERO, //初始经验        胜利获得经验=玩家等级*初始经验        19
        'reward_silver' => ++$ZERO, //初始掉落银币     胜利获得银币=初始掉落银币        20
        'reward_soul' => ++$ZERO, //初始将魂        胜利获得将魂=初始掉落将魂        21
        'drop_items' => ++$ZERO, //掉落显示物品ID        显示用22
        'arr_drop_id' => ++$ZERO, //掉落表ID组     23             "掉落表ID1，掉落表ID2，……每张掉落表独立掉落。"        
        'need_execution' => ++$ZERO, //消耗行动力24
        'pre_normal_copy' => ++$ZERO, //需要通关前置普通副本ID25
        'pre_team_copy' => ++$ZERO, //需要通关前置组队副本ID26
        'after_team_copy' => ++$ZERO, //通关该副本后可以开启的下一个组队副本ID27
        'copy_img' => ++$ZERO, //副本缩略图片28
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
			case 'template_name':
			case 'profile':
			case 'vectory_condition':
			case 'reward_profile':
			case 'army_template':
			case 'army_img':
			case 'battle_background':
			case 'drop_items':
			case 'copy_img':
				break;
			case 'arr_drop_id':
			case 'team_limit':
			    $copy[$key] = array2Int(str2Array($line[$v], ','));
				break;
			case 'reward_exp':
			case 'reward_silver':
			case 'reward_soul':
			    $subKey = substr($key, strlen('reward_'));
			    $copy['reward'][$subKey] = intval($line[$v]);
			    break;
			default:
				$copy[$key] = intval($line[$v]);
		}
	}
	$copies[$copy['id']] = $copy;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($copies));
fclose($file);