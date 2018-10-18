<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Statistics.class.php 159164 2015-02-16 07:42:12Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/statistics/Statistics.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2015-02-16 07:42:12 +0000 (Mon, 16 Feb 2015) $
 * @version $Revision: 159164 $
 * @brief
 *
 **/

class Statistics
{
	/**
	 *
	 * 登录统计
	 *
	 * @param int $login_time							登录时间
	 * @param int $logout_time							离线时间
	 *
	 * @return NULL
	 *
	 */
	public static function loginTime($login_time, $logout_time, $wallowKick=false)
	{
		if ( FrameworkConfig::DEBUG == TRUE )
		{
			return;
		}

		$login_time = intval($login_time);
		$logout_time = intval($logout_time);

		$pid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_PID);

		if ( empty($pid) )
		{
			Logger::WARNING('invalid pid in statistics!');
			return;
		}

		$group_id = RPCContext::getInstance()->getFramework()->getGroup();
		$client_ip =RPCContext::getInstance()->getFramework()->getClientIp();
		
		if (in_array($client_ip, StatisticsConfig::$ARR_WHITE_IP))
		{
			$client_ip = StatisticsConfig::WHITE_IP_TO;
		}

		$client_ip = ip2long($client_ip);

		if ($wallowKick)
		{
			$serverId = 0;
		}
		else
		{
			$serverId = StatisticsUtil::getServerId();
		}
		
		$clientUuid = RPCContext::getInstance ()->getSession ( 'global.clientUuid');
		if( empty($clientUuid) )
		{
			$clientUuid = '';
		}

		$values = array (
			StatisticsDef::ST_SQL_PID => $pid,
			StatisticsDef::ST_SQL_SERVER	=> $serverId,
			StatisticsDef::ST_SQL_LOGIN_TIME => $login_time,
			StatisticsDef::ST_SQL_LOGOUT_TIME => $logout_time,
			StatisticsDef::ST_SQL_LOGIN_IP => $client_ip,
			StatisticsDef::ST_SQL_UUID => $clientUuid,
		);

		StatisticsDAO::insertOnline($values);
		
		self::deviceLoginTime($login_time, $logout_time);
	}
	
	
	
	/**
	 *
	 * 设备登录统计
	 *
	 * @param int $loginTime							登录时间
	 * @param int $logoutTime							离线时间
	 *
	 * @return NULL
	 *
	 */
	public static function deviceLoginTime($loginTime, $logoutTime)
	{
		if ( FrameworkConfig::DEBUG == TRUE )
		{
			//return;
		}
		
		if ( !defined('PlatformConfig::TRUST_DEVICE_OPEN')
				|| PlatformConfig::TRUST_DEVICE_OPEN <= 0 )
		{
			return;
		}
	
		$onlineTime = intval($logoutTime) - intval($loginTime);
		if ( $onlineTime <= 0 )
		{
			Logger::warning('invalid onlineTime:%d', $onlineTime);
			return;
		}
	
		$pid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_PID);
		if ( empty($pid) )
		{
			Logger::WARNING('invalid pid in statistics!');
			return;
		}
		
	 	$deviceId = RPCContext::getInstance()->getSession('global.bindid');
        if ( empty($deviceId) )
        {
        	Logger::warning('not found bindid. please check');
        	return;
        }
		
        $addOnlineTime = new IncOperator($onlineTime);
        
	
		$arrValue = array (
				'pid' => $pid,
				'bindid' => $deviceId,
				'onlinetime' => $addOnlineTime,
				'created' => strtotime(date( "Y-m-d",Util::getTime())),
		);
	
		StatisticsDAO::insertDeviceOnline($arrValue);
	}

	/**
	 *
	 * 金币统计
	 *
	 * @param int $functionId						函数id
	 * @param int $deltNum							金币增加/减少数量
	 * @param int $curNum							当前值
	 * @param int $time								操作时间
	 * @param boolean $is_sub						是否为减少,减少为TRUE,增加为FALSE
	 *
	 * @return NULL
	 */
	public static function gold($functionId, $deltNum, $curNum, $pid = 0)
	{
		Logger::debug('functionId:%d, deltNum:%d, curNum:%d', $functionId, $deltNum, $curNum);
		
		if ( FrameworkConfig::DEBUG == TRUE )
		{
			return;
		}

		$functionId = intval($functionId);
		$deltNum = intval($deltNum);
		$curNum = intval($curNum);
		
		$isSub = 0;
		if($deltNum < 0)
		{
			$deltNum = -$deltNum;
			$isSub = 1;
		}		
		$time = Util::getTime();
		
		if ( empty($pid) )
		{
			$pid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_PID);
			if ( empty($pid) )
			{
				Logger::WARNING('invalid pid in statistics!');
				return;
			}
		}
		
		$clientUuid = RPCContext::getInstance ()->getSession ( 'global.clientUuid');
		if( empty($clientUuid) )
		{
			$clientUuid = '';
		}

		$values = array (
			StatisticsDef::ST_SQL_PID => $pid,
			StatisticsDef::ST_SQL_SERVER => StatisticsUtil::getServerId(),
			StatisticsDef::ST_SQL_FUNCTION => $functionId,
			StatisticsDef::ST_SQL_GOLD_DIRECTION => $isSub,
			StatisticsDef::ST_SQL_GOLD_NUM => $deltNum,
			StatisticsDef::ST_SQL_ITEM_TEMPLATE_ID => 0,
			StatisticsDef::ST_SQL_ITEM_NUM => 0,
			StatisticsDef::ST_SQL_GOLD_TIME => $time,
			StatisticsDef::ST_SQL_CUR_NUM => $curNum,
			StatisticsDef::ST_SQL_UUID => $clientUuid,
		);

		StatisticsDAO::insertGold($values);
	}

	/**
	 *
	 * 金币统计(物品相关)
	 *
	 * @param int $functionId						函数id
	 * @param int $deltNum							金币增加/减少数量
	 * @param int $time								操作时间
	 * @param int $itemTplId					物品模板id
	 * @param int $itemNum							物品数量
	 * @param boolean $is_sub						是否为减少,减少为TRUE,增加为FALSE
	 *
	 * @return NULL
	 */
	public static function gold4Item($functionId, $deltNum, $itemTplId, $itemNum, $curNum )
	{
		Logger::debug('functionId:%d, deltNum:%d, itemTplId:%d, itemNum:%d, curNum:%d',
				$functionId, $deltNum, $itemTplId, $itemNum, $curNum);
		
		if ( FrameworkConfig::DEBUG == TRUE )
		{
			return;
		}

		$functionId = intval($functionId);
		$deltNum = intval($deltNum);
		$itemTplId = intval($itemTplId);
		$itemNum = intval($itemNum);

		$isSub = 0;
		if($deltNum < 0)
		{
			$deltNum = -$deltNum;
			$isSub = 1;
		}	
		$time = Util::getTime();

		$pid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_PID);
		if ( empty($pid) )
		{
			Logger::WARNING('invalid pid in statistics!');
			return;
		}
		
		$clientUuid = RPCContext::getInstance ()->getSession ( 'global.clientUuid');
		if( empty($clientUuid) )
		{
			$clientUuid = '';
		}

		$values = array (
			StatisticsDef::ST_SQL_PID => $pid,
			StatisticsDef::ST_SQL_SERVER => StatisticsUtil::getServerId(),
			StatisticsDef::ST_SQL_FUNCTION => $functionId,
			StatisticsDef::ST_SQL_GOLD_DIRECTION => $isSub,
			StatisticsDef::ST_SQL_GOLD_NUM => $deltNum,
			StatisticsDef::ST_SQL_ITEM_TEMPLATE_ID => $itemTplId,
			StatisticsDef::ST_SQL_ITEM_NUM => $itemNum,
			StatisticsDef::ST_SQL_GOLD_TIME => $time,
			StatisticsDef::ST_SQL_CUR_NUM => $curNum,
			StatisticsDef::ST_SQL_UUID => $clientUuid,
		);

		StatisticsDAO::insertGold($values);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */