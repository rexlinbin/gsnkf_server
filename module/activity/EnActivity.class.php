<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnActivity.class.php 198269 2015-09-14 02:05:57Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/EnActivity.class.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-09-14 02:05:57 +0000 (Mon, 14 Sep 2015) $
 * @version $Revision: 198269 $
 * @brief 
 *  
 **/



class EnActivity
{
	public static $confBuff = array();
	
	/**
	 * 获取某个配置
	 * 
	 * 注意！ 业务模块获取配置后必须先判断活动是否开启，如果未开启，则不保证活动配置是正确的。
	 * 原因：当平台那边缺少某个活动的配置时，此接口返回一个没有开启的活动配置@see ActivityConfLogic::$NULL_CONF_BACKEND
	 * @param string $name
	 * 
	 * @return
	 * <code>
	 * 		{
	 * 			start_time:int		开始时间
	 * 			end_time:int			结束时间
	 * 			data => array{}		配置数据
	 * 		}
	 * </code>
	 */
	public static function getConfByName($name)
	{
		if( isset(self::$confBuff[$name]) )
		{
			Logger::trace('get conf:%s from buff. ret:%s', $name, self::$confBuff[$name]);
			return self::$confBuff[$name];
		}
		
		//是新服，要新服活动，直接给
		if (ActivityNSLogic::inNS()&& isset( ActivityConf::$NS_ACTIVITY[$name] ))
		{
			Logger::debug('now inNs for getConfByName');
			$activityConf = ActivityNSLogic::getNSActivityByName($name);
		}
		else
		{
			$version = RPCContext::getInstance()->getSession(ActivityDef::SESSION_KEY_VERSION);
			if(empty($version))
			{
				$version = 0;
				Logger::debug('cant get version from session');
			}
				
			$ret = ActivityConfLogic::getConf4Backend($name, $version);
			Logger::debug('before getrealconf:%s',$ret);
			$ret = ActivityConfLogic::getRealConf($name, $ret);
			Logger::debug('after getrealconf:%s',$ret);
			
			//要全服活动，直接给
			if( ActivityConfLogic::isWholeServerActivity($name,$ret) )
			{
				Logger::debug('is whole server activity');
				$activityConf = $ret;
			}
			else 
			{
				//================================新服与sess刷新的冲突
				if ( in_array( $name , ActivityConf::$MULCONF_ACTIVITY) ) 
				{
					$activityConf = $ret;
				}
				
				//新服期间既不要新服也不要全服，啥也不给
				elseif( ActivityNSLogic::inNS() )
				//================================新服与sess刷新的冲突
				{
					Logger::debug('in new server');
					$activityConf = ActivityConfLogic::$NULL_CONF_BACKEND;
				}
				else 
				{
					//不是新服期间了，要的活动是无效的，不给
					if (!ActivityNSLogic::inNormalActivity($ret['start_time'])) 
					{
						Logger::debug('normal activity, start time within ns time');
						$activityConf = ActivityConfLogic::$NULL_CONF_BACKEND;
					}
					else 
					{
						Logger::debug('not ns, effect ac');
						//不是新服，要的活动是有效的，给
						$activityConf = $ret;
					}
				}
			}
			
			//validity====
			if( $name != ActivityName::VALIDITY )
			{
				if( !ValidityCheck::isActivityValid($name, $activityConf['start_time'], $activityConf['end_time'])
				/* &&!ActivityConfLogic::isWholeServerActivity($name, $activityConf) */)
				{
					Logger::debug('name: %s unset by validity', $name);
					$activityConf = ActivityConfLogic::$NULL_CONF_BACKEND;
				}
			}
			
		}

		self::$confBuff[$name] = $activityConf;
		Logger::trace('get conf:%, ret:%s', $name, $activityConf);
		
		return $activityConf;
	}
	
	
	/**
	 * 根据活动时间获取活动配置。一般用于当活动结束后，获取活动配置用
	 * @param string $name
	 * @param int $startTime
	 * @param int $endTime
	 */
	public static function getConfByNameAndTime($name, $startTime, $endTime)
	{
		if (ActivityNSLogic::inNS($startTime)&&ActivityNSLogic::inNS($endTime))
		{
			$conf = ActivityNSLogic::getNSActivityByName($name,array($startTime,$endTime));
			if ($conf['need_open_time'] == 0)//TODO
			{
				throw new InterException('not found config:%s use time: %d %d',$name,$startTime,$endTime );
			}
		}
		elseif( ActivityNSLogic::timeValid(ActivityConf::NS_DURATION, $startTime, false) ) 
		{
			$conf = ActivityConfDao::getByNameAndTime($name, $startTime, $endTime, ActivityDef::$ARR_CONF_FIELD);
		}
		
		if( empty($conf) )
		{
			throw new InterException('not found version:%d of config:%s', version, $name);
		}
		return $conf;
	}
	
	public static function isOpen($name)
	{
		$ret = self::getConfByName($name);
		$now = Util::getTime();
		if( $now >= $ret['start_time'] && $now <= $ret['end_time'] 
			&& strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME) <= $ret['need_open_time'])
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 返回某个时间（默认当前）是活动的第几天。如果不爱活动期间返回-1，如果在活动期间就从0开始（0即为活动的第一天）
	 * @param string $name
	 * @param int $timeStamp
	 * @throws FakeException
	 * @return number
	 */
	public static function getActivityDay($name, $timeStamp = 0)
	{
		if ( !self::isOpen($name) )
		{
			return -1;
		}
		
		if (!empty( $timeStamp ))
		{
			if (!is_numeric($timeStamp))
			{
				throw new FakeException( 'timestamp should be a num,%s',$timeStamp );	
			}
			$time = $timeStamp;
		}
		else 
		{
			$time = Util::getTime();
		}
		
		$confInfo = self::getConfByName($name);
		$startTime = $confInfo['start_time'];
		if( $startTime > $time )
		{
			return -1;
		}
		
		$firstDayTime = intval(strtotime(date('Y-m-d', $startTime)));
		
		$secondsDuration = $time-$firstDayTime;
		$days = intval( $secondsDuration/86400 );
		
		return $days;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */