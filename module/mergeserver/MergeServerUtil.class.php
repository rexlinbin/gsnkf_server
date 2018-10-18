<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerUtil.class.php 177999 2015-06-10 14:12:14Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/MergeServerUtil.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-10 14:12:14 +0000 (Wed, 10 Jun 2015) $
 * @version $Revision: 177999 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : MergeServerUtil
* Description : 合服活动内部辅助类
* Inherit     :
**********************************************************************************************************************/
class MergeServerUtil
{
	/**
	 * checkEffect 检查某种合服活动是否有效 
	 * 
	 * @param int $rewardType 活动类型 
	 * @static
	 * @access public
	 * @return bool
	 */
	public static function checkEffect($rewardType)
	{
		// 1 检查基本配置
		if (FALSE === self::checkBasicConfig())
		{
			Logger::debug('check failed because of basic config.');
			return FALSE; 
		}
		
		// 2 检查时间是否有效
		if (FALSE === self::checkRewardTime($rewardType, Util::getTime()))
		{
			Logger::debug('check failed because of activity[%s] is over.', self::getStringDesc($rewardType));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * getRewardConfig 获得某个活动的配置 
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access public
	 * @throws 
	 * @return BtstoreElement 
	 */
	public static function getRewardConfig($rewardType)
	{
		if (MergeServerDef::MSERVER_TYPE_LOGIN == $rewardType) 
		{
			return btstore_get()->MERGESERVER_LOGIN;
		}
		else if (MergeServerDef::MSERVER_TYPE_RECHARGE == $rewardType) 
		{
			return btstore_get()->MERGESERVER_RECHARGE;
		}
		else if (MergeServerDef::MSERVER_TYPE_EXP_GOLD == $rewardType) 
		{
			return btstore_get()->MERGESERVER_EXP_GOLD;
		}
		else if (MergeServerDef::MSERVER_TYPE_COMPENSATION == $rewardType) 
		{
			return btstore_get()->MERGESERVER_COMPENSATION;
		}
		else if (MergeServerDef::MSERVER_TYPE_MINERAL == $rewardType)
		{
			return btstore_get()->MERGESERVER_MINERAL;
		}
		else 
		{
			throw new InterException('mergeserver config type[%s] not exist', self::getStringDesc($rewardType));
		}
	}
	
	/**
	 * getActivityStartTime 获得活动开始时间 
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access public
	 * @return int 活动开始时间
	 */
	public static function getActivityStartTime($rewardType)
	{
		// 如果是合服补偿，开始时间直接置为合服时间，玩家可以立马领取补偿
		if ($rewardType === MergeServerDef::MSERVER_TYPE_COMPENSATION) 
		{
			return strtotime(GameConf::MERGE_SERVER_OPEN_DATE);
		}
		
		return strtotime(substr(GameConf::MERGE_SERVER_OPEN_DATE, 0, 8) . "000000") 
			             + SECONDS_OF_DAY
						 + self::getActivityOffset($rewardType);
	}
	
	/**
	 * getActivityEndTime 获得活动结束时间 
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access public
	 * @return int 活动结束时间
	 */
	public static function getActivityEndTime($rewardType)
	{
		return self::getActivityStartTime($rewardType) + self::getActivityDays($rewardType) * SECONDS_OF_DAY - 1;
	}
	
	/**
	 * getMergeTimes 获得合服次数 
	 * 
	 * @static
	 * @access public
	 * @return int 合服次数
	 */
	public static function getMergeTimes()
	{
		return  self::getMergeTimesRecursive(GameConf::$MERGE_SERVER_DATASETTING);
	}
	
	/**
	 * getOpenDate 获得开服日期 
	 * 
	 * @param $serverId 服务器id
	 * @static
	 * @access public
	 * @return int 开服日期
	 */
	public static function getOpenDate($serverId)
	{
		return self::getOpenDateRecursive($serverId, GameConf::$MERGE_SERVER_DATASETTING);
	}

	/**
	 * getMinOpenDate 获得最早开服时间 
	 * 
	 * @static
	 * @access public
	 * @return int 最早开服时间
	 */
	public static function getMinOpenDate()
	{
		return self::getMinOpenDateRecursive(GameConf::$MERGE_SERVER_DATASETTING);
	}

	/**
	 * getStringDesc 获得合服活动类型的字符串描述
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access public
	 * @return string 活动类型的字符串描述
	 */
	public static function getStringDesc($rewardType)
	{
		$des = '';

		switch ($rewardType)
		{
		case MergeServerDef::MSERVER_TYPE_LOGIN:
			$des = 'LOGIN';
			break;
		case MergeServerDef::MSERVER_TYPE_RECHARGE:
			$des = 'RECHARGE';
			break;
		case MergeServerDef::MSERVER_TYPE_EXP_GOLD:
			$des = 'EXP_GOLD';
			break;
		case MergeServerDef::MSERVER_TYPE_ARENA:
			$des = 'ARENA';
			break;
		case MergeServerDef::MSERVER_TYPE_MONTH_CARD:
			$des = 'MONTH_CARD';
			break;
		case MergeServerDef::MSERVER_TYPE_MINERAL:
			$des = 'MINERAL';
			break;
		default:
			$des = 'UNKNOW TYPE';
			break;
		}

		return $des;
	}

	/**
	 * checkBasicConfig 检查合服活动的基本配置 
	 * 
	 * @static
	 * @access public 
	 * @return bool
	 */
	public static function checkBasicConfig()
	{
		if (!defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			Logger::debug('the GameConf::MERGE_SERVER_OPEN_DATE is not defined.');
			return FALSE;
		}
	
		if (!isset(GameConf::$MERGE_SERVER_DATASETTING) || !self::checkDateSettingRecursive(GameConf::$MERGE_SERVER_DATASETTING))
		{
			Logger::warning('the GameConf::$MERGE_SERVER_DATASETTING is not set or error.');
			return FALSE;
		}
	
		if (!isset(MergeServerConf::$MSERVER_DURING_DAYS))
		{
			Logger::warning('the MergeServerConf::$MSERVER_DURING_DAYS is not set.');
			return FALSE;
		}
	
		return TRUE;
	}
	
	/**
	 * checkRewardTime 检查活动是否生效 
	 * 
	 * @param int $rewardType 活动类型 
	 * @param int $time 检测的时间，默认是当前request时间
	 * @static
	 * @access public
	 * @return bool
	 */
	public static function checkRewardTime($rewardType, $time = 0)
	{
		$start = self::getActivityStartTime($rewardType);
		$days = self::getActivityDays($rewardType);
		$end = self::getActivityEndTime($rewardType);
		$curr = ($time == 0 ? Util::getTime() : $time);
		
		Logger::debug('start:%s, end:%s, days:%s, curr:%s',
					  strftime("%Y%m%d-%H%M%S", $start), strftime("%Y%m%d-%H%M%S", $end),
					  $days, strftime("%Y%m%d-%H%M%S", $curr));
	
		if ($curr >= $start && ($curr <= $end || $days == 0))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * getDaysBetween 获得两个日期字符串相隔天数
	 *
	 * @param string $date1 日期字符串，yyyymmdd
	 * @param string $date2 日期字符串，yyyymmdd
	 * @static
	 * @access public
	 * @return bool/int 格式不对返回FALSE，否则返回天数差
	 */
	public static function getDaysBetween($date1, $date2)
	{
		if(strlen($date1) != 8 || strlen($date2) != 8 )
		{
			return FALSE;
		}
		
		return abs(intval(strtotime($date1) - strtotime($date2))) / SECONDS_OF_DAY;
	}
	
	/**
	 * getActivityOffset 获得活动的开始时间的偏移量 
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access private
	 * @return bool 偏移量
	 */
	private static function getActivityOffset($rewardType)
	{
		$time = MergeServerConf::$MSERVER_DURING_DAYS[$rewardType]['offset'];
		
		$hour = substr($time, 0, 2);
		$min = substr($time, 2, 2);
		$sec = substr($time, 4, 2);
		
		return $hour * 3600 + $min * 60 + $sec;
	}
	
	/**
	 * getActivityDays 获得活动持续天数 
	 * 
	 * @param int $rewardType 活动类型
	 * @static
	 * @access private 
	 * @return int 活动持续天数
	 */
	private static function getActivityDays($rewardType)
	{
		return MergeServerConf::$MSERVER_DURING_DAYS[$rewardType]['days'];
	}
	
	/**
	 * checkDateSettingRecursive 检测合服时间配置是否有效 
	 * 
	 * @param array $arrConfig 配置
	 * @static
	 * @access private 
	 * @return bool
	 */
	private static function checkDateSettingRecursive($arrConfig)
	{
		if (empty($arrConfig))
		{
			return FALSE;
		}
	
		foreach ($arrConfig as $key => $value)
		{
			if (EMPTY($key) || EMPTY($value))
			{
				return FALSE;
			}
				
			if (!is_array($value) && !(intval($value) == $value))
			{
				return FALSE;
			}
				
			if (is_array($value) && !self::checkDateSettingRecursive($value))
			{
				return FALSE;
			}
		}
	
		return TRUE;
	}
	
	/**
	 * getOpenDateRecursive 获得某个serverId的开服时间 
	 * 
	 * @param int $serverId 服务器id
	 * @param array $arrConfig 配置
	 * @static
	 * @access private 
	 * @return bool/int 没有配置返回FALSE,有则返回开服时间
	 */
	private static function getOpenDateRecursive($serverId, $arrConfig)
	{
		if (empty($arrConfig))
		{
			return FALSE;
		}
	
		foreach ($arrConfig as $key => $value)
		{
			if (intval($key) == $serverId && is_string($value) && intval($value) == $value)
			{
				return intval($value);
			}
		}
	
		foreach ($arrConfig as $key => $value)
		{
			if (is_array($value))
			{
				$ret = self::getOpenDateRecursive($serverId, $value);
				if (is_int($ret))
				{
					return $ret;
				}
			}
		}
	
		return FALSE;
	}
	
	/**
	 * getMergeTimesRecursive 递归获得合服次数
	 * 
	 * @param array $arrConfig 配置
	 * @static
	 * @access private 
	 * @return int 合服次数
	 */
	private static function getMergeTimesRecursive($arrConfig)
	{
		if (empty($arrConfig))
		{
			return 0;
		}
	
		$haveArr = FALSE;
		foreach ($arrConfig as $key => $value)
		{
			if (is_array($value))
			{
				$haveArr = TRUE;
				break;
			};
		}
	
		if (!$haveArr)
		{
			return 1;
		}
	
		$ret = 0;
		foreach ($arrConfig as $key => $value)
		{
			if (is_array($value))
			{
				$tmp = self::getMergeTimesRecursive($value);
				if ($tmp > $ret)
				{
					$ret = $tmp;
				}
			}
		}
	
		return $ret + 1;
	}
	
	/**
	 * getMinOpenDateRecursive 递归获得最早开服时间 
	 * 
	 * @param array $arrConfig 配置
	 * @static
	 * @access private
	 * @return int 开服时间
	 */
	private static function getMinOpenDateRecursive($arrConfig)
	{
		if (empty($arrConfig))
		{
			return FALSE;
		}
	
		$min = PHP_INT_MAX;
		foreach ($arrConfig as $key => $value)
		{
			if ($key == "self")
			{
				continue;
			}
				
			if (is_string($value) && intval($value) == $value && intval($value) < $min)
			{
				$min = intval($value);
				continue;
			}
				
			if (is_array($value) && !empty($value))
			{
				$ret = self::getMinOpenDateRecursive($value);
				if (FALSE !== $ret && $ret < $min)
				{
					$min = $ret;
					continue;
				}
			}
		}
	
		return $min;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
