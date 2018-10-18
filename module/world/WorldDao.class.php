<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldDao.class.php 241559 2016-05-09 06:43:26Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/WorldDao.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-09 06:43:26 +0000 (Mon, 09 May 2016) $
 * @version $Revision: 241559 $
 * @brief 
 *  
 **/
class WorldDao 
{
	static $serverTable = 't_server';
	
	public static function getAllServerInfo($dbName)
	{
		$arrRet = array();
		$offset = 0;
		for ($i = 0; $i < 1024; ++$i)
		{
			$data = new CData();
			$data->useDb($dbName);
			$ret = $data->select(array('server_id', 'server_name', 'db_name', 'open_time'))
						->from(self::$serverTable)
						->where(array('server_id', '>', '0'))
						->limit($offset, DataDef::MAX_FETCH)
						->orderBy('server_id', TRUE)
						->query();
			$arrRet = array_merge($arrRet, $ret);
			if (count($ret) < DataDef::MAX_FETCH)
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		$arrRet = Util::arrayIndex($arrRet, 'server_id');
		return $arrRet;
	}
	
	public static function getArrServerInfo($dbName, $arrServerId)
	{
		$arrRet = array();
		$arrChunkServerId = array_chunk($arrServerId, CData::MAX_FETCH_SIZE);
		foreach ($arrChunkServerId as $chunkServerId)
		{
			$data = new CData();
			$data->useDb($dbName);
			$ret = $data->select( array( 'server_id','server_name','db_name', 'open_time' ) )
						->from(self::$serverTable)
						->where(array( 'server_id','IN', $chunkServerId ))
						->query();
			$arrRet = array_merge($arrRet, $ret);
		}
		
		if( empty($arrRet) )
		{
			return array();
		}
		$arrRet = Util::arrayIndex($arrRet, 'server_id');
		return $arrRet;
	}
	
	
	public static function updateServerInfo($dbName, $serverId, $serverName, $serverDbName, $openTime)
	{
		$data = new CData();
		$data->useDb($dbName);
		
		$arrValue = array(
				'server_id' => $serverId, 
		        'server_name' => $serverName,
				'db_name' => $serverDbName,
				'open_time' => $openTime,
		);
		$arrRet = $data->insertOrUpdate(self::$serverTable)
		               ->values($arrValue)
		               ->query();

		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
