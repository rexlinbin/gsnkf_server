<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackUtil.class.php 259357 2016-08-30 06:42:54Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/WelcomebackUtil.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-30 06:42:54 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259357 $
 * @brief 
 *  
 **/
class WelcomebackUtil
{
	/**
	 * 获取玩家离线天数，离线时间从离线后的下一个0点开始计算
	 * @param int $offlineTime
	 * @param int $backTime 上线时间
	 */
	public static function getOfflineDay($offlineTime, $backTime)
	{
		$offlineDay = date('Ymd', $offlineTime + 86400);//获取离线后的下一天
		$offlineStamp = strtotime($offlineDay);//获取离线后的下一天0点的时间戳
		
		return intval(($backTime - $offlineStamp)/86400);
	}
	
	/**
	 * @return array
	 */
	public static function getRewardConf()
	{
		return btstore_get()->WELCOMEBACK_REWARD->toArray();
	}
	
	/**
	 * @return array
	 */
	public static function getWelcomebackConf()
	{
		return btstore_get()->WELCOMEBACK_TASK->toArray();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */