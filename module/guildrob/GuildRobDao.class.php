<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobDao.class.php 259369 2016-08-30 07:04:50Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRobDao.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-30 07:04:50 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259369 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobDao
* Description : 军团粮仓数据交互类
* Inherit     :
**********************************************************************************************************************/
class GuildRobDao
{
	const GuildRobUserTable = 't_guild_rob_user';
	const GuildRobTable = 't_guild_rob';
	
	public static function getKillTopN($robId, $topN = -1, $arrField = array())
	{
		if (empty($arrField)) 
		{
			$arrField = GuildRobUserField::$GUILD_ROB_USER_ALL_FIELDS;
		}
		
		$robObj = GuildRobObj::getInstance($robId);
		$startTime = $robObj->getStartTime();
		
		$arrCond = array
			(
				array(GuildRobUserField::TBL_FIELD_ROB_ID, '=', $robId),
				array(GuildRobUserField::TBL_FIELD_REWARD_TIME, '=', 0),
				array(GuildRobUserField::TBL_FIELD_JOIN_TIME, '>=', $startTime),
			);
	
		$data = new CData();
		$data->select($arrField)->from(self::GuildRobUserTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$data->orderBy(GuildRobUserField::TBL_FIELD_KILL_NUM, FALSE);
		$data->orderBy(GuildRobUserField::TBL_FIELD_KILL_TIME, TRUE);
		
		if ($topN > 0) 
		{
			$data->limit(0, $topN);
		}
		
		$ret = $data->query();
		
		return $ret;
	}
	
	public static function selectUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildRobUserTable);
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
	
	public static function selectArrUser($arrCond, $arrField)
	{
	    $data = new CData();
	    $data->select($arrField)->from(self::GuildRobUserTable);
	    foreach ($arrCond as $cond)
	    {
	        $data->where($cond);
	    }
	
	    $arrRet = $data->query();
	    
	    return empty( $arrRet ) ? array() : $arrRet;
	}
	
	public static function insertUser($arrField)
	{
		$data = new CData();
		$data->insertInto(self::GuildRobUserTable)->values($arrField);
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	public static function updateUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::GuildRobUserTable)->set($arrField);
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
	
	public static function selectRob($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildRobTable);
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
	
	public static function selectMultiRob($arrCond, $arrField)
	{
		if (!isset($arrField[GuildRobField::TBL_FIELD_GUILD_ID])) 
		{
			$arrField[] = GuildRobField::TBL_FIELD_GUILD_ID;
		}
		
		$data = new CData();
		$data->select($arrField)->from(self::GuildRobTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$arrRet = $data->query();
		return Util::arrayIndex($arrRet, GuildRobField::TBL_FIELD_GUILD_ID);
	}
	
	public static function insertRob($arrField)
	{
		$data = new CData();
		$data->insertInto(self::GuildRobTable)->values($arrField);
		$ret = $data->query();
	
		if ($ret['affected_rows'] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	public static function updateRob($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::GuildRobTable)->set($arrField);
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
	
	public static function selectRobCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(self::GuildRobTable);
		
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$ret = $data->query();
		return intval($ret[0]['count']);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */