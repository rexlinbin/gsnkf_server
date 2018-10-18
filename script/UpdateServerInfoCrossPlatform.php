<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UpdateServerInfoCrossPlatform.php 199487 2015-09-17 11:21:42Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/UpdateServerInfoCrossPlatform.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-17 11:21:42 +0000 (Thu, 17 Sep 2015) $
 * @version $Revision: 199487 $
 * @brief 
 *  
 **/
 
class UpdateServerInfoCrossPlatform extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		$db = WorldCarnivalUtil::getCrossDbName();
		if (!empty($arrOption[0]))
		{
			$db = $arrOption[0];
		}
		
		$allPlatName = empty(PlatformConfig::$ALL_PALT_NAME) ? array('yueyu', 'appstore', 'android') : PlatformConfig::$ALL_PALT_NAME;
		foreach ($allPlatName as $aPlatName)
		{
			self::updateAllServerInfo($db, $aPlatName);
		}
	}

	/**
	 * 每日从杨老师那里拉取一次服务器ID对应的服务器名
	 */
	private static function updateAllServerInfo($db, $platName)
	{
		// 获取所有服的名字
		try
		{
			$platform = ApiManager::getApi(true);
			$argv = array (
					'platName' => $platName,
			);
			$allServers = $platform->users('getNameAll', $argv);
			Logger::debug('allservers are: %s', $allServers);
		}
		catch (Exception $e)
		{
			Logger::fatal('get all serverInfo from plat failed:%s', $e->getMessage() );
			return;
		}

		$serverInfoMgr = ServerInfoManager::getInstance($db);
		$proxy = new PHPProxy('module');
		foreach ($allServers as $serverId => $serverInfo)
		{
			try
			{
				$serverId = intval($serverId);
				$serverName = $serverInfo['server_name'];
				$openTime = $serverInfo['open_time'];

				$arrInfo = $proxy->getZkInfo(sprintf('/card/lcserver/lcserver#game%03d', $serverId));
				$dbName = $arrInfo['db'];

				Logger::debug('ServerId:[%s], ServerName:[%s], DbName:[%s].', $serverId, $serverName, $dbName);
				$serverInfoMgr->updateServerInfo($serverId, $serverName, $dbName, $openTime);
			}
			catch (Exception $e)
			{
				Logger::fatal('getZkInfo failed:%s, serverId:%s', $e->getMessage(), $serverId);
			}
		}
		return;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */