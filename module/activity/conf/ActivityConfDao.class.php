<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActivityConfDao.class.php 231110 2016-03-05 06:30:44Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/conf/ActivityConfDao.class.php $
 * @author $Author: GuohaoZheng $(wuqilin@babeltime.com)
 * @date $Date: 2016-03-05 06:30:44 +0000 (Sat, 05 Mar 2016) $
 * @version $Revision: 231110 $
 * @brief 
 *  
 **/

class ActivityConfDao
{
	const tblName = 't_activity_conf';
	
	
	public static function getArrCurConf($arrName, $arrField)
	{
		$arrRet = array();
		foreach($arrName as $name)
		{
			$ret = self::getCurConfByName($name, $arrField);
			if( empty($ret) )
			{
				Logger::info('no conf:%s in db', $name);
				continue;
			}
			Logger::info('get conf:%s, version:%d', $name, $ret['version']);
			$arrRet[$name] = $ret;
		}
		return $arrRet;
	}

	
	public static function getCurConfByName($name, $arrField)
	{
		//最大的版本对应的配置即为当前版本
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblName)
				->where('name', '==', $name)->orderBy('version', false)->limit(0, 1)->query();
		if (empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function getTrunkVersion()
	{
		$data = new CData();
		$ret = $data->select(array('max(version)'))->from(self::tblName)->where('version', '>', 0)->query();
		if (empty($ret))
		{
			return 0;
		}
		return current($ret[0]);
	}
	
	public static function getByNameAndVersion($name, $version, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblName)
				->where('name', '==', $name)->where('version', '<=', $version)
				->orderBy('version', false)->limit(0, 1)->query();
		if (empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function getByNameAndTime($name, $startTime, $endTime, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblName)
				->where('name', '==', $name)
				->where('start_time', '=', $startTime)
				->where('end_time', '=', $endTime)
				->orderBy('version', false)->limit(0, 1)->query();
		if (empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insert($arrField)
	{
		$data = new CData();
		
		try
		{
			$data->insert(self::tblName)->values($arrField)->query();
		}
		catch (Exception $e)
		{
			throw new SysException('db failed. err:%s', $e->getMessage ());
		}
	}
	
	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		
		try
		{
			$data->insertOrUpdate(self::tblName)->values($arrField)->query();
		}
		catch (Exception $e)
		{
			throw new SysException('db failed. err:%s', $e->getMessage ());
		}

	}
	
	public static function getLastConfContainTime($name, $time, $arrField)
	{
	    $data = new CData();
	    $ret = $data->select($arrField)
	               ->from(self::tblName)
	               ->where('name', '==', $name)
	               ->where('start_time', '<=', $time)
	               ->where('end_time', '>=', $time)
	               ->orderBy('version', FALSE)
	               ->limit(0, 1)
	               ->query();
	    
	    return empty($ret[0]) ? array() : $ret[0];
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */