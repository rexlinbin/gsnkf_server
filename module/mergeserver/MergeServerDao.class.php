<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerDao.class.php 135595 2014-10-10 05:00:49Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/MergeServerDao.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-10 05:00:49 +0000 (Fri, 10 Oct 2014) $
 * @version $Revision: 135595 $
 * @brief 
 * 
 **/
 
/**********************************************************************************************************************
 * Class       : MergeServerDao
 * Description : 合服活动数据交互类
 * Inherit     : 
 **********************************************************************************************************************/
class MergeServerDao
{
    /**
     * 合服活动奖励表名 
     */
    const table = 't_mergeserver_reward';

	/**
	 * select 查询合服活动用户信息 
	 * 
	 * @param array $arrCond 检索条件
	 * @param array $arrField 检索项目
	 * @static
	 * @access public 
	 * @return array 合服活动用户信息
	 */
	public static function select($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::table);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$arrRet = $data->query();
		if (!empty($arrRet))
		{
			$arrRet = $arrRet[0];
		}
		return $arrRet;
	}	
	
	/**
	 * insert 插入合服活动用户信息 
	 * 
	 * @param array $arrField 插入项目
	 * @static
	 * @access public 
	 * @return void
	 */
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(self::table)->values($arrField);
		
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('insert affected num 0, field: %s', $arrField);
		}
	}

	/**
	 * update 更新合服活动用户信息 
	 * 
	 * @param array $arrCond 更新条件
	 * @param array $arrField 更新项目
	 * @static
	 * @access public 
	 * @return void
	 */
	public static function update($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::table)->set($arrField);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}	
		
	    $ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('update affected num 0, field: %s, cond: %s', $arrField, $arrCond);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
