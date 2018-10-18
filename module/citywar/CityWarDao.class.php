<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWarDao.class.php 109230 2014-05-19 07:37:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/CityWarDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-05-19 07:37:25 +0000 (Mon, 19 May 2014) $
 * @version $Revision: 109230 $
 * @brief 
 *  
 **/
class CityWarDao
{
	public static function selectCity($cityId)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR)
					   ->where(array(CityWarDef::CITY_ID, '=', $cityId))
					   ->query();
		if (!empty($arrRet[0]))
		{
			return $arrRet[0];
		}
		return array();
	}
	
	public static function insertOrUpdateCity($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(CityWarDef::TABLE_CITY_WAR)->values($arrField)->query();
	}
	
	public static function updateCity($cityId, $arrField)
	{
		$data = new CData();
		$data->update(CityWarDef::TABLE_CITY_WAR)
			 ->set($arrField)
			 ->where(array(CityWarDef::CITY_ID, '=', $cityId))
			 ->query();
	}
	
	public static function getCityList()
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR)
					   ->where(array(CityWarDef::LAST_GID, '>', 1))
					   ->orderBy(CityWarDef::CITY_ID, true)
					   ->query();
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::CITY_ID);
		}
		return array();
	}
	
	public static function getGuildCityList($guildId, $field = CityWarDef::LAST_GID)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR)
					   ->where(array($field, '=', $guildId))
					   ->orderBy(CityWarDef::OCCUPY_TIME, true)
					   ->query();
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::CITY_ID);
		}
		return array();
	}
	
	public static function selectAttack($signupId)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
					   ->where(array(CityWarDef::SIGNUP_ID, '=', $signupId))
					   ->query();
		if (!empty($arrRet[0]))
		{
			return $arrRet[0];
		}
		return array();
	}
	
	public static function insertAttack($arrField)
	{
		$data = new CData();
		$data->insertIgnore(CityWarDef::TABLE_CITY_WAR_ATTACK)
			 ->values($arrField)
			 ->uniqueKey(CityWarDef::SIGNUP_ID)
		     ->query();
	}
	
	public static function updateAttack($signupId, $arrField)
	{
		$data = new CData();
		$data->update(CityWarDef::TABLE_CITY_WAR_ATTACK)
			 ->set($arrField)
			 ->where(array(CityWarDef::SIGNUP_ID, '=', $signupId))
			 ->query();
	}
	
	public static function getAttack($arrCond)
	{
		$data = new CData();
		$data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)->from(CityWarDef::TABLE_CITY_WAR_ATTACK);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		return $arrRet;
	}
	
	public static function getGuildSignupList($guildId, $startTime, $endTime)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
					   ->where(array(CityWarDef::ATTACK_GID, '=', $guildId))
					   ->where(array(CityWarDef::SIGNUP_TIME, 'between', array($startTime, $endTime)))
					   ->orderBy(CityWarDef::SIGNUP_ID, true)
					   ->query();
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::CITY_ID);
		}
		return array();
	}
	
	public static function getCitySignupList($cityId, $startTime, $endTime)
	{
		$data = new CData();
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		$i = 0;
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$ret = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
						->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
						->where(array(CityWarDef::CITY_ID, '=', $cityId))
						->where(array(CityWarDef::SIGNUP_TIME, 'between', array($startTime, $endTime)))
						->orderBy(CityWarDef::SIGNUP_ID, true)
						->limit($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE)
						->query();
			$arrRet = array_merge($arrRet, $ret);
			$count = count($ret);
			$i++;
		}
		
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::ATTACK_GID);
		}
		return array();
	}
	
	public static function getGuildAttackList($guildId, $startTime, $endTime)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
					   ->where(array(CityWarDef::ATTACK_GID, '=', $guildId))
					   ->where(array(CityWarDef::SIGNUP_TIME, 'between', array($startTime, $endTime)))
					   ->where(array(CityWarDef::ATTACK_TIMER, '>', 0))
					   ->orderBy(CityWarDef::ATTACK_TIMER, true)
					   ->query();
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::CITY_ID);
		}
		return array();
	}
	
	public static function getCityAttackList($cityId, $startTime, $endTime)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
					   ->where(array(CityWarDef::CITY_ID, '=', $cityId))
					   ->where(array(CityWarDef::SIGNUP_TIME, 'between', array($startTime, $endTime)))
					   ->where(array(CityWarDef::ATTACK_TIMER, '>', 0))
					   ->orderBy(CityWarDef::ATTACK_TIMER, true)
					   ->query();
		if (!empty($arrRet))
		{
			return $arrRet;
		}
		return array();
	}
	
	public static function getAllAttackList($startTime, $endTime)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_ATTACK_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_ATTACK)
					   ->where(array(CityWarDef::SIGNUP_TIME, 'between', array($startTime, $endTime)))
					   ->where(array(CityWarDef::ATTACK_TIMER, '>', 0))
					   ->orderBy(CityWarDef::ATTACK_TIMER, true)
					   ->query();
		if (!empty($arrRet))
		{
			return $arrRet;
		}
		return array();
	}
	
	public static function selectUser($uid)
	{
		$data = new CData();
		$arrRet = $data->select(CityWarDef::$CITY_WAR_USER_FIELDS)
					   ->from(CityWarDef::TABLE_CITY_WAR_USER)
					   ->where(array(CityWarDef::USER_ID, '=', $uid))
					   ->query();
		if (!empty($arrRet[0]))
		{
			return $arrRet[0];
		}
		return array();
	}
	
	public static function insertOrUpdateUser($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(CityWarDef::TABLE_CITY_WAR_USER)->values($arrField)->query();
	}
	
	public static function updateUser($uid, $arrField)
	{
		$data = new CData();
		$data->update(CityWarDef::TABLE_CITY_WAR_USER)
					->set($arrField)
					->where(array(CityWarDef::USER_ID, '=', $uid))
					->query();
	}
	
	public static function getArrUser($arrCond, $arrField)
	{
		if (!in_array(CityWarDef::USER_ID, $arrField)) 
		{
			$arrField[] = CityWarDef::USER_ID;
		}
		$data = new CData();
		$data->select($arrField)->from(CityWarDef::TABLE_CITY_WAR_USER);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$arrRet = $data->query();
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, CityWarDef::USER_ID);
		}
		return array();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */