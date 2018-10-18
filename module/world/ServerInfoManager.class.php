<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ServerInfoManager.class.php 177934 2015-06-10 10:46:09Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/ServerInfoManager.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-06-10 10:46:09 +0000 (Wed, 10 Jun 2015) $
 * @version $Revision: 177934 $
 * @brief 
 *  
 **/
class ServerInfoManager
{
	private static $gArrInstance;

	private $mDbName;
	
	private $mArrServeInfo;

	/**
	 * @return ServerInfoManager
	 */
	public static function getInstance($dbName = '')
	{
		if( empty($dbName) )
		{
			$dbName = LordwarUtil::getCrossDbName();
		}

		if( empty(self::$gArrInstance[$dbName]) )
		{
			self::$gArrInstance[$dbName] = new ServerInfoManager($dbName);
		}
		return self::$gArrInstance[$dbName];
	}

	public static function releaseInstance($dbName = '') 
	{
		if( empty($dbName) )
		{
			$dbName = LordwarUtil::getCrossDbName();
		}
		
		unset( self::$gArrInstance[$dbName] );
	}


	private function __construct($dbName)
	{
		$this->mDbName = $dbName;
	}
	
	public function getAllServerInfo()
	{
		$arrRet = WorldDao::getAllServerInfo($this->mDbName);
		$this->mArrServeInfo = $arrRet;
		return $this->mArrServeInfo;
	}
	
	public function getArrServerInfo($arrServerId)
	{
		$arrServerIdNeedFromDb = array();
		
		$arrServerInfo = array();
		
		foreach( $arrServerId as $serverId )
		{
			if( isset( $this->mArrServeInfo[$serverId] ) )
			{
				$arrServerInfo[$serverId] = $this->mArrServeInfo[$serverId];
			}
			else 
			{
				$arrServerIdNeedFromDb[] = $serverId;	
			}
		}
		
		if( !empty( $arrServerIdNeedFromDb ) )
		{
			Logger::debug('get data from db. arrServerId:%s', $arrServerIdNeedFromDb);
			
			$arrRet = WorldDao::getArrServerInfo($this->mDbName, $arrServerIdNeedFromDb);
			foreach( $arrServerIdNeedFromDb as $serverId )
			{
				if( empty( $arrRet[$serverId] ) )
				{
					Logger::fatal('not found info of serverId:%d', $serverId);
					$this->mArrServeInfo[$serverId] = array();
				}
				else
				{
					$this->mArrServeInfo[$serverId] = $arrRet[$serverId];
				}
				//$this->mArrServeInfo[$serverId] = isset($arrRet[$serverId]) ? $arrRet[$serverId] : array();

				$arrServerInfo[$serverId] = $this->mArrServeInfo[$serverId];
			}
		}
		
		return $arrServerInfo;
	}
	
	public function getArrDbName($arrServerId)
	{
		$arrServerInfo = $this->getArrServerInfo($arrServerId);
		$arrDbName = array();
		foreach( $arrServerId as $serverId )
		{
			if( empty($arrServerInfo[$serverId]) )
			{
				throw new InterException('not found serverInfo. serverId:%d', $serverId);
			}
			$arrDbName[$serverId] = $arrServerInfo[$serverId]['db_name'];
		}
		return $arrDbName;
	}
	
	public function getArrServerName($arrServerId)
	{
		$arrServerInfo = $this->getArrServerInfo($arrServerId);
		$arrServerName = array();
		foreach( $arrServerId as $serverId )
		{
			if( empty($arrServerInfo[$serverId]) )
			{
				throw new InterException('not found serverInfo. serverId:%d', $serverId);
			}
			$arrServerName[$serverId] = $arrServerInfo[$serverId]['server_name'];
		}
		return $arrServerName;
	}
	

	public function getServerInfoByServerId($serverId)
	{
		$arrServerInfo = $this->getArrServerInfo(array($serverId));
		return $arrServerInfo[$serverId];
	}
	
	
	public function getDbNameByServerId($serverId)
	{
		$arrServerInfo = $this->getArrServerInfo(array($serverId));

		if( empty($arrServerInfo[$serverId]) )
		{
			throw new InterException('not found serverInfo. serverId:%d', $serverId);
		}
		
		return $arrServerInfo[$serverId]['db_name'];
	}
	
	public function getServerNameByServerId($serverId)
	{
		$arrServerInfo = $this->getArrServerInfo(array($serverId));

		if( empty($arrServerInfo[$serverId]) )
		{
			throw new InterException('not found serverInfo. serverId:%d', $serverId);
		}
		
		return $arrServerInfo[$serverId]['server_name'];
	}
	
	
	public function updateServerInfo($serverId, $serverName, $serverDbName, $openTime)
	{
		$this->mArrServeInfo[$serverId] = array(
			'server_id' => $serverId, 
		    'server_name' => $serverName,
			'db_name' => $serverDbName,
			'open_time' => $openTime,
		);
		
		WorldDao::updateServerInfo($this->mDbName, $serverId, $serverName, $serverDbName, $openTime);
	}
	
	
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */