<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PetFragItem.php 98558 2014-04-09 07:50:20Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/PetFragItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-04-09 07:50:20 +0000 (Wed, 09 Apr 2014) $
 * @version $Revision: 98558 $
 * @brief 
 *  
 **/
function readPetFragItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_PETFRAG_NUM			=> ($index+=2)-1,		//所需碎片数量
			ItemDef::ITEM_ATTR_NAME_PETFRAG_FORM		=> $index++				//合成物品id
	);

	$file = fopen("$inputDir/item_pet_fragment.csv", 'r');
	echo "read $inputDir/item_pet_fragment.csv\n";

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
		//check config
		if (empty($conf[ItemDef::ITEM_ATTR_NAME_PETFRAG_NUM]))
		{
			trigger_error("petFrag:$data[0] num is empty!\n");
		}
		
		//如果合成所需的物品的碎片数不等于碎片物品的可叠加数,则抛出错误
		if ($conf[ItemDef::ITEM_ATTR_NAME_STACKABLE] != $conf[ItemDef::ITEM_ATTR_NAME_PETFRAG_NUM])
		{
			trigger_error("petFrag:$data[0] fragment number is not equal stackable number\n");
		}
		
		if (empty($conf[ItemDef::ITEM_ATTR_NAME_PETFRAG_FORM]))
		{
			trigger_error("petFrag:$data[0] form id is empty!\n");
		}

		$conf[ItemDef::ITEM_ATTR_NAME_USE_REQ] = array();
		$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ] = array(
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET => array (
						intval($conf[ItemDef::ITEM_ATTR_NAME_PETFRAG_FORM]) => 1
				)
		);
		unset($conf[ItemDef::ITEM_ATTR_NAME_PETFRAG_FORM]);
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */