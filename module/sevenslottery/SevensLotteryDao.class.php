<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLotteryDao.class.php 254271 2016-08-02 10:28:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/SevensLotteryDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-02 10:28:45 +0000 (Tue, 02 Aug 2016) $
 * @version $Revision: 254271 $
 * @brief 
 *  
 **/
class SevensLotteryDao
{
	public static function select($uid)
	{
		$data = new CData();
	
		$arrRet = $data->select(SevensLotteryDef::$TBL_SEVENS_LOTTERY_FIELDS)
					   ->from(SevensLotteryDef::TBL_SEVENS_LOTTERY)
					   ->where(array(SevensLotteryDef::FIELD_UID, '=', $uid))
					   ->query();
		if (empty($arrRet))
		{
			return array();
		}
	
		return $arrRet[0];
	}
	
	public static function insert($arrFiled)
	{
		$data = new CData();
		$ret = $data->insertInto(SevensLotteryDef::TBL_SEVENS_LOTTERY)->values($arrFiled)->query();
		return true;
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->update(SevensLotteryDef::TBL_SEVENS_LOTTERY)->set($arrField)->where(array(SevensLotteryDef::FIELD_UID, '=', $uid))->query();
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */