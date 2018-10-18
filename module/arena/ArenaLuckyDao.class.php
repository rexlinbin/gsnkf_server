<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaLuckyDao.class.php 119091 2014-07-07 12:04:18Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaLuckyDao.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2014-07-07 12:04:18 +0000 (Mon, 07 Jul 2014) $
 * @version $Revision: 119091 $
 * @brief 
 *  
 **/



class ArenaLuckyDao
{
	const tblName = 't_arena_lucky';
	
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(self::tblName)->values($arrField)->query();
	}
	
	public static function select($beginDate, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblName)->where('begin_date', '=', $beginDate)->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;		
	}
	
	public static function get($arrDate, $arrField)
	{
		if (!in_array('begin_date', $arrField))
		{
			$arrField[] = 'begin_date';
		}
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::tblName)->where('begin_date', 'in', $arrDate)->query();
		return $arrRet;
	}
	
	public static function update($beginDate, $arrField)
	{
		$data = new CData();
		$data->update(self::tblName)->set($arrField)->where('begin_date', '=', $beginDate)->query();
	}
	
	public static function getRewardLuckyList($arrField)
	{
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::tblName)->where(1, '=', 1)
			->orderBy('begin_date', false)->limit(0, 2)->query();
		$num = count($arrRet);
		if ($num == 1)
		{
			$arrRet[] = array();
		}
		else if($num==0)
		{
			$arrRet = array(array(), array());
		}
		
		return array_reverse($arrRet);
	}	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */