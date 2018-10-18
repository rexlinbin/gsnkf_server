<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: TimeInterval.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/TimeInterval.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class TimeIntervalDef
{
	const DAY_TIME			=	86400;
	const MAX_WHILE_TIME	=	1024;
	const WEEKEND			=	7;
}

class TimeInterval
{
	/**
	 * 根据配置，获取下一天，如果得不到下一天，那么返回当日
	 *
	 * @param int	$time							当前时刻
	 * @param int	$end_time						截止时间,如果是0,表示没有截止时间
	 * @param array $day_list						设定好的日期数组
	 * @param array $week_list						设定好的星期数组
	 *
	 * @return
	 */
	private static function getNextDay($time, $end_time, $day_list, $week_list)
	{
		for ($i = 0; $i< TimeIntervalDef::MAX_WHILE_TIME; $i++)
		{
			if ( !empty($end_time) && $time > $end_time )
			{
				break;
			}

			// 当前的年月日星期(1-7)
			$current_year = date("Y", $time);
			$current_month = date("m", $time);
			$current_day = date("d", $time);
			$current_week = date("N", $time);

			// 如果两个都不为空，则需要重叠判断
			if (!empty($day_list) && !empty($week_list))
			{
				// 如果恰好合适，就返回计算好的日期
				if (in_array($current_day, $day_list) && in_array($current_week, $week_list))
				{
					return $time;
				}
				// 不然就加一天
				else
				{
					$time += TimeIntervalDef::DAY_TIME;
				}
			}
			// 如果只设置了日期
			else if (!empty($day_list))
			{
				foreach ($day_list as $day)
				{
					if ($day >= $current_day && $day <= intval(date('t', $time)))
					{
						return $time + ($day - $current_day) * TimeIntervalDef::DAY_TIME;
					}
				}
				// 如果循环一遍都没找到,检查下个月
				$time += ( intval(date('t', $time)) - $current_day + 1) * TimeIntervalDef::DAY_TIME;
			}
			// 如果只设置了星期
			else if (!empty($week_list))
			{
				foreach ($week_list as $week)
				{
					// 当前的星期大于配置星期，那么看下一个
					if ($week >= $current_week)
					{
						return $time + ($week - $current_week) * TimeIntervalDef::DAY_TIME;
					}
				}
				// 如果循环一遍都没找到,检查下个星期
				$time += ( TimeIntervalDef::WEEKEND - $current_week + 1) * TimeIntervalDef::DAY_TIME;
			}
			else
			{
				// 如果都没设置，则返回当前日期够用了
				return $time;
			}
		}
		Logger::DEBUG('foreach :%d end!', TimeIntervalDef::MAX_WHILE_TIME);
		return 0;
	}

	/**
	 *
	 * 得到当前可用的时间区间(!import $day_start_times, $day_end_times, $day_list, $week_list需要排序)
	 * (区间为左闭右开)
	 *
	 * @param int $cur_time							当前时间
	 * @param int $start_time						活动开始时间
	 * @param int $end_time							活动结束时间
	 * @param array $day_start_times				活动每天的开始时间数组(要求使用sort排序)
	 * @param array $day_end_times					活动每天的结束时间数组(要求使用sort排序)
	 * @param array $day_list						每月的那几天有活动(要求使用sort排序)
	 * @param array $week_list						每周的那几天有活动(要求使用sort排序)
	 *
	 * @throws Exception							如果参数错误会抛出config异常
	 *
	 *
	 * @return array								最近一次的活动区间段
	 * <code>
	 * [
	 * 		start_time:int
	 * 		end_time:int
	 * ]
	 * </code>
	 */
	public static function getTimeInterval($cur_time, $start_time, $end_time, $day_start_times, $day_end_times,
				$day_list, $week_list)
	{
		$cur_day_time = mktime(0, 0, 0, date('m', $cur_time), date('d', $cur_time), date('Y', $cur_time));

		//获取刷新点的刷新时刻
		if (empty($day_start_times) || empty($day_end_times) || count($day_start_times) != count($day_end_times) )
		{
			Logger::fatal("day start times or day end times is NULL or is not equal.");
			throw new Exception('config');
		}

		Logger::debug('current time:%d', $cur_time);
		Logger::debug('The start time of day is %s', $day_start_times);
		Logger::debug('The end time of day is %s', $day_end_times);

		//如果整个活动的开始时间没有被设置或者小于当前天的时间值,则开始时间设置为当天的时间
		if ( empty($start_time) || $start_time < $cur_day_time )
		{
			$start_time = $cur_day_time;
		}


		//如果当前时间超过了活动结束时间,则直接返回
		if ($cur_time > $end_time)
		{
			Logger::DEBUG('cur_time:%d > end_time:%d', $cur_time, $end_time);
			return array();
		}
		//如果还没有开始
		else if ($cur_time < $start_time)
		{
			Logger::DEBUG('cur_time:%d < start_time:%d', $cur_time, $start_time);
			//使用活动开始时间得到第一次的时间
			$next_day_time = self::getNextDay($start_time, $end_time, $day_list, $week_list);
			if ( empty($next_day_time) )
			{
				return array();
			}
			else
			{
				return array (
					$next_day_time + $day_start_times[0],
					$next_day_time + $day_end_times[0],
				);
			}
		}

		$next_day_time = self::getNextDay($cur_day_time, $end_time, $day_list, $week_list);
		if ( $next_day_time > $cur_day_time )
		{
			Logger::DEBUG('first valid day > cur_day');
			return array(
				$next_day_time + $day_start_times[0],
				$next_day_time + $day_end_times[0],
			);
		}
		else if ( $next_day_time == $cur_day_time )
		{
			Logger::DEBUG('first valid day = cur_day');
			//比较每日的时间段
			for ( $i = 0; $i < count($day_start_times); $i++)
			{
				//如果在当前时间段内，或者还没有到开始时间，则match
				if ( ($cur_time >= $next_day_time + $day_start_times[$i] && $cur_time < $next_day_time + $day_end_times[$i]) ||
					$cur_time < $next_day_time+$day_start_times[$i] )
				{
					return array(
						$next_day_time + $day_start_times[$i],
						$next_day_time + $day_end_times[$i],
					);
				}
			}
			//如果当天的时间点都不合适,则从明天开始查找
			Logger::DEBUG('cur_day all time interval is not match');
			$next_day_time = self::getNextDay($cur_day_time + TimeIntervalDef::DAY_TIME, $end_time, $day_list, $week_list);
			if ( empty($next_day_time) )
			{
				return array ();
			}
			else
			{
				return array(
					$next_day_time + $day_start_times[0],
					$next_day_time + $day_end_times[0],
				);
			}
		}
		else
		{
			Logger::DEBUG('can not find a time interval');
			return array();
		}
	}

	/**
	 * 根据配置，获取下一天，如果得不到下一天，那么返回当日
	 *
	 * @param int	$time							当前时刻
	 * @param int	$start_time						截止时间,如果是0,表示没有截止时间
	 * @param array $day_list						设定好的日期数组
	 * @param array $week_list						设定好的星期数组
	 *
	 * @return
	 */
	private static function getBeforeDay($time, $start_time, $day_list, $week_list)
	{
		for ($i = 0; $i< TimeIntervalDef::MAX_WHILE_TIME; $i++)
		{
			if ( !empty($start_time) && $time < $start_time )
			{
				break;
			}

			// 当前的年月日星期(1-7)
			$current_year = date("Y", $time);
			$current_month = date("m", $time);
			$current_day = date("d", $time);
			$current_week = date("N", $time);

			// 如果两个都不为空，则需要重叠判断
			if (!empty($day_list) && !empty($week_list))
			{
				// 如果恰好合适，就返回计算好的日期
				if (in_array($current_day, $day_list) && in_array($current_week, $week_list))
				{
					return $time;
				}
				// 不然就加一天
				else
				{
					$time -= TimeIntervalDef::DAY_TIME;
				}
			}
			// 如果只设置了日期
			else if (!empty($day_list))
			{
				foreach ($day_list as $day)
				{
					if ($day >= $current_day && $day <= intval(date('t', $time)))
					{
						return $time - ($day - $current_day) * TimeIntervalDef::DAY_TIME;
					}
				}
				// 如果循环一遍都没找到,检查上个月
				$time -= $current_day * TimeIntervalDef::DAY_TIME;
			}
			// 如果只设置了星期
			else if (!empty($week_list))
			{
				foreach ($week_list as $week)
				{
					// 当前的星期大于配置星期，那么看下一个
					if ($week <= $current_week)
					{
						return $time - ($week - $current_week) * TimeIntervalDef::DAY_TIME;
					}
				}
				// 如果循环一遍都没找到,检查上个星期
				$time -= $current_week * TimeIntervalDef::DAY_TIME;
			}
			else
			{
				// 如果都没设置，则返回当前日期够用了
				return $time;
			}
		}
		Logger::DEBUG('foreach :%d end!', TimeIntervalDef::MAX_WHILE_TIME);
		return 0;
	}

	/**
	 *
	 * 得到上个可用的时间区间(!import $day_start_times, $day_end_times, $day_list, $week_list需要排序)
	 *
	 * @param int $cur_time							当前时间
	 * @param int $start_time						活动开始时间
	 * @param int $end_time							活动结束时间
	 * @param array $day_start_times				活动每天的开始时间数组(要求使用rsort排序)
	 * @param array $day_end_times					活动每天的结束时间数组(要求使用rsort排序)
	 * @param array $day_list						每月的那几天有活动(要求使用rsort排序)
	 * @param array $week_list						每周的那几天有活动(要求使用rsort排序)
	 *
	 * @throws Exception							如果参数错误会抛出config异常
	 *
	 *
	 * @return array								最近一次的活动区间段
	 * <code>
	 * [
	 * 		start_time:int
	 * 		end_time:int
	 * ]
	 * </code>
	 */
	public static function getTimeIntervalBefore($cur_time, $start_time, $end_time, $day_start_times, $day_end_times,
				$day_list, $week_list)
	{
		$cur_day_time = mktime(0, 0, 0, date('m', $cur_time), date('d', $cur_time), date('Y', $cur_time));

		//获取刷新点的刷新时刻
		if (empty($day_start_times) || empty($day_end_times) || count($day_start_times) != count($day_end_times) )
		{
			Logger::fatal("day start times or day end times is NULL or is not equal.");
			throw new Exception('config');
		}

		Logger::debug('current time:%d', $cur_time);
		Logger::debug('The start time of day is %s', $day_start_times);
		Logger::debug('The end time of day is %s', $day_end_times);

		//如果整个活动的结束时间没有被设置或者小于当前天的时间值,则结束时间设置为当天的时间
		if ( empty($end_time) || $end_time < $cur_day_time )
		{
			$end_time = $cur_day_time;
		}


		//如果当前时间小于活动开始时间,则直接返回
		if ($cur_time < $start_time)
		{
			Logger::DEBUG('cur_time:%d < start_time:%d', $cur_time, $start_time);
			return array();
		}
		//如果已经结束
		else if ($cur_time > $end_time)
		{
			Logger::DEBUG('cur_time:%d > end_time:%d', $cur_time, $end_time);
			//使用活动结束时间得到最后一次的时间
			$before_day_time = self::getBeforeDay($end_time, $start_time, $day_list, $week_list);
			if ( empty($before_day_time) )
			{
				return array();
			}
			else
			{
				return array (
					$before_day_time + $day_start_times[0],
					$before_day_time + $day_end_times[0],
				);
			}
		}

		$before_day_time = self::getBeforeDay($cur_day_time, $start_time, $day_list, $week_list);
		if ( $before_day_time < $cur_day_time )
		{
			Logger::DEBUG('first valid day < cur_day');
			return array(
				$before_day_time + $day_start_times[0],
				$before_day_time + $day_end_times[0],
			);
		}
		else if ( $before_day_time == $cur_day_time )
		{
			Logger::DEBUG('first valid day = cur_day');
			//比较每日的时间段
			for ( $i = 0; $i < count($day_start_times); $i++)
			{
				if ( $cur_time >= $before_day_time + $day_end_times[$i] )
				{
					return array(
						$before_day_time + $day_start_times[$i],
						$before_day_time + $day_end_times[$i],
					);
				}
			}
			//如果当天的时间点都不合适,则从昨天开始查找
			Logger::DEBUG('cur_day all time interval is not match');
			$before_day_time = self::getBeforeDay($cur_day_time - TimeIntervalDef::DAY_TIME, $start_time, $day_list, $week_list);
			if ( empty($before_day_time) )
			{
				return array ();
			}
			else
			{
				return array(
					$before_day_time + $day_start_times[0],
					$before_day_time + $day_end_times[0],
				);
			}
		}
		else
		{
			Logger::DEBUG('can not find a time interval');
			return array();
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */