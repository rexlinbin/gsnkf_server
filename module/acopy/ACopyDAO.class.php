<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ACopyDAO.class.php 75731 2013-11-20 02:51:10Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ACopyDAO.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-20 02:51:10 +0000 (Wed, 20 Nov 2013) $
 * @version $Revision: 75731 $
 * @brief 
 *  
 **/
class ACopyDAO
{
	private static $tbl_activity = 't_activity_copy';

	private static $status = array('status', '!=', DataDef::DELETED);

	public static function getActivityCopyList($uid)
	{
		$data = new CData();
		$ret = array();
		$ret = $data->select(CopyConf::$ACTIVITY_TBL_ALL_FIELD)
					->from(self::$tbl_activity)
					->where(array('uid','=',$uid))
					->where(self::$status)
					->query();
		if(!empty($ret))
		{
			$ret = Util::arrayIndex($ret, 'copy_id');
		}		
		return $ret;
	}

	public static function getActivityCopyInfo($uid,$copyId)
	{
		$data = new CData();
		$ret = array();
		$ret = $data->select(CopyConf::$ACTIVITY_TBL_ALL_FIELD)
					->from(self::$tbl_activity)
					->where(array('uid','=',$uid))
					->where(array('copy_id','=',$copyId))
					->where(self::$status)
					->query();
		if(empty($ret))
		{
			return array();
		}
		return $ret[0];
	}

	public static function saveActivityCopy($uid,$copyId,$copyInfo)
	{
		if(empty($copyInfo))
		{
			Logger::debug('the parameter copyObj is empty');
		}
		$data = new CData();
		$copyInfo['status'] = DataDef::NORMAL;
		$ret = $data->insertOrUpdate(self::$tbl_activity)
					->values($copyInfo)
					->query();		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */