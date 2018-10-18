<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UnionDao.class.php 182847 2015-07-08 07:09:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/UnionDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-07-08 07:09:39 +0000 (Wed, 08 Jul 2015) $
 * @version $Revision: 182847 $
 * @brief 
 *  
 **/
class UnionDao
{
	public static function select($uid)
	{
		$data = new CData();
		$ret = $data->select(UnionDef::$TBL_FIELDS)
					->from(UnionDef::TBL_UNION)
					->where(array(UnionDef::FIELD_UID, '=', $uid))
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
		$data->insertOrUpdate(UnionDef::TBL_UNION)->values($arrField)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */