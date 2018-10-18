<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BlackDao.class.php 113825 2014-06-12 09:25:38Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/BlackDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-12 09:25:38 +0000 (Thu, 12 Jun 2014) $
 * @version $Revision: 113825 $
 * @brief 
 *  
 **/
class BlackDao
{
	static $table = 't_black';
	
	public static function getVaBlack($uid)
	{
		$data = new CData();
		$ret = $data->select( array( 'va_black' ) )->from(self::$table)
		->where(array( 'uid','=',$uid ))
		->query();
		
		if ( empty($ret) )
		{
			return array();
		}
		
		return $ret[0];
	} 
	
	public static function insertOrUpdate($uid,$arrField)
	{
		if (!isset($arrField['uid']))
		{
			$arrField['uid'] = $uid;
		}
		
		$data = new CData();
		$ret = $data->insertOrUpdate( self::$table )->values($arrField)
		->query();
		
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */