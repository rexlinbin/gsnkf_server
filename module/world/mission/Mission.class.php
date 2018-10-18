<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mission.class.php 227721 2016-02-16 13:00:02Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/Mission.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-02-16 13:00:02 +0000 (Tue, 16 Feb 2016) $
 * @version $Revision: 227721 $
 * @brief 
 *  
 **/
class Mission implements IMission
{
	private $uid = null;
	function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	/* (non-PHPdoc)
	 * @see IMission::getMissionInfo()
	 */
	public function getMissionInfo() {
		// TODO Auto-generated method stub
		$teamId = MissionLogic::getTeamId();
		if( $teamId <= 0 )
		{
			$ret[MissionFrontField::TEAMID] = $teamId;
			return $ret;
		}
		$userInfo = MissionLogic::getMissionUserInfo($this->uid);
		$userInfo[MissionFrontField::TEAMID] = $teamId;
		$configInfo = MissionLogic::getMissionConfig();
		$userInfo[MissionFrontField::CONFIG_INFO] = $configInfo;
		$userInfo[MissionFrontField::RANK] = MissionLogic::getMyRankFreeTime($this->uid);
		
		return $userInfo;
	}
	
	/* (non-PHPdoc)
	 * @see IMission::getMissionInfoLogin()
	*/
	public function getMissionInfoLogin() {
		
		$teamId = MissionLogic::getTeamId();
		if( $teamId <= 0 )
		{
			$ret[MissionFrontField::TEAMID] = $teamId;
			return $ret;
		}
		$userInfo = array();//$userInfo = MissionLogic::getMissionUserInfo($this->uid);
		$userInfo[MissionFrontField::TEAMID] = $teamId;
		$configInfo = MissionLogic::getMissionConfig();
		$userInfo[MissionFrontField::CONFIG_INFO] = $configInfo;
		
		$innerUserObj = MissionUserObj::getInstance($this->uid);
		$dayRewardTime = $innerUserObj->getDayRewardTime();
		$userInfo[MissionFrontField::DAY_REWATD_TIME] = $dayRewardTime;
		
		return $userInfo;
	}
	
	/* (non-PHPdoc)
	 * @see IMission::doMissionItem()
	 */
	public function doMissionItem($itemArr) {
		// TODO Auto-generated method stub
		MissionLogic::doMission(MissionType::FROM_FRONT,$this->uid, MissionType::ITEM,$itemArr );
	}

	/* (non-PHPdoc)
	 * @see IMission::doMissionGold()
	 */
	public function doMissionGold($goldNum) {
		// TODO Auto-generated method stub
		MissionLogic::doMission(MissionType::FROM_FRONT,$this->uid, MissionType::GOLD, $goldNum );
	}

	/* (non-PHPdoc)
	 * @see IMission::getRankList()
	 */
	public function getRankList() {
		// TODO Auto-generated method stub
		$returnRankList = array( MissionFrontField::RANK_LIST => array(), MissionFrontField::MYRANK =>array() );
		$rankList =  MissionLogic::getRankList($this->uid);
		if( isset($rankList[MissionFrontField::RANK_LIST] ) )
		{
			foreach ($rankList[MissionFrontField::RANK_LIST] as $index => $data)
			{
				$missionDress = array();
				if( isset( $data[MissionDBField::CROSS_VA_USER][MissionVAField::MISSION_DRESS] ) )
				{
					$missionDress =  $data[MissionDBField::CROSS_VA_USER][MissionVAField::MISSION_DRESS];
					unset( $data[MissionDBField::CROSS_VA_USER] );
				}
				
				$returnRankList[MissionFrontField::RANK_LIST][$index + 1] = $data;
				$returnRankList[MissionFrontField::RANK_LIST][$index + 1][MissionVAField::MISSION_DRESS] = $missionDress;
			}
		}
		
		if( isset($rankList[MissionFrontField::MYRANK][MissionFrontField::RANK] ) )
		{
			$returnRankList[MissionFrontField::MYRANK] = $rankList[MissionFrontField::MYRANK];
			if (is_numeric($rankList[MissionFrontField::MYRANK][MissionFrontField::RANK])) 
			{
				$returnRankList[MissionFrontField::MYRANK][MissionFrontField::RANK] = $rankList[MissionFrontField::MYRANK][MissionFrontField::RANK] +1;
			}
			else 
			{
				$returnRankList[MissionFrontField::MYRANK][MissionFrontField::RANK] = $rankList[MissionFrontField::MYRANK][MissionFrontField::RANK];
			}
		}
		
		return $returnRankList;
	}
	/* (non-PHPdoc)
	 * @see IMission::receiveDayReward()
	 */
	public function receiveDayReward() {
		// TODO Auto-generated method stub
		MissionLogic::receiveDayReward($this->uid);
	}
		
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
