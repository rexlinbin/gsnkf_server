<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalItem.php 218721 2015-12-30 09:13:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/NormalItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-30 09:13:59 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218721 $
 * @brief 
 *  
 **/

function readNormalItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_VALUE				=> ($index+=2)-1,		//物品价值
			ItemDef::ITEM_ATTR_NAME_NORMAL_CAN_DONATE	=> $index++,			//是否可以捐献
			ItemDef::ITEM_ATTR_NAME_NORMAL_FAME			=> $index++,			//名望
			ItemDef::ITEM_ATTR_NAME_NORMAL_IS_HERO_JH_ITEM	=> $index++,		//是否可以捐献
			ItemDef::ITEM_ATTR_NAME_NORMAL_RESOLVE_HERO_JH_GET	=> $index++,	//武将精华物品分解获得的精华数值
			ItemDef::ITEM_ATTR_NAME_NORMAL_TALLYEXP		=> $index++,			//提供兵符经验
	);

	$file = fopen("$inputDir/item_normal.csv", 'r');
	echo "read $inputDir/item_normal.csv\n";

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

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */