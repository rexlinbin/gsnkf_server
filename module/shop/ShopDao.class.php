<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ShopDao.class.php 58286 2013-08-08 06:37:04Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/ShopDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-08-08 06:37:04 +0000 (Thu, 08 Aug 2013) $
 * @version $Revision: 58286 $
 * @brief 
 *  
 **/
class ShopDao
{
	/**
	 * 插入新的用户数据
	 *
	 * @param array $arrField
	 */
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(ShopDef::TABLE_NAME_SHOP)->values($arrField)->query();
	}

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
		$arrRet = $data->select(ShopDef::$SHOP_FIELDS)
					   ->from(ShopDef::TABLE_NAME_SHOP)
		               ->where(array(ShopDef::USER_ID, '=', $uid))
		               ->query();
		// 检查返回值
		if (!empty($arrRet[0]))
		{
			return $arrRet[0];
		}
		// 没检索结果的时候，直接返回false
		return false;
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
		$arrRet = $data->update(ShopDef::TABLE_NAME_SHOP)
					   ->set($arrField)
					   ->where(array(ShopDef::USER_ID, '=', $uid))
					   ->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */