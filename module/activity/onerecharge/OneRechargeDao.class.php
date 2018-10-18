<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRechargeDao.class.php 232451 2016-03-14 07:25:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/OneRechargeDao.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-14 07:25:26 +0000 (Mon, 14 Mar 2016) $
 * @version $Revision: 232451 $
 * @brief 单充回馈数据库操作类
 *  
 **/
class OneRechargeDao
{
	private static $tblOneRecharge = 't_one_recharge';
	public static function getInfo($uid)
	{
		$arrSelect = array(
				OneRechargeDef::UID,
				OneRechargeDef::REFRESH_TIME,
				OneRechargeDef::IF_REMAIN,
				OneRechargeDef::VA_INFO,
		);
		$data = new CData();
		$ret = $data->select($arrSelect)
					->from(self::$tblOneRecharge)
					->where('uid', '=', $uid)
					->query();
		return (isset($ret[0])) ? $ret[0] : array();
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$tblOneRecharge)
					->values($arrField)
					->where('uid', '=', $uid)
					->query();
		return $ret;
	}
	
	public static function selectAllRemainRewardUsersData($isRemain, $offset, $limit)
	{
		$data = new CData();
		$arrRet = $data->select(array(OneRechargeDef::UID, OneRechargeDef::REFRESH_TIME))
						->from(self::$tblOneRecharge)
						->where(array(OneRechargeDef::IF_REMAIN, '=', $isRemain))
						->orderBy(OneRechargeDef::UID, true)
						->limit($offset, $limit)
						->query();
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */