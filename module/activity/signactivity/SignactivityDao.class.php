<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SignactivityDao.class.php 87945 2014-01-20 12:02:54Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/signactivity/SignactivityDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-20 12:02:54 +0000 (Mon, 20 Jan 2014) $
 * @version $Revision: 87945 $
 * @brief 
 *  
 **/
class SignactivityDao
{
	public static  $tblName = 't_sign_activity';
	
	public static function getSignactivityInfo($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::$tblName)->where('uid', '=', $uid)->query();
		if (empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function insert($uid, $arrField)
	{
		$arrField['uid'] = $uid;
		$data = new CData();
		$ret= $data->insertInto(self::$tblName)->values($arrField)->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new SysException( 'insert to signactivity DB failed' );
		}
	}

	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->update(self::$tblName)->set($arrField)->where('uid', '=', $uid)->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */