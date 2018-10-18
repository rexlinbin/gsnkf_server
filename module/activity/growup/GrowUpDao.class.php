<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GrowUpDao.class.php 53793 2013-07-03 11:47:27Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/growup/GrowUpDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-07-03 11:47:27 +0000 (Wed, 03 Jul 2013) $
 * @version $Revision: 53793 $
 * @brief 
 *  
 **/
class GrowUpDao
{
	private static $tblGrowUp = 't_growup';
	/**
	 * 获取成长计划的信息
	 *
	 * @param int $uid							用户ID
	 * @return 返回相应信息
	 */
	public static function getGrowUpInfo($uid)
	{
		// 进行查询
		$data = new CData();

		$arrRet = $data->select(array('uid',
				'activation_time',
				'va_grow_up'))
		               ->from(self::$tblGrowUp)
					   ->where(array("uid", "=", $uid))
					   ->query();
		// 检查是否为空
		return isset($arrRet[0]) ? $arrRet[0] : false;
	}

	/**
	 * 添加成长计划
	 * 
	 * @param int $uid							用户ID
	 */
	public static function addNewGrowUpInfo($uid)
	{
		// 设置属性
		$arr = array('uid' => $uid,
					 'activation_time' => Util::getTime(),
					 'va_grow_up' => array('already' => array()));

		$data = new CData();
		$arrRet = $data->insertInto(self::$tblGrowUp)
		               ->values($arr)->query();
		return $arr;
	}

	/**
	 * 更新成长计划
	 * 
	 * @param int $uid							用户ID
	 * @param array $arr						成长计划
	 */
	public static function updNewGrowUpInfo($uid ,$arr)
	{
		// 更新数据库
		$data = new CData();

		$arrRet = $data->update(self::$tblGrowUp)
		               ->set(array('va_grow_up' => $arr))
		               ->where(array("uid", "=", $uid))
		               ->query();
		return $arrRet;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */