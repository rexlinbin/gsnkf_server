<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGiftDao.class.php 206954 2015-11-03 12:14:35Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/RechargeGiftDao.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-03 12:14:35 +0000 (Tue, 03 Nov 2015) $
 * @version $Revision: 206954 $
 * @brief 
 *  
 **/
class RechargeGiftDao
{
	private static $tblChargeGift = 't_recharge_gift';

	public static function getAllInfo($uid)
	{
		$data = new CData();
		$ret = $data->select(RechargeGiftDef::$allColumns)
		->from(self::$tblChargeGift)
		->where('uid', '=', $uid)
		->query();
		return (isset($ret[0])) ? $ret[0] : array();
	}

	public static function insert($arrInsert)
	{
		$data = new CData();
		$ret = $data->insertInto(self::$tblChargeGift)
		->values($arrInsert)
		->query();
	}
	
	public static function update($uid, $arrSelect)
	{
		$data = new CData();
		$ret = $data->update(self::$tblChargeGift)
		->set($arrSelect)
		->where('uid', '=', $uid)
		->query();
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */