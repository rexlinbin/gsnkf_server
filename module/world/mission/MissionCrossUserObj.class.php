<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionCrossUserObj.class.php 214360 2015-12-07 09:09:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionCrossUserObj.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-07 09:09:52 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214360 $
 * @brief 
 *  
 **/
class MissionCrossUserObj
{
	private static $instance = NULL;
	private $missUserInfo = NULL;
	private $missUserInfoBak = NULL;
	private $serverId = NULL;
	private $pid = NULL;
	private $uid = NULL;
	private $teamId = NULL;
	
	/**
	 * 
	 * @param unknown $pid
	 * @param unknown $serverId
	 * @return MissionCrossUserObj
	 */
	static function getInstance( $pid, $serverId )
	{ 
		if( !isset( self::$instance[$serverId][$pid] ) )
		{
			self::$instance[$serverId][$pid] = new self( $serverId, $pid );
		}
		return self::$instance[$serverId][$pid];
	}
	static function releaseInstance()
	{
		if( !empty( self::$instance ) )
		{
			self::$instance = null;
		}
	}
	
	/**
	 * 基本上每次获取都会有更新才对，除了拉取排行
	 * @param unknown $serverId
	 * @param unknown $pid
	 */
	function __construct( $serverId, $pid )
	{
		$uid = RPCContext::getInstance()->getUid();
		if( empty( $uid ) )
		{
			throw new InterException( 'should not call this' );
		}
		$this->uid = $uid;
		$this->serverId = $serverId;
		$this->pid = $pid;
		$this->teamId = MissionTeamMgr::getInstance($serverId)->getTeamIdByServerId();
		$this->missUserInfo = MissionDao::getCrossUserInfo($serverId, $pid, $this->teamId);
		
		if( empty( $this->missUserInfo ) )
		{
			$this->missUserInfo = $this->init();
		}
		$this->missUserInfoBak = $this->missUserInfo;
		$this->refresh();
	}
	
	private function init()
	{
		$user =  EnUser::getUserObj($this->uid);
		$initArr = array(
				MissionDBField::CROSS_PID => $this->pid,
				MissionDBField::CROSS_SERVERID => $this->serverId,
				MissionDBField::CROSS_UNAME => EnUser::getUserObj($this->uid)->getUname(),
				MissionDBField::CROSS_FAME => 0,
				MissionDBField::CROSS_HTID => $user->getHeroManager()->getMasterHeroObj()->getHtid(),
				MissionDBField::CROSS_VIP => $user->getVip(),
				MissionDBField::CROSS_LEVEL => $user->getLevel(),
				MissionDBField::CROSS_VA_USER => array(MissionVAField::MISSION_DRESS => $user->getDressInfo()),
				MissionDBField::CROSS_UPDATE_TIME => Util::getTime(),
		);
		
		MissionDao::insertCrossUserInfo($this->teamId,$initArr);
		
		return $initArr;
	}
	
	private function refresh()
	{
		$user = EnUser::getUserObj($this->uid);
		$missionConObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );
		if( $missionConObj->isConfValid() )
		{
			$startTime = $missionConObj->getStartTime();
			if( $this->missUserInfo[MissionDBField::CROSS_UPDATE_TIME] < $startTime )
			{
				$this->missUserInfo[MissionDBField::CROSS_UNAME] = EnUser::getUserObj($this->uid)->getUname();
				$this->missUserInfo[MissionDBField::CROSS_FAME] = 0;
				$this->missUserInfo[MissionDBField::CROSS_HTID] = $user->getHeroManager()->getMasterHeroObj()->getHtid();
				$this->missUserInfo[MissionDBField::CROSS_VIP] = $user->getVip();
				$this->missUserInfo[MissionDBField::CROSS_LEVEL] = $user->getLevel();
				$this->missUserInfo[MissionDBField::CROSS_VA_USER][MissionVAField::MISSION_DRESS] = $user->getDressInfo();
				$this->missUserInfo[MissionDBField::CROSS_UPDATE_TIME] = Util::getTime();
			}
		}
	}
	
	function getMyRank()
	{
		if( $this->teamId <= 0 )
		{
			return -1;
		}
		$confObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );
		if( !$confObj->isConfValid() )
		{
			return -1;
		}
		else
		{
			$time = $confObj->getStartTime();
		}
		if($this->missUserInfo[MissionDBField::CROSS_UPDATE_TIME] < $time 
		|| $this->missUserInfo[MissionDBField::CROSS_FAME] == 0)
		{
			return -1;	
		}
	
		$num = MissionDao::getMyRank($this->teamId, $this->serverId, $this->pid, 
				$this->missUserInfo[MissionDBField::CROSS_FAME], $time, $this->missUserInfo[MissionDBField::CROSS_UPDATE_TIME] );
	
		return $num;
	}
	
	public function setFame($fame)
	{
		$fame = $fame <0? 0:$fame;
		$this->missUserInfo[MissionDBField::CROSS_FAME] = $fame;
	}
	
	public function update()
	{
		if( $this->missUserInfo == $this->missUserInfoBak )
		{
			Logger::warning('nothing change');
			return;
		}
		
		$updateArr = array();
		foreach ( $this->missUserInfo as $key => $val )
		{
			if( $val != $this->missUserInfoBak[$key] )
			{
				$updateArr[$key] = $val;
			}
		}
		if( empty( $updateArr ) )
		{
			return;
		}
		$user = EnUser::getUserObj($this->uid);
		$updateArr[MissionDBField::CROSS_UPDATE_TIME] = Util::getTime();
		$updateArr[MissionDBField::CROSS_HTID] = $user->getHeroManager()->getMasterHeroObj()->getHtid();
		$updateArr[MissionDBField::CROSS_VIP] = $user->getVip();
		$updateArr[MissionDBField::CROSS_LEVEL] = $user->getLevel();
		$updateArr[MissionDBField::CROSS_UNAME] = $user->getUname();
		$updateArr[MissionDBField::CROSS_VA_USER][MissionVAField::MISSION_DRESS] = $user->getDressInfo();
		MissionDao::updatCrossUserInfo($this->serverId, $this->pid, $this->teamId, $updateArr);
		$this->missUserInfoBak = $this->missUserInfo;
	}
	
	public function getFame()
	{
		return $this->missUserInfo[MissionDBField::CROSS_FAME];
	}
	
	static function getRankList($teamId, $topNum, $validTime = 2)
	{
		$confObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );
		if( !$confObj->isConfValid() )
		{
			return array();
		}
		if( $teamId < 0 )
		{
			return array();
		}
		if( $topNum > DataDef::MAX_FETCH )
		{
			//throw new FakeException( 'not allow fetch num: %s', $topNum  );
		}
		$time = $confObj->getStartTime();
		
		$ret = self::getRankListFromMemOrDb($teamId, $topNum, $time, $validTime);
		
		return $ret;
	}
	
	static function getRankListFromMemOrDb( $teamId, $topNum, $startTime, $validTime )
	{
		if( $validTime <= 0 )
		{
			$ret = MissionDao::getRankList($teamId,$startTime, $topNum);
			Logger::info('rank from db');
			return $ret;
		}
		
		if( $validTime > 30 )
		{
			$validTime = 30;
			Logger::fatal('validtime too long,%s, reset to 30', $validTime);
		}
		
		$addKey = self::getSessionAddKey($teamId);
		$rankKey = self::getSessionRankKey($teamId);
		$crossDb =  MissionUtil::getCrossDbName();
		
		McClient::setDb($crossDb);
		$dataInMem = McClient::get($rankKey);
		
		if( !isset( $dataInMem[MissionDef::RANK_SESS_SET_TIME] )
		||  !isset( $dataInMem[MissionDef::RANK_SESS_LIST] )
		|| $dataInMem[MissionDef::RANK_SESS_SET_TIME] + $validTime <= Util::getTime() )
		{
			McClient::setDb($crossDb);
			$addRet = McClient::add($addKey, array(1) , $validTime);
			if( $addRet == "STORED" )
			{
				$rawRet = MissionDao::getRankList($teamId,$startTime, $topNum);
				
				$ret = array();
				// 前200名都取出来
				$ret = array_slice($rawRet, 0, 200);
				// 300名，400名，500名
				$arrSpecialRank = array(299,399,499);
				foreach ($arrSpecialRank as $aRank)
				{
					if (!empty($rawRet[$aRank]))
					{
						$ret[$aRank] = $rawRet[$aRank];
					}
				}
				
				$forSetArr[MissionDef::RANK_SESS_SET_TIME] = Util::getTime();
				$forSetArr[MissionDef::RANK_SESS_LIST] = $ret;
				McClient::setDb($crossDb);
				McClient::set($rankKey, $forSetArr);
				Logger::info('info from db to refresh mem done');
				return $ret;
			}
			else 
			{
				if( empty( $dataInMem[MissionDef::RANK_SESS_LIST] ) )
				{
					Logger::info('info from db to refresh mem NOT_STORED and empty last memlist');
					return array();//就没有排名
				}
				else 
				{
					Logger::info('info from db to refresh mem NOT_STORED and not empty last memlist');
					return $dataInMem[MissionDef::RANK_SESS_LIST];
				}
			}
			
		}
		else
		{
			Logger::debug('from mem');
			if( empty( $dataInMem[MissionDef::RANK_SESS_LIST] ) )
			{
				return array();
			}
			else 
			{
				return $dataInMem[MissionDef::RANK_SESS_LIST];
			}
		}
	}
	
	static function getSessionAddKey( $teamId )
	{
		return MissionDef::MISS_ADD_KEY."_"."$teamId";
	} 
	
	static function getSessionRankKey( $teamId )
	{
		return MissionDef::MISS_RANK_SESSKEY."_"."$teamId";
	}
	
	static function getParticularRankInfo( $teamId, $rank )
	{
		$confObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );
		if( !$confObj->isConfValid() )
		{
			return array();
		}
		if( $teamId < 0 )
		{
			return array();
		}
		$time = $confObj->getStartTime();
		$ret = MissionDao::getParticularRankInfo($teamId,$time, $rank);
		
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */