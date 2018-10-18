<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DirectItem.php 232769 2016-03-15 09:56:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/DirectItem.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2016-03-15 09:56:03 +0000 (Tue, 15 Mar 2016) $
 * @version $Revision: 232769 $
 * @brief 
 *  
 **/

function readDirectItem($inputDir)
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
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER		=> ($index+=2)-1,		//使用获得银两
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD		=> $index++,			//使用获得金币
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION	=> $index++,			//使用获得行动力
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL		=> $index++,			//使用获得将魂
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA		=> $index++,			//使用获得耐力
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS		=> $index++,			//使用获得物品
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO		=> $index++,			//使用获得英雄
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE	=> $index++,			//使用获得竞技次数
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL  => $index++,            //使用需要用户等级
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET			=> $index++,         	//使用获得宠物
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXP			=> ($index+=3)-1,		//使用获得经验
			ItemDef::ITEM_ATTR_NAME_IS_ADD_VIP_EXP		=> $index++,			//使用获得对应金币的vip经验
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_PRESTIGE	=> $index++,			//使用获得声望
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_BOOK		=> $index++,			//使用获得赤卷天书
	);
	
	$arrKeyV2 = array(ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS, ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO);
	
	$useAcqAttrs = array(
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXP,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_PRESTIGE,
			ItemDef::ITEM_ATTR_NAME_USE_ACQ_BOOK,
	);
	
	$useReqAttrs = array(
			ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL
	);
	
	$file = fopen("$inputDir/item_direct.csv", 'r');
	echo "read $inputDir/item_direct.csv\n";
	
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
							trigger_error( "directItem:$data[0] invalid $key, need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
			else 
			{
				$conf[$key] = intval($data[$index]);
				if ( is_numeric($conf[$key]) || empty($conf[$key]) )
				{
					$conf[$key] = intval($conf[$key]);
				}
			}
		}
		
		//处理下宠物字段
		if (!empty($conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET])) 
		{
			$petId = $conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET];
			$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET] = array($petId => 1);
		}
		
		$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ] = array();
		foreach ( $useAcqAttrs as $attr )
		{
			if ( !empty($conf[$attr]) )
			{
				$conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][$attr] = $conf[$attr];
			}
			unset($conf[$attr]);
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
		
		//config check
		/*if (empty($conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ])) 
		{
			trigger_error("direct:$data[0] use acq is empty!\n");
		}*/
	
		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */