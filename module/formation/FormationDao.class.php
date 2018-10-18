<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: FormationDao.class.php 140785 2014-11-19 08:49:04Z MingTian $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/FormationDao.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2014-11-19 08:49:04 +0000 (Wed, 19 Nov 2014) $
 * @version $Revision: 140785 $
 * @brief 
 *  
 **/



class FormationDao
{
	
	public static $tblName = 't_hero_formation';
	
	public static $tabField = array('uid','craft_id', 'va_formation');

	
	/**
	 * 获取用户的阵型信息
	 * @param int $uid	
	 */
	public static function getByUid($uid)
	{
		$data = new CData();
		$arrRet = $data->select(self::$tabField)
		               ->from(self::$tblName)
		               ->where(array('uid', '=', $uid))
		               ->query();
		if(empty($arrRet))
		{
			return NULL;
		}
		
		return $arrRet[0];
	}
	
	/**
	 * 获取批量用户的阵型信息
	 * @param int $arrUid
	 */
	public static function getByArrUid($arrUid)
	{
		$data = new CData();
		$arrRet = $data->select(self::$tabField)
					   ->from(self::$tblName)
					   ->where(array('uid', 'IN', $arrUid))
					   ->query();
		if(empty($arrRet))
		{
			return array();
		}
	
		return $arrRet;
	}

	/**
	 * 更新阵型信息
	 * @param int $uid	
	 * @param array $arrField
	 */
	public static function update($uid, $arrField)
	{			
		$data = new CData();
		$arrRet = $data->update(self::$tblName)
		     		   ->set($arrField)
		     		   ->where(array('uid', '=', $uid))
		     		   ->query();
		
		return $arrRet;
	}

	/**
	 * 给用户插入阵型信息
	 * @param array $arrField
	 */
	public static function insert($arrField)
	{
		$data = new CData();
		$arrRet = $data->insertInto(self::$tblName)
		     		   ->values($arrField)
		     		   ->query();

		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */