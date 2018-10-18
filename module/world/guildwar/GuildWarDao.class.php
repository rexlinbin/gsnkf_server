<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarDao.class.php 158266 2015-02-10 12:02:56Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/GuildWarDao.class.php $
 * @author 
 * $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-10 12:02:56 +0000 (Tue, 10 Feb 2015) $
 * @version $Revision: 158266 $
 * @brief 
 *  
 **/

class GuildWarDao
{
	private static $tblGuildWarServer = 't_guild_war_cross_server';
	private static $tblGuildWarUser = 't_guild_war_inner_user';
	private static $tblGuildWarTemple = 't_guild_war_inner_temple';
	private static $tblGuildWarProcedure = 't_guild_war_procedure';
	
	public static function selectGuildWarServer($session, $serverId, $guildId, $arrField = array())
	{
		if (empty($arrField)) 
		{
			$arrField = GuildWarServerField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, "=", $session),
				array(GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID, "=", $serverId),
				array(GuildWarServerField::TBL_FIELD_GUILD_ID, "=", $guildId),
		);
		
		$arrRet = self::_selectGuildWarServer($arrCond, $arrField);
		return isset($arrRet[0]) ? $arrRet[0] : array();
	}
	
	public static function _selectGuildWarServer($arrCond, $arrField, $offset = 0, $limit = CData::MAX_FETCH_SIZE)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$data->select($arrField)->from(self::$tblGuildWarServer);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		
		return $data->query();
	}
	
	public static function selectAllGuildWarServer($session, $teamId, $failNum, $signUpStartTime, $arrField = array())
	{
		if (empty($arrField)) 
		{
			$arrField = GuildWarServerField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarServerField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(GuildWarServerField::TBL_FIELD_LOSE_TIMES, '<', $failNum),
				array(GuildWarServerField::TBL_FIELD_SIGN_TIME, '>=', $signUpStartTime),
		);
		
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		$offset = 0;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$ret = self::_selectGuildWarServer($arrCond, $arrField, $offset, CData::MAX_FETCH_SIZE);
			$arrRet = array_merge($arrRet, $ret);
			$count = count($ret);
			$offset += $count;
		}
		
		return $arrRet;
	}
	
	public static function getCountOfSignUp($session, $teamId, $signUpStartTime)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarServerField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(GuildWarServerField::TBL_FIELD_SIGN_TIME, '>=', $signUpStartTime),
		);
		
		$data->select(array("count('guild_id') as cguild"))->from(self::$tblGuildWarServer);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$result = $data->query();
		
		return $result[0]['cguild'];
	}
	
	public static function selectFinalsGuildInfoByRank($arrField, $session, $teamId, $failNum, $signUpStartTime, $rank)
	{
		if (empty($arrField)) 
		{
			$arrField = GuildWarServerField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarServerField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(GuildWarServerField::TBL_FIELD_FINAL_RANK, '=', $rank),
				array(GuildWarServerField::TBL_FIELD_POS, '>', 0),
				array(GuildWarServerField::TBL_FIELD_LOSE_TIMES, '<', $failNum),
				array(GuildWarServerField::TBL_FIELD_SIGN_TIME, '>=', $signUpStartTime),
		);
		
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$data->select($arrField)->from(self::$tblGuildWarServer);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->orderBy(GuildWarServerField::TBL_FIELD_POS, TRUE);
		
		return $data->query();
	}
	
	public static function selectFinalsGuildInfo($arrField, $session, $teamId, $failNum, $signUpStartTime)
	{
		if (empty($arrField))
		{
			$arrField = GuildWarServerField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarServerField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(GuildWarServerField::TBL_FIELD_FINAL_RANK, '>', 0),
				array(GuildWarServerField::TBL_FIELD_LOSE_TIMES, '<', $failNum),
				array(GuildWarServerField::TBL_FIELD_SIGN_TIME, '>=', $signUpStartTime),
		);
		
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$data->select($arrField)->from(self::$tblGuildWarServer);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->orderBy(GuildWarServerField::TBL_FIELD_POS, TRUE);
		
		return $data->query();
	}
	
	/**
	 * 更新跨服军团战军团信息
	 * 
	 * @param array $arrCond
	 * @param array $arrField
	 * @throws InterException
	 */
	public static function updateGuildWarServer($arrCond, $arrField)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$data->update(self::$tblGuildWarServer)->set($arrField);
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
	
	public static function getAllCheerUserInfo($arrField, $cheerRound, $minJoinTime)
	{
		$data = new CData();
		
		if (empty($arrField)) 
		{
			$arrField = GuildWarUserField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarUserField::TBL_FIELD_CHEER_ROUND, '=', $cheerRound),
				array(GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME, '>=', $minJoinTime),
		);
		
		$offset = 0;
		$ret = array();
		for ($i = 0; $i < 65535; $i++)
		{
			$data->select($arrField)->from(self::$tblGuildWarUser);
			foreach ($arrCond as $cond)
			{
				$data->where($cond);
			}
			$data->orderBy(GuildWarUserField::TBL_FIELD_UID, TRUE)->limit($offset, DataDef::MAX_FETCH);
			$result = $data->query();
			
			$ret = array_merge($ret, $result);
			if (count($result) < DataDef::MAX_FETCH)
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		
		$ret = Util::arrayIndex($ret, GuildWarUserField::TBL_FIELD_UID);
		return $ret;
	}
	
	public static function getAllCheerUserInfoCount($cheerRound, $minJoinTime, $db = '')
	{
		$data = new CData();
		if (!empty($db))
		{
			$data->useDb($db);
		}
	
		$result = $data->select(array("count('uid') as cuid"))->from(self::$tblGuildWarUser)
					   ->where(GuildWarUserField::TBL_FIELD_CHEER_ROUND, '=', $cheerRound)
		               ->where(GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME, '>=', $minJoinTime)
		               ->query();
		
		return $result[0]['cuid'];
	}
	
	/**
	 * 获取一组uid的跨服军团战的个人信息
	 * 
	 * @param array $uids
	 * @param string $db
	 * @param bool $needBattleFmt
	 * @return array
	 */
	public static function getArrGuildWarUserInfo($arrUid, $arrField = array(), $db = 0)
	{
		$data = new CData();
		if (!empty($db))
		{
			$data->useDb($db);
		}
		
		if (empty($arrField)) 
		{
			$arrField = GuildWarUserField::$ALL_FIELDS;
		}
		
		$arrCond = array
		(
				array(GuildWarUserField::TBL_FIELD_UID, 'IN', $arrUid),
		);
		
		$data->select($arrField)->from(self::$tblGuildWarUser);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->orderBy(GuildWarUserField::TBL_FIELD_FIGHT_FORCE, FALSE);
		$arrRet = $data->query();

		return Util::arrayIndex($arrRet, GuildWarUserField::TBL_FIELD_UID);
	}
	
	public static function insertGuildWarServer($arrField)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		$data->insertInto(self::$tblGuildWarServer)->values($arrField);
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	public static function selectGuildWarUser($arrCond, $arrField, $db = '')
	{
		$data = new CData();
		if (!empty($db)) 
		{
			$data->useDb($db);
		}
	
		$data->select($arrField)->from(self::$tblGuildWarUser);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
	
		return isset($arrRet[0]) ? $arrRet[0] : array();
	}
	
	public static function updateGuildWarUser($arrCond, $arrField, $db = '')
	{
		$data = new CData();
		if (!empty($db))
		{
			$data->useDb($db);
		}

		$data->update(self::$tblGuildWarUser)->set($arrField);
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
	
	public static function insertGuildWarUser($arrField, $db = '')
	{
		$data = new CData();
		if (!empty($db))
		{
			$data->useDb($db);
		}
		
		$data->insertInto(self::$tblGuildWarUser)->values($arrField);
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			return FALSE;
		}
	
		return TRUE;
	}
	
	public static function getLastRoundData($session, $teamId)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
			
		$arrField =	GuildWarProcedureField::$ALL_FIELDS;
		$arrCond = array
		(
				array(GuildWarProcedureField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarProcedureField::TBL_FIELD_TEAM_ID, '=', $teamId),
		);
	
		$data->select($arrField)->from(self::$tblGuildWarProcedure);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->orderBy(GuildWarProcedureField::TBL_FIELD_ROUND, FALSE)->limit(0, 1)->query();
	
		return isset($arrRet[0]) ? $arrRet[0] : array();
	}
	
	public static function getRoundData($session, $teamId, $round)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
		
		$arrField =	GuildWarProcedureField::$ALL_FIELDS;
		$arrCond = array
		(
				array(GuildWarProcedureField::TBL_FIELD_SESSION, '=', $session),
				array(GuildWarProcedureField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(GuildWarProcedureField::TBL_FIELD_ROUND, '=', $round),
		);
		
		$data->select($arrField)->from(self::$tblGuildWarProcedure);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return isset($arrRet[0]) ? $arrRet[0] : array();
	}
	
	public static function updateGuildWarProcedure($arrValue)
	{
		$data = new CData();
		$data->useDb(GuildWarUtil::getCrossDbName());
	
		$ret = $data->insertOrUpdate(self::$tblGuildWarProcedure)
					->values($arrValue)
					->query();
	}
	
	public static function selectGuildWarTemple($session)
	{
		$data = new CData();
		
		$arrField = GuildWarTempleField::$ALL_FIELDS;
		$arrCond = array
		(
				array(GuildWarTempleField::TBL_FIELD_SESSION, '=', $session), 
		);
		
		$data->select($arrField)->from(self::$tblGuildWarTemple);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		
		return isset($arrRet[0]) ? $arrRet[0] : array();
	}
	
	public static function updateGuildWarTemple($arrValue)
	{
		$data = new CData();	
		$ret = $data->insertOrUpdate(self::$tblGuildWarTemple)
					->values($arrValue)
					->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */