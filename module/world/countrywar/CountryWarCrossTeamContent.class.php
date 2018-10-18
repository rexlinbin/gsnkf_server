<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarCrossTeamContent.class.php 217730 2015-12-25 08:14:56Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarCrossTeamContent.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-25 08:14:56 +0000 (Fri, 25 Dec 2015) $
 * @version $Revision: 217730 $
 * @brief 
 *  
 *  应该是只在跨服上调用，并且是在doCheckAndCreateRoom的那个线程里
 *  (也可以考虑在服内报名的时候通过自增修改该表的国家人数字段，省了在串化的请求中db，不过scene就会有两种取值，会复杂一些)
 *  va里存了分房间要用的信息
 *  
 **/
class CountryWarCrossTeamContent
{
	private static $instance = null;
	private $teamContentInfo = NULL;
	private $teamContentInfoBak = NULL;
	private $teamId = NULL;
	private $curWarId = NULL;
	
	static function getInstance($teamId)
	{
		if( !isset( self::$instance[$teamId] ) )
		{
			self::$instance[$teamId] = new self($teamId);
		}
		return self::$instance[$teamId];
	}
	static function releaseInstance()
	{
		if( isset( self::$instance ) )
		{
			self::$instance = null;
		}
	}
	
	function __construct($teamId)
	{
		$this->teamId = $teamId;
		$this->curWarId = CountryWarConfig::warId(Util::getTime());
		
		if( empty( $this->teamContentInfo ) )
		{
			$this->teamContentInfo = CountryWarTeamContentDao::getInfoByTeamId($this->curWarId, $this->teamId);
			if( empty( $this->teamContentInfo ) )
			{
				$this->init();
			}
		}
		$this->teamContentInfoBak = $this->teamContentInfo;
	}
	
	private function init()
	{
		$initArr = array(
				CountryWarCrossTeamContentField::WAR_ID => $this->curWarId,
				CountryWarCrossTeamContentField::TEAM_ID => $this->teamId,
				CountryWarCrossTeamContentField::RESOURCE_A => CountryWarConfig::finalInitResource(),
				CountryWarCrossTeamContentField::RESOURCE_B => CountryWarConfig::finalInitResource(),
				CountryWarCrossTeamContentField::NUM_COUNTRY_1 => 0,
				CountryWarCrossTeamContentField::NUM_COUNTRY_2 => 0,
				CountryWarCrossTeamContentField::NUM_COUNTRY_3 => 0,
				CountryWarCrossTeamContentField::NUM_COUNTRY_4 => 0,
				CountryWarCrossTeamContentField::ROOM_NUM =>0,
				CountryWarCrossTeamContentField::VA_EXTRA => array(),
		);
		CountryWarTeamContentDao::insertInfo($initArr);
		$this->teamContentInfo = $initArr;
	}
	
	public function addPeopleForCountry( $countryId, $num )
	{
		$field = $this->getMyCountryField($countryId);
		$this->teamContentInfo[$field] += $num;
	}
	
	public function getMaxSignNumOfCountry()
	{
		$max = 0;
		foreach ( CountryWarCountryId::$ALL as $id )
		{
			if( $this->teamContentInfo[$this->getMyCountryField( $id )] > $max )
			{
				$max = $this->teamContentInfo[$this->getMyCountryField( $id )];
			}
		}
		
		return $max;
	}
	
	public function getAllCountrySignNum()
	{
		foreach ( CountryWarCountryId::$ALL as $id )
		{
			$arr[$id] = $this->teamContentInfo[$this->getMyCountryField( $id )];
		}
		return $arr;
	}
	
	public function getRoomNum()
	{
		return $this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM];
	}
	
	public function addRoomNum( $num )
	{
		$this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM] += $num;
	}
	
	public function getLastDealRoomIdAndSide($countryId)
	{
		if( empty( $this->teamContentInfo[CountryWarCrossTeamContentField::VA_EXTRA][$countryId] ) )
		{
			return array();
		}
		else 
		{
			return  $this->teamContentInfo[CountryWarCrossTeamContentField::VA_EXTRA][$countryId];
		}
		
	}
	
	public function divideOneUser( $countryId, $roomId, $side )
	{
		$this->teamContentInfo[CountryWarCrossTeamContentField::VA_EXTRA][$countryId] = array($roomId,$side);
	}
	
	public function isCountryFull($countryId)
	{
		$num = 0;
		if( !empty($this->teamContentInfo[$this->getMyCountryField( $countryId )] ))
		{
			$num = $this->teamContentInfo[$this->getMyCountryField( $countryId )];
		}
		if( $this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM] < 1 )
		{
			return true;
		}
		$roomUserNum = $num/$this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM];
		if( $roomUserNum >= CountryWarConfig::battleMaxNum() )
		{
			return true;
		}
		return false;
	}

	public function isNobodyJoin( $countryId )
	{
		if( empty( $this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM] ) )
		{
			return true;
		}
		return false;
	}
	
	
	public function getNeedMoreRoomNum()
	{
		return CountryWarUtil::getMoreRoomNum($this->teamContentInfo[CountryWarCrossTeamContentField::ROOM_NUM], $this->getMaxSignNumOfCountry());
	}
	private function getMyCountryField( $countryId )
	{
		return 'num_country_'.$countryId;
	}
	private function dealSpecialKey( $key )
	{
		//特殊需要的字段特殊处理一把,或者是检查
		$info = $this->teamContentInfo[$key];
		switch ( $key )
		{
			case CountryWarCrossTeamContentField::NUM_COUNTRY_1:
			case CountryWarCrossTeamContentField::NUM_COUNTRY_2:
			case CountryWarCrossTeamContentField::NUM_COUNTRY_3:
			case CountryWarCrossTeamContentField::NUM_COUNTRY_4:
				$delta = $this->teamContentInfo[$key] - $this->teamContentInfoBak[$key];
				if( $delta > 0 )
				{
					$info = new  IncOperator($delta);
				}
				else
				{
					throw new InterException( 'signNum decrease?before:%s,now:%s',$this->teamContentInfo[$key],$this->teamContentInfoBak[$key] );
				}
				break;
			case CountryWarCrossTeamContentField::RESOURCE_A:
			case CountryWarCrossTeamContentField::RESOURCE_B:
				$delta = $this->teamContentInfo[$key] - $this->teamContentInfoBak[$key];
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
	public function update()
	{
		if( $this->teamContentInfo == $this->teamContentInfoBak )
		{
			Logger::warning('nothing change');
			return;
		}
		$updateFields = array();
		foreach ( $this->teamContentInfo as $key => $info )
		{
			if( $this->teamContentInfoBak[$key] != $info )
			{
				$updateFields[$key] = self::dealSpecialKey($key);
			}
		}
		
		CountryWarTeamContentDao::update( $this->curWarId, $this->teamId, $updateFields );
		$this->teamContentInfoBak = $this->teamContentInfo;
	}
	
	public function sideRobSide( $side, $num)
	{
		$opSide = CountryWarUtil::getOppositeSide( $side );
		$side = CountryWarUtil::getResourceSideDbKey($side);
		$opSide = CountryWarUtil::getResourceSideDbKey($opSide);
		$robNum = $num > $this->teamContentInfo[$opSide]?$this->teamContentInfo[$opSide]:$num;
		$this->teamContentInfo[$side] += $robNum;
		$this->teamContentInfo[$opSide] -= $robNum;
		if( $this->teamContentInfo[$opSide] < 0 )
		{
			$this->teamContentInfo[$opSide] =0;
		}
		
		return $robNum;
	}
	
	public function setResource( $attackerResource, $defenderResource )
	{
		$attackerSide = CountryWarConf::SIDE_A;
		$defenderSide = CountryWarConf::SIDE_B;
		$this->teamContentInfo[CountryWarUtil::getResourceSideDbKey($attackerSide)] = $attackerResource;
		$this->teamContentInfo[CountryWarUtil::getResourceSideDbKey($defenderResource)] = $defenderResource;
	}
	
	public function isSideWin($side)
	{
		$winSide = self::getWinSide();
		if( $winSide == CountryWarConf::NOSIDEWIN )
		{
			Logger::warning('no side win');
			return false;
		}
		$winSideKey = CountryWarUtil::getResourceSideDbKey($winSide);
		$sideKey = CountryWarUtil::getResourceSideDbKey($side);
		if( $sideKey == $winSideKey )
		{
			Logger::debug('win side:%s',$side);
			return true;
		}
		Logger::debug('lose side:%s',$side);
		return false;
	}
	
	public function getWinSide()
	{
		$side_a = $this->teamContentInfo[CountryWarCrossTeamContentField::RESOURCE_A];
		$side_b = $this->teamContentInfo[CountryWarCrossTeamContentField::RESOURCE_B];
		if( $side_a == $side_b )
		{
			$highestInfo =  CountryWarCrossUser::getHighestInfoByWarIdTeamId(CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP), $this->teamId);
			if( empty( $highestInfo ) )
			{
				return CountryWarConf::NOSIDEWIN;
			}
			$countryId = $highestInfo[CountryWarCrossUserField::COUNTRY_ID];
			$winSide = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $countryId);
		}
		else
		{
			$winSide = $side_a > $side_b?CountryWarConf::SIDE_A:CountryWarConf::SIDE_B;
		}
		
		return $winSide;
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */