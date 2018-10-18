<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PocketItem.php 190810 2015-08-13 05:53:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/PocketItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-08-13 05:53:59 +0000 (Thu, 13 Aug 2015) $
 * @version $Revision: 190810 $
 * @brief 
 *  
 **/
function readPocketItem($inputDir)
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
			PocketDef::ITEM_ATTR_NAME_POCKET_TYPE				=> ($index+=2)-1,		//锦囊类型
			PocketDef::ITEM_ATTR_NAME_POCKET_EXPID				=> ($index+=3)-1,		//经验表id
			PocketDef::ITEM_ATTR_NAME_POCKET_LEVELLIMIT			=> $index++,			//最大等级
			PocketDef::ITEM_ATTR_NAME_POCKET_VALUE				=> $index++,			//提供经验
			PocketDef::ITEM_ATTR_NAME_POCKET_VALUECOST			=> $index++,			//每经验消耗银币
			PocketDef::ITEM_ATTR_NAME_POCKET_ATTRS				=> $index++,			//基础属性
			PocketDef::ITEM_ATTR_NAME_POCKET_EFFECT				=> ($index+=2)-1,		//每级对应效果ID
			PocketDef::ITEM_ATTR_NAME_POCKET_ISEXP				=> $index++,			//是否为经验锦囊
	);
	
	$arrKeyV2 = array(
			PocketDef::ITEM_ATTR_NAME_POCKET_ATTRS, 
			PocketDef::ITEM_ATTR_NAME_POCKET_EFFECT
	);

	$file = fopen("$inputDir/item_pocket.csv", 'r');
	echo "read $inputDir/item_pocket.csv\n";

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
			else
			{
				$conf[$key] = intval($data[$index]);
			}
		}

		//整理战魂的成长属性
		$key = PocketDef::ITEM_ATTR_NAME_POCKET_ATTRS;
		$index = $arrConfKey[$key] + 1;
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

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */