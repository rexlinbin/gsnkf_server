<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BagDAO.class.php 69143 2013-10-16 06:20:48Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/BagDAO.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2013-10-16 06:20:48 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69143 $
 * @brief
 *
 **/



class BagDAO
{
	public static function selectBag($select, $where)
	{
		$data = new CData();
		$ret = $data->select($select)->from(BagDef::BAG_TABLE_NAME)->where($where)->query();
		return $ret;
	}

	public static function insertOrupdateBag($values)
	{
	    Logger::trace('insertOrupdateBag params %s.',$values);
		$data = new CData();
		$ret = $data->insertOrUpdate(BagDef::BAG_TABLE_NAME)->values($values)->query();
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */