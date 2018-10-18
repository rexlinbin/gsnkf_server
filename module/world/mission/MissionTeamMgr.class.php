<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionTeamMgr.class.php 215852 2015-12-15 12:18:31Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionTeamMgr.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-15 12:18:31 +0000 (Tue, 15 Dec 2015) $
 * @version $Revision: 215852 $
 * @brief 
 *  
 **/
class MissionTeamMgr
{
	private static $instance = NULL;
	private $teamInfo = NULL;
	private $teamInfoBak = NULL;
	private $serverId = NULL;
	
	static function getInstance( $serverId )
	{
		if( $serverId <= 0 )
		{
			throw new InterException( 'serverId: %s', $serverId );
		}
		
		if( !isset( self::$instance[$serverId] ) )
		{
			self::$instance[$serverId] = new self( $serverId );  
		}
		return self::$instance[$serverId];
	}
	
	static function releaseInstance()
	{
		if( isset( self::$instance ) )
		{
			unset(self::$instance);
		}
	}
	 function __construct( $serverId )
	 {
	 	$this->serverId = $serverId;
	 	$this->teamInfo = MissionDao::getTeamInfoByServerId($this->serverId);
	 	if( empty( $this->teamInfo ) )
	 	{
	 		$this->teamInfo = $this->init();
	 	}
	 	
	 	$this->refresh();
	 }
	 
	 private function init()
	 {
	 	$initArr = array(
	 			MissionDBField::SERVER_ID => $this->serverId,
	 			MissionDBField::TEAMID => 0,
	 			MissionDBField::UPDATE_TIME => 0,
	 	);
	 	
	 	return $initArr;
	 }
	 
	 //如果要改配置的话要清一下updatetime
	 private function refresh()
	 {
	 	$confObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );//这里cross inner都可以
	 	if( $confObj->isConfValid() )
	 	{
	 		$startTime = $confObj->getStartTime();
	 		$sess = $confObj->getSess();
	 		if( $sess <=0 )
	 		{
	 			throw new InterException( 'should not:%s', $sess );
	 		}
	 		if( $this->teamInfo[MissionDBField::UPDATE_TIME] <= $startTime )
	 		{
	 			//这里没有分组也写进去了
	 			$teamMgr = TeamManager::getInstance(ActivityName::MISSION,$sess );
	 			$teamId = $teamMgr->getTeamIdByServerId($this->serverId);
	 			$teamId = $teamId < 0 ? 0:$teamId;
	 			$this->teamInfo[MissionDBField::TEAMID] = $teamId;
	 			$this->teamInfo[MissionDBField::TEAM_UPDATE_TIME] = Util::getTime();
	 			$this->update();
	 		}
	 		$this->setSessionInfo($this->teamInfo[MissionDBField::TEAMID]);
	 	}
	 	else
	 	{
	 		$this->setSessionInfo(0);
	 	}
	 }
	 
	 private function setSessionInfo( $teamId )
	 {
	 	$sessionInfo  = array(
	 			MissionDef::MISS_TD => $teamId,
	 			MissionDef::MISS_TDST => Util::getTime(),
	 	);
	 	RPCContext::getInstance()->setSession(MissionDef::MISSIONTEAMID_SESSION_KEY, $sessionInfo);
	 }
	 private function update()
	 {
	 	if( $this->teamInfo == $this->teamInfoBak )
	 	{
	 		Logger::warning('nothing change: %s', $this->teamInfo);
	 		return;
	 	}
	 	$this->teamInfo[MissionDBField::TEAM_UPDATE_TIME] = Util::getTime();
	 	MissionDao::updateTeamInfo($this->teamInfo);
	 	$this->teamInfoBak = $this->teamInfo;
	 }
	 
	 function getTeamIdByServerId()
	 {
	 	return $this->teamInfo[MissionDBField::TEAMID];
	 }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */