<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnScoreShop.class.php 160215 2015-03-05 09:58:14Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/scoreshop/EnScoreShop.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-03-05 09:58:14 +0000 (Thu, 05 Mar 2015) $
 * @version $Revision: 160215 $
 * @brief 
 *  
 **/
class EnScoreShop
{
	public static function readScoreShopCSV($dataArr, $version, $startTime, $endTime , $needOpenTime)
	{
		$index = 0;
		
		$arrConfKey = array(
				'id' => $index,
				ScoreShopDef::GAIN_POINT_DAY => ++$index,
				ScoreShopDef::TO_POINT => ($index += 2),
				ScoreShopDef::ITEMS => ++$index,
		);
		
		$confList = array();
		
		$firstDayTime = intval(strtotime(date('Y-m-d', $startTime)));
		$secondsDuration = $endTime - $firstDayTime;
		$days = intval( $secondsDuration/86400 ) + 1;
		
		if ($days > UserConf::SPEND_GOLD_DATE_NUM 
				|| $days > UserConf::SPEND_EXECUTION_DATE_NUM
				|| $days > UserConf::SPEND_STAMINA_DATE_NUM)
		{
			throw new ConfigException('Act scoreShop gain point day is longer than we kept.Gain day:%d, keep gold %d days, keep execution %d days, keep stamina %d days.',$days,UserConf::SPEND_GOLD_DATE_NUM,UserConf::SPEND_EXECUTION_DATE_NUM,UserConf::SPEND_STAMINA_DATE_NUM);
		}
		
		foreach ($dataArr as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$conf = array();
			
			foreach ($arrConfKey as $key => $index)
			{
				switch ($key)
				{
					case ScoreShopDef::TO_POINT:
						$confPoint = array_map('intval', Util::str2Array($data[$index],'|'));
						$conf[$key] = array();
						$conf[$key] = array(
								ScoreShopDef::GOLD_EACH_POINT => $confPoint[0],
								ScoreShopDef::EXECUTION_EACH_POINT => $confPoint[1],
								ScoreShopDef::STAMINA_EACH_POINT => $confPoint[2]
						);
						break;
					case ScoreShopDef::ITEMS:
						$confItems = Util::str2Array($data[$index],',');
						foreach ($confItems as $items)
						{
							$conf[$key][] = array_map('intval', Util::str2Array($items,'|'));
						}
						break;
					default:
						$conf[$key] = intval($data[$index]);
				}
			}
		}
		
		foreach ($conf[ScoreShopDef::TO_POINT] as $key => $value)
		{
			if ($value <= 0)
			{
				trigger_error("toPoint illegal.$key is $value.\n");
			}
		}
		
		$confList[ScoreShopDef::GAIN_POINT_DAY] = $conf[ScoreShopDef::GAIN_POINT_DAY];
		$confList[ScoreShopDef::TO_POINT] = $conf[ScoreShopDef::TO_POINT];
		
		//req && acq (20150302  KuaiJl说这块的req应该只有物品和银币。)
		foreach ($conf[ScoreShopDef::ITEMS] as $key => $value)
		{
			$goodsId = $key+1;
			
			switch ($value[0])
			{
				case RewardConfType::SILVER:
					$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SILVER] = $value[2];
					break;
				case RewardConfType::SOUL:
					$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SOUL] = $value[2];
					break;
				case RewardConfType::GOLD:
					$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_GOLD] = $value[2];
					break;
				case RewardConfType::ITEM:
				case RewardConfType::ITEM_MULTI:
					$item = array($value[1] => $value[2]);
					$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = $item;
					break;
				case RewardConfType::HERO:
				case RewardConfType::HERO_MULTI:
					$hero = array($value[1] => $value[2]);
					$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = $hero;
					break;
				default:
					trigger_error("unsupported goods type: $value[0].\n");
			}
			
			$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $value[3];
			$confList[ScoreShopDef::ITEMS][$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][ScoreShopDef::POINT] = $value[4];
		}
		
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */