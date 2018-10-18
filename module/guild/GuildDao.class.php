<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildDao.class.php 178175 2015-06-11 08:06:48Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/GuildDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-06-11 08:06:48 +0000 (Thu, 11 Jun 2015) $
 * @version $Revision: 178175 $
 * @brief 
 *  
 **/
class GuildDao
{
	public static function insertGuild($arrField)
	{
		$data = new CData();
		$arrRet = $data->insertIgnore(GuildDef::TABLE_GUILD)
					   ->values($arrField)
					   ->uniqueKey(GuildDef::GUILD_ID)
					   ->query();
		if ($arrRet['affected_rows'] == 0)
		{
			return 0;
		}
		return $arrRet[GuildDef::GUILD_ID];
	}

	public static function selectGuild($guildId)
	{
		$data = new CData();
		$arrRet = $data->select(GuildDef::$GUILD_FIELDS)
					   ->from(GuildDef::TABLE_GUILD)
					   ->where(array(GuildDef::GUILD_ID, '=', $guildId))
					   ->where(array(GuildDef::STATUS, '=', GuildStatus::OK))
					   ->query();
		if (!empty($arrRet[0])) 
		{
			return $arrRet[0];
		}
		return array();
	}
	
	public static function updateGuild($guildId, $arrField, $noCache = false)
	{
		$data = new CData();
		$data->update(GuildDef::TABLE_GUILD)
			 ->set($arrField)
			 ->where(array(GuildDef::GUILD_ID, '=', $guildId));
		if ($noCache)
		{
			$data->noCache();
		}
		$data->query();
	}

	public static function getGuildCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(GuildDef::TABLE_GUILD);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return $arrRet[0]['count'];
	}
	
	public static function getGuildList($arrCond, $arrField, $offset, $limit)
	{
		$data = new CData();
		$data->select($arrField)->from(GuildDef::TABLE_GUILD);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->where(array(GuildDef::STATUS, '=', GuildStatus::OK))
					   ->orderBy(GuildDef::FIGHT_FORCE, false)
					   ->orderBy(GuildDef::GUILD_LEVEL, false)
					   ->orderBy(GuildDef::UPGRADE_TIME, true)
					   ->orderBy(GuildDef::GUILD_ID, true)
					   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	public static function getGuildRankList($arrField, $offset, $limit)
	{
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(GuildDef::TABLE_GUILD)
					   ->where(array(GuildDef::STATUS, '=', GuildStatus::OK))
					   ->orderBy(GuildDef::FIGHT_FORCE, false)
					   ->orderBy(GuildDef::GUILD_LEVEL, false)
					   ->orderBy(GuildDef::UPGRADE_TIME, true)
					   ->orderBy(GuildDef::GUILD_ID, true)
					   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	public static function getArrGuild($arrGuildId, $arrField)
	{
		$i = 0;
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$guildIds = array_slice($arrGuildId, $i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			if (empty($guildIds))
			{
				break;
			}
			$arrCond = array(array(GuildDef::GUILD_ID, 'in', $guildIds));
			$ret = self::getGuildList($arrCond, $arrField, 0, CData::MAX_FETCH_SIZE);
			$arrRet = array_merge($arrRet, $ret);
			$count = count($ret);
			$i++;
		}
		
		return Util::arrayIndex($arrRet, GuildDef::GUILD_ID);
	}

	public static function insertMember($arrField)
	{
		$data = new CData();
		$arrKey = array(
				GuildDef::GUILD_ID, 
				GuildDef::MEMBER_TYPE,
		);
		$arrRet = $data->insertOrUpdate(GuildDef::TABLE_GUILD_MEMBER)
			 		   ->values($arrField)
			 		   ->onDuplicateUpdateKey($arrKey)
			 		   ->query();
		if ($arrRet['affected_rows'] == 0)
		{
			return false;
		}
		return true;
	}

	public static function selectMember($uid)
	{
		$data = new CData();
		$arrRet = $data->select(GuildDef::$GUILD_MEMBER_FIELDS)
					   ->from(GuildDef::TABLE_GUILD_MEMBER)
					   ->where(array(GuildDef::USER_ID, '=', $uid))
					   ->query();
		if (!empty($arrRet[0])) 
		{
			return $arrRet[0];
		}
		return array();
	}
	
	public static function updateMember($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(GuildDef::TABLE_GUILD_MEMBER)->set($arrField);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		if ($arrRet['affected_rows'] == 0)
		{
			return false;
		}
		return true;
	}

	public static function getMember($arrCond, $arrField = array())
	{
		$data = new CData();
		if (empty($arrField)) 
		{
			$arrField = GuildDef::$GUILD_MEMBER_FIELDS;
		}
		$data->select($arrField)->from(GuildDef::TABLE_GUILD_MEMBER);
		foreach ( $arrCond as $cond )
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return $arrRet;
	}

	public static function getMemberCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(GuildDef::TABLE_GUILD_MEMBER);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return $arrRet[0]['count'];
	}
	
	public static function getMemberList($guildId, $arrField, $offset = 0, $limit = CData::MAX_FETCH_SIZE, $dbName = '')
	{	
		$data = new CData();
		if (!empty($dbName)) 
		{
			$data->useDb($dbName);
		}
		$arrRet = $data->select($arrField)
			 		   ->from(GuildDef::TABLE_GUILD_MEMBER)
					   ->where(array(GuildDef::GUILD_ID, '=', $guildId))
			 		   ->orderBy(GuildDef::CONTRI_TOTAL, false)
			 		   ->orderBy(GuildDef::CONTRI_TIME, true)
			 		   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	public static function getArrMember($arrUid, $arrField, $offset, $limit)
	{
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(GuildDef::TABLE_GUILD_MEMBER)
					   ->where(array(GuildDef::USER_ID, 'in', $arrUid))
					   ->orderBy(GuildDef::CONTRI_POINT, false)
					   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	//拉取所有军团的所有成员
	public static function getArrMemberList($arrGuildId, $arrField)
	{
		$i = 0;
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$guildIds = array_slice($arrGuildId, $i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			if (empty($guildIds))
			{
				break;
			}
			$arrCond = array(array(GuildDef::GUILD_ID, 'in', $guildIds));
			$ret = self::getMember($arrCond, $arrField);
			$arrRet = array_merge($arrRet, $ret);
			$count = count($ret);
			$i++;
		}
		
		return $arrRet;
	}

	public static function insertApply($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(GuildDef::TABLE_GUILD_APPLY)->values($arrField)->query();
	}

	public static function selectApply($uid, $guildId)
	{
		$data = new CData();
		$arrRet = $data->select(GuildDef::$GUILD_APPLY_FIELDS)
					   ->from(GuildDef::TABLE_GUILD_APPLY)
					   ->where(array(GuildDef::USER_ID, '=', $uid))
					   ->where(array(GuildDef::GUILD_ID, '=', $guildId))
					   ->where(array(GuildDef::STATUS, '=', GuildApplyStatus::OK))
		   			   ->query();
		if (!empty($arrRet[0])) 
		{
			return $arrRet[0];
		}
		return array();
	}

	public static function updateApply($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(GuildDef::TABLE_GUILD_APPLY)->set($arrField);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		if ($arrRet['affected_rows'] == 0)
		{
			return false;
		}
		return true;
	}

	public static function getApplyCount($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(GuildDef::TABLE_GUILD_APPLY);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return $arrRet[0]['count'];
	}

	public static function getApplyList($cond, $arrField, $offset, $limit)
	{	
		$data = new CData();
		$arrRet = $data->select($arrField)
			 		   ->from(GuildDef::TABLE_GUILD_APPLY)
					   ->where($cond)
			 		   ->where(array(GuildDef::STATUS, '=', GuildApplyStatus::OK))
			 		   ->orderBy(GuildDef::APPLY_TIME, false)
			 		   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	public static function insertRecord($arrField)
	{
		$data = new CData();
		$data->insertInto(GuildDef::TABLE_GUILD_RECORD)
			 ->values($arrField)
			 ->uniqueKey(GuildDef::RECORD_ID)
			 ->query();
	}
	
	public static function getRecordList($guildId, $arrType, $offset, $limit, $time = 0)
	{
		$data = new CData();
		$arrRet = $data->select(GuildDef::$GUILD_RECORD_FIELDS)
					   ->from(GuildDef::TABLE_GUILD_RECORD)
					   ->where(array(GuildDef::GUILD_ID, '=', $guildId))
					   ->where(array(GuildDef::RECORD_TYPE, 'IN', $arrType))
					   ->where(array(GuildDef::RECORD_TIME, '>=', $time))
					   ->orderBy(GuildDef::RECORD_TIME, false)
					   ->limit($offset, $limit)
					   ->query();
		return $arrRet;
	}
	
	public static function getHarvestList($guildId, $fieldId)
	{
		$i = 0;
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		$data = new CData();
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$ret = $data->select(GuildDef::$GUILD_RECORD_FIELDS)
						->from(GuildDef::TABLE_GUILD_RECORD)
						->where(array(GuildDef::GUILD_ID, '=', $guildId))
						->where(array(GuildDef::RECORD_TYPE, '=', GuildRecordType::HARVEST_FIELD))
						->where(array(GuildDef::RECORD_DATA, '=', $fieldId))
						->where(array(GuildDef::RECORD_TIME, '>=', Util::getTime() - SECONDS_OF_DAY * 2))
						->orderBy(GuildDef::RECORD_TIME, false)
						->limit($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE)
						->query();
			$arrRet = array_merge($arrRet, $ret);
			$count = count($ret);
			$i++;
		}
		
		return $arrRet;
	}
	
	public static function getRecord($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(GuildDef::TABLE_GUILD_RECORD);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->orderBy(GuildDef::RECORD_TIME, false)->query();
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */