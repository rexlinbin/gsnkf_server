<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarInnerUser.class.php 215833 2015-12-15 11:23:12Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarInnerUser.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-15 11:23:12 +0000 (Tue, 15 Dec 2015) $
 * @version $Revision: 215833 $
 * @brief 
 *  
 *  目的：
 *  操作inneruser表的对象
 *  场景：
 *  只有innerScene
 **/
class CountryWarInnerUser
{
	private static $instance = null;
	private $innerUserInfo = NULL;
	private $innerUserInfoBak = NULL;
	private $serverId = NULL;
	private $pid = NULL;
	private $startTime = NULL;
	
	/**
	 * @param unknown $serverId
	 * @param unknown $pid
	 * @return CountryWarInnerUser
	 */
	static function getInstance( $serverId, $pid )
	{
		if( !isset( self::$instance[$serverId][$pid] ) )
		{
			self::$instance[$serverId][$pid] = new self( $serverId, $pid );
		}
		return self::$instance[$serverId][$pid];
	}
	static function releaseInstance()
	{
		if( isset( self::$instance ) )
		{
			unset( self::$instance );
		}
	}
	
	function __construct( $serverId, $pid )
	{
		$uid = RPCContext::getInstance()->getUid();
		$mypid = EnUser::getUserObj($uid)->getPid();
		$myserverid = Util::getServerIdOfConnection();
		if( $serverId != $myserverid || $pid != $mypid )
		{
			throw new InterException( 'not alowed to call others' );
		}
		$this->serverId = $serverId;
		$this->pid = $pid;
		$this->startTime = CountryWarConfig::roundStartTime(Util::getTime());
		
		if( empty( $this->innerUserInfo ) )
		{
			$this->innerUserInfo = CountryWarInnerUserDao::getInfoByServerIdPid($serverId, $pid);
			if( empty( $this->innerUserInfo ) )
			{
				$this->init();
			}
		}
		$this->innerUserInfoBak = $this->innerUserInfo;
		$this->refresh();
	}
	
	private function init()
	{
		$initArr = array(
				CountryWarInnerUserField::PID => $this->pid,
				CountryWarInnerUserField::SERVER_ID => $this->serverId,
				CountryWarInnerUserField::SUPPORT_PID => 0,
				CountryWarInnerUserField::SUPPORT_SERVER_ID => 0,
				CountryWarInnerUserField::SUPPORT_SIDE => 0,
				CountryWarInnerUserField::WORSHIP_TIME => 0,
				CountryWarInnerUserField::AUDITION_REWARD_TIME => 0,
				CountryWarInnerUserField::SUPPORT_REWARD_TIME => 0,
				CountryWarInnerUserField::FINAL_REWARD_TIME => 0,
				CountryWarInnerUserField::UPDATE_TIME => 0,
		);
		CountryWarInnerUserDao::insertInfo($initArr);
		$this->innerUserInfo = $initArr;
	}
	
	private function refresh()
	{
		$lastUpdateTime =  $this->innerUserInfo[CountryWarInnerUserField::UPDATE_TIME];
		if( $lastUpdateTime < $this->startTime )
		{
				$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_PID] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SERVER_ID] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SIDE] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::WORSHIP_TIME] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::AUDITION_REWARD_TIME] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_REWARD_TIME] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::FINAL_REWARD_TIME] = 0;
				$this->innerUserInfo[CountryWarInnerUserField::UPDATE_TIME] = Util::getTime();
		}
	}
	
	public function getSignTime()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::SIGN_TIME];
	}
	
	public function sign()
	{
		$this->innerUserInfo[CountryWarInnerUserField::SIGN_TIME] = Util::getTime();
	}
	
	public function alreadySupportOneUser()
	{
		if( $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SERVER_ID] > 0 )
		{
			return true;
		}
		return false;
	}
	
	public function alreadySupportFinalSide()
	{
		if( $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SIDE] > 0 )
		{
			return true;
		}
		return false;
	}
	public function getSupportServerId()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SERVER_ID];
	}
	
	public function getSupportPid()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_PID];
	}
	
	public function getSupportFinalSide()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SIDE];
	}
	public function supportOneUser($serverId, $pid)
	{
		$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SERVER_ID] = $serverId;
		$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_PID] = $pid;
	}
	public function supportFinalSide($side)
	{
		$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_SIDE] = $side;
	}
	
	public function isWorshipToday()
	{
		if( Util::isSameDay( $this->innerUserInfo[CountryWarInnerUserField::WORSHIP_TIME] ) )
		{
			return true;
		}
		return false;
	}
	
	public function getWorshipTime()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::WORSHIP_TIME];
	}
	
	public function worship()
	{
		$this->innerUserInfo[CountryWarInnerUserField::WORSHIP_TIME] = Util::getTime();
	}
	
	public function alreadyGainSupportReward()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::SUPPORT_REWARD_TIME] >0;
	}
	
	public function alreadyGainAuditionRankReward()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::AUDITION_REWARD_TIME] >0;
	}
	
	public function alreadyGainFinaltionRankReward()
	{
		return $this->innerUserInfo[CountryWarInnerUserField::FINAL_REWARD_TIME] >0;
	}
	
	public function rewardSupport()
	{
		$this->innerUserInfo[CountryWarInnerUserField::SUPPORT_REWARD_TIME] = Util::getTime();
	}
	
	public function rewardAudition()
	{
		$this->innerUserInfo[CountryWarInnerUserField::AUDITION_REWARD_TIME] = Util::getTime();
	}
	
	public function rewardFinaltion()
	{
		$this->innerUserInfo[CountryWarInnerUserField::FINAL_REWARD_TIME] = Util::getTime();
	}
	
	public function update()
	{
		if( $this->innerUserInfo == $this->innerUserInfoBak )
		{
			Logger::warning('nothing change');
			return;
		}
		$updateFields = array();
		foreach ( $this->innerUserInfo as $key => $info )
		{
			if( $this->innerUserInfoBak[$key] != $info )
			{
				$updateFields[$key] = $info;
			}
		}
		if( !isset( $updateFields[CountryWarInnerUserField::UPDATE_TIME] ) )
		{
			$updateFields[CountryWarCrossUserField::UPDATE_TIME] = Util::getTime();
		}
		
		CountryWarInnerUserDao::update( $this->serverId, $this->pid, $updateFields );
		$this->innerUserInfoBak = $this->innerUserInfo;
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */