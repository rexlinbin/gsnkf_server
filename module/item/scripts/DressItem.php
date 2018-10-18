<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DressItem.php 245458 2016-06-03 09:57:07Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/DressItem.php $
 * @author $Author: QingYao $(tianming@babeltime.com)
 * @date $Date: 2016-06-03 09:57:07 +0000 (Fri, 03 Jun 2016) $
 * @version $Revision: 245458 $
 * @brief 
 *  
 **/
function readDressItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_DRESS_ATTRS			=> ($index+=6)-1,		//时装增加属性数组
			ItemDef::ITEM_ATTR_NAME_DRESS_COST			=> ($index+=3)-1,		//强化消耗银币物品ID组
			ItemDef::ITEM_ATTR_NAME_DRESS_RESOLVE		=> $index++,			//时装分解物品组
			ItemDef::ITEM_ATTR_NAME_DRESS_REBORN		=> $index++,			//时装重生花费金币
			ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA			=> $index++,			//额外属性
			ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA_ATTR  =>($index+=15)-1,  //时装强化到一定等级加属性
	);
	
	$arrKeyV2 = array(
			ItemDef::ITEM_ATTR_NAME_DRESS_ATTRS,
			ItemDef::ITEM_ATTR_NAME_DRESS_COST,
			ItemDef::ITEM_ATTR_NAME_DRESS_RESOLVE,
			ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA,
	);

	$file = fopen("$inputDir/item_dress.csv", 'r');
	echo "read $inputDir/item_dress.csv\n";

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
			}elseif ($key==ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA_ATTR )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else 
				{
					$conf[$key] = array();
					$arr = str2array($data[$index]);
					foreach ($arr as $value)
					{
						$conf[$key][]=array2Int(str2Array($value, '|'));
					}
				}
			}
			else
			{
				$conf[$key] = intval($data[$index]);
			}
		}
		
		$key = ItemDef::ITEM_ATTR_NAME_DRESS_ATTRS;
		$index = $arrConfKey[$key] + 2;
		if(!empty($conf[$key]) && !empty($data[$index]))
		{
			$add = array();
			$arr = str2array($data[$index]);
			foreach( $arr as $value )
			{
				if(!strpos($value, '|'))
				{
					trigger_error( "invalid $key, need v2\n" );
				}
				$ary = array2Int(str2Array($value, '|'));
				$add[$ary[0]] = $ary[1];
			}
			$base = $conf[$key];
			$conf[$key] = array();
			foreach ($base as $attrId => $attrValue)
			{
				$conf[$key][$attrId][0] = $attrValue;
				if (!empty($add[$attrId]))
				{
					$conf[$key][$attrId][1] = $add[$attrId];
				}
			}
		}
		
		//整理强化所需花费
		$key = ItemDef::ITEM_ATTR_NAME_DRESS_COST;
		$index = $arrConfKey[$key];
		if(!empty($conf[$key]))
		{
			$arr = str2array($data[$index]);
			$conf[$key] = array();
			$i = 0;
			foreach( $arr as $value )
			{
				if(!strpos($value, '|'))
				{
					trigger_error( "invalid $key, need v2\n" );
				}
				$ary = array2Int(str2Array($value, '|'));
				if (count($ary) != 4) 
				{
					trigger_error( "invalid cost:$value, is not 4 element array\n" );
				}
				$conf[$key][++$i] = array(
						'silver' => $ary[0],
						'item' => array($ary[1] => $ary[2]),
						'level' => $ary[3],
				);
			}
		}

		if (empty($conf[ItemDef::ITEM_ATTR_NAME_DRESS_ATTRS]))
		{
			trigger_error("dress:$data[0] attrs is empty!\n");
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */