<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnTravelShop.class.php 198388 2015-09-14 08:06:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/EnTravelShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-14 08:06:54 +0000 (Mon, 14 Sep 2015) $
 * @version $Revision: 198388 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/TravelShop.def.php";

class EnTravelShop
{
	public static function readTravelShopCSV($arrData, $version, $startTime, $endTime, $needOpenTime)
	{
		$lastDay = strtotime(strftime("%Y%m%d", $endTime));
		if (!FrameworkConfig::DEBUG && $endTime - $lastDay != TravelShopDef::REWARD_TIME) 
		{
			throw new ConfigException('end time is invalid');
		}
		
		$index = 1;
		$arrConfKey = array(
				TravelShopDef::DAYS => $index++,
				TravelShopDef::GOODS => $index++,
				TravelShopDef::LIMIT => $index++,
				TravelShopDef::COST => ($index+=2)-1,
				TravelShopDef::SCORE => $index++,
				TravelShopDef::PAYBACK => $index++,
				TravelShopDef::REWARD => $index++,
				TravelShopDef::DEADLINE => $index++,
		);
		
		$arrKeyV1 = array(TravelShopDef::DAYS);
		$arrKeyV2 = array(
				TravelShopDef::GOODS, 
				TravelShopDef::COST, 
				TravelShopDef::PAYBACK, 
				TravelShopDef::REWARD
		);
		
		$confList = array();
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$conf = array();
			foreach ($arrConfKey as $key => $index)
			{
				if (in_array($key, $arrKeyV1, true))
				{
					$conf[$key] = array2Int(str2array($data[$index]));
				}
				elseif (in_array($key, $arrKeyV2, true))
				{
					$conf[$key] = array();
					$arr = str2array($data[$index]);
					foreach ($arr as $value)
					{
						$ary = array2Int(str2Array($value, '|'));
						if ($key == TravelShopDef::PAYBACK) 
						{
							$confList[$key][$ary[0]] = array($ary[1], $ary[2]);	
						}
						elseif ($key == TravelShopDef::REWARD)
						{
							$confList[$key][$ary[0]][] = array($ary[1], $ary[2], $ary[3]);
						}
						else 
						{
							$conf[$key][] = $ary;
						}
					}
				}
				else
				{
					if ($key == TravelShopDef::DEADLINE) 
					{
						if (!empty($data[$index])) 
						{
							$confList[$key] = intval($data[$index]);
						}
					}
					else 
					{
						$conf[$key] = intval($data[$index]);
					}
				}
			}
			unset($conf[TravelShopDef::PAYBACK]);
			unset($conf[TravelShopDef::REWARD]);
			$confList[TravelShopDef::ALL][$data[0]] = $conf;
		}
		
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */