<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BowlDao.class.php 152191 2015-01-13 10:02:48Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/BowlDao.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-13 10:02:48 +0000 (Tue, 13 Jan 2015) $
 * @version $Revision: 152191 $
 * @brief 
 *  
 **/
 
class BowlDao
{
	const table = 't_bowl';
	
	public static function select($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::table);
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
	
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(self::table)->values($arrField)->query();
	}
	
	public static function update($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::table)->set($arrField);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$ret = $data->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */