<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRob.script.php 148903 2014-12-24 13:16:13Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/script/GuildRob.script.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-24 13:16:13 +0000 (Wed, 24 Dec 2014) $
 * @version $Revision: 148903 $
 * @brief 
 *  
 **/

$csvFile = 'rob_food.csv';
$outFileName = 'GUILD_ROB';

if (isset($argv[1]) && $argv[1] == '-h')
{
	exit("usage: $csvFile $outFileName\n");
}

if ($argc < 3)
{
	trigger_error("Please input enough arguments:inputPath outputPath\n");
}

$incre = 0;
$tag = array
(
		"begin_index" => ++$incre,
		"end_index" => ++$incre,
		"attack_limit_index" => ++$incre,
		"ready_time_index" => ++$incre,
		"battle_time_index" => ++$incre,
		"route_index" => ++$incre,
		"attack_kill_user_reward_index" => ++$incre,
		"defend_kill_user_reward_index" => ++$incre,
		"attack_touch_down_user_reward_index" => ++$incre,
		"defend_touch_down_user_reward_index" => ++$incre,
		"join_reward_merit_index" => ++$incre,
		"spec_barn_time_limit_index" => ++$incre,
		"spec_barn_reward_index" => ++$incre,
		"spec_barn_win_merit_index" => ++$incre,
		"join_cd_index" => ++$incre,
		"clear_join_cd_cost_index" => ++$incre,
		"speedup_cost_index" => ++$incre,
		"speedup_multiple_index" => ++$incre,
		"open_3rd_limit_index" => ++$incre,
		"defend_limit_index" => ++$incre,
		"attacker_morale_index" => ++$incre,
		"beat_speed_index" => ++$incre,
		"sub_time_speed_index" => ++$incre,
		"defend_be_killed_user_reward_index" => ++$incre,
		"can_rob_percent_index" => ++$incre,
		"after_attack_cd_time_index" => ++$incre,
		"after_defend_cd_time_index" => ++$incre,
		"terminal_reward_index" => ++$incre,
		"can_rob_min_percent_index" => ++$incre,
		"rob_grain_least_index" => ++$incre,
);

$config = array();
$file = fopen($argv[1] . "/$csvFile", 'r');
if (FALSE == $file)
{
	echo $argv[1] . "/{$csvFile} open failed! exit!\n";
	exit;
}

fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;

	$openArr = explode(',', $data[$tag["begin_index"]]);
	$closeArr = explode(',', $data[$tag["end_index"]]);
	
	foreach ($openArr as $open)
	{
		$tmp = explode('|', $open);
		if (count($tmp) != 2) 
		{
			continue;
		}
		
		$config['effect_time'][intval($tmp[0])][0] = $tmp[1]; 
	}
	
	foreach ($closeArr as $close)
	{
		$tmp = explode('|', $close);
		if (count($tmp) != 2)
		{
			continue;
		}
		
		if (empty($config['effect_time'][intval($tmp[0])]))
		{
			continue;
		}
	
		$config['effect_time'][intval($tmp[0])][1] = $tmp[1];
	}
	
	$config['attack_limit'] = intval($data[$tag["attack_limit_index"]]);
	$config['defend_limit'] = intval($data[$tag["defend_limit_index"]]);
	$config['ready_time'] = intval($data[$tag["ready_time_index"]]);
	$config['battle_time'] = intval($data[$tag["battle_time_index"]]);
	
	$routeArr = explode(',', $data[$tag["route_index"]]);
	foreach ($routeArr as $route)
	{
		$tmp = explode('|', $route);
		$config['route'][] = intval($tmp[1]);
	}
	
	$rewardArr = explode(',', $data[$tag["attack_kill_user_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$detail = explode('|', $reward);
		$config['attack_kill_user_reward'][intval($detail[0])] = array(intval($detail[1]), intval($detail[2]));
	}
	
	$rewardArr = explode(',', $data[$tag["defend_kill_user_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$detail = explode('|', $reward);
		$config['defend_kill_user_reward'][intval($detail[0])] = array(intval($detail[1]), intval($detail[2]));
	}
	
	$rewardArr = explode('|', $data[$tag["defend_be_killed_user_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$config['defend_be_killed_user_reward'][]= intval($reward);
	}
	
	$rewardArr = explode('|', $data[$tag["attack_touch_down_user_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$config['attack_touch_down_user_reward'][] = intval($reward);
	}
	$config['rob_grain_least'] = intval($data[$tag["rob_grain_least_index"]]);
	
	$rewardArr = explode('|', $data[$tag["defend_touch_down_user_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$config['defend_touch_down_user_reward'][]= intval($reward);
	}
	
	$config['join_reward_merit'] = intval($data[$tag["join_reward_merit_index"]]);
	$config['spec_barn_time_limit'] = intval($data[$tag["spec_barn_time_limit_index"]]);
	
	$rewardArr = explode('|', $data[$tag["spec_barn_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$config['spec_barn_reward'][]= intval($reward);
	}
	
	$rewardArr = explode('|', $data[$tag["spec_barn_win_merit_index"]]);
	foreach ($rewardArr as $reward)
	{
		$config['spec_barn_win_merit'][]= intval($reward);
	}
	
	$config['join_cd'] = intval($data[$tag["join_cd_index"]]);
	
	$costArr = explode(',', $data[$tag["clear_join_cd_cost_index"]]);
	foreach ($costArr as $cost)
	{
		$detail = explode('|', $cost);
		$config['clear_join_cd_cost'][intval($detail[0])] = intval($detail[1]);
	}
	
	$config['speedup_cost'] = intval($data[$tag["speedup_cost_index"]]);
	$config['speedup_multiple'] = intval($data[$tag["speedup_multiple_index"]]);
	$config['open_3rd_limit'] = intval($data[$tag["open_3rd_limit_index"]]);
	$config['attacker_morale'] = intval($data[$tag["attacker_morale_index"]]);
	$config['beat_speed'] = intval($data[$tag["beat_speed_index"]]);
	$config['sub_time_speed'] = intval($data[$tag["sub_time_speed_index"]]);
	$config['can_rob_percent'] = intval($data[$tag["can_rob_percent_index"]]);
	$config['can_rob_min_percent'] = intval($data[$tag["can_rob_min_percent_index"]]);
	$config['after_attack_cd_time'] = intval($data[$tag["after_attack_cd_time_index"]]);
	$config['after_defend_cd_time'] = intval($data[$tag["after_defend_cd_time_index"]]);
	
	$rewardArr = explode(',', $data[$tag["terminal_reward_index"]]);
	foreach ($rewardArr as $reward)
	{
		$detail = explode('|', $reward);
		$config['terminal_reward'][intval($detail[0])] = intval($detail[1]);
	}
}
fclose($file);
print_r($config);

// 输出文件
$file = fopen($argv[2] . "/$outFileName", "w");
if (FALSE == $file)
{
	trigger_error($argv[2] . "/$outFileName open failed! exit!\n");
}
fwrite($file, serialize($config));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */