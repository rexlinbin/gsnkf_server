<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarRoomObj.class.php 213925 2015-12-03 08:43:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-32-3/module/world/countrywar/CountryWarRoomObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-03 16:43:30 +0800 (四, 2015-12-03) $
 * @version $Revision: 213925 $
 * @brief 
 *  
 *  一般只有在跨服去获取，修改发生在
 *  1.后端或者脚本的调用，一个teamid会被串化到一个线程，故没有冲突
 *  2.对于resource的操作，自增自减
 *  3.分房会改va，在玩家直接进的时候，加锁
 *  
 *  
 **/
class CountryWarRoomObj extends CountryWarBaseObj
{/* 
	private static $instance = null;
	private $roomInfo = NULL;
	private $roomInfoBak = NULL;
	private $curWarId = NULL;
	
	static function getInstance()
	{
		if( !isset( self::$instance ) )
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	static function releaseInstance()
	{
		if( isset( self::$instance ) )
		{
			unset( self::$instance );
		}
	}
	
	function __construct()
	{
		parent::__construct( array( CountryWarScene::INNER,CountryWarScene::CROSS ) );
		$this->curWarId = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::TEAM);
		$this->roomInfo = array();
	}
	
	public function loadRoomInfo( $teamRoomId )
	{
		if( !isset( $this->roomInfo[$teamRoomId] ) )
		{
			$this->roomInfo[$teamRoomId] = CountryWarCrossRoomDao::getInfoByTeamRoomId($this->curWarId, $teamRoomId);
			$this->roomInfoBak[$teamRoomId] = $this->roomInfo[$teamRoomId];
		}
		if( empty( $this->roomInfo ) )
		{
			throw new InterException( 'when load, should not be empty' );
		}
		return $this->roomInfo[$teamRoomId];
	}
	
	public function getRoomInfo( $teamRoomId )
	{
		$this->loadRoomInfo($teamRoomId);
		return $this->roomInfo[$teamRoomId];
	}
	
	public function getRoomResource( $teamRoomId,$side )
	{
		$this->loadRoomInfo($teamRoomId);
		$side = CountryWarUtil::getSideDbKey( $side );
		
		return $this->roomInfo[$teamRoomId][$side];
	}
	
	public function addNewRoom( $teamRoomId )
	{
		//依赖插入的时候直接报错，不然就getInfo一把TODO
		if( !empty( $this->roomInfo[$teamRoomId] ) )
		{
			throw new InterException( 'already have this room' );
		}
		$this->roomInfo[$teamRoomId] = array(
				CountryWarCrossRoomField::WAR_ID => $this->curWarId,
				CountryWarCrossRoomField::TEAM_ROOM_ID => $teamRoomId,
				CountryWarCrossRoomField::RESOURCE_A => CountryWarConfig::finalInitResource(),
				CountryWarCrossRoomField::RESOURCE_B => CountryWarConfig::finalInitResource(),
				CountryWarCrossRoomField::VA_EXTRA => array(),
		);
	}
	
	public function getRoomCountOfTeam( $teamId )
	{
		$count = CountryWarCrossRoomDao::getRoomCountOfTeam($this->curWarId, $teamId);
		Logger::info('room count now is:%s', $count);
		
		return $count;
	}
	
	public function dealSpecialKey( $id, $key )
	{
		$info = $this->roomInfo[$id][$key];
		switch ($key)
		{
			case CountryWarCrossRoomField::RESOURCE_A:
			case CountryWarCrossRoomField::RESOURCE_B:
				$delta = $this->roomInfoBak[$id][$key] - $this->roomInfo[$id][$key];
				if( $delta > 0 )
				{
					$info = new IncOperator( $delta );
				}
				else 
				{
					$info = new DecOperator( -$delta );
				}
				break;
			default:break;
		}
		
		return $info;
	}
	
	public function sideRobSide( $teamRoomId, $side, $num)
	{
		$this->loadRoomInfo($teamRoomId);
		$opSide = CountryWarUtil::getOppositeSide( $side );
		$side = CountryWarUtil::getSideDbKey($side);
		$opSide = CountryWarUtil::getSideDbKey($opSide);
		$robNum = $num > $this->roomInfo[$teamRoomId][$opSide]?$this->roomInfo[$teamRoomId][$opSide]:$num;
		$this->roomInfo[$teamRoomId][$side] += $robNum;
		$this->roomInfo[$teamRoomId][$opSide] -= $robNum;
		if( $this->roomInfo[$teamRoomId][$opSide] < 0 )
		{
			$this->roomInfo[$teamRoomId][$opSide] =0;
		}
	}
	
	public function update()
	{
		if( $this->roomInfo == $this->roomInfoBak )
		{
			Logger::warning('nothing change');
			return;
		}
		$addRoom = array();
		$updateRoom = array();
		foreach ( $this->roomInfo as $id => $info )
		{
			if( !isset( $this->roomInfoBak[$id] ) )
			{
				if( isset( $addRoom[$id] ) )
				{
					throw new InterException( 'same room use twice: %s', $info );
				}
				$addRoom[$id] = $info;
				continue;
			}
			if( $this->roomInfoBak[$id] == $info )
			{
				continue;
			}
			else 
			{
				foreach ( $this->roomInfo[$id] as $key => $roomDetail )
				{
					if( $this->roomInfoBak[$id][$key] != $roomDetail )
					{
						$info = $this->dealSpecialKey( $id, $key );
						$updateRoom[$id][$key] = $info;
					}
				}
			}
		}
		if( !empty( $updateRoom ) )
		{
			foreach ( $updateRoom as $teamRoomId => $upFields )
			{
				CountryWarCrossRoomDao::update($this->curWarId, $teamRoomId, $upFields);
			}
		}
		
		if( !empty( $addRoom ) )
		{
			foreach ( $addRoom as $id => $insertFields )
			{
				//只有在串化创建房间的时候能走到这
				CountryWarCrossRoomDao::insertInfo($insertFields);
				Logger::info('create room, teamRoomId:%s,curwarId:%s',$id,$this->curWarId);
			}
		}
		
		//TODO 不能up多次
		$this->roomInfoBak = $this->roomInfo;
	}
	
	public function isSideWin($teamRoomId,$side)
	{
		$this->loadRoomInfo($teamRoomId);
		$side_a = $this->roomInfo[$teamRoomId][CountryWarCrossRoomField::RESOURCE_A];
		$side_b = $this->roomInfo[$teamRoomId][CountryWarCrossRoomField::RESOURCE_B];
		if( $side_a == $side_b )
		{
			$highestInfo =  CountryWarCrossUser::getHighestInfoByWarIdTeamRoomId(CountryWarConfig::roundStartTime(Util::getTime()), $teamRoomId);
			$countryId = $highestInfo[CountryWarCrossUserField::COUNTRY_ID];
			$winside = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $countryId);
			$winSideKey = CountryWarUtil::getSideDbKey($winside);
		}
		else
		{
			$winSideKey =  $side_a> $side_b?CountryWarCrossRoomField::RESOURCE_A:CountryWarCrossRoomField::RESOURCE_B;
		}
		
		$sideKey = CountryWarUtil::getSideDbKey($side);
		if( $sideKey == $winSideKey )
		{
			return true;
		}
		return false;
	}
	
	public function getAllRoomInfo( $teamId )
	{
		$allRoomInfo = CountryWarCrossRoomDao::getAllRoomInfo($this->curWarId,$teamId);
		
		return $allRoomInfo;
	}
 */}
