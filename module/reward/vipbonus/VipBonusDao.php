<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipBonusDao.php 237823 2016-04-12 09:28:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/vipbonus/VipBonusDao.php $
 * @author $Author: MingTian $(hoping@babeltime.com)
 * @date $Date: 2016-04-12 09:28:41 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237823 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : VipBonusDao
* Description : Vip福利数据库类
* Inherit     :
**********************************************************************************************************************/

class VipBonusDao
{
	public static function select($uid)
	{
		$data = new CData();
		$ret = $data->select(VipBonusDef::$TABLE_FIELDS)
					->from(VipBonusDef::SQL_TABLE)
					->where(array(VipBonusDef::SQL_UID, '=', $uid))	
					->query();
		if(isset($ret[0]))
		{
			return $ret[0];
		}				
		return array();
	}
	
	public static function update($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(VipBonusDef::SQL_TABLE)->values($arrField)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */