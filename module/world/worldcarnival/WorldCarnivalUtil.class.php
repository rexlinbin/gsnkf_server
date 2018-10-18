<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalUtil.class.php 199483 2015-09-17 10:48:14Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalUtil.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-17 10:48:14 +0000 (Thu, 17 Sep 2015) $
 * @version $Revision: 199483 $
 * @brief 
 *  
 **/
 
class WorldCarnivalUtil
{
	/**
	 * 获得跨服db
	 * 
	 * @return string
	 */
	public static function getCrossDbName()
	{	
		return 'pirate_worldcarnival_all';
	}
	
	/**
	 * 获得玩家的pid
	 *
	 * @param int $uid
	 * @param boolean $inMyConn
	 * @throws FakeException
	 * @return int
	 */
	public static function getPid($uid, $inMyConn = TRUE)
	{
		$sessionUid = RPCContext::getInstance()->getUid();
		if ($inMyConn && $sessionUid != $uid)
		{
			throw new FakeException('not in my connection');
		}
		return EnUser::getUserObj($uid)->getPid();
	}
	
	/**
	 * 判断serverId是不是在本服上
	 *
	 * @param int $serverId
	 * @return boolean
	 */
	public static function isMyServer($serverId)
	{
		$group = RPCContext::getInstance()->getFramework()->getGroup();
		if (empty($group))
		{
			return FALSE;
		}
	
		$arrServerId = Util::getAllServerId();
		return in_array($serverId, $arrServerId);
	}
	
	/**
	 * 根据serverId返回db
	 *
	 * @param int $serverId
	 * @return int
	 */
	public static function getServerDbByServerId($serverId)
	{
		return ServerInfoManager::getInstance()->getDbNameByServerId($serverId);
	}
	
	/**
	 * 根据serverId和pid获取uid
	 * 
	 * @param number $serverId
	 * @param number $pid
	 */
	public static function getUid($serverId, $pid)
	{
		try
		{
			$group = Util::getGroupByServerId($serverId);
			$proxy = new ServerProxy();
			$proxy->init($group, Util::genLogId());
			$ret = $proxy->getArrUserByPid(array($pid), array('uid'));
			return $ret[0]['uid'];
		}
		catch (Exception $e)
		{
			Logger::fatal('WorldCarnivalUtil.getUid error serverGroup:%s', $serverId);
			throw $e;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */