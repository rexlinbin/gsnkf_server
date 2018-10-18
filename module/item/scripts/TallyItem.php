<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TallyItem.php 223314 2016-01-18 11:44:43Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/TallyItem.php $
 * @author $Author: BaoguoMeng $(tianming@babeltime.com)
 * @date $Date: 2016-01-18 11:44:43 +0000 (Mon, 18 Jan 2016) $
 * @version $Revision: 223314 $
 * @brief 
 *  
 **/

function readTallyItem($inputDir)
{
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE			=> $index++,			//物品模板ID
			ItemDef::ITEM_ATTR_NAME_QUALITY				=> ($index+=7)-1,		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL				=> $index++,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE			=> $index++,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE			=> $index++,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE			=> $index++,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND				=> $index++,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY				=> $index++,			//可否摧毁
			ItemDef::ITEM_ATTR_NAME_TALLY_TYPE			=> ($index+=2)-1,		//兵符类型
			ItemDef::ITEM_ATTR_NAME_TALLY_EXPID			=> ($index+=2)-1,		//兵符经验表id
			ItemDef::ITEM_ATTR_NAME_TALLY_LEVEL_LIMIT	=> $index++,			//最大等级
			ItemDef::ITEM_ATTR_NAME_TALLY_UPGRADE_COST  => $index++,			//每经验消耗银币
			ItemDef::ITEM_ATTR_NAME_TALLY_ATTRS  		=> $index++,			//基础属性
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_NEED  => ($index+=2)-1,		//进阶要求
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_COST  => $index++,			//进阶消耗
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_ATTRS => $index++,			//进阶提升属性
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_NEED  	=> $index++,			//精炼要求
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_COST  	=> $index++,			//精炼消耗
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_EFFECT => $index++,			//精炼对应效果ID
			ItemDef::ITEM_ATTR_NAME_TALLY_POINT 		=> $index++,			//兵符炼化获得积分
			ItemDef::ITEM_ATTR_NAME_TALLY_BOOK_ATTRS 	=> $index++,			//兵符录属性
			ItemDef::ITEM_ATTR_NAME_TALLY_REBORN_COST	=> ($index+=2)-1,		//重生花费
	);
	
	//a|b|c => (a,b,c)
	$arrKeyV1 = array(
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_NEED,
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_NEED,
	);
	
	//a|b,c|d => (a=>b,c=>d)
	$arrKeyV2 = array(
			ItemDef::ITEM_ATTR_NAME_TALLY_ATTRS,
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_EFFECT,
			ItemDef::ITEM_ATTR_NAME_TALLY_BOOK_ATTRS,
	);
	
	//a|b,c|d;e|f,g|h => ((a=>b,c=>d), (e=>f,g=>h))
	$arrKeyV3 = array(
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_ATTRS,
	);
	
	//a|b|c,d|e|f;g|h|i => (((a,b,c),(d,e,f)),((g,h,j)))
	$arrKeyV4 = array(
			ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_COST,
			ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_COST,
	);

	$file = fopen("$inputDir/item_bingfu.csv", 'r');
	echo "read $inputDir/item_bingfu.csv\n";
	
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
		foreach ($arrConfKey as $key => $index)
		{
			if (in_array($key, $arrKeyV1, true))
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$conf[$key] = array2Int(str2array($data[$index], '|'));
				}
			}
			elseif (in_array($key, $arrKeyV2, true))
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$arr = str2array($data[$index]);
					$conf[$key] = array();
					foreach ($arr as $value)
					{
						if (!strpos($value, '|'))
						{
							trigger_error("tally:$data[0] invalid key:$key, value:$value need v2\n");
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
			elseif (in_array($key, $arrKeyV3, true))
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$arrs = str2array($data[$index], ';');
					$conf[$key] = array();
					$i = 0;
					foreach ($arrs as $arr)
					{
						if (empty($arr)) 
						{
							$conf[$key][$i] = array();
						}
						else 
						{
							$arr = str2array($arr);
							foreach ($arr as $value)
							{
								$ary = array2Int(str2Array($value, '|'));
								$conf[$key][$i][$ary[0]] = $ary[1];
							}
						}
						$i++;
					}
				}
			}
			elseif (in_array($key, $arrKeyV4, true))
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else
				{
					$arrs = str2array($data[$index], ';');
					$conf[$key] = array();
					$i = 0;
					foreach ($arrs as $arr)
					{
						if (empty($arr))
						{
							$conf[$key][$i] = array();
						}
						else
						{
							$arr = str2array($arr);
							foreach ($arr as $value)
							{
								$conf[$key][$i][] = array2Int(str2Array($value, '|'));
							}
						}
						$i++;
					}
				}
			}
			else
			{
				$conf[$key] = intval($data[$index]);
			}
		}
		
		//整理基础属性和成长属性
		$key = ItemDef::ITEM_ATTR_NAME_TALLY_ATTRS;
		if (!empty($conf[$key]))
		{
			$index = $arrConfKey[$key] + 1;
			$arr = str2array($data[$index]);
			foreach ($arr as $value)
			{
				$ary = array2Int(str2Array($value, '|'));
				if (isset($conf[$key][$ary[0]])) 
				{
					$conf[$key][$ary[0]] = array($conf[$key][$ary[0]], $ary[1]);
				}
			}
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);
	
	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */