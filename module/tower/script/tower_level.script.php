<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: tower_level.script.php 98692 2014-04-09 12:47:17Z TiantianZhang $
 * 
 **************************************************************************/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/script/tower_level.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-04-09 12:47:17 +0000 (Wed, 09 Apr 2014) $
 * @version $Revision: 98692 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 

$csvFile = 'tower_layer.csv';
$outFileName = 'TOWERLEVEL';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!TOWER.csv output\n";
	trigger_error ("input error parameters.");	
}

$ZERO = 0;
	/**
	塔层id
	塔层名称
	塔层描述
	塔层图片
	通关奖励银币
	通关奖励将魂
	通关奖励体力
	通关奖励耐力
	通关奖励物品ID组
	通关后可攻打塔层ID
	攻打塔层需要人物等级
	抽奖展示物品ID组
	抽奖掉落表ID
	据点ID	
	 */
$field_names = array(
		'id'=>$ZERO,
		'name'=>++$ZERO,
		'profile'=>++$ZERO,
		'img'=>++$ZERO,
		'reward_silver'=>++$ZERO,
		'reward_soul'=>++$ZERO,
		'reward_execution'=>++$ZERO,
		'reward_stamina'=>++$ZERO,
		'reward_item'=>++$ZERO,//10001,10002,10005
		'pass_open_lv'=>++$ZERO,//通关此塔层可攻击塔层
		'need_lv'=>++$ZERO,//开启需要玩家等级
		'lottery_items'=>++$ZERO, //抽奖展示物品
		'lottery_drop_id'=>++$ZERO,//抽奖掉落表ID
		'base_id'=>++$ZERO,//此塔层对应的据点ID
		'reward_show'=>++$ZERO,//是否进行奖励预览
		'monster_type'=>++$ZERO,//怪物类型
        'monster_quality'=>++$ZERO,//怪物品质
        'monster_figure'=>++$ZERO,//怪物形象
        'pass_condition'=>++$ZERO,//通关条件
        'open_special_chance'=>++$ZERO,//开启隐藏关概率
        'open_special_lv'=>++$ZERO,//隐藏关据点ID组
		
);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$lvs = array();
$lv = array();
while(TRUE)
{
	$lv = array();
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
			case 'img':
			case 'lottery_items':
			case 'reward_show':
				break;
			case 'open_special_lv':
			    $arrTmp = str2Array($line[$v], ',');
			    foreach($arrTmp as $baseDrop)
			    {
			        $baseDropInfo =  array2Int(str2Array($baseDrop, '|'));
			        if(count($baseDropInfo) != 2)
			        {
			            trigger_error('error config in open_special_lv');
			        }
			        $lv[$key][$baseDropInfo[0]] = array('weight'=>$baseDropInfo[1]);
			    }
			    break;
			case 'pass_condition':
			    $arrCond = str2Array($line[$v], ',');
			    $lv[$key] = array();
			    var_dump($arrCond);
			    foreach($arrCond as $cond)
			    {
			        $condArr = array_map('intval', str2Array($cond, '|'));
			        if(count($condArr) != 2)
			        {
			            trigger_error('pass_condition config error.every cond should be two field.');
			        }
			        $lv[$key][$condArr[0]] = $condArr[1];
			        var_dump($lv);
			    }
			    break;
			default:
				if($key == 'reward_silver')
				{
					$lv['reward'][RewardType::SILVER] = intval($line[$v]);
				}
				else if($key == 'reward_soul')
				{
					$lv['reward'][RewardType::SOUL] = intval($line[$v]);
				}
				else if($key == 'reward_execution')
				{
					$lv['reward'][RewardType::EXE] = intval($line[$v]);
				}
				else if($key == 'reward_stamina')
				{
					$lv['reward'][RewardType::STAMINA] = intval($line[$v]);
				}
				else if($key == 'reward_item')
				{
				    $arrTmp = str2Array($line[$v],',');
				    foreach($arrTmp as $tmp)
				    {
				        $itemInfo = array_map('intval', str2Array($tmp, '|'));
				        if(count($itemInfo) != 2)
				        {
				            trigger_error('item reward conf field is not 2');
				        }
				        if(!isset($lv['reward']['item'][$itemInfo[0]]))
				        {
				            $lv['reward']['item'][$itemInfo[0]] = 0;
				        }
				        $lv['reward']['item'][$itemInfo[0]] += $itemInfo[1];
				    }
				}
				else
				{					
					$lv[$key] = intval($line[$v]);
				}
		}
	}	
	$lvs[$lv['id']] = $lv;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($lvs));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */