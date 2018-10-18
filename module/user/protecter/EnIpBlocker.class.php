<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: EnUser.class.php 234891 2016-03-25 05:19:41Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL:
 * svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/module/user/EnUser.class.php $
 * 
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 *         @date $Date: 2016-03-25 13:19:41 +0800 (星期五, 25 三月 2016) $
 * @version $Revision: 234891 $
 *          @brief
 *         
 *         
 */
 

class EnIpBlocker 
{
    const RULE_LOGIN_UID_NUM = 1;//同一个ip登陆的uid数目太多导致封号
    const RULE_LOGIN_REQ_NUM = 2;//同一个ip每个小时发送的请求量太大导致封号
    const RULE_LOGIN_REQ_AND_UID_NUM = 3;//同时满足1和2
    
    const BLACK_IP_FOREVER_WEIGHT = 10000;//永久的黑名单ip
    const VALID_TIME_INTERVAL = 86400;//每次增加有效时间的秒数
    
    /**
     * 检测ip是否在黑名单范围内
     * 
     * @param long $ip		要检测的ip
     * @return bool			是否在黑名单
     */
    public static function checkIp($ipStr)
    {
    	try 
    	{
    		// debug模式，直接返回false
    		if (FrameworkConfig::DEBUG) 
    		{
    			return FALSE;
    		}
    		
    		$ip = ip2long($ipStr);
    		
    		// 从db里取
    		$arrCond = array
    		(
    				array('ip', '=', $ip),
    		);
    		$arrField = array('ip', 'server_id', 'by_rule', 'valid_time', 'weight');
    		$arrInfo = IpBlockerDao::select($arrCond, $arrField);
    		
    		// 黑名单里如果没有这个ip，直接返回false
    		if (empty($arrInfo)) 
    		{
    			return FALSE;
    		}
    		
    		// 如果这个ip是永久黑名单，直接返回true
    		if ($arrInfo['weight'] >= self::BLACK_IP_FOREVER_WEIGHT) 
    		{
    			Logger::warning('ip:%s is black, weight:%d', $ipStr, $arrInfo['weight']);
    			return TRUE;
    		}
    		
    		// 如果有效时间是大于当前时间的，
    		if ($arrInfo['valid_time'] >= Util::getTime()) 
    		{
    			Logger::warning('ip:%s is black, valid time:%s', $ipStr, strftime('%Y%m%d %H%M%S', $arrInfo['valid_time']));
    			return TRUE;
    		}
    		
    		return FALSE;
    	} 
    	catch (Exception $e) 
    	{
    		Logger::fatal("occur exception when check ip[%d], exception[%s]", $ip, $e->getTraceAsString());
    		return FALSE;
    	}
    }    
    
    
    /**
     * 增加一个黑名单ip
     * 
     * @param long $ip
     * @param int $serverId
     * @param int $byRule
     * @param int $validTime
     * @param int $weight
     */
    public static function addBlackIp($ip, $weight = 1, $serverId = 0, $byRule = 0, $validTime = 0)
    {
    	try 
    	{    		
    		$arrField = array
    		(
    				'ip' => $ip,
    				'server_id' => $serverId,
    				'by_rule' => $byRule,
    				'valid_time' => empty($validTime) ? Util::getTime() + self::VALID_TIME_INTERVAL : $validTime,
    				'weight' => $weight,
    		);
    		
    		IpBlockerDao::insert($arrField);
    	} 
    	catch (Exception $e) 
    	{
    		Logger::fatal("occur exception when add black ip[%d], exception[%s]", $ip, $e->getTraceAsString());
    	}
    }
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */