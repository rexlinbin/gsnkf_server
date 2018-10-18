<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

class RestrictUser
{
	public static function needDefendServer()
	{
		if ( !defined('PlatformConfig::PLAT_NAME') || !in_array(PlatformConfig::PLAT_NAME, array('android', 'appstore', 'yueyu') ) )
		{
			return false;
		}
	
		$openserverTime = strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$dayIndex = intval( (Util::getTime() - $openserverTime) / SECONDS_OF_DAY );
	
		return $dayIndex < 3;
	}
	
	public static function beforeCreate()
	{
		if( !self::needDefendServer() )
		{
			return array();
		}
		$info = self::getIpCreateInfo();
		if ( self::needBlock($info, 'create') )
		{
			Logger::warning('block create. info:%s', implode(',', $info));
			throw new Exception('close');
		}
		return $info;
	}
	
	public static function afterCreate($info, $uid)
	{
		if( !self::needDefendServer() )
		{
			return;
		}
		if ( empty($info) )
		{
			$info = self::getIpCreateInfo();
		}
		$info[0] += 1;
		$info[1] = Util::getTime();
		if( $info[0] <= self::getInitBlockNum() && $uid > 0 )
		{
			$info[2] = $uid;
		}
		self::setIpCreateInfo($info);
	}
	
	public static function beforeLogin($uid)
	{
		if( !self::needDefendServer() )
		{
			return;
		}
	
		$userObj = EnUser::getUserObj($uid);
		$level = $userObj->getLevel();
		$gold = User4BBpayDao::getSumGoldByUid($uid, 0);
		if (  $level >= 15 || $gold > 0  )
		{
			Logger::debug('uid:%d, level:%d, gold:%d ignore', $uid, $level, $gold);
			return;
		}
	
		$info = self::getIpCreateInfo();
		if ( self::needBlock($info, 'login', $uid) )
		{
			$banTime = Util::getTime() + 3*SECONDS_OF_DAY;
			$userObj->ban($banTime, "账号封停，请联系客服");
			$userObj->update();
			Logger::warning('block login. info:%s', implode(',', $info));
			throw new Exception('close');
		}
	}
	
	public static function getInitBlockNum()
	{
		return 30;
	}
	
	public static function needBlock($info, $type, $uid = 0)
	{
		if ( $info[0] < self::getInitBlockNum()  )
		{
			return false;
		}
		switch ( $type )
		{
			case 'login':
				if(  $uid <= $info[2] )
				{
					return true;
				}
				break;
			case 'create':
				if( Util::getTime() - $info[1] < 1800 )
				{
					return true;
				}
				break;
			default:
				break;
		}
		return false;
	}
	
	public static function getIpCreateInfo( $ip = '')
	{
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
		$key = 'ipcreate_'.ip2long( $ip );
		$ret = McClient::get($key);
		if ( empty($ret) )
		{
			return array(0,0,0);
		}
		return $ret;
	}
	
	public static function setIpCreateInfo( $info, $ip = '' )
	{
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
		if ( empty($info) )
		{
			Logger::fatal('empty info');
			return;
		}
		$key = 'ipcreate_'.ip2long( $ip );
		$ret = McClient::set($key, $info, 86400);
		if ( $ret != 'STORED' )
		{
			Logger::info('set ip create info failed. info:%s', $info);
		}
		Logger::info('set ip create info. info:%s', $info);
	}
	
	
	public static function needRestrictRequestSpeed()
	{
		if ( !defined('PlatformConfig::PLAT_NAME') || !in_array(PlatformConfig::PLAT_NAME, array('android', 'appstore', 'yueyu') ) )
		{
			return false;
		}
	
		$openserverTime = strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$dayIndex = intval( (Util::getTime() - $openserverTime) / SECONDS_OF_DAY );
	
		return $dayIndex < 3;
	}
	
	/*
	 * 现在战斗cd存储的是什么时候可以战斗，而不是上次战斗的时间。所以这里传过来的cd时间是在原来基础上新增的cd时间
	 */
	public static function checkFightCdTime($uid, $extraCd = 2, $immuneLevel = 15)
	{
		if( !self::needRestrictRequestSpeed() )
		{
			return;
		}
		$userObj = EnUser::getUserObj($uid);
		if ( $userObj->getLevel() >= $immuneLevel || $userObj->getVip() > 0 )
		{
			return;
		}
	
		Logger::debug('checkFightCdTime delt:%d', Util::getTime() - $userObj->getFightCdTime() );
		if( Util::getTime() < $userObj->getFightCdTime() + $extraCd )
		{
			Logger::warning('checkFightCdTime failed. delt:%d', Util::getTime() - $userObj->getFightCdTime() );
			throw new FakeException('checkFightCdTime failed');
		}
		return;
	}
	
	
	//上面是限制创建账号，以下是限制一个ip一段时间内登陆的账号个数
	public static function getIpLoginInfo( $ip = '')
	{
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
		$key = 'iplogin_'.ip2long( $ip );
		$ret = McClient::get($key);
		if ( empty($ret) )
		{
			return array(
				'uids' => array(),
			);
		}
		return $ret;
	}
	
	public static function setIpLoginInfo( $info, $ip = '' )
	{
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
		if ( empty($info) )
		{
			Logger::fatal('empty info');
			return;
		}
		$key = 'iplogin_'.ip2long( $ip );
		$ret = McClient::set($key, $info, 86400);
		if ( $ret != 'STORED' )
		{
			Logger::info('set ip login info failed. info:%s', $info);
		}
		Logger::info('set ip login info. idNum:%d', count($info['uids']));
		//Logger::info('set ip login info. info:%s', $info);
	}
	
	public static function canIpLogin($uid, $ip = '')
	{
		if( !self::needRestrictRequestSpeed() )
		{
			return true;
		}
		
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
		
		$userObj = EnUser::getUserObj($uid);
		$level = $userObj->getLevel();
		$vip = $userObj->getVip();
		if (  $level >= 40 || $vip > 0  )
		{
			Logger::debug('uid:%d, level:%d, vip:%d ignore', $uid, $level, $vip);
			return true;
		}
		
		$maxLoginUidNum = 30;
		$info = self::getIpLoginInfo($ip);
		
		if ( count($info['uids']) > $maxLoginUidNum )
		{
			Logger::warning('ip:%s cant login. uids:%s', $ip, implode(',', $info['uids']));
			return false;
		}
		
		if( !in_array($uid, $info['uids']) )
		{
			$info['uids'][] = $uid;
			self::setIpLoginInfo($info, $ip);
		}
		
		if (EnIpBlocker::checkIp($ip)) 
		{
			Logger::warning('ip:%s cant login. black', $ip);
			return false;
		}
		
		return self::canIpLoginByConnectionNum($uid, $ip);
	}
	
	public static function canIpLoginByConnectionNum($uid, $ip = '')
	{
	    $conNumOfIp = 0;
	    try 
		{
			$proxy = new PHPProxy ( 'lcserver' );
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
			$conNumOfIp = $proxy->getConnNumOfIp( $ip );
		}
		catch(Exception $e)
		{
			Logger::warning("getConnNumOfIp fail. err:%s", $e->getMessage());
		}
		
		if($conNumOfIp > 4)
		{
		    Logger::warning('uid:%s same ip login too many uid. conNumOfIp:%d', $uid, $conNumOfIp);
		    return false;
		}
		
	    return true;
	}
	
} 



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */