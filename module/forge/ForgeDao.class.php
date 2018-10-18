<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ForgeDao.class.php 52489 2013-06-25 07:14:10Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/ForgeDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-06-25 07:14:10 +0000 (Tue, 25 Jun 2013) $
 * @version $Revision: 52489 $
 * @brief 
 *  
 **/

class ForgeDao
{
	public static function selectForge($uid)
	{
		// 使用uid作为检索条件
		$data = new CData();
		$arrCond = array('uid', '=', $uid);
		$arrRet = $data->select(ForgeDef::$FORGE_FIELDS)
					   ->from(ForgeDef::FORGE_TABLE_NAME)
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
	
	public static function insertForge($arrField)
	{
		$data = new CData();
		$data->insertInto(ForgeDef::FORGE_TABLE_NAME)->values($arrField)->query();
	}
	
	public static function updateForge($uid, $arrField)
	{
		$data = new CData();
		$arrCond = array('uid', '=', $uid);
		$arrRet = $data->update(ForgeDef::FORGE_TABLE_NAME)
					   ->set($arrField)
					   ->where($arrCond)
					   ->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */