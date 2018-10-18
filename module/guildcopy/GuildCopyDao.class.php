<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyDao.class.php 171359 2015-05-06 10:19:40Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-06 10:19:40 +0000 (Wed, 06 May 2015) $
 * @version $Revision: 171359 $
 * @brief 
 *  
 **/
 
class GuildCopyDao
{
	const GuildCopyUserTable = 't_guild_copy_user';
	const GuildCopyTable = 't_guild_copy';
	
	public static function selectUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildCopyUserTable);
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
	
	public static function getRankListByDamage($arrCond, $arrField, $count = DataDef::MAX_FETCH)
	{
		$ret = array();
		
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getRankListByDamage($arrCond, $arrField, $offset, $limit);
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
	
	public static function _getRankListByDamage($arrCond, $arrField, $offset = 0, $limit = DataDef::MAX_FETCH)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildCopyUserTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		$data->orderBy(GuildCopyUserField::TBL_FIELD_ATK_DAMAGE, FALSE);
		$data->orderBy(GuildCopyUserField::TBL_FIELD_UID, TRUE);
	
		return $data->query();
	}
	
	public static function getRankListByDamageLast($arrCond, $arrField, $count)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getRankListByDamageLast($arrCond, $arrField, $offset, $limit);
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
	
	public static function _getRankListByDamageLast($arrCond, $arrField, $offset = 0, $limit = DataDef::MAX_FETCH)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildCopyUserTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		$data->orderBy(GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST, FALSE);
		$data->orderBy(GuildCopyUserField::TBL_FIELD_UID, TRUE);
		
		return $data->query();
	}
	
	public static function insertUser($arrField)
	{
		$data = new CData();
		$data->insertInto(self::GuildCopyUserTable)->values($arrField);
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}
	
	public static function updateUser($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::GuildCopyUserTable)->set($arrField);
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
	
	public static function selectGuild($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildCopyTable);
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
	
	public static function getGuildList($arrCond, $arrField, $count = DataDef::MAX_FETCH)
	{
		$ret = array();
	
		$offset = 0;
		while ($count > 0)
		{
			$limit = ($count >= DataDef::MAX_FETCH ? DataDef::MAX_FETCH : $count);
			$arrPart = self::_getGuildList($arrCond, $arrField, $offset, $limit);
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
	
	public static function _getGuildList($arrCond, $arrField, $offset = 0, $limit = DataDef::MAX_FETCH)
	{
		$data = new CData();
		$data->select($arrField)->from(self::GuildCopyTable);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$data->limit($offset, $limit);
		$data->orderBy(GuildCopyField::TBL_FIELD_MAX_PASS_COPY, FALSE);
		$data->orderBy(GuildCopyField::TBL_FIELD_MAX_PASS_TIME, TRUE);
	
		return $data->query();
	}
	
	public static function insertGuild($arrField)
	{
		$data = new CData();
		$data->insertIgnore(self::GuildCopyTable)->values($arrField);
		$ret = $data->query();
	
		if ($ret['affected_rows'] == 0)
		{
			Logger::warning('insert affected num 0, field: %s', $arrField);
			return FALSE;
		}
		else 
		{
			return TRUE;
		}
	}
	
	public static function updateGuild($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::GuildCopyTable)->set($arrField);
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
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */