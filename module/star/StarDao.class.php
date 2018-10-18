<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StarDao.class.php 128306 2014-08-21 03:39:02Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/StarDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-21 03:39:02 +0000 (Thu, 21 Aug 2014) $
 * @version $Revision: 128306 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : StarDao
 * Description : 名将系统的数据库操作类
 * Inherit     :
 **********************************************************************************************************************/
class StarDao
{
	/**
	 * 修数据用
	 * @param unknown $uid
	 */
	public static function initAllStar($uid)
	{
		$arrField = array(
				StarDef::STAR_USER_ID => $uid,
				StarDef::STAR_SEND_NUM => 0,
				StarDef::STAR_DRAW_NUM => 0,
				StarDef::STAR_SEND_TIME => 0,
				StarDef::STAR_VA_INFO => array()
		);
		$data = new CData();
		$data->insertInto(StarDef::STAR_TABLE_ALL_STAR)->values($arrField)->query();
		
		unset($arrField[StarDef::STAR_USER_ID]);
		return $arrField;
	}
	/**
	 * 插入新的用户数据
	 * 
	 * @param array $arrField 					
	 */
	public static function insertOrUpdateStar($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(StarDef::STAR_TABLE_STAR)->values($arrField)->query();
	}
	
	/**
	 * 插入新的用户数据
	 *
	 * @param array $arrField
	 */
	public static function insertOrUpdateAllStar($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(StarDef::STAR_TABLE_ALL_STAR)->values($arrField)->query();
	}
	
	/**
	 * 获取用户的所有名将信息
	 * 
	 * @param int $uid							用户id
	 * @return array $arrRet                	结果集或false
	 */
	public static function selectStar($uid)
	{
		$data = new CData();
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		$i = 0;
		
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$ret = $data->select(StarDef::$STAR_FIELDS)
				       	->from(StarDef::STAR_TABLE_STAR)
					   	->where(array(StarDef::STAR_USER_ID, '=', $uid))
					   	->orderBy(StarDef::STAR_ID, true)
					   	->limit($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE)
					   	->query();
			$count = count($ret);
			$i++; 
			$arrRet = array_merge($arrRet, $ret);
		}
		
		// 检查返回值
		if (!empty($arrRet))
		{
			return $arrRet;
		}
		// 没检索结果的时候，直接返回false
		return false;
	}
	
	/**
	 * 获取用户的后宫信息
	 *
	 * @param int $uid							用户id
	 * @return array $arrRet                	结果集或false
	 */
	public static function selectAllStar($uid)
	{
		// 使用uid作为检索条件
		$data = new CData();
		$arrCond = array(StarDef::STAR_USER_ID, '=', $uid);
		$arrRet = $data->select(StarDef::$ALL_STAR_FIELDS)
					   ->from(StarDef::STAR_TABLE_ALL_STAR)
					   ->where($arrCond)
					   ->query();
		// 检查返回值
		if (isset($arrRet[0]))
		{
			return $arrRet[0];
		}
		// 没检索结果的时候，直接返回false
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */