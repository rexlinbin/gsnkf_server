<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SwitchDao.class.php 70054 2013-10-23 03:03:02Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/SwitchDao.class.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2013-10-23 03:03:02 +0000 (Wed, 23 Oct 2013) $
 * @version $Revision: 70054 $
 * @brief 
 *  
 **/

class SwitchDao
{
	const tblName =  't_switch';
	private static $switchTblField    =    array(
	        TblSwitchField::UID,
	        TblSwitchField::GROUP0,
	        TblSwitchField::GROUP1,
	        TblSwitchField::GROUP2,
	        );

	public static function get($uid)
	{
		$data = new CData();
		$ret =  $data->select(self::$switchTblField)
		             ->from(self::tblName)
		             ->where('uid', '=', $uid)
		             ->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;
	}

	public static function insert($uid, $arrField)
	{
		$data = new CData();
		$arrField['uid'] = $uid;
		$ret = $data->insertIgnore(self::tblName)
		            ->values($arrField)
		            ->query();
	}

	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::tblName)
		     ->values($arrField)
		     ->query();
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */