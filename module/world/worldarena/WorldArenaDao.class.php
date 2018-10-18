<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaDao.class.php 241167 2016-05-05 12:18:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 12:18:17 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241167 $
 * @brief 
 *  
 **/
 
class WorldArenaDao
{
	const WorldArenaInnerUserTable 		= 't_world_arena_inner_user';
	const WorldArenaCrossUserTable 		= 't_world_arena_cross_user';
	const WorldArenaCrossTeamTable 		= 't_world_arena_cross_team';
	const WorldArenaCrossRecordTable   	= 't_world_arena_cross_record';
	
	//**********************************************************************
	//**********************************************************************
	
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
		$data->select($arrField)->from(self::WorldArenaInnerUserTable);
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
		$data->insertInto(self::WorldArenaInnerUserTable)->values($arrField);
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
		$data->update(self::WorldArenaInnerUserTable)->set($arrField);
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
	
	//**********************************************************************
	//**********************************************************************

	/**
	 * 根据组名获取跨服表
	 *
	 * @param int $teamId
	 * @return string
	 */
	public static function getCrossTable($teamId)
	{
		return self::WorldArenaCrossUserTable . '_' . $teamId;
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
		$data->useDb(WorldArenaUtil::getCrossDbName());
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
		$data->useDb(WorldArenaUtil::getCrossDbName());

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
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}

		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			//throw new InterException('update affected num 0, field: %s, cond: %s', $arrField, $arrCond);
		}
	}
	
	/**
	 * 事务更新攻方和守方的信息和排名，输的要到最后一名，赢的如果对手比自己名字高，要替换对手的名次
	 * 同时更新vip,level等信息
	 * 
	 * @param int $teamId   							所属分组id
	 * @param int $roomId								所属房间id
	 * @param WorldArenaCrossUserObj $myCrossObj		玩家跨服obj
	 * @param WorldArenaCrossUserObj $targetCrossObj	对手跨服obj
	 * @param int $kill									是否击杀
	 */
	public static function updateCrossUserByBatch($teamId, $roomId, $myCrossObj, $targetCrossObj, $kill)
	{
		$batchData = new BatchData();
		$batchData->useDb(WorldArenaUtil::getCrossDbName());
		
		// 子查询的sql（改用id啦，子查询sql锁表太严重）
		//$subQuery = sprintf('(select a.x from ((select max(%s) + 1 x from %s.%s for update) a))', WorldArenaCrossUserField::TBL_FIELD_POS, WorldArenaUtil::getCrossDbName(), self::getCrossTable($teamId));
		
		// 从IdGenerator获取最后一名的pos
		$lastPos = IdGenerator::nextId('worldarena_pos_id', WorldArenaUtil::getCrossDbName());
		
		// 更新两个玩家的跨服信息
		if ($kill)
		{
			/*
			 * 如果主动攻打，并且胜利，先更新对方
			 * 将对方放到最后一名，连杀置为0，设置保护时间，更新时间
			 */
			$targetData = $batchData->newData();
			$arrTargetUpdateField = array
			(
					WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM => $targetCrossObj->getCurContiNum(),
					WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME => $targetCrossObj->getProtectTime(),
					WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
					WorldArenaCrossUserField::TBL_FIELD_POS => $lastPos,
			);
			$arrTargetCond = array
			(
					array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $targetCrossObj->getServerId()),
					array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $targetCrossObj->getPid()),
					array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
			);
			$targetData->update(self::getCrossTable($teamId))->set($arrTargetUpdateField);
			$targetData->useDb(WorldArenaUtil::getCrossDbName());
			foreach ($arrTargetCond as $aCond)
			{
				$targetData->where($aCond);
			}
			$targetData->query();
		
			/*
			 * 如果主动攻打，并且胜利
			 * 将自己的vip，level等更新
			 * 将自己的击杀数，当前连杀数，历史连杀数，保护时间，更新时间等
			 * 如果主动攻打的是自己排名前面的玩家，需要设置pos为对方的pos，攻击自己排名后面的玩家，自己的pos不变
			 */
			$myData = $batchData->newData();
			$arrMyUpdateField = array
			(
					WorldArenaCrossUserField::TBL_FIELD_UNAME => $myCrossObj->getUname(),
					WorldArenaCrossUserField::TBL_FIELD_VIP => $myCrossObj->getVip(),
					WorldArenaCrossUserField::TBL_FIELD_LEVEL => $myCrossObj->getLevel(),
					WorldArenaCrossUserField::TBL_FIELD_HTID => $myCrossObj->getHtid(),
					WorldArenaCrossUserField::TBL_FIELD_TITLE => $myCrossObj->getTitle(),
					WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA => array(WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS => $myCrossObj->getDress()),
					WorldArenaCrossUserField::TBL_FIELD_KILL_NUM => $myCrossObj->getKillNum(),
					WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM => $myCrossObj->getCurContiNum(),
					WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM => $myCrossObj->getMaxContiNum(),
					WorldArenaCrossUserField::TBL_FIELD_POS => $myCrossObj->getPos(),
					WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME => $myCrossObj->getProtectTime(),
					WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
			);		
			$arrMyCond = array
			(
					array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $myCrossObj->getServerId()),
					array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $myCrossObj->getPid()),
					array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
			);
			$myData->update(self::getCrossTable($teamId))->set($arrMyUpdateField);
			$myData->useDb(WorldArenaUtil::getCrossDbName());
			foreach ($arrMyCond as $aCond)
			{
				$myData->where($aCond);
			}
			$myData->query();
		}
		else
		{
			/*
			 * 如果主动攻打，并且失败，先更新自己
			 * 需要将自己排到最后一名
			 * 将自己的vip，level等更新
			 * 将自己的当前连杀数置为0，设置保护时间，更新时间等
			 */
			$myData = $batchData->newData();
			$arrMyUpdateField = array
			(
					WorldArenaCrossUserField::TBL_FIELD_UNAME => $myCrossObj->getUname(),
					WorldArenaCrossUserField::TBL_FIELD_VIP => $myCrossObj->getVip(),
					WorldArenaCrossUserField::TBL_FIELD_LEVEL => $myCrossObj->getLevel(),
					WorldArenaCrossUserField::TBL_FIELD_HTID => $myCrossObj->getHtid(),
					WorldArenaCrossUserField::TBL_FIELD_TITLE => $myCrossObj->getTitle(),
					WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA => array(WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS => $myCrossObj->getDress()),
					WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM => $myCrossObj->getCurContiNum(),
					WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME => $myCrossObj->getProtectTime(),
					WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
					WorldArenaCrossUserField::TBL_FIELD_POS => $lastPos,
			);
			$arrMyCond = array
			(
					array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $myCrossObj->getServerId()),
					array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $myCrossObj->getPid()),
					array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
			);
			$myData->update(self::getCrossTable($teamId))->set($arrMyUpdateField);
			$myData->useDb(WorldArenaUtil::getCrossDbName());
			foreach ($arrMyCond as $aCond)
			{
				$myData->where($aCond);
			}
			$myData->query();
		
			/*
			 * 如果主动攻打，并且失败，更新对方信息
			 * 设置保护时间，更新时间等
			 */
			$targetData = $batchData->newData();
			$arrTargetUpdateField = array
			(
					WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME => $targetCrossObj->getProtectTime(),
					WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
			);
			$arrTargetCond = array
			(
					array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $targetCrossObj->getServerId()),
					array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $targetCrossObj->getPid()),
					array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
			);
			$targetData->update(self::getCrossTable($teamId))->set($arrTargetUpdateField);
			$targetData->useDb(WorldArenaUtil::getCrossDbName());
			foreach ($arrTargetCond as $aCond)
			{
				$targetData->where($aCond);
			}
			$targetData->query();
		}
		
		$batchData->query();
		
		return $lastPos;
	}
	
	/**
	 * 批量拉取一定数量的位置排行信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $count
	 * @return array
	 */
	public static function getPosRankList($teamId, $arrCond, $arrField = array(), $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getPosRankList($teamId, $arrCond, $arrField, $offset, $limit);
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
	 * 批量拉取一定数量的位置排行信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function _getPosRankList($teamId, $arrCond, $arrField, $offset, $limit)
	{
		if (empty($arrField)) 
		{
			$arrField = WorldArenaCrossUserField::$ALL_FIELDS;
		}
		
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_POS, TRUE);
		$data->limit($offset, $limit);
		$arrRet = $data->query();
		
		return $arrRet;
	}

	/**
	 *  根据serverId,pid批量拉取用户信息
	 *  
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $count
	 * @return array
	 */
	public static function getUserList($teamId, $arrCond, $arrField = array(), $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getUserList($teamId, $arrCond, $arrField, $offset, $limit);
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
	 * 根据serverId,pid批量拉取用户信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function _getUserList($teamId, $arrCond, $arrField, $offset, $limit)
	{
		if (empty($arrField)) 
		{
			$arrField = WorldArenaCrossUserField::$ALL_FIELDS;
		}
		
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, TRUE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_PID, TRUE);
		$data->limit($offset, $limit);
		$arrRet = $data->query();
		
		return $arrRet;
	}
	
	/**
	 * 批量拉取一定数量的击杀总数排行信息
	 *
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $count
	 * @return array
	 */
	public static function getKillRankList($teamId, $arrCond, $arrField = array(), $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getKillRankList($teamId, $arrCond, $arrField, $offset, $limit);
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
	 * 批量拉取一定数量的击杀总数排行信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function _getKillRankList($teamId, $arrCond, $arrField, $offset, $limit)
	{
		if (empty($arrField)) 
		{
			$arrField = WorldArenaCrossUserField::$ALL_FIELDS;
		}
		
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_KILL_NUM, FALSE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE, FALSE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, TRUE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_PID, TRUE);
		$data->limit($offset, $limit);
		$arrRet = $data->query();
		
		return $arrRet;
	}
	
	/**
	 * 批量拉取一定数量的最大连杀排行信息
	 *
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $count
	 * @return array
	 */
	public static function getContiRankList($teamId, $arrCond, $arrField = array(), $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getContiRankList($teamId, $arrCond, $arrField, $offset, $limit);
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
	 * 批量拉取一定数量的最大连杀排行信息
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function _getContiRankList($teamId, $arrCond, $arrField, $offset, $limit)
	{
		if (empty($arrField)) 
		{
			$arrField = WorldArenaCrossUserField::$ALL_FIELDS;
		}
		
		$data = new CData();
		$data->select($arrField)->from(self::getCrossTable($teamId));
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM, FALSE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE, FALSE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, TRUE);
		$data->orderBy(WorldArenaCrossUserField::TBL_FIELD_PID, TRUE);
		$data->limit($offset, $limit);
		$arrRet = $data->query();
		
		return $arrRet;
	}
	
	/**
	 * 获取某一个team下面满足某些条件的玩家个数，一般用于根据玩家的pos，计算小于pos的玩家个数，从而得到玩家的准确排名
	 * 
	 * @param int $teamId
	 * @param array $arrCond
	 */
	public static function selectCrossUserCount($teamId, $arrCond)
	{		
		$data = new CData();
		$data->selectCount()->from(self::getCrossTable($teamId));
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$arrRet = $data->query();
		return $arrRet[0]['count'];
	}
	
	//**********************************************************************
	//**********************************************************************
	
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
			$data->useDb(WorldArenaUtil::getCrossDbName());
			$data->select($arrField)->from(self::WorldArenaCrossTeamTable)->limit($offset, DataDef::MAX_FETCH);
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
		$data->selectCount()->from(self::WorldArenaCrossTeamTable);
		$data->useDb(WorldArenaUtil::getCrossDbName());
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
		$data->insertOrUpdate(self::WorldArenaCrossTeamTable)->values($arrField);
		$data->useDb(WorldArenaUtil::getCrossDbName());
	
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
		$data->update(self::WorldArenaCrossTeamTable)->set($arrField);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$ret = $data->query();
	}
	
	//**********************************************************************
	//**********************************************************************
	
	/**
	 * 插入一条记录
	 *
	 * @param array $arrField
	 */
	public static function insertCrossRecord($arrField)
	{
		$data = new CData();
		$data->insertInto(self::WorldArenaCrossRecordTable)->values($arrField);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		$data->query();
	}
	
	/**
	 * 获得某个玩家的战报记录
	 *
	 * @param int $teamId
	 * @param int $roomId
	 * @param int $serverId
	 * @param int $pid
	 * @param int $beginTime 
	 * @param int $maxCount
	 * @return array
	 */
	public static function getUserRecordList($teamId, $roomId, $serverId, $pid, $beginTime, $maxCount)
	{
		$arrField = WorldArenaCrossRecordField::$ALL_FIELDS;
	
		// 拉作为攻方的记录
		$arrCond = array
		(
				array(WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_SERVER_ID, '=', $serverId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_PID, '=', $pid),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, '>=', $beginTime),
		);
		$data = new CData();
		$data->select($arrField)->from(self::WorldArenaCrossRecordTable);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, FALSE);
		$data->limit(0, $maxCount);
		$arrAttackRecord = $data->query();
	
		// 拉取作为守方的记录
		$arrCond = array
		(
				array(WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_SERVER_ID, '=', $serverId),
				array(WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_PID, '=', $pid),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, '>=', $beginTime),
		);
		$data = new CData();
		$data->select($arrField)->from(self::WorldArenaCrossRecordTable);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, FALSE);
		$data->limit(0, $maxCount);
		$arrDefendRecord = $data->query();
	
		// 合并排序
		$sortCmp = new SortByFieldFunc(array(WorldArenaCrossRecordField::TBL_FIELD_ID => SortByFieldFunc::DESC));
		$arrAllRecord = array_merge($arrAttackRecord, $arrDefendRecord);
		usort($arrAllRecord, array($sortCmp, 'cmp'));
	
		$arrRet = array();
		$i = 0;
		$curRecord = current($arrAllRecord);
		while ($i++ < $maxCount && $curRecord)
		{
			$arrRet[] = $curRecord;
			$curRecord = next($arrAllRecord);
		}
		return $arrRet;
	}
	
	/**
	 * 拉取某个team某个room的所有有效档位的连杀和终结连杀
	 *
	 * @param int $teamId
	 * @param int $roomId
	 * @param int $beginTime
	 * @param int $maxCount
	 * @return array
	 */
	public static function getContiRecordList($teamId, $roomId, $beginTime, $maxCount)
	{
		$arrField = WorldArenaCrossRecordField::$ALL_FIELDS;
	
		// 拉攻方的有效的连杀记录
		$arrCond = array
		(
				array(WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_CONTI, 'IN', array(5,10,15,20,25,30,35,40,45,50)),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, '>=', $beginTime),
		);
		$data = new CData();
		$data->select($arrField)->from(self::WorldArenaCrossRecordTable);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, FALSE);
		$data->limit(0, $maxCount);
		$arrContiRecord = $data->query();
	
		// 拉攻方的有效的终结连杀记录
		$arrCond = array
		(
				array(WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_TERMINAL_CONTI, '>=', 5),
				array(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, '>=', $beginTime),
		);
		$data = new CData();
		$data->select($arrField)->from(self::WorldArenaCrossRecordTable);
		$data->useDb(WorldArenaUtil::getCrossDbName());
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$data->orderBy(WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME, FALSE);
		$data->limit(0, $maxCount);
		$arrTerminalContiRecord = $data->query();
	
		// 合并排序
		$sortCmp = new SortByFieldFunc(array(WorldArenaCrossRecordField::TBL_FIELD_ID => SortByFieldFunc::DESC));		
		$arrAllRecord = array();
		foreach ($arrContiRecord as $aInfo)
		{
			$aId = $aInfo[WorldArenaCrossRecordField::TBL_FIELD_ID];
			if (!isset($arrAllRecord[$aId])) 
			{
				$arrAllRecord[$aId] = $aInfo;
			}
		}
		foreach ($arrTerminalContiRecord as $aInfo)
		{
			$aId = $aInfo[WorldArenaCrossRecordField::TBL_FIELD_ID];
			if (!isset($arrAllRecord[$aId])) 
			{
				$arrAllRecord[$aId] = $aInfo;
			}
		}
		$arrAllRecord = array_merge($arrAllRecord);
		usort($arrAllRecord, array($sortCmp, 'cmp'));
	
		$arrRet = array();
		$i = 0;
		$curRecord = current($arrAllRecord);
		while ($i++ < $maxCount && $curRecord)
		{
			$arrRet[] = $curRecord;
			$curRecord = next($arrAllRecord);
		}
		return $arrRet;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */