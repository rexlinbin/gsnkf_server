<?php
/***************************************************************************
 * 
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: AchieveDAO.class.php 111507 2014-05-27 10:59:33Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/AchieveDAO.class.php $
 * @author $Author: wuqilin $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-27 10:59:33 +0000 (Tue, 27 May 2014) $
 * @version $Revision: 111507 $
 * @brief 
 *  
 **/
 
class AchieveDAO
{
	const TABLE = 't_achieve';
	const FILED_UID = 'uid';
	const FILED_AID = 'aid';
	const FILED_STATUS = 'status';
	const FILED_FINISH_NUM = 'finish_num';
	const FILED_DATA = 'va_data';
	
	static $ALL_FILEDS = array(self::FILED_UID, self::FILED_AID,
		 self::FILED_STATUS, self::FILED_FINISH_NUM, self::FILED_DATA);
	
	
	public static function getAll($uid) 
	{
		$db = new CData();
		$ret = $db->select(self::$ALL_FILEDS)->from(self::TABLE)
			->where(self::FILED_UID, "=", $uid)->query();
		return empty($ret) ? array() : $ret; 
	}
	
	public static function getType($uid, $type)
	{
		$db = new CData();
		$ret = AchieveObj::getTypeConf($type);
		$aids = array_keys( $ret->toArray() );
		if( empty($aids) )
		{
			return array();
		}
		$ret = $db->select(self::$ALL_FILEDS)
			->from(self::TABLE)
			->where(self::FILED_UID, "=", $uid)
			->where(self::FILED_AID, "IN", $aids)
			->query();
		return empty($ret) ? array() : $ret; 
	}
	
	public static function put($uid, $data)
	{
		if(empty($data)) return;
		$bdb = new BatchData();
		$db = $bdb->newData();
		foreach($data as $_ => $info)
		{
			Logger::debug("AchieveDao.put %s", $info);
			$db->insertOrUpdate(self::TABLE)
				->values($info)->query();
		}
		$bdb->query();
	}
}
 
