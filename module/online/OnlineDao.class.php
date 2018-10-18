<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OnlineDao.class.php 56563 2013-07-25 09:37:23Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/OnlineDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-07-25 09:37:23 +0000 (Thu, 25 Jul 2013) $
 * @version $Revision: 56563 $
 * @brief 
 *  
 **/

class OnlineDao
{
	public static function getOnlineInfo($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from( OnlineDef::TBL )
					->where( 'uid', '=', $uid )
					->query();
		if (isset($ret[0]))
			{
				return $ret[0];
			}
			return array();	
	}

	public static function insert($uid, $arrField)
	{
		$data = new CData();
		$data->insertOrUpdate( OnlineDef::TBL )
			->values( $arrField )
			->query();
	}

	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->update( OnlineDef::TBL )
			->set( $arrField )
			->where( 'uid', '=', $uid )
			->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */