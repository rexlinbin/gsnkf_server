<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ValidityCheck.class.php 192858 2015-08-20 03:47:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/validitycheck/ValidityCheck.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-08-20 03:47:52 +0000 (Thu, 20 Aug 2015) $
 * @version $Revision: 192858 $
 * @brief 
 *  
 **/
class ValidityCheck
{
	static  $sessKey = 'validtycheck.session';
	static  $platIdKeySession = 'platid.session';
	public static function isActivityValid( $name, $startTime = -1, $endTime = -1)
	{
		
		if( !defined( 'PlatformConfig::ACTIVITY_VALIDITY' ) || !PlatformConfig::ACTIVITY_VALIDITY )
		{
			return true;
		}
		
		$myPlatId = RPCContext::getInstance()->getSession( UserDef::SESSION_KEY_PLOSGN );
		Logger::debug('sourceid: %s', $myPlatId);
		if( empty( $myPlatId ) )
		{
			return true;
			//throw new InterException( 'no plat id' );
		}
		
		if( $name == ActivityName::VALIDITY )
		{
			return true;
		}
		
		if( !self::isValidityOpen(Util::getTime()))
		{
			return true;
		}
		
		if( in_array( $name, ActivityConf::$MUST_VALID_FOR_VALIDITY) )
		{
			return true;
		}
		//由于这个的问题，在做所有活动的有效性判定的时候一定要把这个方法放到其他的后面，其实
		if( ActivityNSLogic::inNS(Util::getTime()) && isset(ActivityConf::$NS_ACTIVITY[$name]) )
		{
			return true;
		}
		
		$validity = self::getValidityFromSession();
		
		if( $validity['val'] == 1)
		{
			if( $startTime != -1 && $endTime != -1 )
			{
				if( ($validity['start_time'] <= $startTime ) && $validity['end_time'] >=$endTime )
				{
					return false;
				}
				else
				{
					return true;
				}
			}
			else 
			{
				return false;
			}
			
		}
		else 
		{
			return true;
		}
	}
	
	public static function isActivityValidByOutPlatId()
	{
		if( !defined( 'PlatformConfig::ACTIVITY_VALIDITY' ) || !PlatformConfig::ACTIVITY_VALIDITY )
		{
			return true;
		}
		
		$myPlatId = RPCContext::getInstance()->getSession( UserDef::SESSION_KEY_PLOSGN );
		Logger::debug('sourceid2: %s', $myPlatId);
		if( empty( $myPlatId ) )
		{
			return true;
		}
		$validity = self::getValidityFromSession();
		if( $validity['val'] == 1)
		{
			return false;
		}
		else 
			return true;

	}
	
	public static function getValidityFromSession()
	{
		$valArr = RPCContext::getInstance()->getSession(self::$sessKey);
		if( empty( $valArr ) || Util::getTime() >= $valArr['nextRefresh'] )
		{
			$valArr = self::refreshSession();
		}
		
		return $valArr;
	}
	
	public static function isValidityOpen( $time )
	{
		$validitySession = self::getValidityFromSession();
		if( $time >= $validitySession['start_time'] && $time <= $validitySession['end_time'])
		{
			return true;
		}
		
		return false;
		
	}
	
	public static function refreshSession()
	{
		$valArr = array(
			'start_time' => 0,
			'end_time' => 0,
			'nextRefresh' => Util::getTime()+1800,
			'val' => 0,
		);
		
		if( !defined( 'PlatformConfig::ACTIVITY_VALIDITY' ) || !PlatformConfig::ACTIVITY_VALIDITY )
		{
			return $valArr;
		}
		
		$myPlatId = RPCContext::getInstance()->getSession( UserDef::SESSION_KEY_PLOSGN );
		if( empty( $myPlatId ) )
		{
			Logger::fatal( 'no plat id' );
			return $valArr;
			//throw new InterException( 'no plat id' );
		}
		$activity = EnActivity::getConfByName(ActivityName::VALIDITY);
		
		$data = $activity['data'];
		$value = 0;
		foreach ( $data as $id => $onePlat )
		{
			if($myPlatId == $onePlat['platId'] && $onePlat['switch'] == 1)
			{
				$value = 1;
			}
		}
		$valArr['start_time'] = $activity['start_time'];
		$valArr['end_time'] = $activity['end_time'];
		$valArr['nextRefresh'] =  Util::getTime() + 1800;
		$valArr['val'] = $value;
	
		Logger::info('refresh validity value: %s', $valArr);
		RPCContext::getInstance()->setSession(self::$sessKey, $valArr);
		
		return $valArr;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */