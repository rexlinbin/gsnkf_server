<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RetrieveDao.class.php 146528 2014-12-16 12:32:32Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/RetrieveDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-16 12:32:32 +0000 (Tue, 16 Dec 2014) $
 * @version $Revision: 146528 $
 * @brief 
 *  
 **/
 
class RetrieveDao
{
	const table = 't_retrieve';
	
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
		$data->insertInto(self::table)->values($arrField);
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
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
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('update affected num 0, field: %s, cond: %s', $arrField, $arrCond);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */