<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityDao.class.php 242504 2016-05-13 02:18:25Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivityDao.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-13 02:18:25 +0000 (Fri, 13 May 2016) $
 * @version $Revision: 242504 $
 * @brief “开服7天乐”数据库操作类
 *  
 **/
class NewServerActivityDao
{
	public static function getData($uid)
	{
		$data = new CData();
		$ret = $data->select(NewServerActivitySqlDef::$arrColumn)
		->from(NewServerActivitySqlDef::T_NEW_SERVER_ACT)
		->where(array(NewServerActivitySqlDef::UID, '=', $uid))
		->query();
		if(empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(NewServerActivitySqlDef::T_NEW_SERVER_ACT)
			->values($arrField)
			->query();
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->update(NewServerActivitySqlDef::T_NEW_SERVER_ACT)
					->set($arrField)
					->where(array(NewServerActivitySqlDef::UID, '=', $uid))
					->query();
		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			return false;
		}
		return true;
	}
	
	public static function getGoodsData($day)
	{
		$data = new CData();
		$ret = $data->select(NewServerActivitySqlDef::$goodsColumn)
					->from(NewServerActivitySqlDef::T_NEW_SERVER_GOODS)
					->where(array(NewServerActivitySqlDef::DAY, '=', $day))
					->query();
		if(empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insertGoods($arrValue)
	{
		$data = new CData();
		$data->insertInto(NewServerActivitySqlDef::T_NEW_SERVER_GOODS)
			->values($arrValue)
			->query();
	}
	
	public static function updateGoods($day, $arrValue)
	{
		$data = new CData();
		$ret = $data->update(NewServerActivitySqlDef::T_NEW_SERVER_GOODS)
					->set($arrValue)
					->where(array(NewServerActivitySqlDef::DAY, '=', $day))
					->query();
		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			return false;
		}
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */