<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SignDao.class.php 135948 2014-10-13 11:18:19Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/SignDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-13 11:18:19 +0000 (Mon, 13 Oct 2014) $
 * @version $Revision: 135948 $
 * @brief 
 *  
 **/



class SignDao
{
	const tblName = 't_acc_sign';
	const tblNameNormal = 't_normal_sign';
	const tblNameMonth = 't_month_sign';

	public static function getSingnInfo($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblName)->where('uid', '=', $uid)->query();
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
		$ret= $data->insertInto(self::tblName)->values($arrField)->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new SysException( 'insert to sign DB failed' );
		}
	}

	public static function insertNormal($uid, $arrField)
	{
		$arrField['uid'] = $uid;
		$data = new CData();
		$ret= $data->insertInto(self::tblNameNormal)->values($arrField)->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new SysException( 'insert to signnormal DB failed' );
		}
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->update(self::tblName)->set($arrField)->where('uid', '=', $uid)->query();
	}
	
	public static function getNormalInfo( $uid, $arrField )
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblNameNormal)->where('uid', '=', $uid)->query();
		if (empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function updateNormal( $uid, $arrField )
	{
		$data = new CData();
		$data->update(self::tblNameNormal)->set($arrField)->where('uid', '=', $uid)->query();
	}
	
	public static function getMonthSignInfo($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select( $arrField )->from(self::tblNameMonth)
		->where( array('uid','=', $uid) )->query();
		
		if( empty($ret) )
		{
			return array();
		}
		return $ret[0];
		
	}
	
	public static function insertMonthSign( $uid, $initValArr )
	{
		$data = new CData();
		$ret= $data->insertInto(self::tblNameMonth)->values($initValArr)->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new SysException( 'insert to sign DB failed' );
		}
	}
	
	public static function updateMonthSign($uid, $ret)
	{
		$data = new CData();
		$ret= $data->update(self::tblNameMonth)->set($ret)->where( 'uid','=',$uid )->query();
		if ( $ret[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new SysException( 'update sign DB failed' );
		}
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */