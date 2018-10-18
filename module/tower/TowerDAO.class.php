<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TowerDAO.class.php 131489 2014-09-11 06:19:32Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/TowerDAO.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-09-11 06:19:32 +0000 (Thu, 11 Sep 2014) $
 * @version $Revision: 131489 $
 * @brief 
 *  
 **/
class TowerDAO
{
	private static $tbltower = 't_tower';
	
	public static function getTowerInfo($uid,$arrFiled)
	{
		$ret = array();
		$data = new CData();
		$ret = $data->select($arrFiled)
					->from(self::$tbltower)
					->where(array('uid','=',$uid))
					->query();
		
		if(empty($ret[0]))
		{
			return array();
		}
		return $ret[0];
	}
	public static function save($uid,$tower)
	{		
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$tbltower)
					 ->values($tower)
					 ->where('uid','=',$uid)
					 ->query();
		unset($tower['status']);
		return $ret;
	}
	
	public static function getRank($num)
	{
	    $data = new CData();
	    $ret = $data->select(array(TOWERTBL_FIELD::MAX_LEVEL,TOWERTBL_FIELD::UID))
	                ->from(self::$tbltower)
	                ->where(array(TOWERTBL_FIELD::MAX_LEVEL,'>',0))
	                ->orderBy(TOWERTBL_FIELD::MAX_LEVEL, FALSE)
	                ->orderBy(TOWERTBL_FIELD::MAX_LEVEL_TIME, TRUE)
	                ->orderBy(TOWERTBL_FIELD::UID, TRUE)
	                ->limit(0, $num)
	                ->query();
	    return $ret;
	}
	
	public static function getRankOfUser($uid,$maxLv,$maxLvTime)
	{
	    $data = new CData();
	    $ret1 = $data->selectCount()
	                ->from(self::$tbltower)
	                ->where(array(TOWERTBL_FIELD::MAX_LEVEL,'>',$maxLv))
	                ->query();
	    $ret2 = $data->selectCount()
            	    ->from(self::$tbltower)
            	    ->where(array(TOWERTBL_FIELD::MAX_LEVEL,'=',$maxLv))
            	    ->where(array(TOWERTBL_FIELD::MAX_LEVEL_TIME,"<",$maxLvTime))
            	    ->query();
	    $ret3 = $data->selectCount()
	                 ->from(self::$tbltower)
	                 ->where(array(TOWERTBL_FIELD::MAX_LEVEL,'=',$maxLv))
            	     ->where(array(TOWERTBL_FIELD::MAX_LEVEL_TIME,"=",$maxLvTime))
            	     ->where(array(TOWERTBL_FIELD::UID,'<',$uid))
            	     ->query();
	    return $ret1[0]['count']+$ret2[0]['count']+$ret3[0]['count']+1;
	}
	
	public static function getUserRank($uid)
	{
	    
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */