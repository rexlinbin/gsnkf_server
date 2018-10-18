<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActiveDao.class.php 93524 2014-03-14 09:35:47Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/ActiveDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-03-14 09:35:47 +0000 (Fri, 14 Mar 2014) $
 * @version $Revision: 93524 $
 * @brief 
 *  
 **/
class ActiveDao
{
	public static function select($uid)
	{
		$data = new CData();
		$ret = $data->select(ActiveDef::$ACTIVE_FIELDS)
					->from(ActiveDef::ACTIVE_TABLE)
					->where(array(ActiveDef::UID, '=', $uid))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return false;
	}

	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(ActiveDef::ACTIVE_TABLE)->values($arrField)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */