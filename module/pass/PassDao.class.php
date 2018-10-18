<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassDao.class.php 149109 2014-12-25 12:23:26Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/PassDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-25 12:23:26 +0000 (Thu, 25 Dec 2014) $
 * @version $Revision: 149109 $
 * @brief 
 *  
 **/
class PassDao
{
	static  $tbl = 't_pass';
	
	public static function getPassInfo( $uid, $fields )
	{
		$data = new CData();
		$ret = $data->select( $fields )->from( self::$tbl )
		->where( array('uid','=',$uid) )->query();
		if( empty( $ret ) )
		{
			return array();
		}
		
		return $ret[0];
	}
	
	public static function insertPassInfo( $uid, $insertInfo )
	{
		$data = new CData();
		$ret = $data->insertInto( self::$tbl )->values( $insertInfo )->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] <= 0 ) 
		{
			throw new InterException( 'insertFailed, info: %s', $insertInfo );
		}
	}
	
	public static function updatePassInfo( $uid, $updateInfo )
	{
		$data = new CData();
		$ret = $data->update( self::$tbl )->set( $updateInfo )
		->where( array( 'uid','=',$uid ) )->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] <= 0 )
		{
			throw new InterException( 'updateFailed, info: %s', $updateInfo );
		}
	}
	
	
	public static function getCount( $wheres )
	{
		$data = new CData();
		$data->selectCount()->from(self::$tbl);
		foreach ( $wheres as $where )
		{
			$data->where( $where );
		}
		$ret = $data->query();
		
		return $ret[0][DataDef::COUNT];
	}
	
	
	public static function getRankList( $fields, $wheres, $offset, $limit )
	{
		$data = new CData();
		
		$data->select( $fields )-> from(self::$tbl);
		foreach ( $wheres as $where )
		{
			$data->where( $where );
		}
		$ret = $data->orderBy( 'point' , false )->orderBy( 'pass_num' , false )
		->orderBy( 'reach_time' , true )->orderBy( 'uid', true )->limit($offset, $limit)->query();//TODOsuoyin qingqiu 
		
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */