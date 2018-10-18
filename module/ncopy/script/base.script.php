<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 

$csvFile = 'stronghold.csv';
$outFileName = 'BASE';

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
		 'id' => $ZERO,   //
		 'tname' => ++$ZERO, //据点模板名称    NONO
		 'name' => ++$ZERO, //据点显示名称		 NONO
		 'profile' => ++$ZERO,//据点描述  NONO
		 'type' => ++$ZERO,//据点类型
		 'copyid' => ++$ZERO,//据点对应副本ID				
		 'bg_scene' => ++$ZERO,//据点场景背景  NONO
		 'fg_scene' => ++$ZERO,//据点场景前景 NONO
		 'level' => ++$ZERO,//据点显示等级
		 'surfacet' => ++$ZERO,//据点显示模型 NONO
		 'head_img' => ++$ZERO,//据点头像图片	 NONO
		 'pass_open_copy' => ++$ZERO,//****通关该据点开启副本1,2,3(活动副本、普通副本)(可能为空）
		 'free_defeat_num' => ++$ZERO,//该据点每日攻击次数
		 'attack_need_base'=>++$ZERO,//需要击败某据点简单难度才能攻击
		 'show_need_base' => ++$ZERO,//需要击败某据点简单难度才能显示
		 'pass_open_base' => ++$ZERO,//*****击败该简单难度能攻击哪些据点1,2,3
		 'pass_show_base' => ++$ZERO,//*****击败该简单难度能显示哪些据点1,2,3
		 'click_dialog' => ++$ZERO,//点击弹出对话ID	 NONO		
		 'pass_dialog' => ++$ZERO,//胜利弹出对话ID	 NONO
		 'fail_dialog' => ++$ZERO,// 失败弹出对话ID	 NONO
		 'simple_hp_modal' => ++$ZERO,//据点血量模式		
		 'simple_revive_modal'=> ++$ZERO,//据点死后复活模式
		 'simple_army_num' => ++$ZERO,//简单难度据点部队总数	  		
		 'simple_army_arrays' => ++$ZERO,//简单难度部队ID组31,32,33
		 'npc_army_num' => ++$ZERO,//NPC简单难度部队总数
		 'npc_army_arrays' => ++$ZERO,//NPC简单难度部队ID组31,32,33
		 'simple_pass_condition' => ++$ZERO,//简单难度胜利条件描述		 NONO
		 'simple_scored_condition' => ++$ZERO, //简单难度得分条件
//          'simple_scored_round_num' => ++$ZERO,//通关该据点战斗回合总数不超过N
//          'simple_scored_cost_hp' => ++$ZERO,//通关该据点全员总损血不超过N
//          'simple_scored_revive_num' => ++$ZERO,//通关该据点复活总次数不超过N
//          'simple_scored_dead_num' => ++$ZERO,//通关该据点全员死亡人次不超过N
//          'simple_scored_three_star_hero' => ++$ZERO,//至少上阵X名3星武将
//          'simple_scored_four_star_hero' => ++$ZERO,//至少上阵X名4星武将
		 'simple_drop_itemids' => ++$ZERO,//简单难度掉落显示物品ID  NONO
		 'simple_droptbl_ids' => ++$ZERO,//简单难度掉落表ID组	  15312,15313
		 'extra_droptbl_ids'=> ++$ZERO,//据点额外掉落表ID组
		 'simple_reward_exp' => ++$ZERO,//简单难度通关奖励经验		
		 'simple_reward_silver' => ++$ZERO,//简单难度通关奖励银币
		 'simple_reward_soul' => ++$ZERO,//简单难度通关奖励将魂
		 'simple_need_power' => ++$ZERO,//简单难度通关该据点后消耗体力		
		 'normal_hp_modal' => ++$ZERO,//据点血量模式
		 'normal_revive_modal'=> ++$ZERO,//据点死后复活模式
		 'normal_army_num' => ++$ZERO,//普通难度据点部队总数
		 'normal_army_arrays' => ++$ZERO,//普通难度部队ID组	31,32,33			
		 'normal_pass_condition' => ++$ZERO,//普通难度胜利条件描述 NONO
         'normal_scored_condition' => ++$ZERO, //简单难度得分条件
//          'normal_scored_round_num' => ++$ZERO,//通关该据点战斗回合总数不超过N
//          'normal_scored_cost_hp' => ++$ZERO,//通关该据点全员总损血不超过N
//          'normal_scored_revive_num' => ++$ZERO,//通关该据点复活总次数不超过N
//          'normal_scored_dead_num' => ++$ZERO,//通关该据点全员死亡人次不超过N
//          'normal_scored_three_star_hero' => ++$ZERO,//至少上阵X名3星武将
//          'normal_scored_four_star_hero' => ++$ZERO,//至少上阵X名4星武将
		 'normal_drop_itemids' => ++$ZERO,//普通难度掉落显示物品ID NONO
		 'normal_droptbl_ids' => ++$ZERO,//普通难度掉落表ID组  15312,15313
		 'normal_reward_exp' => ++$ZERO,//普通难度通关奖励经验		
		 'normal_reward_silver' => ++$ZERO,//普通难度通关奖励银币
		 'normal_reward_soul' => ++$ZERO,//普通难度通关奖励将魂
		 'normal_need_power' => ++$ZERO,//普通难度通关该据点后消耗体力		
		 'hard_hp_modal' => ++$ZERO,//据点血量模式
		 'hard_revive_modal'=> ++$ZERO,//据点死后复活模式
		 'hard_army_num' => ++$ZERO,//困难难度据点部队总数
		 'hard_army_arrays' => ++$ZERO,//困难难度部队ID组31,32,33		
		 'hard_pass_condition' => ++$ZERO,//困难难度胜利条件描述 NONO
         'hard_scored_condition' => ++$ZERO, //简单难度得分条件
//          'hard_scored_round_num' => ++$ZERO,//通关该据点战斗回合总数不超过N
//          'hard_scored_cost_hp' => ++$ZERO,//通关该据点全员总损血不超过N
//          'hard_scored_revive_num' => ++$ZERO,//通关该据点复活总次数不超过N
//          'hard_scored_dead_num' => ++$ZERO,//通关该据点全员死亡人次不超过N
//          'hard_scored_three_star_hero' => ++$ZERO,//至少上阵X名3星武将
//          'hard_scored_four_star_hero' => ++$ZERO,//至少上阵X名4星武将
		 'hard_drop_itemids' => ++$ZERO,//困难难度掉落显示物品ID NONO
		 'hard_droptbl_ids' => ++$ZERO,//困难难度掉落表ID组   15312,15313
		 'hard_reward_exp' => ++$ZERO,//困难难度通关奖励经验			
		 'hard_reward_silver' => ++$ZERO,//困难难度通关奖励银币
		 'hard_reward_soul' => ++$ZERO,//困难难度通关奖励将魂
		 'hard_need_power' => ++$ZERO,//困难难度通关该据点后消耗体力	
		 'background_id'	=> ++$ZERO,//战斗背景id
		 'music_id'			=> ++$ZERO,//战斗音乐id		
		);
// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$bases = array();
$base = array();
while(TRUE)
{
	$base = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	
	foreach($field_names as $key => $v)
	{
		switch($key)
		{
			case 'pass_open_base':
			case 'pass_show_base':
			case 'pass_open_copy':
			case 'extra_droptbl_ids':
				$base[$key] = array_map('intval', str2Array($line[$v],','));
				break;
			case 'simple_army_arrays':
			case 'simple_droptbl_ids':	
			    $base['simple'][$key] = array2Int(str2Array($line[$v], ','));		    
				break;
			case 'npc_army_arrays':
				$base['npc'][$key] = array2Int(str2Array($line[$v], ','));
				break;
			case 'normal_army_arrays':
			case 'normal_droptbl_ids':
				$base['normal'][$key] = array2Int(str2Array($line[$v], ','));
				break;
			case 'hard_army_arrays':			
			case 'hard_droptbl_ids':				
				$base['hard'][$key] = array2Int(str2Array($line[$v], ','));
				break;
			case 'profile':
			case 'bg_scene':
			case 'fg_scene':
			case 'surfacet':
			case 'head_img':
			case 'click_dialog':
			case 'pass_dialog':
			case 'fail_dialog':
			case 'simple_pass_condition':
			case 'simple_drop_itemids':
			case 'normal_pass_condition':
			case 'normal_drop_itemids':
			case 'hard_pass_condition':
			case 'hard_drop_itemids':
			    break;
			case 'simple_scored_condition':
			case 'normal_scored_condition':
			case 'hard_scored_condition':
			    $base[$key] = array();
			    $tmp = str2Array($line[$v], ',');
			    foreach($tmp as $index => $confCond)
			    {
			        $condition = str2Array($confCond, '|');
			        if(count($condition) < 2)
			        {
			            trigger_error('error config.should give two field(type and value)'.$confCond);
			        }
			        if(isset($base[$key][intval($condition[0])]))
			        {
			            trigger_error('error config.duplicate type of condition :'.$line[$v]);
			        }
			        $base[$key][intval($condition[0])] = intval($condition[1]);
			    }
				break;	
			default:
				if(strpos($key,'simple') === 0)
				{
					 $base['simple'][$key] = intval($line[$v]);
				}
				else if(strpos($key,'normal') === 0)
				{
					 $base['normal'][$key] = intval($line[$v]);
				}
				else if(strpos($key,'hard') === 0)
				{
					 $base['hard'][$key] = intval($line[$v]);
				}
				else if(strpos($key,'npc') === 0)
				{
					$base['npc'][$key] = intval($line[$v]);
				}
				else 
				{
					$base[$key] = intval($line[$v]);
				}		
		}		
	}
	if(isset($base['normal']) && ($base['normal']['normal_army_num'] == 0))
	{
		unset($base['normal']);
	}
	if(isset($base['simple']) && ($base['simple']['simple_army_num'] == 0))
	{
		unset($base['simple']);
	}
	if(isset($base['hard']) && ($base['hard']['hard_army_num'] == 0))
	{
		unset($base['hard']);
	}
	if(isset($base['npc']) && ($base['npc']['npc_army_num'] == 0))
	{
		unset($base['npc']);
	}	
	else if(isset($base['simple']))
	{
	    $base['npc']['npc_hp_modal']    =    $base['simple']['simple_hp_modal'];
	    $base['npc']['npc_revive_modal']    =    $base['simple']['simple_revive_modal'];
	    $base['npc']['npc_reward_exp']    =    $base['simple']['simple_reward_exp'];
	    $base['npc']['npc_reward_silver']    =    $base['simple']['simple_reward_silver'];
	    $base['npc']['npc_reward_soul']    =    $base['simple']['simple_reward_soul'];
	    $base['npc']['npc_need_power']    =    0;
	    $base['npc']['npc_droptbl_ids']    =    $base['simple']['simple_droptbl_ids'];
	    $base['npc_scored_condition'] = $base['simple_scored_condition'];
	}
	if($base['free_defeat_num'] == 0)
	{
	    $base['free_defeat_num'] = 100000;
	}
	$bases[$base['id']] = $base;
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($bases));
fclose($file);
