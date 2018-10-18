<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FightSoulItem.php 195135 2015-08-28 04:00:37Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/FightSoulItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-08-28 04:00:37 +0000 (Fri, 28 Aug 2015) $
 * @version $Revision: 195135 $
 * @brief 
 *  
 **/
function readFightSoulItem($inputDir)
{
	//数据对应表
	$index = 0;
	$arrConfKey = array (
			ItemDef::ITEM_ATTR_NAME_TEMPLATE					=> $index++,			//物品模板ID
			ItemDef::ITEM_ATTR_NAME_QUALITY						=> ($index+=7)-1,		//物品品质
			ItemDef::ITEM_ATTR_NAME_SELL						=> $index++,			//可否出售
			ItemDef::ITEM_ATTR_NAME_SELL_TYPE					=> $index++,			//售出类型
			ItemDef::ITEM_ATTR_NAME_SELL_PRICE					=> $index++,			//售出价格
			ItemDef::ITEM_ATTR_NAME_STACKABLE					=> $index++,			//可叠加数量
			ItemDef::ITEM_ATTR_NAME_BIND						=> $index++,			//绑定类型
			ItemDef::ITEM_ATTR_NAME_DESTORY						=> $index++,			//可否摧毁
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_TYPE			=> ($index+=2)-1,		//战魂类型
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_VALUE		=> $index++,			//战魂基础经验
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EXPID		=> $index++,			//战魂升级经验表id
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRS		=> $index++,			//战魂属性数组
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVELLIMIT	=> ($index+=2)-1,		//战魂等级上限
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_BASERATIO 	=> $index++,			//强化基础等级参数
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVELRATIO	=> $index++,			//强化等级系数参数
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_SCORE		=> $index++,			//战魂评分
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_SORT			=> $index++,			//战魂排序
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_CANDEVELOP	=> $index++,			//战魂是否可以进化
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVELIMIT	=> $index++,			//战魂精炼最高等级
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRRATIO	=> $index++,			//精炼每级成长
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVECOST	=> $index++,			//精炼每级消耗
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPLV	=> $index++,			//进化需要战魂等级
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPID	=> $index++,			//进化后战魂ID
			FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPCOST  => $index++,			//进化消耗物品ID和数量组
	);
	
	//a|b|c,d|e|f => {{a,b,c},{d,e,f}}
	$arrKeyV1 = array(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPCOST);
	//a|b,d|e => {a=>b,d=>e}
	$arrKeyV2 = array(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRS);
	//1|a|b|c,2|d|e|f => {1=>{{a,b,c}},2=>{{d,e,f}}}
	$arrKeyV3 = array(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVECOST);

	$file = fopen("$inputDir/item_fightsoul.csv", 'r');
	echo "read $inputDir/item_fightsoul.csv\n";

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
			if( in_array($key, $arrKeyV1, true) )
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
							trigger_error( "invalid $key, need v2\n" );
						}
						$conf[$key][] = array2Int(str2Array($value, '|'));
					}
				}
			}
			elseif ( in_array($key, $arrKeyV2, true) )
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
							trigger_error( "invalid $key, need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]] = $ary[1];
					}
				}
			}
			elseif ( in_array($key, $arrKeyV3, true) )
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
							trigger_error( "invalid $key, need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]][] = array($ary[1], $ary[2], $ary[3]);
					}
				}
			}
			else
			{
				$conf[$key] = intval($data[$index]);
			}
		}

		//整理战魂的成长属性
		$key = FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRS;
		$index = $arrConfKey[$key] + 1;
		if(!empty($conf[$key]) && !empty($data[$index]))
		{
			$add = array();
			$arr = str2array($data[$index]);
			foreach( $arr as $value )
			{
				if(!strpos($value, '|'))
				{
					trigger_error( "invalid $key, need v2\n" );
				}
				$ary = array2Int(str2Array($value, '|'));
				$add[$ary[0]] = $ary[1];
			}
			$base = $conf[$key];
			$conf[$key] = array();
			foreach ($base as $attrId => $attrValue)
			{
				$conf[$key][$attrId][0] = $attrValue; 
				if (!empty($add[$attrId])) 
				{
					$conf[$key][$attrId][1] = $add[$attrId]; 
				}
			}
		}

		$confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
	}
	fclose($file);

	return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */