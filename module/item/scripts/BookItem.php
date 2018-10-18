<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BookItem.php 55621 2013-07-16 08:28:28Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/BookItem.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2013-07-16 08:28:28 +0000 (Tue, 16 Jul 2013) $
 * @version $Revision: 55621 $
 * @brief 
 *  
 **/

function readBookItem($inputDir)
{	
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE				=> $index++,			//物品模板ID
			ItemDef::ITEM_ATTR_NAME_QUALITY					=> ($index+=7)-1,		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL					=> $index++,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE				=> $index++,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE				=> $index++,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE				=> $index++,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND					=> $index++,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY					=> $index++,			//可否摧毁
			ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS				=> ($index+=2)-1,       //技能书属性数组
			ItemDef::ITEM_ATTR_NAME_BOOK_SKILLS				=> ($index+=21)-1,		//技能书技能组
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE			=> $index++,			//技能书能否被摘除
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_SILVER		=> $index++,			//技能书摘除花费银两
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_GOLD		=> $index++,			//技能书摘除花费金币
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS		=> $index++,			//技能书摘除花费物品
			ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_EXTRA		=> ($index+=2)-1,		//技能书属性成长数组
			ItemDef::ITEM_ATTR_NAME_BOOK_EQUIP_SLOT			=> ($index+=10)-1,		//技能书装备栏位槽
			ItemDef::ITEM_ATTR_NAME_BOOK_TYPE				=> $index++,			//技能书类型
			ItemDef::ITEM_ATTR_NAME_BOOK_SKILL_BUFF_GROUP	=> $index++,			//技能书升级对应附加技能Id组
			ItemDef::ITEM_ATTR_NAME_BOOK_EXP				=> $index++,			//技能书提供经验
			ItemDef::ITEM_ATTR_NAME_BOOK_CAN_LEVEL_UP		=> $index++,			//技能书是否可以升级
			ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_TABLE		=> $index++,			//技能书升级经验表
			ItemDef::ITEM_ATTR_NAME_BOOK_MAX_LEVEL			=> $index++,			//技能书等级上限	
	);
	
	$file = fopen("$inputDir/item_book.csv", 'r');
	echo "read $inputDir/item_book.csv\n";
	
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
			if( $key == ItemDef::ITEM_ATTR_NAME_BOOK_SKILLS )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else 
				{
					$tmpArr = str2Array($data[$index]);
					foreach ( $tmpArr as $value )
					{
						$conf[$key][]= array2Int(str2Array($value, '|'));
					}
				}
				continue;
			}
			if( $key == ItemDef::ITEM_ATTR_NAME_BOOK_EQUIP_SLOT )
			{
				if (empty($data[$index])) 
				{
					trigger_error("book:$data[0] equip slot is empty!\n");
				}
				$conf[$key] =  array2Int( str2Array($data[$index]));
				continue;
			}
			if( $key ==  ItemDef::ITEM_ATTR_NAME_BOOK_SKILL_BUFF_GROUP )
			{
				if (empty($data[$index]))
				{
					$conf[$key] = array();
				}
				else 
				{
					$conf[$key] =  array2Int( str2Array($data[$index]));
				}
				continue;
			}
			$conf[$key] = intval($data[$index]);
		}
		//config check
		if ( $conf[ItemDef::ITEM_ATTR_NAME_STACKABLE] != ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			trigger_error("book:$data[0] can not be stackable!\n");
		}
		
		if ( !in_array($conf[ItemDef::ITEM_ATTR_NAME_BOOK_TYPE], ItemDef::$BOOK_VALID_TYPES) )
		{
			trigger_error("book:$data[0] is not valid type!\n");
		}
			
		//技能书附加属性数组
		$addAttrNum = $conf[ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS];			
		$arrAddAttr = array();
		for ( $i = 0; $i < $addAttrNum; $i++ )
		{
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS] + $i*2 + 1;
			if (empty($data[$index]))
			{
				trigger_error("book:$data[0] the $i attr id is empty!\n");
			}
			if (empty($data[$index + 1]))
			{
				trigger_error("book:$data[0] the $i attr num is empty!\n");
			}
			$arrAddAttr[ intval($data[$index++]) ] = intval($data[$index++]);
		}
		$conf[ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS] = $arrAddAttr;
		
		//取下技能书需要消耗的物品
		$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS];
		if (empty($data[$index]))
		{
			$conf[ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS] = array();		
		}
		else
		{
			$conf[ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS] = array($data[$index++] => $data[$index++]);
		}
			
		//每个级别额外附加数值, 可为空
		$arrLevelExtra = array();
		$arrKey = array_keys($conf[ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS]);
		for ( $i = 0; $i < $addAttrNum; $i++ )
		{	
			$index = $arrConfKey[ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_EXTRA] + $i;
			$arrLevelExtra[$arrKey[$i]] = intval($data[$index]);
		}
		$conf[ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_EXTRA] = $arrLevelExtra;	
		
		//可以升级的情况下检查升级表id是否为空
		if ($conf[ItemDef::ITEM_ATTR_NAME_BOOK_CAN_LEVEL_UP]
		&& empty($conf[ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_TABLE]))
		{
			trigger_error("book:$data[0] the level table id is empty!\n");
		}
		
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	
	fclose($file);

	return $confList;
}




/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */