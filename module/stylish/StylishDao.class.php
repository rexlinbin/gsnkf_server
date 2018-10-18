<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StylishDao.class.php 241036 2016-05-04 10:44:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/StylishDao.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-04 10:44:22 +0000 (Wed, 04 May 2016) $
 * @version $Revision: 241036 $
 * @brief 
 *  
 **/
class StylishDao
{
	public static function select($uid, $arrField = array())
	{
		if (empty($arrField)) 
		{
			$arrField = StylishDef::$TBL_STYLISH_FIELDS;
		}
		
		$data = new CData();

		$arrRet = $data->select($arrField)
					   ->from(StylishDef::TBL_STYLISH)
					   ->where(array(StylishDef::FIELD_UID, '=', $uid))
					   ->query();
		if (empty($arrRet))
		{
			return array();
		}

		return $arrRet[0];
	}

	public static function insert($arrFiled)
	{
		$data = new CData();
		$ret = $data->insertInto(StylishDef::TBL_STYLISH)
					->values($arrFiled)
					->query();
		if ($ret['affected_rows'] == 0)
		{
			Logger::warning('t_stylish insert failed!');
			return false;
		}
		return true;
	}

	public static function update($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->update(StylishDef::TBL_STYLISH)
					->set($arrField)
					->where(array(StylishDef::FIELD_UID, '=', $uid))
					->query();
		if ($ret['affected_rows'] == 0)
		{
			Logger::warning('t_stylish update failed!');
			return false;
		}
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */