<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TreasFragItem.php 82482 2013-12-23 07:47:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/TreasFragItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-12-23 07:47:25 +0000 (Mon, 23 Dec 2013) $
 * @version $Revision: 82482 $
 * @brief 
 *  
 **/
function readTreasFragItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_TREASFRAG_FORM			=> ($index+=3)-1,		//合成物品id
			ItemDef::ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_BASE => $index++,			//基础掠夺概率
			ItemDef::ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_NPC  => $index++,			//NPC掠夺概率
			ItemDef::ITEM_ATTR_NAME_TREASFRAG_SPECIAL_NUM	=> $index++, 			//特殊掉落值
	);

	$file = fopen("$inputDir/item_treasure_fragment.csv", 'r');
	echo "read $inputDir/item_treasure_fragment.csv\n";

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
			$conf[$key] = intval($data[$index]);
			if ( is_numeric($conf[$key]) || empty($conf[$key]) )
			{
				$conf[$key] = intval($conf[$key]);
			}
		}

		if (empty($conf[ItemDef::ITEM_ATTR_NAME_TREASFRAG_FORM]))
		{
			trigger_error("treasFrag:$data[0] form id is empty!\n");
		}
		
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */