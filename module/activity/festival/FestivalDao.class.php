<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalDao.class.php 152184 2015-01-13 09:57:11Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/FestivalDao.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-13 09:57:11 +0000 (Tue, 13 Jan 2015) $
 * @version $Revision: 152184 $
 * @brief 
 *  
 **/
class FestivalDao
{
	const table = 't_festival';

	public static function select($uid, $arrField)
	{
		$data = new CData();

		$arrRet = $data->select($arrField)
						->from(self::table)
						->where(array('uid','=',$uid))
						->query();
		if ( empty($arrRet) )
		{
			return array();
		}

		return $arrRet[0];
	}

	public static function insert($arrFiled)
	{
		$data = new CData();
		$ret = $data->insertInto(self::table)
					->values($arrFiled)
					->query();
	}

	public static function update($uid, $arrField)
	{
		$data = new CData();

		$ret = $data->update(self::table)
					->set($arrField)
					->where(array('uid','=',$uid))
					->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */