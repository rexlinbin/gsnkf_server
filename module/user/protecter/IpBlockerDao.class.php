<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IpBlockerDao.class.php 239498 2016-04-21 07:44:54Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/protecter/IpBlockerDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-04-21 07:44:54 +0000 (Thu, 21 Apr 2016) $
 * @version $Revision: 239498 $
 * @brief 
 *  
 **/
 
class IpBlockerDao
{
	const IpBlackTable   	= 't_ip_black';
	
	/**
	 * 获得信息
	 *
	 * @param array $arrCond
	 * @param array $arrField
	 * @return array
	 */
	public static function select($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::IpBlackTable);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
	
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$arrRet = $data->query();
		if (!empty($arrRet))
		{
			$arrRet = $arrRet[0];
		}
		return $arrRet;
	}
	
	/**
	 * 插入信息
	 *
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::IpBlackTable)->values($arrField);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
		$data->query();
	}
	
	/**
	 * 更新信息
	 *
	 * @param array $arrCond
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function update($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::IpBlackTable)->set($arrField);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
	
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('update affected num 0, field: %s, cond: %s', $arrField, $arrCond);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */