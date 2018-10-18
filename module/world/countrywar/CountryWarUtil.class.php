<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarUtil.class.php 218826 2015-12-31 03:04:21Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarUtil.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-31 03:04:21 +0000 (Thu, 31 Dec 2015) $
 * @version $Revision: 218826 $
 * @brief 
 *  
 *  获取场景，serverid等一些其他操作，全静态
 *  
 **/
class CountryWarUtil
{
	
	public static function getTeamIdByBattleId($battleId)
	{
		return intval( $battleId/CountryWarConf::ROOM_MAX/CountryWarConf::BATTLE_MAX );
	}
	
	public static function checkTransferId($transferId,$side)
	{
		$transferIdArr = self::getTransferIdArrBySide($side);
		if( !in_array( $transferId , $transferIdArr) )
		{
			throw new InterException( 'invalid transferId:%s for side:%s',$transferId,$side );
		}
	}
	
	public static function cprRankMemberAudition( $memberA, $memberB )
	{
		$sortKeys = array( 
				CountryWarCrossUserField::AUDITION_POINT, 
				CountryWarCrossUserField::SERVER_ID, 
				CountryWarCrossUserField::PID,
		);//就整一样的排序吧
		foreach ( $sortKeys as $key )
		{
			if ( $memberA[ $key ] == $memberB[ $key ] )
			{
				if( $key == CountryWarCrossUserField::PID )
				{
					return 0;
				}
				continue;
			}
			return $memberA[ $key ] > $memberB[ $key ]? -1:1;
		}
	}
	
	public static function cprRankMemberSupport( $memberA, $memberB )
	{
		$sortKeys = array(
				CountryWarCrossUserField::FANS_NUM,
				CountryWarCrossUserField::AUDITION_POINT,
				CountryWarCrossUserField::SERVER_ID,
				CountryWarCrossUserField::PID,
		);//就整一样的排序吧
		
		foreach ( $sortKeys as $key )
		{
			if ( $memberA[ $key ] == $memberB[ $key ] )
			{
				if( $key == CountryWarCrossUserField::PID )
				{
					return 0;
				}
				continue;
			}
			return $memberA[ $key ] > $memberB[ $key ]? -1:1;
		}
		
		
	}
	
	public static function cprRankMemberFinaltion( $memberA, $memberB )
	{
		$sortKeys = array( 
				CountryWarCrossUserField::FINAL_POINT, 
				CountryWarCrossUserField::SERVER_ID, 
				CountryWarCrossUserField::PID,
		);//就这么一样的排序吧
		foreach ( $sortKeys as $key )
		{
			if ( $memberA[ $key ] == $memberB[ $key ] )
			{
				if( $key == CountryWarCrossUserField::PID )
				{
					return 0;
				}
				continue;
			}
			return $memberA[ $key ] > $memberB[ $key ]? -1:1;
		}
	}
	
	static function getFinalBattleIdByTeamId($teamId)
	{
		if( $teamId <= 0 )
		{
			return 0;
		}
		else 
		{
			return $teamId*CountryWarConf::ROOM_MAX*CountryWarConf::BATTLE_MAX;
		}
	}
	
	/**
	 * 获取场景，也就是哪个lc
	 * @throws InterException
	 * @return string
	 */
	static function getScene()
	{
		if( empty( PlatformConfig::$CONTRYWAR_CROSS_GROUP ) )
		{
			return CountryWarScene::INNER;
		}
		$curGroup = RPCContext::getInstance()->getFramework()->getGroup();
		if( empty( $curGroup ) )
		{
			throw new InterException( 'no group info' );
		}
		Logger::debug( 'curGroup:%s', $curGroup );
		if( !in_array( $curGroup , PlatformConfig::$CONTRYWAR_CROSS_GROUP ) )
		{
			return CountryWarScene::INNER;
		}
		else
		{
			return CountryWarScene::CROSS;
		}
	}	
	
	static function isInnerScene()
	{
		$scene = self::getScene();
		return CountryWarScene::INNER == $scene;
	}
	
	static function isCrossScene()
	{
		$scene = self::getScene();
		return CountryWarScene::CROSS == $scene;
	}
	
	static function isCrossGroup()
	{
		if( empty( PlatformConfig::$CONTRYWAR_CROSS_GROUP ) )
		{
			return false;
		}
		$curGroup = RPCContext::getInstance()->getFramework()->getGroup();
		if( empty( $curGroup ) )
		{
			return false;
		}
		if( !in_array( $curGroup , PlatformConfig::$CONTRYWAR_CROSS_GROUP ) )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	static function isStage($stage)
	{
		$nowStage = CountryWarConfig::getStageByTime(time());
		
		if( $stage != $nowStage )
		{
			return false;
		}
		return true;
	}
	
	static function checkTeamId( $serverId = NULL )
	{
		if( empty( $serverId ) )
		{
			$serverId = Util::getServerIdOfConnection();
		}
		$teamObj = CountryWarTeamObj::getInstance();
		$teamId = $teamObj->getTeamIdByServerId($serverId);
		if( $teamId <= 0 )
		{
			throw new InterException( 'no team id for server:%s', $serverId );
		}
		return true;
	
	}
	
	static function isTeamIdRight( $serverId = NULL )
	{
		if( empty( $serverId ) )
		{
			$serverId = Util::getServerIdOfConnection();
		}
		$teamObj = CountryWarTeamObj::getInstance();
		$teamId = $teamObj->getTeamIdByServerId($serverId);
		if( $teamId <= 0 )
		{
			return false;
		}
		return true;
	
	}
	
	static function isUserLvRight( $uid )
	{
		$user = EnUser::getUserObj($uid);
		$lv = $user->getLevel();
		$needLv = CountryWarConfig::reqLevel();
		if( $lv < $needLv )
		{
			Logger::warning( 'user lv low' );
			return false;
		}
		return true;
	}
	
	static function checkInnerBasicInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		if( !self::isUserLvRight($uid) )
		{
			throw new FakeException('low lv');
		}
		if( !self::isTeamIdRight($serverId) )
		{
			throw new FakeException( 'invalid teamId' );
		}
	}
	
/* 	static function isBattleIdRight( $serverId, $pid, $battleId )
	{
		$teamRoomId = CountryWarCrossUser::getInstance($serverId, $pid)->getTeamRoomId();
		$countryId = CountryWarCrossUser::getInstance($serverId, $pid)->getCountryId();
		$needBattleId = self::getBattIdByTeamRoomIdCountryId($teamRoomId, $countryId);
		if( $battleId != $needBattleId )
		{
			Logger::fatal('battleId:%s,needBattleId:%s, not equal,serverId:%s,pid:%s', $battleId,$needBattleId,$serverId,$pid);
			return false;
		}
		return true;
	}
	 */
	static function getBattIdByTeamRoomIdCountryId( $teamRoomId, $countryId )
	{
		if( $countryId > CountryWarConf::BATTLE_MAX-1 )
		{
			throw new InterException( 'countryId:%s', $countryId );
		}
		
		return $teamRoomId*CountryWarConf::BATTLE_MAX + $countryId;
	}
	
	static function getMyInnerDbFromSession( $serverId, $pid )
	{
		$sessInfo = RPCContext::getInstance()->getSession(CountryWarSessionKey::MY_INNER_DB);
		if( empty( $sessInfo ) )
		{
			return array();
		}
		else
		{
			return $sessInfo;
		}
	}
	
	static function getCrossDbName()
	{
		return CountryWarDef::CROSS_DB_PRE.PlatformConfig::PLAT_NAME;
	}
	
	/**
	 * 获取分组所对应的特定的crosslc的group
	 * @param unknown $teamId
	 * @return multitype:string
	 */
	static function getProperGroupByTeamId( $teamId )
	{
		$index = $teamId%count(PlatformConfig::$CONTRYWAR_CROSS_GROUP);
		return PlatformConfig::$CONTRYWAR_CROSS_GROUP[$index];
	}
	
	/**
	 * 获取某个分组要登录的crosslc的ip、port
	 * @param unknown $teamId
	 * @return multitype:unknown string
	 */
	static function getHostInfoByTeamId( $teamId )
	{
		$gameGroup = self::getProperGroupByTeamId($teamId);
		$path = CountryWarConf::ZK_PATH_LC.$gameGroup;
		$arrServerInfo = Util::getZkInfo($path);
		$serverIp = $arrServerInfo["wan_host"];
		$port = $arrServerInfo["wan_port"];
		Logger::debug('getHostInfo, serverIp: %s, port: %s', $serverIp, $port);
		
		return array($serverIp, $port);
	}
	
	/**
	 * 获取某个分组的要用的线程id，串化，比如分房间
	 * @param unknown $teamId
	 * @return number
	 */
	static function getThId( $teamId )
	{
		return $teamId%10 +1;
	}
	
	/**
	 * 根据分组id获得所有可能的teamRoomId
	 * @param unknown $teamId
	 * @throws InterException
	 * @return multitype:number
	 */
	static function getTeamRoomRange( $teamId )
	{
		if( $teamId <= 0 )
		{
			throw new InterException( 'invalid teamid:%s', $teamId );
		}
		$teamRoomIdBegin = $teamId*CountryWarConf::ROOM_MAX;
		$teamRoomIdEnd = $teamRoomIdBegin + CountryWarConf::ROOM_MAX - 1;
		
		return array($teamRoomIdBegin, $teamRoomIdEnd);
	}
	
	/**
	 * 根据现在的房间数和已经报名的人数获得还需要多少房间
	 * @param unknown $roomNum
	 * @param unknown $signNum
	 * @return number
	 */
	static function getMoreRoomNum( $roomNum,$signNum )
	{
		$needMoreRoomNum = $signNum/(CountryWarConfig::battleMaxNum()*(UNIT_BASE-CountryWarConf::SPACE_LEFT_PERCENT)/UNIT_BASE) - $roomNum;
		if( $needMoreRoomNum <= 0 )
		{
			return 0;
		}
		return ceil( $needMoreRoomNum );
	}
	
	static function getTeamRoomId($teamId,$roomId)
	{
		if( $roomId > CountryWarConf::ROOM_MAX-1 )
		{
			throw new InterException( 'too much room in one team:%s', $roomId );
		}
		$teamRoomId = $teamId*CountryWarConf::ROOM_MAX + $roomId;
		
		return $teamRoomId; 
	}
	
	static function getBattIdArr( $teamRoomId )
	{
		$teamId = CountryWarUtil::getTeamIdByTeamRoomId($teamRoomId);
		$arrBattleId[] = CountryWarUtil::getFinalBattleIdByTeamId($teamId);//$teamRoomId*CountryWarConf::BATTLE_MAX + 0;
		foreach ( CountryWarCountryId::$ALL as $id )
		{
			$arrBattleId[] = $teamRoomId*CountryWarConf::BATTLE_MAX + $id;
		}
		
		return $arrBattleId;
	}
	
	static function getSerialKey( $serverId, $pid )
	{
		return CountryWarMemKey::SERIAL.$serverId.'_'.$pid;
	}
	static function getTokenKey( $serverId, $pid )
	{
		return CountryWarMemKey::TOKEN.$serverId.'_'.$pid;
	}
	static function getSerialInfo( $db )
	{
		return array( 'db' => $db );
	}
	static function getTokenString( $serverId, $pid )
	{
		return md5($serverId.'_'.$pid.'_'.Util::getTime().'_'.rand(0,1000));
	}
	
	static function crossMemKeyExist($serialKey)
	{
		$info = self::getCrossMem($serialKey);
		if( !empty( $info ) )
		{
			Logger::warning('cross serial key exist,%s',$serialKey);
			return true;
		}
		return false;
	}
	
	static function getCrossMem( $key )
	{
		McClient::setDb(self::getCrossDbName());
		$info = McClient::get($key);
		
		return $info;
	}
	
	static function setCrossMem($key, $info, $expiredTime)
	{
		McClient::setDb(self::getCrossDbName());
		$info = McClient::set($key, $info,$expiredTime);
	}
	
	static function delCrossMem( $key )
	{
		McClient::setDb(self::getCrossDbName());
		$info = McClient::del($key);
	}
	
	static function getServerIdPidFromSession()
	{
		$serverId = RPCContext::getInstance()->getSession( CountryWarSessionKey::MY_INNER_SERVERID );
		$pid = RPCContext::getInstance()->getSession( CountryWarSessionKey::MY_INNER_PID );
		if( empty( $serverId ) || empty( $pid ) )
		{
			throw new InterException('err serverId:%s, pid:%s', $serverId, $pid);
		}
		return array( $serverId, $pid );
	}
	
	static function getEnterLockKey($teamId)
	{
		return CountryWarLockKey::ENTER.$teamId;
	}
	
	static function getQuitAndLeaveWaitTime($quitBattleTime, $leaveBattleTime )
	{
		$curTime = Util::getTime();
		$quitWaitTime = ($quitBattleTime + CountryWarConfig::joinCd())-$curTime;
		$leaveWaitTime = ($leaveBattleTime + CountryWarConfig::joinCd())-$curTime;
		$quitWaitTime = $quitWaitTime<0? 0:$quitWaitTime;
		$leaveWaitTime = $leaveWaitTime<0? 0:$leaveWaitTime;
		
		return array($quitWaitTime, $leaveWaitTime);
	}
	
	static function getFinalSideDistributeArr( $checkTime )
	{
		$seed = $roundStartTime = CountryWarConfig::roundStartTime($checkTime);
		$arrRandNum = Util::pseudoRand($seed, 1);
		$randNumFirst = $arrRandNum[0];
		$manualConf = CountryWarConfig::manualCountryRatioArr();
		$index = 9999;
		if( $randNumFirst%UNIT_BASE  < CountryWarConfig::randomCountryRatio())
		{
			$index = $randNumFirst%count($manualConf);
		}
		else 
		{
			$sum = 0;
			foreach ($manualConf as $oneManualIndex => $ratioInfo)
			{
				$sum += $ratioInfo[2];
				$manualConf[$oneManualIndex][] = $sum;
			}
			$dropPlace = $randNumFirst%$sum;
			foreach ($manualConf as $oneManualIndex => $ratioInfo)
			{
				if( $dropPlace <= $ratioInfo[3] )
				{
					$index = $oneManualIndex;
					break;
				}
			}
			
		}
		$distributeHalf = array(
				$manualConf[$index][0],
				$manualConf[$index][1],
		);
		$distributeNextHalf = array_diff(CountryWarCountryId::$ALL, $distributeHalf);
		$ret = array(CountryWarConf::SIDE_A => $distributeHalf, CountryWarConf::SIDE_B=> array_merge( $distributeNextHalf ));
		Logger::debug('final distribute:%s',$ret);
		return $ret;
	}
	
	static function getFinalSideByCountryId($checkTime, $countryId)
	{
		$disArr = self::getFinalSideDistributeArr($checkTime);
		if( in_array( $countryId , $disArr[CountryWarConf::SIDE_A]) )
		{
			return CountryWarConf::SIDE_A;
		}
		elseif( in_array( $countryId , $disArr[CountryWarConf::SIDE_B]) )
		{
			return CountryWarConf::SIDE_B;
		}
		else 
		{
			throw new InterException( 'fuck' );
		}
	}

	static function getTeamRoomIdByBattleId( $battleId )
	{
		return intval( $battleId/CountryWarConf::BATTLE_MAX );
	}
	
	static function getCountryIdByBattleId($battleId)
	{
		return intval( $battleId%(CountryWarConf::BATTLE_MAX) );
	}

	static function getResourceSideDbKey( $side )
	{
		if( $side == CountryWarConf::SIDE_A )
		{
			$suffix = 'a';
		}
		else
		{
			$suffix = 'b';
		}
		return CountryWarDef::RESOURCE_PRE.$suffix;
	}
	
	static function getOppositeSide( $side )
	{
		$both = CountryWarConf::$BOTH_SIDE;
		$opSide = current( $both );
		if( $opSide == $side )
		{
			$opSide = next( $both );
		}
		return $opSide;
	}
	
	static function getMemAddKey($battleId)
	{
		return CountryWarMemKey::RANK_ADD.$battleId;
	}
	static function getMemRankKey($battleId)
	{
		return CountryWarMemKey::RANK_SET.$battleId;
	}
	static function getMemAddKeyWhenMarkUsers($battleId)
	{
		return CountryWarMemKey::MARK_USER_ADD.$battleId;
	}
	
	static function needCreateRoomRightNow( $needMoreRoomNum )
	{
		return $needMoreRoomNum >= CountryWarConf::NEED_CREATE_ROOM_RANGE;
	}
	static function checkFinalMembers($finalMembers)
	{
		if(!empty( $finalMembers ) )
		{
			throw new InterException( 'finalmembers not empty:%s', $finalMembers );
		}
	}
	static function checkBattleId($battleId)
	{
		if( empty( $battleId ) )
		{
			/* $backtrace = debug_backtrace();
			array_shift($backtrace); */
			throw new InterException( 'no battleId' );
		}
	}

	static function checkRecoverPara($percent)
	{
		$recoverRangeArr = CountryWarConfig::recoverRangeArr();
		if( $percent <$recoverRangeArr[0] || $percent > $recoverRangeArr[1] )
		{
			throw new InterException( 'percent err:%s, range:%s', $percent, $recoverRangeArr);
		}
	}
	
	static function checkStage($checkTime,$checkStage)
	{
		$realStage = CountryWarConfig::getStageByTime($checkTime);
		if($realStage!=$checkStage)
		{
			throw new InterException( 'stage err,realstage:%s,checkstage:%s ',$realStage, $checkStage );
		}
	}
	
	static function checkSide($side)
	{
		if(  $side != CountryWarConf::SIDE_A && $side != CountryWarConf::SIDE_B)
		{
			throw new FakeException( 'invalid side:%s',$side );
		}
	}
	
	static function getServerIdAndPidFromInner()
	{
		$serverId = Util::getServerIdOfConnection();
		$pid = EnUser::getUserObj(RPCContext::getInstance()->getUid())->getPid();
		
		return array($serverId, $pid); 
	}
	
	static function checkCountryId($countryId)
	{
		if( !in_array( $countryId , CountryWarCountryId::$ALL) )
		{
			throw new FakeException( 'invalid countryId:%s',$countryId );
		}
	}
	
	static function isValidCountryId( $countryId )
	{
		return in_array( $countryId , CountryWarCountryId::$ALL);
	}
	
	static function getTeamIdByTeamRoomId($teamRoomId)
	{
		return intval( $teamRoomId/CountryWarConf::ROOM_MAX );
	}
	
	static function getTransferIdBySide( $side )
	{
		$rnum = rand(0,1);
		if( $rnum == 0 )
		{
			 $rnum = 0;
		}
		else
		{
			$rnum = 3;
		}
		return CountryWarConf::$TRANSFERARR[$side][$rnum];
	}
	
	static function getTransferIdArrBySide( $side )
	{
		if( !isset( CountryWarConf::$TRANSFERARR[$side] ) )
		{
			throw new InterException( 'invalid side:%s',$side );	
		}
		return CountryWarConf::$TRANSFERARR[$side];
	}
	
	static function isFinalBattleId( $id )
	{
		if( $id%CountryWarConf::BATTLE_MAX == 0 )
		{
			return true;
		}
		return false;
	}
	
	static function isAuditionBattleId( $id )
	{
		if( $id%CountryWarConf::BATTLE_MAX > 0 )
		{
			return true;
		}
		return false;
	}
	
	static function canRecoverConsiderCurCondition()
	{
		$hpRageInfo = RPCContext::getInstance()->getSession( CountryWarSessionKey::HP_RAGE_INFO );
		if( empty( $hpRageInfo ) )
		{
			Logger::debug('empty session no need to recover');
			return false;
		}
		foreach ( $hpRageInfo as $index => $oneHeroInfo)
		{
			if( $oneHeroInfo['currHp'] < $oneHeroInfo['maxHp'] || $oneHeroInfo['currRage'] < $oneHeroInfo['initRage'] )
			{
				return true;
			}
		}
		Logger::debug('no one need to recover:%s', $hpRageInfo);
		return false;
	}
	
	static function recoverHpRageInSession()
	{
		$hpRageInfo = RPCContext::getInstance()->getSession( CountryWarSessionKey::HP_RAGE_INFO );
		if( empty($hpRageInfo) )
		{
			throw new InterException( 'no hprageinfo in session, set first' );
		}
		foreach ( $hpRageInfo as $index => $oneHeroInfo)
		{
			$hpRageInfo[$index]['currHp'] = $oneHeroInfo['maxHp'];
			$hpRageInfo[$index]['currRage'] = $oneHeroInfo['initRage'];
		}
		RPCContext::getInstance()->setSession(CountryWarSessionKey::HP_RAGE_INFO, $hpRageInfo);
		
		return $hpRageInfo;
	}
	
	static function setHpRageFromLcToSession($hpRageInfo)
	{
		foreach ( $hpRageInfo as $index => $oneHeroInfo)
		{
			if( count( $oneHeroInfo ) != 5 )
			{
				throw new InterException( 'invalid para:%s', $oneHeroInfo );
			}
		}
		RPCContext::getInstance()->setSession(CountryWarSessionKey::HP_RAGE_INFO, $hpRageInfo);
	}
	
	static function isAutoRecoverBySession()
	{
		$onOrOff = RPCContext::getInstance()->getSession(CountryWarSessionKey::AUTO_RECOVER);
		if(  $onOrOff == CountryWarConf::AUTO_RECOVER_ON )
		{
			Logger::debug('auto recover on');
			return true;
		}
		Logger::debug('auto recover off');
		return false;
	}
	
	static function needRecover($recoverPara)
	{
		$hpRageInfo = RPCContext::getInstance()->getSession( CountryWarSessionKey::HP_RAGE_INFO );
		if( empty($hpRageInfo) )
		{
			throw new InterException( 'no hprageinfo in session, set first' );
		}
		$totalCurHp = 0;
		$totalMaxHp = 0;
		foreach ( $hpRageInfo as $index => $oneHeroInfo)
		{
			$totalCurHp += $oneHeroInfo['currHp'];
			$totalMaxHp += $oneHeroInfo['maxHp'];
		}
		if( $totalCurHp/$totalMaxHp < $recoverPara/UNIT_BASE )
		{
			Logger::debug('need recover by check percent:%s',$hpRageInfo );
			return true;
		}
		Logger::debug('no need recover by check percent:%s',$hpRageInfo );
		return false;
	}
	
	/**
	 * 通知crosslc
	 * @param unknown $teamId
	 * @param unknown $method
	 * @param unknown $argsArr
	 * @return boolean
	 */
	static function notifyCrossLc( $thid, $method, $argsArr )
	{
		Logger::trace( 'notifyCrossLc,args:%s,%s,%s', $thid, $method, $argsArr );
		$teamId = $argsArr[0];
		$group = self::getProperGroupByTeamId( $teamId );
		$proxy = new ServerProxy();
		$token=RPCContext::getInstance ()->getFramework ()->getLogid () + 1000;
		$proxy->init($group,$token );
		try
		{
			$proxy->asyncExecuteRequest($thid,$method,$argsArr);
		}
		catch (Exception $e)
		{
			Logger::warning('notifyCrossLc failed! teamid:%s, group:%s, method:%s, args:%s', $teamId, $group, $method,$argsArr);
			return false;
		}
		Logger::info('notifyCrossLc success! teamid:%s, group:%s, method:%s, args:%s', $teamId, $group, $method,$argsArr);
		return true;
	}
	
	static function notifyLcCreate( $teamRoomId, $nowRoomNum )
	{
		$battleConfig = CountryWarConfig::getCreateConfig(CountryWarStage::AUDITION);
		$startTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::AUDITION);
		$battleIdArr = self::getBattIdArr( $teamRoomId );
		try
		{
			$proxy = new PHPProxy('lcserver');
			$frameGroup = RPCContext::getInstance()->getFramework()->getGroup();
			if( empty( $frameGroup ) )
			{
				$teamId = self::getTeamIdByTeamRoomId($teamRoomId);
				$group = self::getProperGroupByTeamId( $teamId );
				$proxy->setGroup( $group );
			}
			
			foreach ( $battleIdArr as $id )
			{
				if( CountryWarUtil::isFinalBattleId( $id ) )
				{
					if( $nowRoomNum == 0 )
					{
						$finalStartTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::FINALTION);
						$finalBattleConfig = CountryWarConfig::getCreateConfig(CountryWarStage::FINALTION);
						$ret = $proxy->createCountryBattle($id, $finalStartTime, $finalBattleConfig);
					}
					else 
					{
						$ret = $id;
						continue;
					}
				}
				else
				{
					$ret = $proxy->createCountryBattle($id, $startTime, $battleConfig);
				}
				if( $ret != $id )
				{
					Logger::fatal('notifyLcCreate failed! id:%s',$id);
					return false;
				}
			}
		}catch (Exception $e)
		{
			Logger::fatal('notifyLcCreate failed!');
			return false;
		}
	
		return true;
	}
	
	static function notifyLcJoin($uuid,$battleId, $transferId, $battleData)
	{
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->joinCountryBattle($uuid, $battleId, $transferId, $battleData);
	
		return $ret;
	}
	
	static function notifyLcRecoverByUser()
	{
		try 
		{
			$uuid = RPCContext::getInstance()->getUid();
			$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
			$proxy = new PHPProxy ('lcserver');
			$ret = $proxy->hpRecoverCountryBattleByUser($uuid, $battleId);
		}
		catch( Exception $e )
		{
			return 'fail';
		}
		
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
