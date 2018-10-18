<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalDao.class.php 196927 2015-09-07 06:43:08Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-07 06:43:08 +0000 (Mon, 07 Sep 2015) $
 * @version $Revision: 196927 $
 * @brief 
 *  
 **/
 
class WorldCarnivalDao
{
	const WorldCarnivalCrossUserTable 		= 't_world_carnival_cross_user';
	const WorldCarnivalProcedureTable		= 't_world_carnival_procedure';
	
	/**
	 * 获得玩家信息
	 *
	 * @param array $arrCond
	 * @param array $arrField
	 * @return array
	 */
	public static function selectCrossUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::WorldCarnivalCrossUserTable);
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
	 * 获得某个名次的所有参赛者的记录
	 * 
	 * @param number $rank
	 * @param number $startTime
	 * @return array
	 */
	public static function getFightersList($rank, $startTime)
	{
		$arrCond = array
		(
				array(WorldCarnivalCrossUserField::TBL_FIELD_RANK, '=', $rank),
				array(WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $startTime),
		);
		
		$data = new CData();
		$data->select(WorldCarnivalCrossUserField::$ALL_FIELDS)->from(self::WorldCarnivalCrossUserTable);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
		
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$arrRet = $data->query();
		return $arrRet;
	}
	
	/**
	 * 插入玩家信息
	 *
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function insertCrossUser($arrField)
	{
		$data = new CData();
		$data->insertInto(self::WorldCarnivalCrossUserTable)->values($arrField);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	/**
	 * 更新玩家信息
	 *
	 * @param array $arrCond
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function updateCrossUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::WorldCarnivalCrossUserTable)->set($arrField);
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
	
	/**
	 * 根据届数从db里获取这一届所有round的数据记录，注意，应该根据活动开始时间来过滤旧数据, 记录要根据round升序排列
	 * 
	 * @param int $session
	 * @param int $startTime
	 * @return array
	 */
	public static function getRoundData($session, $startTime)
	{
		$arrCond = array
		(
				array(WorldCarnivalProcedureField::TBL_FIELD_SESSION, '=', $session),
				array(WorldCarnivalProcedureField::TBL_FIELD_UPDATE_TIME, '>=', $startTime),
		);
		
		$data = new CData();
		$data->select(WorldCarnivalProcedureField::$ALL_FIELDS)->from(self::WorldCarnivalProcedureTable);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->orderBy(WorldCarnivalProcedureField::TBL_FIELD_ROUND, TRUE);
		$arrRet = $data->query();
		
		return Util::arrayIndex($arrRet, WorldCarnivalProcedureField::TBL_FIELD_ROUND);
	}
	
	/**
	 * 更新一个轮次的信息到db
	 * 
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function updateRoundData($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::WorldCarnivalProcedureTable)->values($arrField);
		$data->useDb(WorldCarnivalUtil::getCrossDbName());
		
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('update affected num 0, field: %s', $arrField);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */