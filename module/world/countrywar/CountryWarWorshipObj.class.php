<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarWorshipObj.class.php 241173 2016-05-05 13:23:46Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarWorshipObj.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-05 13:23:46 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241173 $
 * @brief 
 * 
 *  管理助威innerworship表，inner用
 *  
 *  
 **/

class CountryWarWorshipObj
{
	private static $instance = null;
	private $worship = NULL;
	private $curWarId = NULL;
	private $roundStartTime = NULL;
	
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
		$this->curWarId = CountryWarConfig::warId(Util::getTime());
		$this->roundStartTime = CountryWarConfig::roundStartTime(Util::getTime());
		
		$this->worship = CountryWarWorshipDao::getInfoByWarId($this->curWarId);
		if( empty( $this->worship ) )
		{
			$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId(Util::getServerIdOfConnection());
			$infoArr = CountryWarCrossUser::getHighestInfoByWarIdTeamId(CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP), $teamId);
			if( empty($infoArr) )
			{
				Logger::fatal('nobody join last round, teamid:%s!!!!', $teamId);
				$this->worship = array();
			}
			else
			{
				// 20160504 膜拜增加称号
				$title = 0;
				try
				{
					$worshipPid = $infoArr[CountryWarCrossUserField::PID];
					$worshipServerId = $infoArr[CountryWarCrossUserField::SERVER_ID];
					$arrUserInfo = UserDao::getArrUserByPid($worshipPid, array('uid', 'title'), $worshipServerId);
					if (empty($arrUserInfo))
					{
						throw new InterException('not valid pid[%d], serverId[%d], no user info', $worshipPid, $worshipServerId);
					}
					$title = $arrUserInfo[0]['title'];
				}
				catch (Exception $e)
				{
					Logger::fatal("occurr exception when get title for pid[%d], serverId[%d], exception[%s]", $worshipPid, $worshipServerId, $e->getTraceAsString());
					$title = 0;
				}
				
				$this->worship = $this->getNeedInfo($infoArr);
				$this->worship[CountryWarInnerWorshipField::TITLE] = $title;
				$this->insert( $this->worship );
			}
		}
	}
	
	private function getNeedInfo( $infoArr )
	{
		$needInfoArr = array();
		
		$needInfoArr[CountryWarInnerWorshipField::WAR_ID] = $this->curWarId;
		$needInfoArr[CountryWarInnerWorshipField::PID] = $infoArr[CountryWarCrossUserField::PID];
		$needInfoArr[CountryWarInnerWorshipField::SERVER_ID] = $infoArr[CountryWarCrossUserField::SERVER_ID];
		$needInfoArr[CountryWarInnerWorshipField::UNAME] = $infoArr[CountryWarCrossUserField::UNAME];
		$needInfoArr[CountryWarInnerWorshipField::HTID] = $infoArr[CountryWarCrossUserField::HTID];
		$needInfoArr[CountryWarInnerWorshipField::FIGHT_FORCE] = $infoArr[CountryWarCrossUserField::FIGNT_FORCE];
		$needInfoArr[CountryWarInnerWorshipField::VIP] = $infoArr[CountryWarCrossUserField::VIP];
		$needInfoArr[CountryWarInnerWorshipField::LEVEL] = $infoArr[CountryWarCrossUserField::LEVEL];
		$needInfoArr[CountryWarInnerWorshipField::VA_EXTRA]= array();
		if( isset( $infoArr[CountryWarCrossUserField::VA_EXTRA]['dress'] ) )
		{
			$needInfoArr[CountryWarInnerWorshipField::VA_EXTRA]['dress'] = $infoArr[CountryWarCrossUserField::VA_EXTRA]['dress'];
		}
		
		return $needInfoArr;
		
	}
	
	private function insert($insertArr)
	{
		$ret = CountryWarWorshipDao::insertInfo($insertArr);
		if( $ret[DataDef::AFFECTED_ROWS] <= 0 )
		{
			Logger::fatal('insert failed');
		}
	}	
	
	public function getWorshipInfo()
	{
		return $this->worship;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */