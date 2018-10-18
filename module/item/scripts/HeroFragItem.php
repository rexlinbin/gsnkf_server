<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroFragItem.php 58564 2013-08-09 08:18:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/HeroFragItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-08-09 08:18:46 +0000 (Fri, 09 Aug 2013) $
 * @version $Revision: 58564 $
 * @brief 
 *  
 **/
function readHeroFragItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_FRAGMENT_NUM		=> ($index+=2)-1,		//所需碎片数量
			ItemDef::ITEM_ATTR_NAME_HEROFRAG_UNIVERSAL	=> $index++,			//可使用万能碎片数量
			ItemDef::ITEM_ATTR_NAME_HEROFRAG_FORM		=> $index++				//合成武将id
	);

	$file = fopen("$inputDir/item_hero_fragment.csv", 'r');
	echo "read $inputDir/item_hero_fragment.csv\n";

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
		//检查配置
		if (empty($conf[ItemDef::ITEM_ATTR_NAME_FRAGMENT_NUM]))
		{
			trigger_error("hero frag:$data[0] num is empty!\n");
		}
		if (empty($conf[ItemDef::ITEM_ATTR_NAME_HEROFRAG_FORM]))
		{
			trigger_error("hero frag:$data[0] form id is empty!\n");
		}
		
		$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ] = array();
		$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ] = array(
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO => array(
				intval($conf[ItemDef::ITEM_ATTR_NAME_HEROFRAG_FORM]) => 1
				)
		);
		unset($conf[ItemDef::ITEM_ATTR_NAME_HEROFRAG_FORM]);
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);
	
	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */