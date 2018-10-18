<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShopDao.class.php 198502 2015-09-15 02:18:01Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/TravelShopDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-15 02:18:01 +0000 (Tue, 15 Sep 2015) $
 * @version $Revision: 198502 $
 * @brief 
 *  
 **/
class TravelShopDao
{
	public static function select()
	{
		$data = new CData();
		$ret = $data->select(TravelShopDef::$TBL_TS_FIELDS)
					->from(TravelShopDef::TBL_TRAVEL_SHOP)
					->where(array(TravelShopDef::FIELD_ID, '=', 1))
					->query();
		if (empty($ret[0]))
		{
			return array();
		}
		return $ret[0];
	}

	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(TravelShopDef::TBL_TRAVEL_SHOP)->values($arrField)->query();
	}
	
	public static function update($arrField)
	{
		$data = new CData();
		$data->update(TravelShopDef::TBL_TRAVEL_SHOP)->set($arrField)->where(array(TravelShopDef::FIELD_ID, '=', 1))->query();
	}
	
	public static function selectUser($uid)
	{
		$data = new CData();
		$ret = $data->select(TravelShopDef::$TBL_TSU_FIELDS)
					->from(TravelShopDef::TBL_TRAVEL_SHOP_USER)
					->where(array(TravelShopDef::FIELD_UID, '=', $uid))
					->query();
		if (empty($ret[0]))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insertOrUpdateUser($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(TravelShopDef::TBL_TRAVEL_SHOP_USER)->values($arrField)->query();
	}
	
	public static function getArrUser($offset, $limit, $startTime = 0, $arrField = array())
	{
		if (empty($arrField)) 
		{
			$arrField = TravelShopDef::$TBL_TSU_FIELDS;
		}
		if (!in_array(TravelShopDef::FIELD_UID, $arrField)) 
		{
			$arrField[] = TravelShopDef::FIELD_UID;
		}
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(TravelShopDef::TBL_TRAVEL_SHOP_USER)
					   ->where(array(TravelShopDef::FIELD_REFRESH_TIME, '>=', $startTime))
					   ->where(array(TravelShopDef::FIELD_START_TIME, '>', 0))
					   ->orderBy(TravelShopDef::FIELD_UID, true)
					   ->limit($offset, $limit)
					   ->query();
		return 	Util::arrayIndex($arrRet, TravelShopDef::FIELD_UID);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */