<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarTeamObj.class.php 216379 2015-12-18 08:59:11Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarTeamObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-18 08:59:11 +0000 (Fri, 18 Dec 2015) $
 * @version $Revision: 216379 $
 * @brief 
 * 
 * 获取分组信息,只获取和判定不更新（要做判定然后分吗？没有分组的话）TODO 预案
 * 
 **/
class CountryWarTeamObj 
{
	private static  $instance = NULL;
	private $checkTime = NULL;
	private $serverIdToTeamId = NULL;
	private $allTeamInfo = NULL;
	
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
		$this->checkTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::TEAM);
	}
	
	public function getAllTeamInfo()
	{
		if( NULL == $this->allTeamInfo )
		{
			$periodBgnTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::TEAM);
			
			$allTeamInfo = CountryWarTeamDao::getAllTeamInfo($periodBgnTime);
			$arrRet = array();
			foreach ($allTeamInfo as $aInfo)
			{
				$aTeamId = $aInfo[CountryWarTeamField::TEAM_ID];
				$aServerId = $aInfo[CountryWarTeamField::SERVER_ID];
				if (!isset($arrRet[$aTeamId]))
				{
					$arrRet[$aTeamId] = array();
				}
				$arrRet[$aTeamId][] = $aServerId;
				if( !isset( $this->serverIdToTeamId[$aServerId] ) )
				{
					$this->serverIdToTeamId[$aServerId] = $aTeamId;
				}
			}
			$this->allTeamInfo = $arrRet;
		}
	
		return $this->allTeamInfo;
	}
	
	public function getTeamIdByServerId( $serverId )
	{
		if( !isset( $this->serverIdToTeamId[$serverId] ) )
		{
			$teamInfo = CountryWarTeamDao::getTeamInfoByServerId($serverId, $this->checkTime);
			if(empty($teamInfo[CountryWarTeamField::TEAM_ID]) || $teamInfo[CountryWarTeamField::TEAM_ID] <= 0 )
			{
				$this->serverIdToTeamId[$serverId] = CountryWarConf::UNTEAMED;
			}
			else
			{
				$this->serverIdToTeamId[$serverId] = $teamInfo[CountryWarTeamField::TEAM_ID];
			}
		}
		
		return $this->serverIdToTeamId[$serverId];
	}
	
	public function getAllServerInTeam( $teamId )
	{
		$this->getAllTeamInfo();
		if( !isset( $this->allTeamInfo[$teamId] ) )
		{
			return array();
		}
		return $this->allTeamInfo[$teamId];
		
/* 		if( !isset( $this->allServerIdInOneTeam[$teamId] ) )
		{
			$this->allServerIdInOneTeam[$teamId] = CountryWarTeamDao::getAllServerIdInTeam($teamId, $this->checkTime);
			foreach ( $this->allServerIdInOneTeam[$teamId] as $oneServerId )
			{
				if( !isset( $this->serverIdToTeamId[$oneServerId] ) )
				{
					$this->serverIdToTeamId[$oneServerId] = $teamId;
				}
			}
		}
		
		return $this->allServerIdInOneTeam[$teamId]; */
	}
	
	public function getAllTeamId()
	{
		$this->getAllTeamInfo();
		$teamIdArr = array_keys( $this->allTeamInfo );
		foreach ( $teamIdArr as $index => $oneTeamId )
		{
			if( $oneTeamId <= 0 )
			{
				unset( $teamIdArr[$index] );
			}
		}
		return $teamIdArr;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */