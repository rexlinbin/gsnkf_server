<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonDao.class.php 169111 2015-04-22 12:57:21Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/MoonDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-04-22 12:57:21 +0000 (Wed, 22 Apr 2015) $
 * @version $Revision: 169111 $
 * @brief 
 *  
 **/
 
class MoonDao
{
	const MoonTable = 't_moon';

	public static function selectUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::MoonTable);
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

	public static function insertUser($arrField)
	{
		$data = new CData();
		$data->insertInto(self::MoonTable)->values($arrField);

		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}

	public static function updateUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::MoonTable)->set($arrField);
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