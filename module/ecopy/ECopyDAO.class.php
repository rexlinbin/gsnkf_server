<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ECopyDAO.class.php 56356 2013-07-24 07:22:38Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/ECopyDAO.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-07-24 07:22:38 +0000 (Wed, 24 Jul 2013) $
 * @version $Revision: 56356 $
 * @brief 
 *  
 **/
class ECopyDAO
{
	private static $tblelitecopy = 't_elite_copy';

	private static $status = array('status','!=',DataDef::DELETED);

	public static function getEliteCopyInfo($uid)
	{
		$data = new CData();
		$ret = $data-> select(CopyConf::$ELITE_COPY_TBL_ALL_FIELD)
					-> from(self::$tblelitecopy)
					-> where(array('uid','=',$uid))
					-> where(self::$status)
					-> query();
		if(empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	public static function save($uid,$ecopyInfo)
	{
		if(empty($ecopyInfo))
		{
			Logger::debug('the parameter copyinfo is empty');
			return;
		}
		$ecopyInfo['status']    =    DataDef::NORMAL;
		//使用副本对象中的方法更新到数据库中
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$tblelitecopy)
					->values($ecopyInfo)
					->query();
		return $ret;
	}	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */