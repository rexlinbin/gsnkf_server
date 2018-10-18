<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArmItem.php 207946 2015-11-09 02:53:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/ArmItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-09 02:53:53 +0000 (Mon, 09 Nov 2015) $
 * @version $Revision: 207946 $
 * @brief 
 *  
 **/
function readArmItem($inputDir)
{
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE					=> $index++,			//物品模板ID
			ItemDef::ITEM_ATTR_NAME_QUALITY						=> ($index+=7)-1,		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL						=> $index++,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE					=> $index++,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE					=> $index++,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE					=> $index++,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND						=> $index++,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY						=> $index++,			//可否摧毁
			ArmDef::ITEM_ATTR_NAME_ARM_TYPE						=> $index++,			//装备类型
			ArmDef::ITEM_ATTR_NAME_ARM_LEVEL					=> $index++,			//装备需要英雄等级
			ArmDef::ITEM_ATTR_NAME_ARM_SUIT						=> $index++,			//装备所属套装id
			PropertyKey::HP_BASE								=> $index++,			//生命基础值
			PropertyKey::PHYSICAL_ATTACK_BASE					=> $index++,			//物理攻击基础值
			PropertyKey::MAGIC_ATTACK_BASE						=> $index++,			//魔法攻击基础值
			PropertyKey::PHYSICAL_DEFEND_BASE   				=> $index++,			//物理防御基础值
			PropertyKey::MAGIC_DEFEND_BASE						=> $index++,			//魔法防御基础值
			ArmDef::ITEM_ATTR_NAME_ARM_HP_ADD					=> $index++,			//生命增加值
			ArmDef::ITEM_ATTR_NAME_ARM_PHYSICAL_ATTACK_ADD		=> $index++,			//物理攻击增加值
			ArmDef::ITEM_ATTR_NAME_ARM_MAGIC_ATTACK_ADD			=> $index++,			//魔法攻击增加值
			ArmDef::ITEM_ATTR_NAME_ARM_PHYSICAL_DEFEND_ADD		=> $index++,			//物理防御增加值
			ArmDef::ITEM_ATTR_NAME_ARM_MAGIC_DEFEND_ADD			=> $index++,			//魔法防御增加值
			PropertyKey::HP_RATIO								=> $index++,			//生命值百分比
			PropertyKey::PHYSICAL_ATTACK_ADDITION				=> $index++,			//物理攻击百分比
			PropertyKey::MAGIC_ATTACK_ADDITION					=> $index++,			//魔法攻击百分比
			PropertyKey::PHYSICAL_DEFEND_ADDITION				=> $index++,    		//物理防御百分比
			PropertyKey::MAGIC_DEFEND_ADDITION					=> $index++,			//魔法防御百分比		
			ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE				=> $index++,			//强化费用表id
			ArmDef::ITEM_ATTR_NAME_ARM_RAND_REFRESH				=> $index++, 			//能否随机洗练
			ArmDef::ITEM_ATTR_NAME_ARM_FIXED_REFRESH			=> $index++,			//能否固定洗练
			ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE			=> $index++,			//固定潜能表id
			ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE				=> $index++,			//随机潜能表id
			ArmDef::ITEM_ATTR_NAME_ARM_INIT_LEVEL				=> $index++,			//强化初始等级
			ArmDef::ITEM_ATTR_NAME_ARM_EXCHANGE					=> $index++,			//兑换表id
			ArmDef::ITEM_ATTR_NAME_ARM_EVOLVE					=> $index++,			//进化表id
			ArmDef::ITEM_ATTR_NAME_ARM_SCORE_BASE				=> $index++,			//装备基础评分
			ArmDef::ITEM_ATTR_NAME_ARM_SCORE_ADD				=> $index++,			//装备评分强化增长
			ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_RATE			=> $index++,			//强化等级上限系数
			PropertyKey::GENERAL_ATTACK_BASE					=> $index++,			//通用攻击基础值
			ArmDef::ITEM_ATTR_NAME_ARM_GENERAL_ATTACK_ADD		=> $index++,			//通用攻击增加值
			PropertyKey::GENERAL_ATTACK_ADDITION				=> $index++, 			//通用攻击百分比
			ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RATIO			=> $index++,			//潜能价值等级系数
			ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_INIT				=> $index++,			//潜能价值初始值
			ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_LIMIT			=> $index++,			//潜能价值上限系数
			ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE			=> $index++,			//装备潜能分解物品组
			ArmDef::ITEM_ATTR_NAME_ARM_REBORN_COST				=> $index++, 			//装备重生花费金币
			ArmDef::ITEM_ATTR_NAME_ARM_FOUNDRY					=> ($index+=2)-1,		//橙装炼化对应进化表ID
			ArmDef::ITEM_ATTR_NAME_ARM_CAN_DEVELOP				=> $index++,			//是否可进化为红装
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_LIMIT			=> $index++,			//最大进阶等级
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_ATTRS			=> $index++,			//每次进阶成长属性
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXTRA			=> $index++,			//进阶解锁属性
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXPEND			=> $index++,			//进阶消耗
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_QUALITY			=> $index++,			//进阶后品质
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_SCORE			=> ($index+=3)-1,		//进化后装备物评分
	);
	
	$arrKeyV2 = array(ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE);
	$arrKeyV3 = array(
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_ATTRS,
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXTRA,
	);
	$arrKeyV4 = array(
			ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXPEND
	);
	
	$file = fopen("$inputDir/item_arm.csv", 'r');
	echo "read $inputDir/item_arm.csv\n";

	// 略过 前两行
	$data = fgetcsv($file);
	$data = fgetcsv($file);

	$confList = array();
	while ( true )
	{
		$data = fgetcsv($file);
		if ( empty($data) || empty($data[0]) )
		{
			break;
		}

		$conf = array();
		foreach ( $arrConfKey as $key => $index )
		{
			if( in_array($key, $arrKeyV2, true) )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else 
				{
					$arr = str2array($data[$index]);
					$conf[$key] = array();
					foreach( $arr as $value )
					{
						if(!strpos($value, '|'))
						{
							trigger_error( "invalid $key, need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
			elseif ( in_array($key, $arrKeyV3, true) )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$arr = str2array($data[$index]);
					$conf[$key] = array();
					foreach( $arr as $value )
					{
						if(!strpos($value, '|'))
						{
							trigger_error( "arm:$data[0] invalid key:$key, value:$value need v3\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						if (!isset($conf[$key][$ary[0]][$ary[1]]))
						{
							$conf[$key][$ary[0]][$ary[1]] = 0;
						}
						$conf[$key][$ary[0]][$ary[1]] += $ary[2];
					}
				}
			}
			elseif ( in_array($key, $arrKeyV4, true) )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$arr = str2array($data[$index]);
					$conf[$key] = array();
					foreach( $arr as $value )
					{
						if(!strpos($value, '|'))
						{
							trigger_error( "arm:$data[0] invalid key:$key, value:$value need v4\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]][] = array($ary[1], $ary[2], $ary[3]); 
					}
				}
			}
			else
			{
				$conf[$key] = intval($data[$index]);
			}
		}
		
		//config check 
		if ( $conf[ItemDef::ITEM_ATTR_NAME_STACKABLE] != ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			trigger_error("arm:$data[0] can not be stackable!\n");
		}
		
		if ( !in_array($conf[ArmDef::ITEM_ATTR_NAME_ARM_TYPE], ArmDef::$ARM_VALID_TYPES) )
		{
			trigger_error("arm:$data[0] is not valid type!\n");
		}
		
		if ( empty($conf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE]))
		{
			trigger_error("arm:$data[0] reinforce id is empty!\n");
		}
		
		if (count($conf[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE]) > 1) 
		{
			trigger_error("arm:$data[0] potence resolve item is more than 1!\n");
		}
		
		if (!empty($conf[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE])
			&& current($conf[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE]) <= 0) 
		{
			trigger_error("arm:$data[0] potence resolve item value is less than 0!\n");
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */