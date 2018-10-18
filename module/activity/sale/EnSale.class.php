<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnSale.class.php 60139 2013-08-19 06:07:23Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/sale/EnSale.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-08-19 06:07:23 +0000 (Mon, 19 Aug 2013) $
 * @version $Revision: 60139 $
 * @brief 
 *  
 **/
class EnSale
{
	// 需要对应限时特卖活动的商品表TODO
	public static function readSaleCSV($array)
	{
		//数据对应表
		$index = 1;
		$arrConfKey = array (
				MallDef::MALL_EXCHANGE_GOLD => $index += 5,
				MallDef::MALL_EXCHANGE_VIP => ++$index,
				MallDef::MALL_EXCHANGE_LEVEL => ++$index,
				MallDef::MALL_EXCHANGE_DISCOUNT => ++$index,
				MallDef::MALL_EXCHANGE_NUM => ++$index,
				MallDef::MALL_EXCHANGE_ITEM => ++$index,
				MallDef::MALL_EXCHANGE_DROP => ++$index,
				MallDef::MALL_EXCHANGE_SILVER => ++$index,
				MallDef::MALL_EXCHANGE_SOUL => ++$index,
				MallDef::MALL_EXCHANGE_INCRE => ++$index,
				MallDef::MALL_EXCHANGE_START => ++$index,
				MallDef::MALL_EXCHANGE_END => ++$index,
				MallDef::MALL_EXCHANGE_SERVICE => ++$index				
		);
		
		$arrKeyV2 = array(
				MallDef::MALL_EXCHANGE_DISCOUNT,
				MallDef::MALL_EXCHANGE_INCRE
		);
	
		$exchangeReq = array(
				MallDef::MALL_EXCHANGE_GOLD,
				MallDef::MALL_EXCHANGE_VIP,
				MallDef::MALL_EXCHANGE_LEVEL,
				MallDef::MALL_EXCHANGE_DISCOUNT,
				MallDef::MALL_EXCHANGE_NUM,
				MallDef::MALL_EXCHANGE_INCRE,
				MallDef::MALL_EXCHANGE_START,
				MallDef::MALL_EXCHANGE_END,
				MallDef::MALL_EXCHANGE_SERVICE
		);
	
		$exchangeAcq = array(
				MallDef::MALL_EXCHANGE_ITEM,
				MallDef::MALL_EXCHANGE_DROP,
				MallDef::MALL_EXCHANGE_SILVER,
				MallDef::MALL_EXCHANGE_SOUL
		);
	
		$confList = array();
		foreach ($array as $data)
		{
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
								trigger_error( "sale:$data[0] invalid key:$key, value:$value need v2\n" );
							}
							$ary = array2Int(str2Array($value, '|'));
							$conf[$key][$ary[0]] = $ary[1];
						}
					}	
				}
				else 
				{
					$conf[$key] = intval($data[$index]);
				}
			}
				
			//把获得物品信息整理一下
			if( !empty($conf[MallDef::MALL_EXCHANGE_ITEM]) )
			{
				$items = array($conf[MallDef::MALL_EXCHANGE_ITEM] => 1);
				$conf[MallDef::MALL_EXCHANGE_ITEM] = $items;
			}
				
			$conf[MallDef::MALL_EXCHANGE_REQ] = array();
			foreach ( $exchangeReq as $attr )
			{
				if ( !empty($conf[$attr]) )
				{
					$conf[MallDef::MALL_EXCHANGE_REQ][$attr] = $conf[$attr];
				}
				unset($conf[$attr]);
			}
				
			$conf[MallDef::MALL_EXCHANGE_ACQ] = array();
			foreach ( $exchangeAcq as $attr )
			{
				if ( !empty($conf[$attr]) )
				{
					$conf[MallDef::MALL_EXCHANGE_ACQ][$attr] = $conf[$attr];
				}
				unset($conf[$attr]);
			}
			
			if (empty($exchangeReq) && empty($exchangeAcq))
			{
				trigger_error("sale:$data[0] both exchangeReq: $exchangeReq, exchangeAcq: $exchangeAcq is empty!\n");
			}
	
			$confList[$data[0]] = $conf;
		}
	
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */