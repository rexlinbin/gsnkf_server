<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassDao.class.php 178523 2015-06-12 05:24:54Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPassDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-12 05:24:54 +0000 (Fri, 12 Jun 2015) $
 * @version $Revision: 178523 $
 * @brief 
 *  
 **/
 
class WorldPassDao
{
	const WorldPassInnerUserTable = 't_world_pass_inner_user';
	const WorldPassCrossUserTable = 't_world_pass_cross_user';
	const WorldPassCrossTeamTable = 't_world_pass_cross_team';
	
	/**
	 * 获得服内玩家信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @param string $db
	 * @return array
	 */
	public static function selectInnerUser($arrCond, $arrField, $db = '')
	{
		$data = new CData();
		$data->select($arrField)->from(self::WorldPassInnerUserTable);
		if (!empty($db))
		{
			$data->useDb($db);
		}
		
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
	 * 插入服内玩家信息
	 * 
	 * @param array $arrField
	 * @param string $db
	 * @throws InterException
	 */
	public static function insertInnerUser($arrField, $db = '')
	{
		$data = new CData();
		$data->insertInto(self::WorldPassInnerUserTable)->values($arrField);
		if (!empty($db))
		{
			$data->useDb($db);
		}
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	/**
	 * 更新服内玩家信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @param string $db
	 * @throws InterException
	 */
	public static function updateInnerUser($arrCond, $arrField, $db = '')
	{
		$data = new CData();
		$data->update(self::WorldPassInnerUserTable)->set($arrField);
		if (!empty($db)) 
		{
			$data->useDb($db);
		}
		
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
	 * 获得所有分组信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @return array
	 */
	public static function selectTeamInfo($arrCond, $arrField)
	{
		$arrRet = array();
		
		$offset = 0;
		for ($i = 0; $i < 1024; ++$i)
		{
			$data = new CData();
			$data->useDb(WorldPassUtil::getCrossDbName());
			$data->select($arrField)->from(self::WorldPassCrossTeamTable)->limit($offset, DataDef::MAX_FETCH);
			foreach ($arrCond as $cond)
			{
				$data->where($cond);
			}
			$ret = $data->query();
			$arrRet = array_merge($arrRet, $ret);
			if (count($ret) < DataDef::MAX_FETCH)
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		
		return $arrRet;
	}
	
	/**
	 * 获得分组信息的记录条数
	 * 
	 * @param array $arrCond
	 */
	public static function selectTeamCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(self::WorldPassCrossTeamTable);
		$data->useDb(WorldPassUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$arrRet = $data->query();
		return $arrRet[0]['count'];
	}
	
	/**
	 * 插入分组信息
	 *
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function insertTeamInfo($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::WorldPassCrossTeamTable)->values($arrField);
		$data->useDb(WorldPassUtil::getCrossDbName());
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			//throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	/**
	 * 更新分组信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 */
	public static function updateTeamInfo($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::WorldPassCrossTeamTable)->set($arrField);
		$data->useDb(WorldPassUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$ret = $data->query();
	}
	
	/**
	 * 根据组名获取跨服表
	 * 
	 * @param int $teamId
	 * @return string
	 */
	public static function getCrossTable($teamId)
	{
		return self::WorldPassCrossUserTable . '_' . $teamId;
	}
	
	/**
	 * 获得跨服的玩家信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @return array
	 */
	public static function selectCrossUser($teamId, $arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldPassUtil::getCrossDbName());
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
	 * 插入玩家的跨服信息
	 * 
	 * @param int $teamId
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function insertCrossUser($teamId, $arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::getCrossTable($teamId))->values($arrField);
		$data->useDb(WorldPassUtil::getCrossDbName());
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	/**
	 * 更新玩家的跨服信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function updateCrossUser($teamId, $arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::getCrossTable($teamId))->set($arrField);
		$data->useDb(WorldPassUtil::getCrossDbName());
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
	 * 批量获取指定个数的服内玩家信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @param array $count
	 * @param string $db
	 * @return array
	 */
	public static function getInnerRankList($arrCond, $arrField, $count = DataDef::MAX_FETCH, $db = '')
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getInnerRankList($arrCond, $arrField, $offset, $limit, $db);
			$ret = array_merge($ret, $arrPart);
			$offset += count($arrPart);
			$count -= count($arrPart);
			if (count($arrPart) < $limit)
			{
				break;
			}
		}
	
		return $ret;
	}
	
	/**
	 * 批量获取服内玩家信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @param string $db
	 * @return array
	 */
	public static function _getInnerRankList($arrCond, $arrField, $offset = 0, $limit = DataDef::MAX_FETCH, $db = '')
	{
		$data = new CData();
		$data->select($arrField)->from(self::WorldPassInnerUserTable);
		if (!empty($db)) 
		{
			$data->useDb($db);
		}
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		$data->orderBy(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, FALSE);
		$data->orderBy(WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME, TRUE);
	
		return $data->query();
	}
	
	/**
	 * 获得满足某个条件的服内玩家个数
	 * 
	 * @param array $arrCond
	 */
	public static function getInnerCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(self::WorldPassInnerUserTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$ret = $data->query();
		
		return 	$ret[0]['count'];
	}
	
	/**
	 * 获得满足某个条件的跨服玩家个数
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 */
	public static function getCrossCount($teamId, $arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(self::getCrossTable($teamId));
		$data->useDb(WorldPassUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$ret = $data->query();
	
		return 	$ret[0]['count'];
	}
	
	/**
	 * 批量获取指定数量的跨服玩家信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $count
	 * @return array
	 */
	public static function getCrossRankList($teamId, $arrCond, $arrField, $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getCrossRankList($teamId, $arrCond, $arrField, $offset, $limit);
			$ret = array_merge($ret, $arrPart);
			$offset += count($arrPart);
			$count -= count($arrPart);
			if (count($arrPart) < $limit)
			{
				break;
			}
		}
	
		return $ret;
	}
	
	/**
	 * 批量获取跨服玩家信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function _getCrossRankList($teamId, $arrCond, $arrField, $offset = 0, $limit = DataDef::MAX_FETCH)
	{
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldPassUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		$data->orderBy(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, FALSE);
		$data->orderBy(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, TRUE);
	
		return $data->query();
	}
	
	/**
	 * 更新跨服库上玩家最大分数
	 * 
	 * @param int $teamId
	 * @param int $serverId
	 * @param int $pid
	 * @param int $maxPoint
	 */
	public static function updateMaxPoint($teamId, $serverId, $pid, $maxPoint)
	{
		$arrField = array
		(
				WorldPassCrossUserField::TBL_FIELD_TEAM_ID => $teamId,
				WorldPassCrossUserField::TBL_FIELD_PID => $pid,
				WorldPassCrossUserField::TBL_FIELD_SERVER_ID => $serverId,
				WorldPassCrossUserField::TBL_FIELD_MAX_POINT => $maxPoint,
				WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
		);
			
		$data = new CData();
		$data->insertOrUpdate(self::getCrossTable($teamId))->values($arrField);
		$data->useDb(WorldPassUtil::getCrossDbName());
		$ret = $data->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */