<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GiftItem.php 187644 2015-07-30 07:44:17Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/GiftItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-07-30 07:44:17 +0000 (Thu, 30 Jul 2015) $
 * @version $Revision: 187644 $
 * @brief 
 *  
 **/
function readGiftItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_GIFT_NUM			=> ($index+=2)-1,		//礼物数量
			ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME	=> ($index+=21)-1,		//使用需求延时打开时间
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL 	=> $index++,			//使用所需角色等级限制
			ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS		=> ($index+=2)-1,		//使用需要物品
			ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER		=> ($index+=2)-1,		//使用需要消耗银币
			ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD		=> $index++,			//使用需要消耗金币
			ItemDef::ITEM_ATTR_NAME_GIFT_OPTIONS		=> $index++,			//使用获得可选物品
	);
	
	$useReqAttrs = array(
			ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD,
	);
	
	$arrKeyV2 = array(ItemDef::ITEM_ATTR_NAME_GIFT_OPTIONS);

	$file = fopen("$inputDir/item_gift.csv", 'r');
	echo "read $inputDir/item_gift.csv\n";

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
							trigger_error( "item gift:$data[0] invalid $key, need v2\n" );
						}
						$conf[$key][] = array2Int(str2Array($value, '|'));
					}
				}
			}
			else 
			{
				$conf[$key] = intval($data[$index]);
			}			
		}
		
		if ( empty($conf[ItemDef::ITEM_ATTR_NAME_GIFT_NUM]) && empty($conf[ItemDef::ITEM_ATTR_NAME_GIFT_OPTIONS])
		|| !empty($conf[ItemDef::ITEM_ATTR_NAME_GIFT_NUM]) && !empty($conf[ItemDef::ITEM_ATTR_NAME_GIFT_OPTIONS]))
		{
			trigger_error("gift:$data[0] use method is invalid!\n");
		}
		
		//整理获得物品
		if ( !empty($conf[ItemDef::ITEM_ATTR_NAME_GIFT_NUM]) )
		{
			$items = array();
			for ( $i = 0; $i < $conf[ItemDef::ITEM_ATTR_NAME_GIFT_NUM]; $i++ )
			{
				$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_GIFT_NUM] + $i*2 + 1;
				if (empty($data[$index]))
				{
					trigger_error("gift:$data[0] the $i gift id is empty!\n");
				}
				if (empty($data[$index + 1]))
				{
					trigger_error("gift:$data[0] the $i gift num is empty!\n");
				}
				$items[ intval($data[$index++]) ] = intval($data[$index++]);
			}
			$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS] = $items;
		}
			
		//整理用户等级
		if (!empty($conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL])) 
		{
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL];
			$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL] = array(intval($data[$index]), intval($data[$index+1]));
		}
		
		//整理需要物品
		if (!empty($conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS])) 
		{
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS];
			$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS] = array(intval($data[$index]) => intval($data[$index+1]));
		}
		
		$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ] = array();
		foreach ( $useReqAttrs as $attr )
		{
			if ( !empty($conf[$attr]) )
			{
				$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ][$attr] = $conf[$attr];
			}
			unset($conf[$attr]);
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */