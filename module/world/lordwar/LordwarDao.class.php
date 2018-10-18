<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarDao.class.php 131406 2014-09-10 12:15:04Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/LordwarDao.class.php $
 * @author $Author: wuqilin $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-09-10 12:15:04 +0000 (Wed, 10 Sep 2014) $
 * @version $Revision: 131406 $
 * @brief 
 *  
 **/
class LordwarInnerDao
{
	static $table = 't_lordwar_inner_user';
	static $tableTemple = 't_lordwar_temple';
	
	public static function getLordInfo($serverId,$pid)
	{
		$db = null;
		if( !LordwarUtil::isMyServer($serverId))
		{
			$serverMgr = ServerInfoManager::getInstance();
			$db = $serverMgr->getDbNameByServerId($serverId);
		}
		
		$selectFields = array(
				'pid',
				'uid',
				'server_id',
				'winner_losenum',
				'loser_losenum',
				'team_type',
				'support_pid',
				'support_serverid',
				'support_round',
				'worship_time',
				'update_fmt_time',
				'bless_receive_time',
				'register_time',
				'last_join_time',
				'va_lord',
				'va_lord_extra',//TODO对象release 重新拉取 问题，考虑把战斗数据单独拎出来
		);
		
		$data = new CData();
		if( !empty( $db ) )
		{
			$data->useDb($db);
		}
		
		$data->select( $selectFields )->from( self::$table )
		->where( array('server_id','=',$serverId  ) )
		->where(array('pid','=',$pid));
		
		$ret = $data->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		
		return $ret[0];
	}
	
	public static function insertLord( $initValues )
	{
		$data = new CData();
		$data->insertInto(self::$table)->values( $initValues );
		$ret = $data->query();
		
		if( $ret[DataDef::AFFECTED_ROWS]  == 0 )
		{
			throw new InterException( 'init insert failed, info: %s', $initValues );
		}
	}
	
	public static function update( $serverId, $pid, $updateVals, $db )
	{
		$data = new CData();
		if( !empty( $db ) )
		{
			$data->useDb($db);
		}
		$ret = $data->update(self::$table)->set($updateVals)
		->where(array( 'server_id','=', $serverId))
		->where(array('pid', '=', $pid)) 
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] == 0)
		{
			Logger::fatal('nothing change, a little weired...');
		}
		
	}
	
	public static function getSignUserForAudition($teamType, $registerTime, $offset)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$loseNumConf = $confMgr->getAuditionOutLoseNum(LordwarField::INNER);
		Logger::debug('$loseNumConf is : %s', $loseNumConf);
		
		$data = new CData();
		$wheres = array(
				array('register_time','>=',$registerTime),
				array('team_type','=',LordwarTeamType::NO),
		);
		if( $teamType == LordwarTeamType::WIN )
		{
			$selects = array('pid','server_id','uid','winner_losenum as loseNum');
			$wheres[] = array('winner_losenum', '<',$loseNumConf );
		}
		elseif ( $teamType == LordwarTeamType::LOSE )
		{
			$selects = array('pid','server_id','uid','loser_losenum as loseNum');
			$wheres[] = array('loser_losenum', '<',$loseNumConf );
		}
		else
		{
			return array();
		}
		
		Logger::debug('wheres are : %s', $wheres);
		
		$data->select( $selects )->from(self::$table);
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}
		
		$ret = $data->limit($offset, CData::MAX_FETCH_SIZE)->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	public static function getVaLord($serverId,$pid,$db)
	{
		$data = new CData();
		if( isset( $db ) )
		{
			$data->useDb($db);
		}		
		$ret = $data->select( array('va_lord') )->from( self::$table )
		->where(array( 'server_id','=',$serverId ))
		->where(array( 'pid','=',$pid ))
		->query();
	
		if ( empty( $ret ) ) 
		{
			return array();
		}
		
		return $ret[0];
	}
	
	public static function updateLordInfo( $serverId, $pid, $updateArr )
	{
		$data = new CData();
		$wheres = array(
				array('server_id','=',$serverId),
				array( 'pid', '=', $pid ),
		);
		$ret = $data -> update( self::$table )-> set( $updateArr )-> where()->query();
		if( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			throw new InterException( 'nothing chaned when want to update: %s', $updateArr );
		}
	}
	
	public static function getTempleInfo( $sess )
	{
		$data = new CData();
		$ret = $data->select( array('sess','va_temple') )->from(self::$tableTemple)
		->where( array( 'sess', '=', $sess ) )->orderBy('sess', false)->query();
		
		if ( empty($ret) ) 
		{
			return array();
		}
		return $ret[0];
	}
	
	
	public static function updateTempleInfo($sess, $allTempleInfo)
	{
		$data = new CData();
		$data->insertIgnore(self::$tableTemple)->values(array( 'sess' => $sess,'va_temple' => $allTempleInfo ))
		->query();
	}
	
	public static function getSupportList($round, $minJoinTime)
	{
		$data = new CData();
	
		$offset = 0;
		$ret = array();
		for ( $i = 0; $i < 65535; $i++ )
		{
			$result = $data->select(array('uid', 'pid', 'server_id', 'support_pid', 'support_serverid'))->from(self::$table)
				->where('last_join_time', '>=', $minJoinTime)
				->where('support_round', '=', $round)
				->orderBy('uid', true)->limit($offset, DataDef::MAX_FETCH)->query();
				
			$ret= array_merge($ret,$result);
			if ( count($result) < DataDef::MAX_FETCH )
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		$ret = Util::arrayIndex($ret, 'uid');
		return $ret;
	}

	public static function getSupportListNum($round, $minJoinTime, $db = '')
	{
		$data = new CData();
		
		if( !empty($db) )
		{
			$data->useDb($db);
		}
		
		$result = $data->select(array("count('uid') as cuid"))->from(self::$table)
						->where('support_round', '=', $round)
						->where('last_join_time', '>=', $minJoinTime)
						->query();
		return $result[0]['cuid'];
	}
}

class LordwarCrossDao
{
	static $table = 't_lordwar_cross_user';
	
	public static function getSignUserForAudition($teamId,$teamType, $registerTime, $offset)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$loseNumConf = $confMgr->getAuditionOutLoseNum( LordwarField::CROSS );
		
		$data = new CData();
		$wheres = array(
				array('register_time','>',$registerTime),
				array('team_type','=',LordwarTeamType::NO),
				array('team_id','=',$teamId),
		);
		if( $teamType == LordwarTeamType::WIN )
		{
			$selects = array('pid','server_id','winner_losenum as loseNum');
			$wheres[] = array('winner_losenum', '<',$loseNumConf );
		}
		elseif ( $teamType == LordwarTeamType::LOSE )
		{
			$selects = array('pid','server_id','loser_losenum as loseNum');
			$wheres[] = array('loser_losenum', '<',$loseNumConf );
		}
		else
		{
			return array();
		}
		
		$data->select( $selects )->from(self::$table);
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}
		
		$ret = $data->limit($offset, CData::MAX_FETCH_SIZE)->query();//TODO 不order的话会有默认排序吧
		
		if( empty( $ret ) )
		{
			return array();
		}
		return $ret;
	}
	
	public static function updateLordInfo( $serverId, $pid, $updateArr )
	{
		$db = LordwarUtil::getCrossDbName($serverId);
		$data = new CData();
		$data->useDb( $db );
		$wheres = array(
			array('server_id','=',$serverId),
				array( 'pid', '=', $pid ),
		);
		$data -> update( self::$table )->set( $updateArr );

		foreach ($wheres as $where)
		{
			$data->where($where);
		}
		$ret = $data->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			throw new InterException( 'nothing chaned when want to update: %s', $updateArr );
		}
	}
	
	public static function insertForCross($serverId, $pid, $teamId)
	{
		//将这些数据插入到跨服报名的表里面
		$insertValues = array(
				'server_id' => $serverId,
				'pid' => $pid,
				'register_time' => Util::getTime(),
				'team_id' => $teamId,
				'team_type' => LordwarTeamType::NO,
				'winner_losenum' => 0,
				'loser_losenum' => 0,
		);
		
		$db = LordwarUtil::getCrossDbName();
		$data = new CData();
		$data->useDb($db);
		$ret = $data->insertOrUpdate(self::$table)->values( $insertValues )->query();
		if( $ret[DataDef::AFFECTED_ROWS] == 0 )
		{
			Logger::fatal('register for cross faild, info: %s',$insertValues);
		}
	}
	
	public static function getLordInfoFromCrossAuditon($serverId, $pid, $registerStartTime )
	{
		$selects = array(
			'server_id','pid','register_time','team_id','team_type','winner_losenum','loser_losenum',
		);
		$db = LordwarUtil::getCrossDbName();
		$data = new CData();
		$ret = $data->useDb($db)->select( $selects )->from(self::$table)
		->where(array( 'server_id','=',$serverId ))
		->where(array( 'pid','=',$pid ))
		->where(array( 'register_time','>',$registerStartTime ))
		->query();
		
		if( empty($ret) )
		{
			return array();
		}
		return $ret[0];
	}
	
}

class LordwarDao
{
	static $t_procedure = 't_lordwar_procedure';
	
	
	public static function getSignUserForAudition($teamId, $teamType,$field, $registerTime, $offset)
	{
		if ( $field == LordwarField::INNER )
		{
			$ret = LordwarInnerDao::getSignUserForAudition($teamType, $registerTime, $offset);
		}
		elseif($field == LordwarField::CROSS)
		{
			$ret = LordwarCrossDao::getSignUserForAudition($teamId, $teamType, $registerTime, $offset);
		}
		return $ret;
	}
	
	public static function getRoundData($dbName, $sess, $teamId, $round)
	{
		$data = new CData();
		
		if( !empty($dbName) )
		{
			$data->useDb($dbName);
		}

		$arrField = array(
				'team_id',
				'team_type',
				'round',
				'sess',
				'status',
				'update_time',
				'va_procedure'
		);
		
		$arrRet = $data->select( $arrField )->from(self::$t_procedure)
		->where('team_id', '=', $teamId)
		->where('sess', '=', $sess)
		->where('round', '=', $round)
		->query();
		
		$return = array();
		foreach($arrRet as $value)
		{
			$return[$value['team_type']] = $value;
		}
		
		return $return;			
	}
	

	public static function getLastRoundData($dbName, $sess, $teamId)
	{
		$data = new CData();
	
		if( !empty($dbName) )
		{
			$data->useDb($dbName);
		}
	
		$arrField = array(
				'team_id',
				'team_type',
				'round',
				'sess',
				'status',
				'update_time',
				'va_procedure'
		);
	
		$arrRet = $data->select( $arrField )->from(self::$t_procedure)
		->where('team_id', '=', $teamId)
		->where('sess', '=', $sess)
		->orderBy('round',	false)
		->limit(0, 2)
		->query();
	
		//因为round按大到小排序，所以第一行中的round就是需要的round
		$return = array();
		$round = 0;
		foreach($arrRet as $value)
		{
			if ( ( $round != 0 && $round == $value['round']) || $round == 0 )
			{
				$return[$value['team_type']] = $value;
				$round = $value['round'];
			}
		}
		
		return $return;
	}
	
	public static function updateLordProcedure($dbName, $arrValue )
	{
		$data = new CData();
		
		if( !empty($dbName) )
		{
			$data->useDb($dbName);
		}
		
		$ret = $data->insertOrUpdate( self::$t_procedure )
					->values( $arrValue )
					->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] == 0)
		{
			Logger::fatal('nothing changed');
		}
	}
/*
 * procedure 的va
  lordArr =>array(
                 0=> array(
                 serverId => ,
                 pid => ,
                 uname => ,
                 htid => ,
                 vip => ,
                 dress =>,
                 serverId => ,
                 rank => ,
                 fightForce =>,
                 )
                 ),
    recordArr => array(
                 int => array( array(
                 			atk => array(serverId，pid),
                 			def => array(),
                 			res => 0/1, 
                 			replyId => str,)                 		
                 )

                 ),
                 
     t_lordwar_inner_user 的va
     supportList => array(array(serverId,pid...))
     
 */
	/*
	public static function updateLordwar($updateArr,$field)
	{
		$data = new CData();
		if ( $field == LordwarField::INNER )
		{}
		elseif($field == LordwarField::CROSS)
		{
			$db = LordwarUtil::getCrossDbName();
			$data->useDb($db);
		}
		else
		{
			return;
		}
		
		$ret = $data->insertOrUpdate( self::$t_procedure )->values( $updateArr )
		->query();
		
		if( $ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new FakeException( 'nothing change' );
		}
	}
	
	
	

	
	
	public static function getAllLord($db,$sess,$round,$teamId)
	{
		if( empty( $db ) )
		{
			Logger::fatal('empty db');
			return array();
		}
		
		$selects = array(
				'team_id',
				'team_type',
				'round',
				'sess',
				'va_procedure',
		);
		
		$data = new CData();
		$ret = $data->useDb($db)->select($selects)->from(self::$t_procedure)
		->where(array( 'sess','=',$sess ))
		->where(array('round','=',$round))
		->where(array('team_id','=',$teamId))
		->query();
		
		if( empty( $ret ) )
		{
			return array();
		}
		
		return $ret;
	}

	public static function getMaxRoundInfo( $field, $sess, $teamId, $teamType )
	{
		$selects = array(
			'round',
			'va_procedure',
		);
		
		$wheres = array(
				array('team_id','=',$teamId),
				array('team_type','=',$teamType),
				array('sess','=',$sess),
		);
		
		$data = new CData();
		
		if( $field == LordwarField::INNER )
		{
		}
		elseif ( $field == LordwarField::CROSS )
		{
			$db = LordwarUtil::getCrossDbName();
			$data->useDb($db);
		}
		else
		{
			return array();
		}
		
		$data->select($selects)->from(self::$t_procedure);
		foreach ($wheres as $where)
		{
			$data->where($where);
		}
		$ret = $data->orderBy('round', false)->limit(0, 1)->query();
		
		if( empty( $ret ))
		{
			return array();
		}
		
		return $ret[0];
	}
	
	*/
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */