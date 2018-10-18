<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLotteryUti.class.php 254271 2016-08-02 10:28:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/SevensLotteryUti.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-02 10:28:45 +0000 (Tue, 02 Aug 2016) $
 * @version $Revision: 254271 $
 * @brief 
 *  
 **/
class SevensLotteryUtil
{
	/**
	 * 系统开始时间
	 *
	 * @return int
	 */
	public static function getStartTime()
	{
		return strtotime(SevensLotteryDef::SEVENS_LOTTERY_START);
	}
	
	/**
	 * 获得周期配置
	 * 
	 * @param bool $isNext
	 * @return array
	 */
	public static function getConf($isNext = FALSE)
	{
		$conf = btstore_get()->SEVENS_LOTTERY;
		$id = (self::getPeriodNum() + $isNext) % count($conf) + 1;
		Logger::trace('id:%d', $id);
		return $conf[$id]->toArray();
	}
	
	/**
	 * 过去的秒数
	 * 
	 * @return int
	 */
	public static function getTotalSeconds()
	{
		//当前时间-开始时间
		$totalSeconds = Util::getTime() - self::getStartTime();
		Logger::trace('total seconds:%d', $totalSeconds);
		return $totalSeconds;
	}
	
	/**
	 * 大周期的秒数
	 * 
	 * @return int
	 */
	public static function getSupperiodSeconds()
	{
		//小周期秒数累加
		$supperiodSeconds = 0;
		foreach (self::getArrPeriod() as $periodSeconds)
		{
			$supperiodSeconds += $periodSeconds;
		}
		Logger::trace('supperiod seconds:%d', $supperiodSeconds);
		return $supperiodSeconds;
	}
	
	/**
	 * 过去的大周期数
	 * 
	 * @return int
	 */
	public static function getSupperiodNum()
	{
		//floor(过去的秒数/大周期秒数数)
		$supperiodNum = floor(self::getTotalSeconds() / self::getSupperiodSeconds());
		Logger::trace('supperiod num:%d', $supperiodNum);
		return $supperiodNum;
	}
	
	/**
	 * 过去的小周期数
	 * 
	 * @return int
	 */
	public static function getPeriodNum()
	{
		//大周期数*小周期数量+小周期偏移值
		$periodNum = self::getSupperiodNum() * count(self::getArrPeriod()) + self::getPeriodOffset();
		Logger::trace('period num:%d', $periodNum);
		return $periodNum;
	}
	
	/**
	 * 小周期的偏移值
	 * 
	 * @return int
	 */
	public static function getPeriodOffset()
	{
		//当前小周期处于大周期中的偏移值
		$periodOffset = 0;
		$remainSeconds = self::getTotalSeconds() % self::getSupperiodSeconds();
		foreach (self::getArrPeriod() as $index => $periodSeconds)
		{
			if ($remainSeconds < $periodSeconds)
			{
				$periodOffset = $index;
				break;
			}
			$remainSeconds -= $periodSeconds;
		}
		Logger::trace('period offset:%d', $periodOffset);
		return $periodOffset;
	}
	
	/**
	 * 小周期的数组
	 * 
	 * @return array
	 */
	public static function getArrPeriod()
	{
		$arrPeriod = array();
		foreach (btstore_get()->SEVENS_LOTTERY as $value)
		{
			$arrPeriod[] = $value[SevensLotteryDef::PERIOD_TIME];
		}
		Logger::trace('arr period:%s', $arrPeriod);
		return $arrPeriod;
	}
	
	/**
	 * 小周期的开始和结束时间
	 * 
	 * @return array
	 */
	public static function getPeriodTime($isNext = FALSE)
	{
		//计算小周期的偏移秒数
		$secondsOffset = 0;
		$arrPeriod = self::getArrPeriod();
		$periodOffset = self::getPeriodOffset() + $isNext;
		foreach ($arrPeriod as $index => $periodSeconds)
		{
			if ($periodOffset <= $index)
			{
				break;
			}
			$secondsOffset += $periodSeconds;
		}
	
		//计算小周期的开始和结束时间
		$periodStart = self::getStartTime() + self::getSupperiodNum() * self::getSupperiodSeconds() + $secondsOffset;
		$periodEnd = $periodStart + $arrPeriod[$periodOffset % count($arrPeriod)];
		Logger::trace('period start:%s end:%s', strftime("%Y%m%d-%H%M%S", $periodStart), strftime("%Y%m%d-%H%M%S", $periodEnd));
		return array($periodStart, $periodEnd);
	}
	
	/**
	 * 是否在小周期内
	 * 
	 * @return bool
	 */
	public static function isSamePeriod($checkTime, $isNext = FALSE)
	{
		list($periodStart, $periodEnd) = self::getPeriodTime($isNext);
		return $checkTime >= $periodStart && $checkTime <= $periodEnd ? true : false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */