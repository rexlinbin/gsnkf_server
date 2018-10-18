<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemBookDao.class.php 250248 2016-07-06 09:32:12Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/itemInfo/ItemBookDao.class.php $
 * @author $Author: QingYao $(tianming@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief 
 *  
 **/
class ItemBookDao
{
	const TABLE_ARM_BOOK = 't_arm_book';
	const TABLE_TREAS_BOOK = 't_treas_book';
	const TABLE_GOD_WEAPON_BOOK = 't_god_weapon_book';
	const TABLE_TALLY_BOOK = 't_tally_book';
	const TABLE_CHARIOT_BOOK='t_chariot_book';
	const UID = 'uid';
	const BOOK = 'va_book';
	
	public static function getArmBook($uid)
	{
		$data = new CData();
		$ret = $data->select(array(self::BOOK))
					->from(self::TABLE_ARM_BOOK)
					->where(array(self::UID, '=', $uid))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}
	
	public static function updateArmBook($uid, $book)
	{
		$data = new CData();
		$arrField = array(
				self::UID => $uid,
				self::BOOK => $book
		);
		$data->insertOrUpdate(self::TABLE_ARM_BOOK)->values($arrField)->query();
	}
	
	public static function getTreasBook($uid)
	{
		$data = new CData();
		$ret = $data->select(array(self::BOOK))
					->from(self::TABLE_TREAS_BOOK)
					->where(array(self::UID, '=', $uid))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}
	
	public static function updateTreasBook($uid, $book)
	{
		$data = new CData();
		$arrField = array(
				self::UID => $uid,
				self::BOOK => $book
		);
		$data->insertOrUpdate(self::TABLE_TREAS_BOOK)->values($arrField)->query();
	}
	
	public static function getGodWeaponBook($uid)
	{
		$data = new CData();
		$ret = $data->select(array(self::BOOK))
					->from(self::TABLE_GOD_WEAPON_BOOK)
					->where(array(self::UID, '=', $uid))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}
	
	public static function updateGodWeaponBook($uid, $book)
	{
		$data = new CData();
		$arrField = array(
				self::UID => $uid,
				self::BOOK => $book
		);
		$data->insertOrUpdate(self::TABLE_GOD_WEAPON_BOOK)->values($arrField)->query();
	}
	
	public static function getTallyBook($uid)
	{
		$data = new CData();
		$ret = $data->select(array(self::BOOK))
					->from(self::TABLE_TALLY_BOOK)
					->where(array(self::UID, '=', $uid))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}
	
	public static function updateTallyBook($uid, $book)
	{
		$data = new CData();
		$arrField = array(
				self::UID => $uid,
				self::BOOK => $book
		);
		$data->insertOrUpdate(self::TABLE_TALLY_BOOK)->values($arrField)->query();
	}
	
	public static function getChariotBook($uid)
	{
		$data = new CData();
		$ret = $data->select(array(self::BOOK))
		->from(self::TABLE_CHARIOT_BOOK)
		->where(array(self::UID, '=', $uid))
		->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}
	
	public static function updateChariotBook($uid, $book)
	{
		$data = new CData();
		$arrField = array(
				self::UID => $uid,
				self::BOOK => $book
		);
		$data->insertOrUpdate(self::TABLE_CHARIOT_BOOK)->values($arrField)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */