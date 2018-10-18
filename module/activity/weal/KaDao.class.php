<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: KaDao.class.php 94666 2014-03-21 05:37:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/weal/KaDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-21 05:37:46 +0000 (Fri, 21 Mar 2014) $
 * @version $Revision: 94666 $
 * @brief 
 *  
 **/
class KaDao
{
	private static  $tbl = 't_ka';
	
	public static function getKaInfo( $uid )
	{
		$data = new CData();
		$ret = $data->select( array('uid', 'refresh_time', 'point_today','point_add' ) )
		-> from( self::$tbl )
		-> where( array( 'uid','=', $uid ) )
		->query();
		if ( empty( $ret ) )
		{
			return array();
		}
		
		return $ret[0];
	}
	
	public static function updateKaInfo( $uid, $updateArr )
	{
		$data = new CData();
		$updateArr['uid'] = $uid;
		$ret = $data->insertOrUpdate( self::$tbl )->values( $updateArr )
		->where(array('uid','=',$uid))->query();
		
		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			throw new FakeException( 'insert or update t_ka failed' );
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */