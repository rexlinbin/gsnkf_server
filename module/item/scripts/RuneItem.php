<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RuneItem.php 169021 2015-04-22 08:29:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/RuneItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-04-22 08:29:14 +0000 (Wed, 22 Apr 2015) $
 * @version $Revision: 169021 $
 * @brief 
 *  
 **/

function readRuneItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_RUNE_TYPE			=> ($index+=2)-1,		//符印类型
			ItemDef::ITEM_ATTR_NAME_RUNE_FEATURE		=> $index++,			//属性类型
			ItemDef::ITEM_ATTR_NAME_RUNE_ATTR			=> $index++,			//基础属性
			ItemDef::ITEM_ATTR_NAME_RUNE_RESOLVE		=> $index++,			//分解获得天工令	
	);
	
	$arrKeyV2 = array(ItemDef::ITEM_ATTR_NAME_RUNE_ATTR);

	$file = fopen("$inputDir/item_fuyin.csv", 'r');
	echo "read $inputDir/item_fuyin.csv\n";
	
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
			if ( in_array($key, $arrKeyV2, true) )
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
							trigger_error( "rune:$data[0] invalid key:$key, value:$value need v2\n" );
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

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);
	
	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */