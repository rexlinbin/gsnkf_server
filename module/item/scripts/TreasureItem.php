<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TreasureItem.php 253493 2016-07-28 03:50:17Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/TreasureItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-07-28 03:50:17 +0000 (Thu, 28 Jul 2016) $
 * @version $Revision: 253493 $
 * @brief 
 *  
 **/
function readTreasureItem($inputDir)
{
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE							=> $index++,			//物品模板ID
			ItemDef::ITEM_ATTR_NAME_QUALITY								=> ($index+=7)-1,		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL								=> $index++,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE							=> $index++,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE							=> $index++,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE							=> $index++,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND								=> $index++,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY								=> $index++,			//可否摧毁
			TreasureDef::ITEM_ATTR_NAME_TREASURE_TYPE					=> ($index+=2)-1,		//宝物类型
			TreasureDef::ITEM_ATTR_NAME_TREASURE_ATTRS					=> $index++,			//宝物属性数组
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EXTRA					=> ($index+=10)-1,		//宝物附加属性组
			TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE				=> $index++, 			//宝物基础消耗价值
			TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE			=> $index++, 			//宝物等级升级总价值
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EXPEND_UPGRADE			=> $index++,			//宝物升级消耗游戏币数组
			TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL_LIMIT			=> $index++,			//宝物等级上限
			TreasureDef::ITEM_ATTR_NAME_TREASURE_FRAGMENTS				=> $index++,			//宝物对应碎片ID组	
			TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_BASE				=> $index++, 			//宝物评分基础值
			TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_ADD				=> $index++,			//宝物评分成长值
			TreasureDef::ITEM_ATTR_NAME_TREASURE_NO_ATTR				=> $index++,			//宝物没有属性加成
			TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_EVOLVE				=> $index++, 			//宝物是否能突破
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_ATTRS			=> $index++,			//精炼属性成长
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXTRA			=> $index++,			//精炼属性解锁
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_LIMIT  			=> $index++,			//精炼等级上限
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_SILVER	=> $index++,			//精炼消耗银币
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM1	=> $index++,			//精炼消耗物品1
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM2	=> $index++,			//精炼消耗物品2
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM3	=> $index++,			//精炼消耗物品3
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_RESOLVE			=> $index++,			//炼化基础获得精华ID组
			TreasureDef::ITEM_ATTR_NAME_TREASURE_RESOLVE_ITEM			=> $index++,   			//炼化返还经验宝物ID
			TreasureDef::ITEM_ATTR_NAME_TREASURE_REBORN_COST			=> ($index+=3)-1,		//重生花费金币
			TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_INLAY				=> $index++,			//宝物初始可镶嵌位置数
			TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY_OPEN				=> $index++,			//镶嵌位置开启条件
			TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP			=> $index++,			//是否可进化
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND			=> $index++,			//进化消耗
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_QUALITY		=> ($index+=13)-1,		//进化后宝物品质
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_SCORE			=> $index++,			//进化后宝物评分
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA			=> $index++,			//新增解锁属性
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS			=> $index++,			//橙色进阶增加属性
			TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_UPGRADE			=> ($index+=17)-1,		//是否可强化
			UnionDef::FATE_ATTR										    => $index++,			//宝物缘分堂属性
	);
	
	$arrKeyV1 = array(
			TreasureDef::ITEM_ATTR_NAME_TREASURE_FRAGMENTS	
	);
	
	$arrKeyV2 = array(
			TreasureDef::ITEM_ATTR_NAME_TREASURE_ATTRS,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EXPEND_UPGRADE,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_ATTRS,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_SILVER,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_RESOLVE,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY_OPEN,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS,
			UnionDef::FATE_ATTR,
	);
	
	$arrKeyV3 = array(
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EXTRA,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXTRA,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM1,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM2,
			TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM3,
	);

	$file = fopen("$inputDir/item_treasure.csv", 'r');
	echo "read $inputDir/item_treasure.csv\n";

	// 略过 前两行
	$data = fgetcsv($file);
	$data = fgetcsv($file);
	
	$attrNum = 5;
	$developNum = 13;

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
			if( in_array($key, $arrKeyV1, true) )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$conf[$key] = array2Int( str2array($data[$index]) );
				}
			}
			elseif ( in_array($key, $arrKeyV2, true) )
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
							trigger_error( "treasure:$data[0] invalid key:$key, value:$value need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA) 
						{
							$conf[$key][] = $ary;
						}
						elseif ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND)
						{
							$conf[$key][0][] = $ary;
						}
						elseif ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS)
						{
							$conf[$key][0][$ary[0]] = $ary[1];
						}
						else 
						{
							$conf[$key][$ary[0]] = $ary[1];
						}
					}
					if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA) 
					{
						$arr = str2array($data[$index + 17]);
						foreach ($arr as $value)
						{
							if(!strpos($value, '|'))
							{
								trigger_error( "treasure:$data[0] invalid key:$key, value:$value need v2\n" );
							}
							$ary = array2Int(str2Array($value, '|'));
							$ary[1] += TreasureDef::RED_INIT_DEVELOP;
							$conf[$key][] = $ary;
						}
					}
					if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND) 
					{
						for ($i = 1; $i < $developNum; $i++)
						{
							$arr = str2array($data[$index + $i]);
							foreach( $arr as $value )
							{
								if(!strpos($value, '|'))
								{
									trigger_error( "treasure:$data[0] invalid key:$key, value:$value need v2\n" );
								}
								$conf[$key][$i][] = array2Int(str2Array($value, '|'));
							}
						}
					}
					if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS)
					{
						for ($i = 1; $i < $developNum; $i++)
						{
							$arr = str2array($data[$index + $i]);
							foreach( $arr as $value )
							{
								if(!strpos($value, '|'))
								{
									trigger_error( "treasure:$data[0] invalid key:$key, value:$value need v2\n" );
								}
								$ary = array2Int(str2Array($value, '|'));
								$conf[$key][$i][$ary[0]] = $ary[1];
							}
						}
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
							trigger_error( "treasure:$data[0] invalid key:$key, value:$value need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						if ($ary[1] == 0) 
						{
							$conf[$key][$ary[0]] = array();
							continue;
						}
						if (!isset($conf[$key][$ary[0]][$ary[1]]))
						{
							$conf[$key][$ary[0]][$ary[1]] = 0;
						}
						$conf[$key][$ary[0]][$ary[1]] += $ary[2];
					}
				}
			}
			else 
			{
				$conf[$key] = intval($data[$index]);
				if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP) 
				{
					$conf[$key] = array(
							TreasureDef::ORANGE_INIT_DEVELOP => intval($data[$index]), 
							TreasureDef::RED_INIT_DEVELOP => intval($data[$index + 30])
					);
				}
				if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_QUALITY) 
				{
					$conf[$key] = array(
							TreasureDef::ORANGE_INIT_DEVELOP => intval($data[$index]), 
							TreasureDef::RED_INIT_DEVELOP => intval($data[$index + 17])
					);
				}
				if ($key == TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_SCORE)
				{
					$conf[$key] = array(
							TreasureDef::ORANGE_INIT_DEVELOP => intval($data[$index]), 
							TreasureDef::RED_INIT_DEVELOP => intval($data[$index + 17])
					);
				}
			}
		}
		//整理宝物的基础属性和成长属性
		$key = TreasureDef::ITEM_ATTR_NAME_TREASURE_ATTRS;
		if( !empty($conf[$key]) )
		{
			$conf[$key] = array();
			$index = $arrConfKey[$key];
			for ($i = 0; $i < $attrNum; $i++)
			{
				//基础属性
				if (!empty($data[$index + $i * 2])) 
				{
					$ary0 = array2Int(str2Array($data[$index + $i * 2], '|'));
					$attrId = $ary0[0];
					if (!isset($conf[$key][$attrId][0]))
					{
						$conf[$key][$attrId][0] = 0;
					}
					$conf[$key][$attrId][0] += $ary0[1];
					//成长属性
					if (!empty($data[$index + $i * 2 + 1]))
					{
						$ary1 = array2Int(str2Array($data[$index + $i * 2 + 1], '|'));
						if ($attrId != $ary1[0])
						{
							trigger_error("treasure:$data[0] the $i attr base id:$attrId does not equals attr add id:$ary1[0]!\n");
						}
						if (!isset($conf[$key][$attrId][1]))
						{
							$conf[$key][$attrId][1] = 0;
						}
						$conf[$key][$attrId][1] += $ary1[1];
					}
				}
				else 
				{
					break;
				}
			}
		}
		
		//整理精炼花费
		$key = TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND;
		$key0 = TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_SILVER;
		$key1 = TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM1;
		$key2 = TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM2;
		$key3 = TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM3;
		$conf[$key] = array();
		$evolveLimit = $conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_LIMIT];
		for ($i = 1; $i <= $evolveLimit; $i++)
		{
			$conf[$key][$i]['silver'] = $conf[$key0][$i];
			$item1 = array();
			if (isset($conf[$key1][$i])) 
			{
				$item1 = $conf[$key1][$i];
			}
			$item2 = array();
			if (isset($conf[$key2][$i]))
			{
				$item2 = $conf[$key2][$i];
			}
			$item3 = array();
			if (isset($conf[$key3][$i]))
			{
				$item3 = $conf[$key3][$i];
			}
			$items = array($item1, $item2, $item3);
			$arrRet = array();
			foreach ($items as $item)
			{
				foreach ($item as $itemTplId => $itemNum)
				{
					if (isset($arrRet[$itemTplId]))
					{
						$arrRet[$itemTplId] += $itemNum;
					}
					else
					{
						$arrRet[$itemTplId] = $itemNum;
					}
				}
			}
			$conf[$key][$i]['item'] = $arrRet;
		}
		unset($conf[$key0]);
		unset($conf[$key1]);
		unset($conf[$key2]);
		unset($conf[$key3]);
		
		//整理宝物的经验值
		$key = TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE;
		if( !empty($conf[$key]) )
		{
			$sum = 0;
			foreach ($conf[$key] as $level => $exp)
			{
				$sum += $exp;
				$conf[$key][$level] = $sum;
			}
		}
		
		if (!in_array($conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_TYPE], TreasureDef::$TREASURE_VALID_TYPES)) 
		{
			trigger_error("treasure type is invalid!\n");
		}
		
		//物品可以叠加的时候
		if ($conf[ItemDef::ITEM_ATTR_NAME_STACKABLE] != 1) 
		{
			//物品不能强化
			if ($conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_UPGRADE]) 
			{
				trigger_error("treasure stackable can not upgrade!\n");
			}
			//物品不能精炼
			if ($conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_EVOLVE]) 
			{
				trigger_error("treasure stackable can not evolve!\n");
			}
			//物品不能进化
			if ($conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP][TreasureDef::ORANGE_INIT_DEVELOP]
			|| $conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP][TreasureDef::RED_INIT_DEVELOP])
			{
				trigger_error("treasure stackable can not develop!\n");
			}
			//物品不能镶嵌
			if ($conf[TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_INLAY])
			{
				trigger_error("treasure stackable can not inlay!\n");
			}
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */