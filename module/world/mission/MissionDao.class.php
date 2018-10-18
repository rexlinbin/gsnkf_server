<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionDao.class.php 199500 2015-09-18 02:21:27Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-18 02:21:27 +0000 (Fri, 18 Sep 2015) $
 * @version $Revision: 199500 $
 * @brief 
 *  
 **/
class MissionDao
{
	private static $tableInnerConfig = 't_mission_inner_config';
		
	static function getLastActConf($sess)
	{
		$data = new CData();
		$ret = $data->select( MissionDBField::$innerConfField )
		->from(self::$tableInnerConfig)
		->where( array( MissionDBField::SESS, '=', $sess ) )
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	static function updateInnerConfig( $values )
	{
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$tableInnerConfig)
		->values( $values )
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'insertOrUpdate failed, data: %s', $values );
		}
	}
	
	private static $tableInnerUser = 't_mission_inner_user';
	
	static function getInnerUserInfo( $uid )
	{
		$data = new CData();
		$ret = $data->select( MissionDBField::$innerUserField )
		->from(self::$tableInnerUser)
		->where( array( MissionDBField::UID, '=', $uid ) )
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	static function insertInnerUserInfo( $values )
	{
		$data = new CData();
		$ret = $data->insertInto(self::$tableInnerUser)
		->values( $values )
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'insert failed, data: %s', $values );
		}
	}
	
	static function updatInnerUserInfo( $uid, $updateArr )
	{
		$data = new CData();
		$ret = $data->update(self::$tableInnerUser)
		->set( $updateArr )
		->where(array('uid','=',$uid)) 
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'update failed, data: %s', $updateArr );
		}
		
	}
	
	
	private static $tableCrossUser = 't_mission_cross_user';
	
	static function getCrossUserInfo( $serverId, $pid, $teamId )
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->select( MissionDBField::$crossUserField )
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::CROSS_PID, '=', $pid ) )
		->where( array( MissionDBField::CROSS_SERVERID, '=', $serverId ) )
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	static function insertCrossUserInfo( $teamId, $values )
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->insertInto(MissionUtil::getCrossUserTableName($teamId))
		->values( $values )
		->query();
	
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'insert failed, data: %s', $values );
		}
	}
	
	static function updatCrossUserInfo( $serverId, $pid, $teamId, $updateArr )
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->update(MissionUtil::getCrossUserTableName($teamId))
		->set( $updateArr )
		->where( array( MissionDBField::CROSS_PID, '=', $pid ) )
		->where( array( MissionDBField::CROSS_SERVERID, '=', $serverId ) )
		->query();
	
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'update failed, data: %s', $updateArr );
		}
	
	}
	
	static function getRankList($teamId,$time, $topNum)
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->select( MissionDBField::$crossUserField )
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::UPDATE_TIME, '>=', $time ) )
		->where( array( MissionDBField::CROSS_FAME, '>', 0 ) )
		->orderBy( MissionDBField::CROSS_FAME , false)
		->orderBy( MissionDBField::UPDATE_TIME , true)
		->orderBy(MissionDBField::CROSS_PID, true)
		->orderBy( MissionDBField::CROSS_SERVERID , true)
		->limit(0, $topNum)
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	static function getParticularRankInfo($teamId,$time, $rank)
	{
		$offset = $rank-1;
		$limit = 1;
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->select( MissionDBField::$crossUserField )
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::UPDATE_TIME, '>=', $time ) )
		->orderBy( MissionDBField::CROSS_FAME , false)
		->orderBy( MissionDBField::UPDATE_TIME , true)
		->orderBy(MissionDBField::CROSS_PID, true)
		->orderBy( MissionDBField::CROSS_SERVERID , true)
		->limit($offset, $limit)
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
		
	}
	
	static function getMyRank($teamId,$serverId,$pid,$myfame,$time,$mytime)
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->selectCount()
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::UPDATE_TIME, '>=', $time ) )
		->where( array( MissionDBField::CROSS_FAME, '>',$myfame  ) )
		->query();
		$count1 = $ret[0][DataDef::COUNT];
		$count2 = self::getMyRankSameFame($teamId,$myfame,$time, $mytime);
		$count3 = self::getMyRankSameUpdateTime($teamId, $serverId, $pid, $myfame, $mytime);
		return $count1+$count2+$count3;
	}
	
	static function getMyRankSameFame($teamId,$myfame,$time,$mytime)
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->selectCount()
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::UPDATE_TIME, 'BETWEEN', array( $time, $mytime-1 ) ) )
		->where( array( MissionDBField::CROSS_FAME, '=', $myfame  ) )
		->query();

		return $ret[0][DataDef::COUNT];
	}
	
	static function getMyRankSameUpdateTime($teamId,$serverId,$pid,$myfame,$mytime)
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->selectCount()
		->from(MissionUtil::getCrossUserTableName($teamId))
		->where( array( MissionDBField::UPDATE_TIME, '=', $mytime ) )
		->where( array( MissionDBField::CROSS_FAME, '=', $myfame  ) )
		->where( array( MissionDBField::CROSS_PID, '<', $pid  ) )
		->query();
	
		return $ret[0][DataDef::COUNT];
	}
	
	private static $tableTeam = 't_mission_cross_team';
	
	static function getTeamInfoByServerId( $serverId )
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		$ret = $data->select( MissionDBField::$teamInfoField )
		->from(self::$tableTeam)
		->where( array( MissionDBField::SERVER_ID, '=', $serverId ) )
		->query();
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret[0];
	}
	
	static function updateTeamInfo( $values )
	{
		$data = new CData();
		$data->useDb( MissionUtil::getCrossDbName() );
		
		$ret = $data->insertOrUpdate(self::$tableTeam)
		->values($values)
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			throw new InterException( 'insertOrUpdate failed:%s', $values );
		}
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */