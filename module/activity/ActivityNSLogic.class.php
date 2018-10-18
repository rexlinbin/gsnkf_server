<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActivityNSLogic.class.php 188677 2015-08-04 04:38:14Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/ActivityNSLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-08-04 04:38:14 +0000 (Tue, 04 Aug 2015) $
 * @version $Revision: 188677 $
 * @brief 
 *  
 **/
class ActivityNSLogic
{
	protected static $NULL_CONF = array(
			'version' => 0,
			'start_time' => 0,
			'end_time' => 0,
			'need_open_time' => 0,
			'data' => array(),
	);
	
	public static function inNS( $timeStamp = 0 )
	{
		if(!self::afterPlatformNS())
		{
			return false;
		}
		$checkTime = $timeStamp==0? Util::getTime():$timeStamp;
		$alresdyDays = self::getOpenDays($checkTime);
		
		if ( $alresdyDays > ActivityConf::NS_DURATION )//没有临界点问题吧
		{
			return false;
		}
		return true;
	}
	
	public static function getNSActivityByName($name,$timeArr = array())//$startTime,$endTime
	{
		Logger::debug('now get activity fron NS');
		
		$activityInfo = ActivityConfLogic::$NULL_CONF_BACKEND;
		
		if ( !isset( ActivityConf::$NS_ACTIVITY[$name] ) )
		{
			return $activityInfo;
		}
		
		if ( empty( $timeArr ) ) 
		{
			$curOpenDays = self::getOpenDays(Util::getTime());
			foreach (ActivityConf::$NS_ACTIVITY[$name][NSActivityDef::TIME_ARR] as $index => $period)
			{
				if ( $curOpenDays >= $period[0] && $curOpenDays <= $period[1] )
				{
					$curActivity = $period;
					break;
				}
			}
		}
		else
		{
			foreach ( ActivityConf::$NS_ACTIVITY[$name][NSActivityDef::TIME_ARR] as $index2 => $period2 )
			{
				$oneBeginTime = self::getTimeStamp($period2[0]);
				$oneEndTime = self::getTimeStamp($period2[1],false);
				if ( $timeArr[0] == $oneBeginTime && $timeArr[1] == $oneEndTime )
				{
					$curActivity = $period2;
					break;
				}
			}
		}
		
		if(empty($curActivity))
		{
			Logger::debug('return activity info is defalt: %s',self::$NULL_CONF);
			return $activityInfo;
		}
		
		
		$beginTime = self::getTimeStamp($curActivity[0]);
		$endTime = self::getTimeStamp($curActivity[1],false);
		$needBtsore = ActivityConf::$NS_ACTIVITY[$name][NSActivityDef::BTS_NAME];
		
		$activityInfo['start_time'] = $beginTime;
		$activityInfo['end_time'] = $endTime;
		$activityInfo['need_open_time'] = Util::getTime();
		$activityInfo['data'] = btstore_get()->$needBtsore; 
		
		if (ActivityConf::$NS_ACTIVITY[$name][NSActivityDef::NEED_TOARRAY])
		{
			$activityInfo['data'] = $activityInfo['data']->toArray();
			Logger::debug('already to array:%s',$name);
		}
		Logger::debug('return activity info: %s',$activityInfo);
		return $activityInfo;
	}
	
	public static function getOpenDays( $timeStamp )
	{
		$openserverTime=strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$alreadyDays = intval( ($timeStamp - $openserverTime)/86400 );
		
		return $alreadyDays+1;
	}
	
	public static function getTimeStamp($day,$head = true)
	{
		if ( $day <= 0 )
		{
			throw new FakeException( 'day <= 0', $day );
		}
		
		$openserverTime=strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$timestamp = $openserverTime + ($day-1)*86400;
		
		if ( !$head )
		{
			$timestamp += 86399;
		}
		
		return $timestamp;
		
	}
	
	public static function timeValid($openServerDays,$timeNeedCheck,$before)
	{
		if ($before && self::getOpenDays($timeNeedCheck)> $openServerDays)
		{
			return false;
		}
		if (!$before && self::getOpenDays($timeNeedCheck)<=$openServerDays)
		{
			return false;
		}
		return true;
	}
	
	public static function inNormalActivity($checkTime = -1)
	{
		if(!self::afterPlatformNS())
		{
			return true;
		}
		
		if($checkTime< 0 )
		{
			$checkTime = Util::getTime();
		}
		
		return self::timeValid(ActivityConf::NS_DURATION,$checkTime, false);
	}
	
	public static function getAllNSForFront()
	{
		
		$arrAllConfName = ActivityConfLogic::getAllConfName();
		$arrConf = array();
		foreach( $arrAllConfName as $name )
		{
			if( !isset($arrConf[$name]) )
			{
				$arrConf[$name] = array(
					'version' 			=> 0,
					'start_time' 		=> 0,
					'end_time'			=> 0,
					'need_open_time' 	=> 0,
					'data' 			=> '',
					'newServerActivity' => 1,
				);
				Logger::info('no conf:%s in db. return default conf', $name);
			}
		}
		
		foreach ( ActivityConf::$NS_ACTIVITY as $name => $activityInfo )
		{
			Logger::debug('now get activity of :%s',$name);
			$nsActivity = self::getNSActivityByName($name);
			$arrConf[$name]['start_time'] = $nsActivity['start_time'];
			$arrConf[$name]['end_time'] = $nsActivity['end_time'];
			$arrConf[$name]['need_open_time'] = $nsActivity['need_open_time'];
		}
		
		$frontActivity = ActivityConfLogic::$NULL_CONF_FRONT;
		$frontActivity['arrData'] = $arrConf;
		$frontActivity['validity'] = Util::getTime() + ActivityConf::VALIDITY;
		
		return $frontActivity;
		
	}
	
	
	public static function afterPlatformNS()
	{
		if ( !defined( 'PlatformConfig::NS_START_TIME' ))
		{
			throw new InterException('PlatformConfig::NS_START_TIME is not set');
		}
		
		$openserverTime=strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$ns_time = strtotime(PlatformConfig::NS_START_TIME);
		if ($openserverTime < $ns_time) //边界问题
		{
			return false;
		}
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */