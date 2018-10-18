<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RouletteDao.class.php 170819 2015-05-04 12:43:17Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/RouletteDao.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-04 12:43:17 +0000 (Mon, 04 May 2015) $
 * @version $Revision: 170819 $
 * @brief 
 *  
 **/
class RouletteDao
{
	private static $tblName = 't_roulette';
	
	public static function getRouletteInfo($uid,$arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
		            ->from(self::$tblName)
		            ->where(array(RouletteDef::SQL_FIELD_UID,'=',$uid))
		            ->query();
		if (empty($ret))
		{
			return array();
		}
	
		return $ret[0];
	}
	
	public static function insertRouletteInfo($arrField)
	{
		$data = new CData();
		$data->insertInto(self::$tblName)
		     ->values($arrField)
		     ->query();
	}
	
	public static function updateRouletteInfo($uid,$arrField)
	{
		$data = new CData();
		$data->update(self::$tblName)
		     ->set($arrField)
		     ->where(array(RouletteDef::SQL_FIELD_UID,'=',$uid))
		     ->query();
	}
	
	public static function getRankList($uid, $startTime, $limit, $offset = 0)
	{
		$data = new CData();
		$ret = $data->select(RouletteDef::$ALL_TABLE_FIELD)
					->from(self::$tblName)
					->where(array(RouletteDef::SQL_ACHIEVE_INTEGERAL,'>',0))
					->where(array(RouletteDef::SQL_LAST_RFR_TIME,'>=',$startTime))
					->orderBy(RouletteDef::SQL_ACHIEVE_INTEGERAL, FALSE)
					->orderBy(RouletteDef::SQL_LAST_ROLL_TIME, TRUE)
					->orderBy(RouletteDef::SQL_FIELD_UID, TRUE)
					->limit($offset, $limit)
					->query();
		if (empty($ret))
		{
			return array();
		}
		
		return $ret;
	}
	
	public static function getRankByPointAndTime($uid,$point, $time,$startTime)
	{
		$data = new CData();
		$ret1 = $data->selectCount()
					->from(self::$tblName)
					->where(array(RouletteDef::SQL_ACHIEVE_INTEGERAL,'>',$point))
					->where(array(RouletteDef::SQL_LAST_RFR_TIME,'>=',$startTime))
					->query();
		$ret2 = $data->selectCount()
					->from(self::$tblName)
					->where(array(RouletteDef::SQL_ACHIEVE_INTEGERAL,'=',$point))
					->where(array(RouletteDef::SQL_LAST_RFR_TIME,'>=',$startTime))
					->where(array(RouletteDef::SQL_LAST_ROLL_TIME,'<',$time))
					->query();
		$ret3 = $data->selectCount()
					->from(self::$tblName)
					->where(array(RouletteDef::SQL_ACHIEVE_INTEGERAL,'=',$point))
					->where(array(RouletteDef::SQL_LAST_RFR_TIME,'>=',$startTime))
					->where(array(RouletteDef::SQL_LAST_ROLL_TIME,'=',$time))
					->where(array(RouletteDef::SQL_FIELD_UID,'<',$uid))
					->query();
		
		$ret = $ret1[0]['count'] + $ret2[0]['count'] + $ret3[0]['count'] + 1;
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */