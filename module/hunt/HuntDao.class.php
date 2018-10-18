<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HuntDao.class.php 90773 2014-02-19 11:15:29Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/HuntDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-02-19 11:15:29 +0000 (Wed, 19 Feb 2014) $
 * @version $Revision: 90773 $
 * @brief 
 *  
 **/
class HuntDao
{
	/**
	 * 获取用户信息
	 *
	 * @param int $uid							用户id
	 * @return array $arrRet                	结果集或false
	 */
	public static function select($uid)
	{
		//使用uid作为检索条件
		$data = new CData();
		$ret = $data->select(HuntDef::$HUNT_FIELDS)
					->from(HuntDef::HUNT_TABLE)
					->where(array(HuntDef::HUNT_UID, '=', $uid))
					->query();
		// 检查返回值
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		// 没检索结果的时候，直接返回false
		return false;
	}
	
	/**
	 * 插入新的用户数据
	 *
	 * @param array $arrField
	 */
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(HuntDef::HUNT_TABLE)->values($arrField)->query();
	}
	
	/**
	 * 更新用户信息
	 *
	 * @param string $uid						用户ID
	 * @param array $arrField					更新项目
	 */
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->update(HuntDef::HUNT_TABLE)
			 		->set($arrField)
			 		->where(array(HuntDef::HUNT_UID, '=', $uid))
			 		->query();
		if ($ret['affected_rows'] == 0)
		{
			return false;
		}
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */