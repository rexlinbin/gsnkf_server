<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 



$csvFile = 'army.csv';
$outFileName = 'ARMY';

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
		'id' => $ZERO, //部队ID							
		'tid' => ++$ZERO,//部队模板名称  NONO
		'name' => ++$ZERO,//部队显示名称  NONO
		'baseid' => ++$ZERO,//部队对应据点ID
		'level' => ++$ZERO,//部队显示等级  
		'fight_type' => ++$ZERO,//战斗方式
		'use_type' => ++$ZERO,//部队用途
		'type'=>++$ZERO,//部队类型		
		'teamid' => ++$ZERO,//怪物小队组
		'npc_team_id' => ++$ZERO,//NPC怪物小队组
		'total_round' => ++$ZERO,//战斗总回合				
		'hold_round' => ++$ZERO,//坚守回合数
		'npcid' => ++$ZERO,//	NPC ID
		'hp' => ++$ZERO,//HP
		'defeat_monster_id' => ++$ZERO,//指定消灭怪物ID
		'open_need_army' => ++$ZERO,//需要击败某部队们才能攻击 数值类型
		'pass_open_army' => ++$ZERO,//击败该部队能攻击哪些部队
		'into_type' => ++$ZERO,//该部队出场方式  NONO
		'pre_dialog' => ++$ZERO,//战斗前对话ID	  NONO
		'defeat_dialog' => ++$ZERO,//战斗对话ID组	  NONO		
		'end_dialog' => ++$ZERO,//战斗结束弹出对话ID  NONO
		'music_id'		=> ++$ZERO,//战斗音乐id
		'dialog_end_scene' => ++$ZERO,//对话ID完结后切换战斗场景  NONO
		'dialog_end_music' => ++$ZERO,//对话ID完结后切换战斗场音乐 NONO
		'force_pass'=>++$ZERO//是否强制胜利
		);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$armies = array();
$army = array();
while(TRUE)
{
	$army = array();
	$line = fgetcsv($file);
	// 	var_dump($line);
	if(empty($line))
	{
		break;
	}
	
	foreach($field_names as $key => $v)
	{
	   	switch($key)
		{
			case 'into_type':
			case 'pre_dialog':
			case 'end_dialog':
			case 'dialog_end_scene':
			case 'dialog_end_music':
			break;
			default:
				$army[$key] = intval($line[$v]);
		}		
	}
	$endCondition = array();
	if($army['npcid'] > 0)
	{
		$endCondition['team1'] = array(array($army['npcid'],$army['hp']));		
	}
	unset($army['npcid']);
	unset($army['hp']);
	if($army['defeat_monster_id'] > 0)
	{
		$endCondition['team2'] = array(array($army['defeat_monster_id'],0));		
	}
	unset($army['defeat_monster_id']);	
	if($army['npc_team_id'] < 1)
	{
		unset($army['npc_team_id']);
	}
	if($army['total_round'] > 0)
	{
		$endCondition['attackRound'] = $army['total_round'];
	}
	if($army['hold_round'] > 0)
	{
		$endCondition['defendRound'] = $army['hold_round'];
	}
	unset($army['total_round']);
	unset($army['hold_round']);
	if(!empty($endCondition))
	{		
		$army['end_condition'] = $endCondition;
	}
	$armies[$army['id']] = $army;	
}

fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($armies));
fclose($file);
