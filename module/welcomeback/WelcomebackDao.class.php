<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackDao.class.php 258534 2016-08-26 05:37:16Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/WelcomebackDao.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-26 05:37:16 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258534 $
 * @brief 
 *  
 **/
class WelcomebackDao
{
	private static $table = 't_welcomeback';
	
	public static function getInfo($uid)
	{
		$arrField = array(
				WelcomebackDef::UID,
				WelcomebackDef::OFFLINE_TIME,
				WelcomebackDef::BACK_TIME,
				WelcomebackDef::END_TIME,
				WelcomebackDef::NEED_BUFA,
				WelcomebackDef::VA_INFO
		);
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::$table)
					->where(array(WelcomebackDef::UID, '=', $uid))
					->query();
		return isset($ret[0])? $ret[0] : array();
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$table)
					->values($arrField)
					->where(array(WelcomebackDef::UID, '=', $uid))
					->query();
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */