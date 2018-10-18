<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardDao.class.php 184101 2015-07-14 06:35:52Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/RewardDao.class.php $
 * @author $Author: GuohaoZheng $(wuqilin@babeltime.com)
 * @date $Date: 2015-07-14 06:35:52 +0000 (Tue, 14 Jul 2015) $
 * @version $Revision: 184101 $
 * @brief 
 *  
 **/


class RewardDao
{
	public static function insert($arrField, $db = '')
	{
		$data = new CData ();
		if( !empty($db) )
		{
			Logger::debug('insert reward to db:%s', $db);
			$data->useDb($db);
		}
		$arrRet = $data->insertInto(RewardDef::SQL_TABLE)->values($arrField)
				->uniqueKey(RewardDef::SQL_RID)->query ();
		return $arrRet [RewardDef::SQL_RID];
	}

	public static function getByUid($uid, $arrField, $offset = -1, $limit = -1, $liftTime = RewardCfg::REWARD_LIFE_TIME)
	{
		if ($limit > CData::MAX_FETCH_SIZE)
		{
			throw new InterException('limit:%d exceed max fetch mail size', $limit);			
		}
		
		$sendTime = Util::getTime() - $liftTime;
		
		$data = new CData ();
		$data->select ( $arrField )->from ( RewardDef::SQL_TABLE )
			->where( array(RewardDef::SQL_UID , '=', $uid) )
			->where( array(RewardDef::SQL_RECV_TIME , '=', 0) )
			->where( array(RewardDef::SQL_DELETE_TIME , '=', 0) )
			->where( array(RewardDef::SQL_SEND_TIME , '>', $sendTime) );
		
		if($offset >= 0 && $limit > 0)
		{
			$data->orderBy(RewardDef::SQL_RID, true)
			->limit($offset, $limit);
		}
		
		$arrRet = $data->query();

		return $arrRet;
	}
	
	public static function getByUidRid($uid, $rid, $arrField)
	{
		$data = new CData ();
		$arrRet = $data->select ( $arrField )->from ( RewardDef::SQL_TABLE )
			->where( array(RewardDef::SQL_UID , '=', $uid) )
			->where( array(RewardDef::SQL_RID , '=', $rid) )
			->query();
		if(empty($arrRet))
		{
			return array();
		}
		return $arrRet[0];
	}
	
	public static function getByRidArr( $uid, $ridArr, $arrField )
	{
		$data = new CData ();
		$arrRet = $data->select ( $arrField )->from ( RewardDef::SQL_TABLE )
		->where( array(RewardDef::SQL_UID , '=', $uid) )
		->where( array(RewardDef::SQL_RID , 'IN', $ridArr ) )
		->query();
		if(empty($arrRet))
		{
			return array();
		}
		return $arrRet;
	}
	
	
	public static function updateByArrId( $uid , $arrField, $ridArr )
	{
		try
		{
			$data = new CData();
			$data->update(RewardDef::SQL_TABLE)
			->set($arrField)
			->where(array(RewardDef::SQL_UID, '=', $uid))
			->where( array( RewardDef::SQL_RID, 'IN', $ridArr ) )
			->query();
		}
		catch (Exception $e)
		{
			Logger::FATAL('receiveByRidArr failed!  err:%s ', $e->getMessage ());
			return false;
		}
		return true;
	}
	
	public static function getVipBonusToday( $uid, $timedayBegin )
	{
		$data = new CData();
		$ret = $data->select( array(RewardDef::SQL_RID) )->from( RewardDef::SQL_TABLE )
		->where( array( RewardDef::SQL_UID,'=',$uid ) )
		->where( array( RewardDef::SQL_SEND_TIME,'>=',$timedayBegin ) )
		->where( array( RewardDef::SQL_SOURCE,'=',RewardSource::VIP_DAILY_BONUS ) )
		->query();
		
		if ( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	
	public static function getRewardByUidTime($uid, $source, $startTime, $arrField)
	{
		$data = new CData (); 
		$arrRet = $data->select ( $arrField )->from ( RewardDef::SQL_TABLE )
					->where( RewardDef::SQL_UID , '=', $uid)
					->where( RewardDef::SQL_SEND_TIME, '>=', $startTime)
					->where( RewardDef::SQL_SOURCE , '=', $source )
					->query();
		return $arrRet;
	}
	
	public static function getLatestArenaRank($uid)
	{
		$data = new CData();
		$ret = $data->select( array(RewardDef::SQL_SEND_TIME,RewardDef::SQL_VA_REWARD) )
					->from( RewardDef::SQL_TABLE )
					->where( array( RewardDef::SQL_UID,'=',$uid ) )
					->where( array( RewardDef::SQL_SOURCE,'=',RewardSource::ARENA_RANK) )
					->orderBy(RewardDef::SQL_SEND_TIME, false)
					->limit(0, 1)
					->query();
		if ( empty( $ret ) )
		{
			return array(0, 0);
		}
	
		$sendTime = $ret[0][RewardDef::SQL_SEND_TIME];
		$rank = $ret[0][RewardDef::SQL_VA_REWARD][RewardDef::EXT_DATA]['rank'];
		return array($sendTime, $rank);
	}
	
	public static function getReceivedListByUidTime($uid, $arrField, $offset, $limit, $startTime=1, $endTime=PHP_INT_MAX)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from(RewardDef::SQL_TABLE)
					->where(array(RewardDef::SQL_UID, '=', $uid))
					->where(array(RewardDef::SQL_RECV_TIME, '>=', $startTime))
					->orderBy(RewardDef::SQL_RECV_TIME, FALSE)
					->orderBy(RewardDef::SQL_RID, FALSE)
					->limit($offset, $limit)
					->query();
		
		return empty($ret)?array():$ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */