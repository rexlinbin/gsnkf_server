<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroDao.class.php 155785 2015-01-28 12:50:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroDao.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-01-28 12:50:57 +0000 (Wed, 28 Jan 2015) $
 * @version $Revision: 155785 $
 * @brief 
 *  
 **/



class HeroDao
{
	const TBL_HERO = 't_hero';

	private static $notDel = array('delete_time', '=', 0);

	public static function getByHid($hid, $arrField)
	{
		$where = array('hid', '=', $hid);
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::TBL_HERO)
				->where($where)->where(self::$notDel)->query();
		if (!empty($arrRet))
		{
			return $arrRet[0];
		}
		return null	;
	}
	
	public static function getArrHeroeByUid ($uid, $arrField)
	{
		$where = array("uid", "=", $uid);
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::TBL_HERO)
				->where($where)->where(self::$notDel)->query();
		if (!empty($arrRet))
		{
			$arrRet = Util::arrayIndex($arrRet, 'hid');
		}
		return $arrRet;
	}
	
	public static function getHeroNumByUid($uid)
	{
	    $where = array("uid", "=", $uid);
	    $data = new CData();
	    $arrRet = $data->selectCount()->from(self::TBL_HERO)
	                ->where($where)->where(self::$notDel)->query();
	    return $arrRet[0]['count'];
	}
	
	
	
	public static function getByArrHid($arrHid, $arrField, $noCache=false, $db = '')
	{
		if (empty($arrHid))
		{
			return array();
		}
		
		if (!in_array('hid', $arrField))
		{
			$arrField[] = 'hid';
		}
		
		$data = new CData();
		if (!empty($db)) 
		{
			$data->useDb($db);
		}
		$arrRet = array();
		$arrArrHid = array_chunk($arrHid, CData::MAX_FETCH_SIZE);
		foreach ($arrArrHid as $arrHid)
		{
			$data->select($arrField)->from(self::TBL_HERO)
					->where('hid', 'in', $arrHid)->where(self::$notDel);
			if ($noCache)
			{
				$data->noCache();	
			}	
				
			$ret = $data->query();
			$arrRet = array_merge($arrRet, $ret);
		}
		
		if (!empty($arrRet))
		{
			return Util::arrayIndex($arrRet, 'hid');
		}
		return array();
	}
	
	public static function update($hid, $arrField)
	{
		$where = array("hid", "=", $hid);
		$data = new CData();
		$arrRet = $data->update(self::TBL_HERO)->set($arrField)->where($where)->query();
		return $arrRet;
	}
	
	public static function insertNewHero($arrField)
	{
		$data = new CData();
		$arrRet = $data->insertInto(self::TBL_HERO)->values($arrField)->query();
		return $arrRet;
	}
	
	public static function batchUpdate($arrHidField)
	{
		if (empty($arrHidField))
		{
			return;
		}
	
		$batch = new BatchData();
		foreach ($arrHidField as $hid=>$field)
		{
			$data = $batch->newData();
			$data->update(self::TBL_HERO)->set($field)
					->where('hid', '=', $hid)->query();
		}
	
		$batch->query();
	}
	
    /**
     * 从t_hero表中获取htid in $htidarray中的武将，获取个数是$num
     * @param int $uid
     * @param array $htidArray
     * @param int $num
     * @return array
     * [
     *     hid:int
     *     htid:int
     * ]
     */
	public static function getPartHeroesByHtids($uid,$htidArray,$num=PHP_INT_MAX)
	{
	    $offset = 0;
	    $data    =    new CData();
	    $arrHero = array();
	    do
	    {
	        $limit = min(100,$num);
	        $tmpHeroes   =  $data->select(array('hid','htid'))
                    	        ->from('t_hero')
                    	        ->where(array('uid','=',$uid))
                    	        ->where(array('htid','IN',$htidArray))
                    	        ->limit($offset, $limit)
                    	        ->query();
	        $num = $num - $limit;
	        $offset = $offset + $limit;
	        $arrHero    =    array_merge($arrHero,$tmpHeroes);
	    }
	    while(count($tmpHeroes) > 0 && ($num > 0));
	    return $arrHero;
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */