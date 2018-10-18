<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnBowl.class.php 259718 2016-08-31 08:45:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/EnBowl.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:45:26 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259718 $
 * @brief 
 *  
 **/
 
class EnBowl
{
	public static function readBowlCSV($dataArr, $version, $startTime, $endTime , $needOpenTime)
	{
		$confList = array();
		
		foreach ($dataArr as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
				
			$index = 0;
			$id = intval($data[$index]);
			
			$confList[$id][BowlDef::BOWL_BUY_DAYS] = intval($data[$index+=1]);
			$rewardDays = intval($data[$index+=1]);
			$confList[$id][BowlDef::BOWL_BUY_NEED] = intval($data[$index+=1]);
			$confList[$id][BowlDef::BOWL_BUY_COST] = intval($data[$index+=1]);
			for ($i = $index + 1; $i <= $index + $rewardDays; ++$i)
			{	
				$rewards = explode(',', $data[$i]);
				foreach ($rewards as $reward)
				{
					$confList[$id][BowlDef::BOWL_BUY_REWARD][$i - $index][] = array_map('intval', explode('|', $reward));
				}
			}
			
			$buyDay = $confList[$id][BowlDef::BOWL_BUY_DAYS];
			$minDay = $buyDay + $rewardDays - 1;
			
			if ( $startTime + $minDay * SECONDS_OF_DAY - 1 > $endTime )
			{
			    throw new ConfigException('Act last is not long enough for bowl and receive reward, start: %d, end: %d, buyDays: %d, rewardDays: %d.',$startTime,$endTime,$buyDay,$rewardDays);
			}
		}
		
		return $confList;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */