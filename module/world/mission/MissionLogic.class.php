<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionLogic.class.php 230751 2016-03-03 08:01:50Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-03 08:01:50 +0000 (Thu, 03 Mar 2016) $
 * @version $Revision: 230751 $
 * @brief 
 *  
 **/
class MissionLogic
{
	static function getMissionUserInfo($uid)
	{		
		$missUserObj = MissionUserObj::getInstance($uid);
		$missUserInfo = $missUserObj->getUserInfo();
		
		$ret[MissionFrontField::DONATE_ITEM_NUM] = $missUserInfo[MissionDBField::DONATE_ITEM_NUM];
		$ret[MissionFrontField::SPEC_MISS_FAME] = $missUserInfo[MissionDBField::SPEC_MISS_FAME];
		$ret[MissionFrontField::DAY_REWATD_TIME] = $missUserInfo[MissionDBField::DAY_REWATD_TIME];
		$ret[MissionFrontField::MISSION_INFO] = array();
		if( isset( $missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO] ) )
		{
			$ret[MissionFrontField::MISSION_INFO] = $missUserInfo[MissionDBField::VA_MISSION_USER][MissionVAField::MISSION_INFO];
		}
		
		return $ret;
	}
	
	static function getTeamId()
	{
		$serverId = Util::getServerIdOfConnection();
		$teamId = MissionTeamMgr::getInstance($serverId)->getTeamIdByServerId();
		return $teamId;
	}
	
	static function getMissionConfig()
	{
		$config = array();
		$missConObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		$config[MissionFrontField::RANK_REWARDARR] = $missConObj->getConfData(MissionCsvField::RANK_REWARDARR);
		$config[MissionFrontField::DAY_REWARDARR] = $missConObj->getConfData(MissionCsvField::DAY_REWARDARR);
		$config[MissionFrontField::MISSION_BACKGROUNDARR] = $missConObj->getConfData(MissionCsvField::MISSION_BACKGROUNDARR);
		
		return $config;
	}
	
	static function getRankList($uid)
	{
		$rankInfo[MissionFrontField::RANK_LIST] = array();
		$rankInfo[MissionFrontField::MYRANK][MissionFrontField::FAME] = 0;
		$rankInfo[MissionFrontField::MYRANK][MissionFrontField::RANK] = -1;
		
		$serverId = Util::getServerIdOfConnection();
		$missTeamMgr = MissionTeamMgr::getInstance($serverId);
		$teamId = $missTeamMgr->getTeamIdByServerId();
		if( $teamId <= 0 )
		{
			Logger::warning('user not in any teamid:%s', $teamId);
			return $rankInfo;
		}
		
		$pid = EnUser::getUserObj($uid)->getPid();
		
		$missConObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		if( !$missConObj->isConfValid() )
		{
			return $rankInfo;
		}
		
		$time = $missConObj->getStartTime();
		$missCrossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
		$rankList = MissionCrossUserObj::getRankList($teamId,MissionConf::TOPNUM, MissionConf::VALID_TIME);//这里拉了100名
		
		$showRankArr = $missConObj->getConfData(MissionCsvField::MISSION_SHOWRANKARR);

		$canDbTimes = 0;
		$needRankList = array_slice($rankList, 0, MissionConf::FRONT_TOPNUM);
		foreach ( $showRankArr as $index => $aRank )
		{
			if( $aRank < 1 )
			{
				throw new ConfigException( 'invalid rank:%s', $aRank );
			}
			
			if( isset( $rankList[$aRank-1] ) )
			{
				$needRankList[$aRank-1] = $rankList[$aRank-1];
			}
			else
			{
				if( count( $rankList ) >= MissionConf::TOPNUM && $canDbTimes < MissionConf::CAN_DB_TIMES )
				{
					$canDbTimes ++;
					Logger::fatal('getParticularRank from db!!!');
					 $info = self::getParticularRankInfo($aRank);
					 Logger::debug('db particular once');
					 if( !empty( $info ) )
					 {
					 	$needRankList[$aRank-1] = $info;
					 }
					 else
					 {
					 	break;
					 }
				}
				else
				{
					//$needRankList[$aRank-1] = array();
					break;
				}
			}
		}
		
		$myRank = -1;
		foreach ($rankList as $indexAll => $rankInfoAll)
		{
			if( $rankInfoAll[MissionDBField::CROSS_PID] == $pid && $rankInfoAll[MissionDBField::CROSS_SERVERID] == $serverId )
			{
				$myRank = $indexAll;
			}
		}
		
		$serverIdArr = Util::arrayExtract( $rankList , MissionDBField::CROSS_SERVERID);
		$serverNameArr = ServerInfoManager::getInstance()->getArrServerName($serverIdArr);
		
		foreach ( $needRankList as $rank => $rankInfo )
		{
			if( $rankInfo[MissionDBField::CROSS_PID] == $pid && $rankInfo[MissionDBField::CROSS_SERVERID] == $serverId )
			{
				$myRank = $rank;
			}
			$serverName = 'lol';
			if( isset( $serverNameArr[$rankInfo[MissionDBField::CROSS_SERVERID]] ) )
			{
				$serverName = $serverNameArr[$rankInfo[MissionDBField::CROSS_SERVERID]];
			}
			else
			{
				Logger::fatal('err server name: serverid: %s ', $rankInfo[MissionDBField::CROSS_SERVERID]);
			}
			$needRankList[$rank][MissionDBField::CROSS_SERVER_NAME] = $serverName;
			
			unset($needRankList[$rank][MissionDBField::CROSS_PID]);
			unset($needRankList[$rank][MissionDBField::CROSS_SERVERID]);
			unset($needRankList[$rank][MissionDBField::CROSS_UPDATE_TIME]);
		}
		
		$innerUserObj = MissionUserObj::getInstance($uid);
		$curRoundFame = $innerUserObj ->getFame();
		if( $myRank < 0 )
		{	
			// 非捐赠期间
			if( $curRoundFame > 0 && !$missConObj->isMissionTime() )
			{
				$myRank = $missCrossUserObj->getMyRank();
			}
			
			// 在捐赠期间
			if ($curRoundFame > 0 && $missConObj->isMissionTime()) 
			{
				if (!empty($rankList[299]) && $curRoundFame >= $rankList[299][MissionDBField::CROSS_FAME]) 
				{
					$myRank='201-300';
				}
				else if (!empty($rankList[399]) && $curRoundFame >= $rankList[399][MissionDBField::CROSS_FAME]) 
				{
					$myRank='301-400';
				}
				else if (!empty($rankList[499]))
				{
					if ($curRoundFame >= $rankList[499][MissionDBField::CROSS_FAME]) 
					{
						$myRank='401-500';
					}
					else
					{
						$myRank='500+';
					}
				}
			}
		}
		
		$rankInfo[MissionFrontField::RANK_LIST] = $needRankList;
		$rankInfo[MissionFrontField::MYRANK][MissionFrontField::FAME] = $curRoundFame;
		$rankInfo[MissionFrontField::MYRANK][MissionFrontField::RANK] = $myRank;
		
		return $rankInfo;
	}
	
	static function getMyRankFreeTime( $uid )
	{
		$confObj = MissionConObj::getInstance( MissionDef::FIELD_INNER );
		if( !$confObj->isConfValid() || self::getTeamId() <= 0 || $confObj->isMissionTime() )
		{
			return -1;
		}
		$serverId = Util::getServerIdOfConnection();
		$pid = EnUser::getUserObj($uid)->getPid();
		$rank = MissionCrossUserObj::getInstance($pid, $serverId)->getMyRank();
		
		return $rank;
	}
	
	static function doMission($front, $uid, $type, $data )
	{
		if( ($front && !in_array( $type, MissionType::$front) )||(!$front && !in_array( $type, MissionType::$back)) )
		{
			throw new FakeException( 'invalid type:%s', $type );
		}
		
		//====为在没有活动或者分组的情况能够少db，任务期间要换分组，执行脚本将服内刷打跨服的时间点-换分组时间点>半小时
		$sessionInfo = RPCContext::getInstance()->getSession( MissionDef::MISSIONTIME_SESSION_KEY );
		if( !empty( $sessionInfo ) )
		{
			$startTime = $sessionInfo[MissionDef::MISS_STT];
			$endTime = $sessionInfo[MissionDef::MISS_EDT];
			$nextRefTime = $sessionInfo[MissionDef::MISS_NTT];
			if( (Util::getTime() < $startTime || Util::getTime() > $endTime) && Util::getTime() < $nextRefTime )
			{
				Logger::debug('now mission time from session: %s, %s, %s',Util::getTime(), $startTime, $endTime );
				if( !$front )
				{
					return;
				}
				throw new FakeException( 'invalid time' );
			}
		}
		
		$sessionInfoTeamId = RPCContext::getInstance()->getSession( MissionDef::MISSIONTEAMID_SESSION_KEY );
		if( !empty( $sessionInfoTeamId ) )
		{
			$setTime = $sessionInfoTeamId[MissionDef::MISS_TDST];
			$teamId = $sessionInfoTeamId[MissionDef::MISS_TD];
			
			if (!empty($sessionInfo)) 
			{
				$startTime = $sessionInfo[MissionDef::MISS_STT];
				$endTime = $sessionInfo[MissionDef::MISS_EDT];
				if ($setTime < $startTime && $setTime + MissionConf::REF_TEAMID_GAP_TIME >= $startTime) 
				{
					Logger::info('ignore session team id, set time[%s], valid time[%d], start time[%s]', strftime('%Y%m%d %H%M%S', $setTime), MissionConf::REF_TEAMID_GAP_TIME, strftime('%Y%m%d %H%M%S', $startTime));
				}
				else if( Util::getTime() < $setTime + MissionConf::REF_TEAMID_GAP_TIME && $teamId <= 0 )
				{
					Logger::debug('get mission teamid from session: %s, %s', $setTime, $teamId);
					if( !$front )
					{
						return;
					}
					throw new FakeException( 'invalid teamid: %s', $teamId );
				}
			}
		}
		//====为在没有活动或者分组的情况能够少db
		
		$confObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		if(!$confObj->isMissionTime())
		{
			Logger::debug( 'now is now mission time' );
			if( !$front )
			{
				return;
			}
			throw new FakeException( 'invalid time' );
		}
		$user = EnUser::getUserObj($uid);
		$lv = $user->getLevel();
		if( !$confObj->isLevelOkForMission($lv) )
		{
			if( !$front )
			{
				return;
			}
			throw new FakeException( 'invalid lv:%s', $lv );
		}
		
		$validMissionArr = $confObj->getConfData( MissionCsvField::MISSION_IDARR );
		Logger::debug('validMissionArr:%s', $validMissionArr);
		//$validMissionArr = $validMissionArr->toArray();
		
		if( !in_array( $type , $validMissionArr))
		{
			if( !$front )
			{
				return;
			}
		}
		
		$serverId = Util::getServerIdOfConnection();
		$teamId = MissionTeamMgr::getInstance($serverId)->getTeamIdByServerId();
		if( $teamId <= 0 )
		{
			if( $front )
			{
				throw new FakeException( 'invalid teamId: %s, serverId:%s', $teamId, $serverId );
			}
			else 
			{
				return;
			}
			
		}
		
		$innerUserObj = MissionUserObj::getInstance($uid);
		$originalFame = $innerUserObj->getFame();
		
		$ret =false;
		switch ( $type )
		{
			case MissionType::ITEM:
				$ret = self::subItemAddFame($uid, $data);
				break;
			case MissionType::GOLD:
				$ret = self::subGoldAddFame($uid, $data);
				break;
			default:
				if( in_array( $type , MissionType::$back) )
				{
					$ret = self::doMissionBack($uid, $type, $data);
				}
				else 
				{
					Logger::fatal( 'invalid type: %s', $type );
					return;
				}
		}
		if( $ret  )
		{
			$pid = $user->getPid();
			$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
			
			$bag = BagManager::getInstance()->getInstance()->getBag($uid)->update();
			$innerUserObj->update();
			$crossUserObj->update();
			$user->update();
			if( !$front )
			{
				$nowFame = $innerUserObj->getFame();
				RPCContext::getInstance()->sendMsg(array( $uid ), PushInterfaceDef::MISSION_FAME_CHANGE, array( 'deltFame' => $nowFame - $originalFame ));
			}
		}
		
	}
	
	private static function subItemAddFame( $uid, $itemArr )
	{
		$arr = $itemArr;
		$itemArr = array();
		foreach ($arr as $key => $value)
		{
			$itemArr[intval($key)] = intval($value);
		}
		
		$willDonateNum = array_sum( $itemArr );
		$confObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		$itemDonateLimit = $confObj->getDonateNumMax();//做这些乱七八糟的东西的时候有个前提是活动是否处于任务阶段的判定
		$innerUserObj = MissionUserObj::getInstance($uid);
		$alreadyDonateNum = $innerUserObj->getDonateItemNum();
		if( $alreadyDonateNum + $willDonateNum > $itemDonateLimit )
		{
			throw new FakeException( 'already reach the max: %s, %s, %s', $alreadyDonateNum, $willDonateNum, $itemDonateLimit );
		}
		
		$bag = BagManager::getInstance()->getBag($uid);
		$itemMgr = ItemManager::getInstance();
		$arrItemId = array_keys($itemArr);
		
		$gainFame = 0;
		$itemObjArr = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($itemArr as $itemId => $itemNum )
		{
			if( !isset($itemObjArr[$itemId]) )
			{
				throw new FakeException( 'no item: %s', $itemId );
			}
			
			if(!$itemObjArr[$itemId]->canDonate())
			{
				throw new FakeException( 'item cannot donate: %s', $itemId );
			}
			if(!$bag->decreaseItem($itemId, $itemNum))
			{
				throw new FakeException( 'subItem failed, itemId: %s, itemNum:%s', $itemId, $itemNum );
			}
			$deltFame = $itemObjArr[$itemId]->getFame();
			$gainFame += $deltFame*$itemNum;
		}
		
		$user = EnUser::getUserObj($uid);
		$pid = $user->getPid();
		$serverId = Util::getServerIdOfConnection();
		$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
		$fameNow = $innerUserObj->addFame($gainFame);
		$innerUserObj->addDonateItemNum($willDonateNum);
		$crossUserObj->setFame($fameNow);
		$user->addFameNum($gainFame);
		
		return true;
	}
	
	private static function subGoldAddFame( $uid, $gold )
	{
		if( $gold < 0 )
		{
			throw new FakeException( 'negtive:%s', $gold );
		}
		$confObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		if( !$confObj->isGoldLevelExist( $gold ) )
		{
			throw new FakeException( 'no such goldLevel: %s', $gold );
		}
		
		$famePerGold = $confObj->getFamePerGold();
		$gainFame = $gold * $famePerGold;
		$user = EnUser::getUserObj($uid);
		if( !$user->subGold( $gold, StatisticsDef::ST_FUNCKEY_MISSION_DONATE ))
		{
			throw new FakeException( 'lack gold' );
		}
		$innerUserObj = MissionUserObj::getInstance($uid);
		$user = EnUser::getUserObj($uid);
		$pid = $user->getPid();
		$serverId = Util::getServerIdOfConnection();
		$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
		$fameNow = $innerUserObj->addFame($gainFame);
		$crossUserObj->setFame($fameNow);;
		$user->addFameNum($gainFame);
		
		return true;
	}
	
	private static function doMissionBack($uid, $type, $data = 1)
	{
		$limitNum = MissionConObj::getGeneralConf(MissionBts::MISSION_DETAIL,$type, MissionCsvField::MAX_NUM );
		$innerUserObj = MissionUserObj::getInstance($uid);
		$undoNum = $innerUserObj->getUndoNum($type, $limitNum);
		
		if($undoNum <= 0)
		{
			Logger::debug('type: %s , done', $type);
			return false;
		}
		if( $data> $undoNum )
		{
			$data = $undoNum;
		}
		
		$gainFame = MissionConObj::getGeneralConf(MissionBts::MISSION_DETAIL,$type,MissionCsvField::FAME_RECEIVE) * $data;
		$user = EnUser::getUserObj($uid);
		$pid = $user->getPid();
		$serverId = Util::getServerIdOfConnection();
		$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);		
		
		
		$innerUserObj->doMission( $type, $data );
		$fameNow = $innerUserObj->addFame($gainFame);
		$innerUserObj->addSpecFame($gainFame);
		$crossUserObj->setFame($fameNow);
		$user->addFameNum($gainFame);
		
		return true;
	}
	
	static function receiveDayReward($uid)
	{
		$innerUserObj = MissionUserObj::getInstance($uid);
		$dayRewardTime = $innerUserObj->getDayRewardTime();
		if( Util::isSameDay( $dayRewardTime ) )
		{
			throw new FakeException( 'already draw in:%s', $dayRewardTime );
		}
		
		$user = EnUser::getUserObj($uid);
		$pid = $user->getPid();
		$serverId = Util::getServerIdOfConnection();
		$crossUserObj = MissionCrossUserObj::getInstance($pid, $serverId);
		$fame = $innerUserObj->getFame();
		$missTeamMgr = MissionTeamMgr::getInstance($serverId);
		$teamId = $missTeamMgr->getTeamIdByServerId();
		if( empty( $teamId ) )
		{
			throw new FakeException( 'no team, no reward' );
		}
		
		$confObj = MissionConObj::getInstance(MissionDef::FIELD_INNER);
		
		if( $confObj->isRewardTime() )
		{
			$rewardConf = $confObj->getConfData( MissionCsvField::DAY_REWARDARR );
			$time = $confObj->getStartTime();
			Logger::debug('starttime: %s, %s',$time,$rewardConf  );
		}
		else 
		{
			$rewardConf = array();
		}
		
		if( empty( $rewardConf ) )
		{
			throw new FakeException( 'sess == 0 or conf empty' );
		}
		$rank = $crossUserObj->getMyRank();
		if( $rank < 0 )
		{
			$rank = 9999999;
		}
		foreach ( $rewardConf as $index => $rewardInfo )
		{
			if( $rank + 1 >= $rewardInfo[0] )
			{
				$rewardId = $rewardInfo[1];
			}
		}
		
		if( !isset( $rewardId ) )
		{
			throw new FakeException( 'no reward for rank: %s', $rank );
		}
		$rewardDetail = MissionConObj::getGeneralConf(MissionBts::MISSION_REWARD, $rewardId, MissionCsvField::REWARDARR);
		$rewardDetail = $rewardDetail->toArray();
		$innerUserObj->setDayRewardTime(Util::getTime());
		$innerUserObj->update();
		RewardUtil::reward3DArr($uid, $rewardDetail, StatisticsDef::ST_FUNCKEY_MISSION_DAY_REWARD );
		$bag = BagManager::getInstance()->getBag($uid)->update();
		$user->update();
		//TODO检查背包 
	}
	
	public static function getParticularRankInfo( $frontRank )
	{
		if( $frontRank < 1 )
		{
			throw new FakeException( 'invalid rank:%s', $frontRank );
		}
		
		$serverId = Util::getServerIdOfConnection();
		$teamId = MissionTeamMgr::getInstance($serverId)->getTeamIdByServerId();
		$rankInfo = MissionCrossUserObj::getParticularRankInfo($teamId, $frontRank);
		
		return $rankInfo;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
