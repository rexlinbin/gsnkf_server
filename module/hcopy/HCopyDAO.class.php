<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: HCopyDAO.class.php 110754 2014-05-24 10:51:50Z QiangHuang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/HCopyDAO.class.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-24 10:51:50 +0000 (Sat, 24 May 2014) $
 * @version $Revision: 110754 $
 * @brief
 *
 **/

class HCopyDao
{
	const TABLE = 't_hcopy';
	static $FILED_DATA = array(HCopyDef::FILED_COPYID, HCopyDef::FILED_LEVEL, HCopyDef::FILED_FINISH_NUM, HCopyDef::FILED_COPY_INFO);
	public static function get($uid, $copyId, $level)
	{
		$db = new CData();
		$ret = $db->select(self::$FILED_DATA)->from(self::TABLE)
			->where("uid", "=", $uid)
			//->where("copyid", "=", $copyId)
			->where("level", "<=", $level)
			->query();
		return $ret;
	}
	
	public static function put($data)
	{
		$db = new CData();
		$db->insertOrUpdate(self::TABLE)->values($data)->query();
	}
	
	public static function getAllFinishInfos($uid)
	{
		$db = new CData();
		$ret = $db->select(array(HCopyDef::FILED_COPYID, HCopyDef::FILED_LEVEL, HCopyDef::FILED_FINISH_NUM))
			->from(self::TABLE)->where(HCopyDef::FILED_UID, "=", $uid)->query();
		return $ret;
	}

}

