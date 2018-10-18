<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MallDao.class.php 77342 2013-11-27 08:48:24Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mall/MallDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-27 08:48:24 +0000 (Wed, 27 Nov 2013) $
 * @version $Revision: 77342 $
 * @brief 
 *  
 **/
class MallDao
{
	public static function select($uid, $type)
	{
		//使用uid作为检索条件
		$data = new CData();
		$arrRet = $data->select(array(MallDef::VA_MALL))
					   ->from(MallDef::MALL_TABLE)
					   ->where(array(MallDef::USER_ID, '=', $uid))
					   ->where(array(MallDef::MALL_TYPE, '=', $type))
					   ->query();
		// 检查返回值
		if (!empty($arrRet[0]))
		{
			return $arrRet[0][MallDef::VA_MALL];
		}
		return array();
	}
	
	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		$arrRet = $data->insertOrUpdate(MallDef::MALL_TABLE)
					   ->values($arrField)
					   ->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */