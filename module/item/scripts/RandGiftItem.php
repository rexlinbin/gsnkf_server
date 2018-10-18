<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RandGiftItem.php 159160 2015-02-16 07:35:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/RandGiftItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-02-16 07:35:43 +0000 (Mon, 16 Feb 2015) $
 * @version $Revision: 159160 $
 * @brief 
 *  
 **/
function readRandGiftItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP		=> ($index+=2)-1,		//使用获得掉落表
			ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME	=> $index++,			//使用需求延时打开时间
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL 	=> $index++,			//使用所需角色等级限制
			ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS		=> ($index+=2)-1,		//使用需要物品
			ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER		=> ($index+=2)-1,		//使用需要消耗银币
			ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD		=> $index++,			//使用需要消耗金币
			ItemDef::ITEM_ATTR_NAME_USE_CHAT			=> $index++,			//使用后发送消息模板id
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL=> $index++,			//神兵礼盒暗格
	);
	
	$arrKeyV2 = array(ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL);
	
	$useAcqAttrs = array(
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL,
	);
	
	$useReqAttrs = array(
			ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER,
			ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD,
	);

	$file = fopen("$inputDir/item_randgift.csv", 'r');
	echo "read $inputDir/item_randgift.csv\n";

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
							trigger_error( "item randgift:$data[0] invalid key:$key, value:$value need v2\n" );
						}
						$conf[$key]= array2Int(str2Array($value, '|'));
					}
				}
			}
			else 
			{
				$conf[$key] = intval($data[$index]);
			}
		}
		
		//整理获得物品
		$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ] = array();
		foreach ( $useAcqAttrs as $attr )
		{
			if ( !empty($conf[$attr]) )
			{
				$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][$attr] = $conf[$attr];
			}
			unset($conf[$attr]);
		}
		
		if (empty($conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ])) 
		{
			trigger_error("rand gift:$data[0] use acq is empty!\n");
		}
		
		//整理用户等级
		if (!empty($conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL]))
		{
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL];
			if (intval($data[$index]) > intval($data[$index+1])) 
			{
				trigger_error("rand gift:$data[0] use req user level upper < lower!\n");
			}
			$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL] = array(intval($data[$index]), intval($data[$index+1]));
		}
		
		//整理需要物品
		if (!empty($conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]))
		{
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS];
			if (!empty($data[$index])) 
			{
				$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS] = array(intval($data[$index]) => intval($data[$index+1]));
			}
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