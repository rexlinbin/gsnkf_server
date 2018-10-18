<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotItem.php 251630 2016-07-14 11:49:22Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/ChariotItem.php $
 * @author $Author: BaoguoMeng $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-14 11:49:22 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251630 $
 * @brief 
 *  
 **/
function readChariotItem($inputDir)
{
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE			=> $index,			//物品模板ID
			//ItemDef::ITEM_ATTR_NAME_TYPE  =>($index+=6),       //物品类型
			ItemDef::ITEM_ATTR_NAME_QUALITY				=> ($index+=7),		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL				=> ++$index,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE			=> ++$index,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE			=>++$index,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE			=> ++$index,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND				=> ++$index,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY				=> ++$index,			//可否摧毁
			ItemDef::ITEM_ATTR_NAME_CHARIOT_TYPE =>($index+=2),
			ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE_COST=>($index+=2),
			ItemDef::ITEM_ATTR_NAME_CHARIOT_MAX_LEVEL=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_ATTR=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_GROW_ATTR=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_RESOLVE_GOT=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_BOOK_ATTR=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_REBORN_COST=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_ROUND=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_SKILL=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_FIGHT_RATIO=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL=>($index+=3),
			ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL_MUTIPLE=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_HIT=>++$index,
			ItemDef::ITEM_ATTR_NAME_CHARIOT_PHYSICAL_ATTACK_RATIO=>($index+=3),
			ItemDef::ITEM_ATTR_NAME_CHARIOT_MAGIC_ATTACK_RATIO=>++$index,
	);

	$file = fopen("$inputDir/item_warcar.csv", 'r');
	echo "read $inputDir/item_warcar.csv\n";

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
		foreach ($arrConfKey as $key=>$value)
		{
			if ($key==ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE_COST)
			{
				$enforceCost=array();
				$tmp=explode(',', $data[$value]);
				foreach ($tmp as $v)
				{
					$nextTmp=explode('|', $v);
					$enforceCost[]=array(intval($nextTmp[0]),intval($nextTmp[1]),intval($nextTmp[2]));
				}
				$conf[$key]=$enforceCost;
			}
			elseif ($key==ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_ATTR||
					$key==ItemDef::ITEM_ATTR_NAME_CHARIOT_BOOK_ATTR)
					{
						$tmp=explode(',', $data[$value]);
						foreach ($tmp as $v)
						{
							$nextTmp=explode('|', $v);
							$conf[$key][intval($nextTmp[0])]=intval($nextTmp[1]);
						}
					}
					elseif ($key==ItemDef::ITEM_ATTR_NAME_CHARIOT_GROW_ATTR)
					{
						$tmp=explode(',', $data[$value]);
						foreach ($tmp as $v)
						{
							$nextTmp=explode('|', $v);
							$conf[$key][intval($nextTmp[0])]=intval($nextTmp[1]);
						}
					}
					elseif ($key==ItemDef::ITEM_ATTR_NAME_CHARIOT_RESOLVE_GOT)
					{
						$tmp=explode('|', $data[$value]);
						$conf[$key][]=array(intval($tmp[0])=>intval($tmp[1]));
					}
					else 
					{
						$conf[$key]=intval($data[$value]);
					}
		}
		//强化消耗的配置数要大于等级，保证每次强化都是有对应配置的
		if (count($conf[ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE_COST])<$conf[ItemDef::ITEM_ATTR_NAME_CHARIOT_MAX_LEVEL])
		{
			trigger_error('enforce cost <max level ');
		}
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]]=$conf;
		
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */