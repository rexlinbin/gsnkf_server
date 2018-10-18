<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralElvesDao.class.php 245910 2016-06-08 08:09:35Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/mineralelves/MineralElvesDao.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-06-08 08:09:35 +0000 (Wed, 08 Jun 2016) $
 * @version $Revision: 245910 $
 * @brief 
 *  
 **/
class MineralElvesDao
{
	const tblname='t_mineral_elves';
	
	public static function getMineralElves($time)
	{
		$selectArr=array('domain_id','uid','start_time','end_time');
		$data=new CData();
		$arrRet=$data->select($selectArr)->from(self::tblname)
						->where(array('start_time','<=',$time))
						->where(array('end_time','>=',$time))
						->query();
		return $arrRet;
	}

	public static function getMineralElvesByDomainId($domain_id,$time)
	{
		$selectArr=array('domain_id','uid','start_time','end_time');
		$data=new CData();
		$arrRet=$data->select($selectArr)->from(self::tblname)
						->where(array('domain_id','=',$domain_id))
						->where(array('start_time','<=',$time))
						->where(array('end_time','>',$time))//边界情况
						->query();
		if (empty($arrRet))
		{
			return array();
		}
		return $arrRet[0];
	}

	public static function getMineralElvesNumByUid($uid,$time)
	{
		$data=new CData();
		$arrRet=$data->selectCount()->from(self::tblname)
						->where('uid','=',$uid)
						->where(array('start_time','<=',$time))
						->where(array('end_time','>=',$time))
						->query();
		return $arrRet[0]['count'];
	}
	
	public static function addMineralElves($starttime,$endtime,$domain_id)
	{
		$value=array(
				'domain_id'=>$domain_id,
				'uid'=>0,
				'start_time'=>$starttime,
				'end_time'=>$endtime,
		);
		$data=new CData();
		$arrRet=$data->insertOrUpdate(self::tblname)->values($value)->query();

	}
	
	public static function addMineralElvesBatch($starttime,$endtime,$arrDomainId)
	{
		$arrField = array
		(
				'uid' => 0,
				'start_time' => $starttime,
				'end_time' => $endtime,
		);
		$data=new CData();
		$data->update(self::tblname)->set($arrField)->where(array('domain_id', 'IN',$arrDomainId))->query();
	}

	public static function getMineralElvesByUid($uid,$time)
	{
		$selectArr=array('domain_id','uid','start_time','end_time');
		$data=new CData();
		$arrRet=$data->select($selectArr)->from(self::tblname)
		->where(array('uid','=',$uid))
		->where(array('start_time','<=',$time))
		->where(array('end_time','>',$time))
		->query();
		if (empty($arrRet))
		{
			return array();
		}
		return $arrRet[0];
	}
	public static function updateMineralElves($domain_id,$uid)
	{
		$setArr=array('uid'=>$uid);
		$data=new CData();
		$arrRet=$data->update(self::tblname)
						->set(array('uid'=>$uid))
						->where(array('domain_id','=',$domain_id))
						->query();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */