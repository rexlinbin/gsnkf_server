<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarDao.class.php 234217 2016-03-22 13:30:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarDao.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-22 13:30:39 +0000 (Tue, 22 Mar 2016) $
 * @version $Revision: 234217 $
 * @brief 
 *
 *db请求，所有的表的都合在一个文件了，分开的话文件太多了
 **/

class CountryWarTeamDao
{
	static $table = 't_countrywar_cross_team';

	static function getTeamInfoByServerId( $serverId, $checkTime )
	{
		$data = new CData();
		$data->useDb(CountryWarUtil::getCrossDbName());
		$ret = $data->select( CountryWarTeamField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarTeamField::SERVER_ID,'=',$serverId ) )
		->where( array( CountryWarTeamField::TEAM_ID,'>',0 ) )
		->where( array( CountryWarTeamField::UPDATE_TIME,'>',$checkTime ) )
		->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		else 
		{
			return $ret[0];
		}
	}
	
	static function getAllServerIdInTeam( $teamId, $checkTime )
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarTeamField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarTeamField::TEAM_ID,'=',$teamId ) )
		->where( array(  CountryWarTeamField::UPDATE_TIME,'>',$checkTime) )
		->query();
		
		if( empty( $ret ) )
		{
			Logger::warning('empty server for team:%s', $teamId);
			return array();
		}
		else 
		{
			return Util::arrayExtract($ret, CountryWarTeamField::SERVER_ID);
		}
	}
	
	static function getAllTeamId( $checkTime )
	{
		$data = new CData();
		$data->useDb(CountryWarUtil::getCrossDbName());
		$ret = $data->select( CountryWarTeamField::$ALL_FIELDS )
		->from( self::$table )
		->where( array(  CountryWarTeamField::TEAM_ID,'>',0) )
		->where( array(  CountryWarTeamField::UPDATE_TIME,'>',$checkTime) )
		->query();
		
		if( empty( $ret ) )
		{
			Logger::warning('empty teamIdArr');
			return array();
		}
		else 
		{
			return Util::arrayExtract($ret, CountryWarTeamField::TEAM_ID);
		}
	}
	
	static function getAllTeamInfo( $time )
	{
		$arrField = array
		(
				CountryWarTeamField::TEAM_ID,
				CountryWarTeamField::SERVER_ID,
		);
		$arrCond = array
		(
				array(CountryWarTeamField::UPDATE_TIME, '>=', $time),
		);

		$arrRet = array();
		$maxFetch = 400;
		$offset = 0;
		for ($i = 0; $i < 1024; ++$i)
		{
			$data = new CData();
			$data->useDb(CountryWarUtil::getCrossDbName());
			$data->select($arrField)->from(self::$table)->limit($offset, $maxFetch);
			foreach ($arrCond as $cond)
			{
				$data->where($cond);
			}
			$ret = $data->query();
			$arrRet = array_merge($arrRet, $ret);
			if (count($ret) < $maxFetch)
			{
				break;
			}
			$offset += $maxFetch;
		}
		
		return $arrRet;
		
	}
	static function insertOrUpdateTeamInfo($arrField)
	{
		$data = new CData();
		$data->useDb(CountryWarUtil::getCrossDbName());
		$data->insertOrUpdate(self::$table)->values($arrField);
		$ret = $data->query();
	}
}

class CountryWarInnerUserDao
{
	static $table = 't_countrywar_inner_user';
	
	static function getInfoByServerIdPid( $serverId, $pid )
	{
		$data = new CData();
		$ret = $data->select( CountryWarInnerUserField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarInnerUserField::SERVER_ID,'=',$serverId ) )
		->where( array( CountryWarInnerUserField::PID,'=',$pid ) )
		->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		else 
		{
			return $ret[0];
		}
	}
	static function insertInfo($initArr)
	{
		$data = new CData();
		$ret = $data->insertInto( self::$table )
		->values( $initArr )
		->query();
	}
	
	static function update( $serverId, $pid, $updateFields )
	{
		$wheres = array(
				array( CountryWarCrossUserField::SERVER_ID,'=', $serverId ),
				array( CountryWarCrossUserField::PID,'=',$pid ),
		);
		$data = new CData();
		$data->update( self::$table )
		->set( $updateFields );
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}		
		$ret = $data->query();
		
	}
	
}

class CountryWarCrossUserDao
{
	static $table = 't_countrywar_cross_user';

	static function getInfoByServerIdPid( $serverId, $pid )
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarCrossUserField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarCrossUserField::SERVER_ID,'=',$serverId ) )
		->where( array( CountryWarCrossUserField::PID,'=',$pid ) )
		->query();

		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
	}
	static function insertInfo($initArr)
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->insertInto( self::$table )
		->values( $initArr )
		->uniqueKey ( CountryWarCrossUserField::UUID)
		->query();
	}
	static function update( $serverId, $pid, $updateFields )
	{
		$wheres = array(
				array( CountryWarCrossUserField::SERVER_ID,'=', $serverId ),
				array( CountryWarCrossUserField::PID,'=',$pid ),
		);
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$caution = false;
		if( isset( $updateFields[CountryWarCrossUserField::FANS_NUM] ) )
		{
			if( $updateFields[CountryWarCrossUserField::FANS_NUM] instanceof DecOperator)
			{
				$wheres[] = array( CountryWarCrossUserField::FANS_NUM,'>=',$updateFields[CountryWarCrossUserField::FANS_NUM]->getValue() );
				$caution = true;
			}
		}
		$data->update( self::$table )
		->set( $updateFields );
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}		
		$ret = $data->query();
		if($caution && $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			Logger::fatal('dec failed, serverId:%s,pid:%s,decnum:%s', $serverId,$pid,$updateFields[CountryWarCrossUserField::FANS_NUM]->getValue());
		}
		
	}

	static function getServerIdPidByUuid( $uuid  )
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( array( CountryWarCrossUserField::SERVER_ID, CountryWarCrossUserField::PID ) )
		->from( self::$table )
		->where( array( CountryWarCrossUserField::UUID,'=',$uuid ) )
		->query();
	
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
	}
	
	static function getUnrangeUserInServerArr($serverIdArr, $checkTime, $countryId)
	{
		$arrRet = array();
		
		$offset = 0;
		$limit = CData::MAX_FETCH_SIZE;
		while (TRUE)
		{
			$arrPartServerId = array_slice($serverIdArr, $offset, $limit);
			if (empty($arrPartServerId)) 
			{
				break;
			}
			
			$ret = self::_getUnrangeUserInServerArr($arrPartServerId, $checkTime, $countryId);
			$arrRet = array_merge($arrRet, $ret);
			if ($arrPartServerId < CData::MAX_FETCH_SIZE) 
			{
				break;
			}
			
			$offset += CData::MAX_FETCH_SIZE;
		} 
		
		return $arrRet;
	}
	
	static function _getUnrangeUserInServerArr($serverIdArr, $checkTime, $countryId)
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarCrossUserField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarCrossUserField::SIGN_TIME ,'>=', $checkTime ) )
		->where( array( CountryWarCrossUserField::SERVER_ID ,'IN',$serverIdArr ) )
		->where( array( CountryWarCrossUserField::TEAM_ROOM_ID ,'=', 0 ) )
		->where( array( CountryWarCrossUserField::COUNTRY_ID ,'=', $countryId ) )
		->orderBy( CountryWarCrossUserField::SERVER_ID, true )
		->orderBy( CountryWarCrossUserField::PID, true )
		->limit(0, 500)
		->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret;
		}
	}
	
	
	static function getTopNInfo( $battleId, $type )
	{
		$N = 500;
		$teamRoomId = CountryWarUtil::getTeamRoomIdByBattleId($battleId);
		$countryId = CountryWarUtil::getCountryIdByBattleId($battleId);
		$teamId = CountryWarUtil::getTeamIdByTeamRoomId($teamRoomId);
		$checkTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SINGUP);
		$offset = 0;
		if( CountryWarRankType::AUDITION== $type )
		{
			if( empty( $countryId ) || empty( $teamRoomId ) )
			{
				throw new InterException( 'invalid battleId:%s',$battleId );
			}
			
			$wheres = array(
					array( CountryWarCrossUserField::TEAM_ROOM_ID,'=',$teamRoomId ),
					array( CountryWarCrossUserField::COUNTRY_ID,'=',$countryId ),
					array( CountryWarCrossUserField::SIGN_TIME,'>=',$checkTime ),
					//array( CountryWarCrossUserField::AUDITION_POINT,'>', 0 ),//唐雷的，没有积分没有排名，没有奖励，不能被助威
			);
		}
		elseif ( CountryWarRankType::FINALTION == $type || CountryWarRankType::SUPPORT == $type )
		{
			if( empty( $teamId ) )
			{
				throw new InterException( 'invalid battleId:%s', $battleId );
			}
			
			$teamRoomIdRange = CountryWarUtil::getTeamRoomRange($teamId);
			$wheres = array(
					array( CountryWarCrossUserField::TEAM_ROOM_ID,'BETWEEN',$teamRoomIdRange ),
					array( CountryWarCrossUserField::FINAL_QUALIFY,'>',0 ),
					array( CountryWarCrossUserField::SIGN_TIME,'>=',$checkTime ),
					//array( CountryWarCrossUserField::FINAL_POINT,'>', 0 ),
			);
		}
		else
		{
			throw new InterException( 'invalid stage:%s', $type );
		}
		
		$list = array();
		while ( true )
		{
			$data = new CData();
			$data->useDb( CountryWarUtil::getCrossDbName() );
			$data->select( CountryWarCrossUserField::$POINT_LIST_FIELDS )
			->from( self::$table );
			foreach ($wheres as $where)
			{
				$data->where($where);
			}
			$ret = $data->limit($offset, $N)
			->query();
			$num = count( $ret );
			if( $num <= 0 )
			{
				break;
			}
			$list = array_merge( $list, $ret );
			if( $num < $N)
			{
				break;
			}
			else
			{
				$offset += $num;
				break;
			}
		}
		
		return $list;
	}
	
	static function markFinalMembers($battleId, $uuidArr)
	{
		if( count( $uuidArr ) > DataDef::MAX_FETCH )
		{
			Logger::fatal( 'too much qualify user, %s', $uuidArr );
		}
		
		$teamRoomId = CountryWarUtil::getTeamRoomIdByBattleId($battleId);
		
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->update(self::$table)
		->set( array( CountryWarCrossUserField::FINAL_QUALIFY  => CountryWarConfig::qualifyNumPerAuditionBattle()))
		->where( array( CountryWarCrossUserField::UUID ,'IN', $uuidArr) )
		->where(array( CountryWarCrossUserField::TEAM_ROOM_ID,'=',$teamRoomId ))
		->query();
	}
	
	static function getHighestInfoByWarIdTeamId( $roundStartTime, $teamId )
	{
		$teamRoomIdRange = CountryWarUtil::getTeamRoomRange($teamId);
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarCrossUserField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarCrossUserField::TEAM_ROOM_ID,'BETWEEN',$teamRoomIdRange ) )
		->where( array( CountryWarCrossUserField::SIGN_TIME,'>=',$roundStartTime ) )
		->orderBy( CountryWarCrossUserField::FINAL_POINT , false)
		->limit(0, 1)
		->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
		
	}
	
	static function getHighestInfoByWarIdTeamRoomId( $roundStartTime, $teamRoomId)
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarCrossUserField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarCrossUserField::TEAM_ROOM_ID,'=',$teamRoomId ) )
		->where( array( CountryWarCrossUserField::SIGN_TIME,'>=',$roundStartTime ) )
		->orderBy( CountryWarCrossUserField::FINAL_POINT , false)
		->limit(0, 1)
		->query();
	
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
	
	}
	
}


class CountryWarTeamContentDao
{
	static $table = 't_countrywar_cross_team_content';
	
	static function getInfoByTeamId($warId, $teamId)
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->select( CountryWarCrossTeamContentField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarCrossTeamContentField::WAR_ID,'=',$warId ) )
		->where( array( CountryWarCrossTeamContentField::TEAM_ID,'=',$teamId ) )
		->query();
	
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
	}
	static function insertInfo($initArr)
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$ret = $data->insertInto( self::$table )
		->values( $initArr )
		->query();
	}
	
	static function update( $warId, $teamId, $updateFields )
	{
		$data = new CData();
		$data->useDb( CountryWarUtil::getCrossDbName() );
		$data->update( self::$table )
		->set( $updateFields )
		->where( array( CountryWarCrossTeamContentField::WAR_ID,'=', $warId ) )
		->where( array( CountryWarCrossTeamContentField::TEAM_ID,'=',$teamId ) );
		$caution = false;
		if( isset( $updateFields[CountryWarCrossTeamContentField::RESOURCE_A] ) && $updateFields[CountryWarCrossTeamContentField::RESOURCE_A] instanceof DecOperator )
		{
			$data->where( CountryWarCrossTeamContentField::RESOURCE_A,'>=',$updateFields[CountryWarCrossTeamContentField::RESOURCE_A]->getValue() );
			$caution = true;
		}
		if( isset( $updateFields[CountryWarCrossTeamContentField::RESOURCE_B] ) && $updateFields[CountryWarCrossTeamContentField::RESOURCE_B] instanceof DecOperator )
		{
			$data->where( CountryWarCrossTeamContentField::RESOURCE_B,'>=',$updateFields[CountryWarCrossTeamContentField::RESOURCE_B]->getValue() );
			$caution = true;
		}
		$ret = $data->query();
		if( $caution && $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			Logger::fatal('update failed,updateFields:%s', $updateFields);
		}
	}
	
}

class CountryWarWorshipDao
{
	static $table = 't_countrywar_inner_worship';
	
	static function getInfoByWarId($warId)
	{
		$data = new CData();
		$ret = $data->select( CountryWarInnerWorshipField::$ALL_FIELDS )
		->from( self::$table )
		->where( array( CountryWarInnerWorshipField::WAR_ID,'=',$warId ) )
		->query();
	
		if( empty( $ret ) )
		{
			return array();
		}
		else
		{
			return $ret[0];
		}
	}
	
	static function insertInfo($initArr)
	{
		$data = new CData();
		$ret = $data->insertIgnore( self::$table )
		->values( $initArr )
		->query();
		
		return $ret;
	}
	
	static function update( $warId, $teamRoomId, $updateFields )
	{
		$data = new CData();
		$ret = $data->update( self::$table )
		->set( $updateFields )
		->where( array( CountryWarInnerWorshipField::WAR_ID,'=', $warId ) )
		->where( array( CountryWarInnerWorshipField::TEAM_ROOM_ID,'=',$teamRoomId ) )
		->query();
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */