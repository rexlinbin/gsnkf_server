<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySignDao.class.php 208823 2015-11-11 09:47:50Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/HappySignDao.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-11 09:47:50 +0000 (Wed, 11 Nov 2015) $
 * @version $Revision: 208823 $
 * @brief 
 *  
 **/
class HappySignDao
{
	private static $tblHappySign = 't_happy_sign';
	
	public static function getInfo($uid)
	{
		$arrSelect = array(
				'uid',
				'sign_time',
				'login_num',
				'va_reward',
		);
		$data = new CData();
		$ret = $data->select($arrSelect)
					->from(self::$tblHappySign)
					->where('uid', '=', $uid)
					->query();
		return (isset($ret[0])) ? $ret[0] : array();
	}
	
	public static function insert($arrInsert)
	{
		$data = new CData();
		$ret = $data->insertInto(self::$tblHappySign)
					->values($arrInsert)
					->query();
	}
	
	public static function update($uid, $arrInsert)
	{
		$data = new CData();
		$ret = $data->update(self::$tblHappySign)
					->set($arrInsert)
					->where('uid', '=', $uid)
					->query();
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */