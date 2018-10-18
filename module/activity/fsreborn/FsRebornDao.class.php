<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FsRebornDao.class.php 200753 2015-09-28 06:20:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/FsRebornDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-28 06:20:54 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200753 $
 * @brief 
 *  
 **/
class FsRebornDao
{
	public static function select($uid)
	{
		$data = new CData();
		$ret = $data->select(FsRebornDef::$TBL_FIELDS)
					->from(FsRebornDef::TBL_NAME)
					->where(array(FsRebornDef::FIELD_UID, '=', $uid))
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
		$data->insertOrUpdate(FsRebornDef::TBL_NAME)->values($arrField)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */