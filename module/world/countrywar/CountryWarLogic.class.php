<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarLogic.class.php 251687 2016-07-15 07:16:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-07-15 07:16:17 +0000 (Fri, 15 Jul 2016) $
 * @version $Revision: 251687 $
 * @brief 
 *  
 *  除参数外的合法性检查及逻辑处理,全静态
 *  
 **/
class CountryWarLogic
{

	/**
	 * 登录的时候获取相关信息的接口
	 * @return multitype:NULL
	 */
	static function getCoutrywarInfoWhenLogin()
	{
		list( $serverId, $pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$ret[CountryWarFrontField::TEAM_ID] = CountryWarTeamObj::getInstance()->getTeamIdByServerId($serverId);
		$ret[CountryWarFrontField::TIME_CONFIG] = CountryWarConfig::timeConfig(Util::getTime());
		
		$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
		$ret[CountryWarFrontField::WORSHIP_TIME] = $innerUser->getWorshipTime();
		
		if( CountryWarConf::ONLINE_TEST )
		{
			if( !in_array( $pid , CountryWarConf::$ONLINE_TEST_VALID_PIDARR) )
			{
				$ret[CountryWarFrontField::TEAM_ID] = CountryWarConf::UNTEAMED;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 进入国战功能的第一个接口
	 * @return string|multitype:multitype: number NULL unknown multitype:multitype:NULL  multitype:  multitype:unknown Ambigous <multitype:unknown , multitype:NULL >  Ambigous <NULL, multitype:, unknown>
	 */
	static function getCoutrywarInfo()
	{
		list( $serverId, $pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId($serverId);
		$stage = CountryWarConfig::getStageByTime(Util::getTime());
		$ret[CountryWarFrontField::TEAM_ID] = $teamId;
		$ret[CountryWarFrontField::STAGE] = $stage;
		$ret[CountryWarFrontField::TIME_CONFIG] = array();
		$ret[CountryWarFrontField::DETAIL] = array();
		$uid = RPCContext::getInstance()->getUid();
		//这里，如果没有资格的话就别往下走了
		if( !CountryWarUtil::isTeamIdRight() || !CountryWarUtil::isUserLvRight($uid) )
		{
			return $ret;
		}
		
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$countryId = $crossUser->getCountryId();
		$detail = array();
		switch ( $stage )
		{
			case CountryWarStage::TEAM:
				break;
			case CountryWarStage::RANGE_ROOM:
			case CountryWarStage::AUDITION:
			case CountryWarStage::SINGUP:
				$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
				$detail[CountryWarFrontField::COUNTRY_ID] = $countryId;
				$detail[CountryWarFrontField::SIGN_TIME] = $crossUser->getSignTime();
				$detail[CountryWarFrontField::COUNTRY_SIGN_NUM] = $teamContent->getAllCountrySignNum();
				if( CountryWarUtil::isValidCountryId($countryId) )
				{
					$detail[CountryWarFrontField::SIDE] = $crossUser->getSide();
				}
				break;
			case CountryWarStage::SUPPORT:
			case CountryWarStage::FINALTION:
				$battleId = CountryWarUtil::getFinalBattleIdByTeamId($teamId);
				$supportStartTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT);
				$detail[CountryWarFrontField::COUNTRY_ID] = $countryId;
				$detail[CountryWarFrontField::FORCE_INFO] = CountryWarUtil::getFinalSideDistributeArr(Util::getTime());
				$detail[CountryWarFrontField::MEMBER_INFO] = CountryWarCrossUser::getTopNByBattleId($battleId,CountryWarRankType::SUPPORT,4,false,false,$supportStartTime);
				$detail[CountryWarFrontField::QUALIFY] = $crossUser->getQualify();
				$detail[CountryWarFrontField::MY_SUPPORT] = self::getMySupport();
				$detail[CountryWarFrontField::SIDE] = 0;
 				if( CountryWarUtil::isValidCountryId($countryId) && $detail[CountryWarFrontField::QUALIFY]>0)
				{
					$detail[CountryWarFrontField::SIDE] = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $countryId);
				}
				
				$teamRoomId = $crossUser->getTeamRoomId();
				if( $teamRoomId > 0 && Util::getTime() > CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT) + 60 )
				{
					//这个地方注意
					//为了解决onBattleEnd并发，这个补救方案助威开始60秒之后开始生效
					//为了解决补救之后缓存没有失效，导致好多玩家在缓存未失效前不停的mark，加了一个mcadd，并在lock成功之后，清掉了排行的缓存
					//另外mcadd的key还加了一个有效时间，这个时间要>排行缓存的有效时间
					$allFinalMember = CountryWarCrossUser::getTopNByBattleId( $battleId,CountryWarRankType::SUPPORT,null,false,false,$supportStartTime );
					$find = false;
					foreach ( $allFinalMember as $oneMember )
					{
						if( $oneMember[CountryWarCrossUserField::TEAM_ROOM_ID] == $teamRoomId && $oneMember[CountryWarCrossUserField::COUNTRY_ID] == $countryId )
						{
							$find = true;
							break;//这是breakforeach
						}
					}
					if( !$find )
					{
						Logger::fatal('failed to find mark users of teamroomId:%s, countryId:%s, try to mark',$teamRoomId,$countryId);
						$auditionBattleId = CountryWarUtil::getBattIdByTeamRoomIdCountryId($teamRoomId, $countryId);
						$auditionQulifyMember = CountryWarCrossUser::getTopNByBattleId( $auditionBattleId,CountryWarRankType::AUDITION,CountryWarConfig::qualifyNumPerAuditionBattle(),false,true,$supportStartTime );
						$markUserAddKey = CountryWarUtil::getMemAddKeyWhenMarkUsers($auditionBattleId);
						McClient::setDb(CountryWarUtil::getCrossDbName());
						$addRet = McClient::add( $markUserAddKey, array(1), CountryWarConf::RANK_LIST_VALID_TIME + 2 );
						if( $addRet == 'STORED' )
						{
							Logger::info('add mark mem key success');
							CountryWarCrossUser::markFinalMembers($battleId, $auditionQulifyMember);
							McClient::setDb(CountryWarUtil::getCrossDbName());
							McClient::del( CountryWarUtil::getMemRankKey($battleId) );
						}
						else
						{
							Logger::warning('add mark mem key fail');
						}						
					}
					////此致 
						////敬礼
				}
				
				break;
				
			case CountryWarStage::WORSHIP:
				$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
				$worship = CountryWarWorshipObj::getInstance();
				$detail = $worship->getWorshipInfo();
				if( !empty( $detail ) )
				{
					if( isset( $detail[CountryWarInnerWorshipField::VA_EXTRA]['dress'] ) )
					{
						$detail[CountryWarFrontField::SERVER_NAME] = ServerInfoManager::getInstance()->getServerNameByServerId($detail[CountryWarInnerWorshipField::SERVER_ID]);
						$detail['dress'] = $detail[CountryWarInnerWorshipField::VA_EXTRA]['dress'];
						unset( $detail[CountryWarInnerWorshipField::VA_EXTRA] );
					}
					else
					{
						$detail['dress'] = array();
					}
				}
				$detail[CountryWarFrontField::WORSHIP_TIME] = $innerUser->getWorshipTime();
				break;
		}
		$ret[CountryWarFrontField::COCOIN] = $crossUser->getCocoinNum();
		$ret[CountryWarFrontField::TIME_CONFIG] = CountryWarConfig::timeConfig(Util::getTime());
		$ret[CountryWarFrontField::DETAIL] = $detail;
		self::checkAndReward( $serverId,$pid );
		
		return $ret;
	 }
	
	static function signForOneCountry( $countryId )
	{
		$ret = CountryWarDef::$RETARR;
		
		list( $serverId,$pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$uid = RPCContext::getInstance()->getUid();
		if( !CountryWarUtil::isTeamIdRight()
			|| !CountryWarUtil::isUserLvRight($uid) )
		{
			throw new FakeException( 'no qualification' );
		}
		if(!CountryWarUtil::isStage(CountryWarStage::SINGUP))
		{
			Logger::warning('invalid stage');
			$ret[CountryWarFrontField::RETCODE] = 'expired';
			return $ret;
		}
		
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$signTime = $crossUser->getSignTime();
		if( $signTime > 0 )
		{
			return $ret;
		}
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$crossUser->sign($countryId);
		$rewardConf = CountryWarConfig::signReward();
		
		$crossUser->update();
		RewardUtil::reward3DArr($uid, $rewardConf, StatisticsDef::ST_FUNCKEY_COUNTRYWAR_SIGN_REWARD);
		BagManager::getInstance()->getBag($uid)->update();
		EnUser::getUserObj($uid)->update();
		
		$teamObj = CountryWarTeamObj::getInstance();
		$teamId = $teamObj->getTeamIdByServerId($serverId);
		$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
		$teamContent->addPeopleForCountry( $countryId, 1 );
		$teamContent->update();
		
		$needMoreRoomNum = $teamContent->getNeedMoreRoomNum();
		Logger::debug('needMoreRoom:%s',$needMoreRoomNum );
		if(CountryWarUtil::needCreateRoomRightNow( $needMoreRoomNum ))
		{
			$thid = CountryWarUtil::getThId($teamId);
			Logger::debug('thid:%s',$thid);
			CountryWarUtil::notifyCrossLc( $thid, 'countrywarcross.doNotifySign', array( $teamId, $serverId, $pid, $countryId ) );
		}
		$ret[CountryWarFrontField::RETCODE] = 'ok';
		
		return $ret;
	}
	
	/**
	 * 在跨服执行的通知crosslc又有一个玩家报名需要再建房间
	 * @param unknown $teamId
	 * @param unknown $serverId
	 * @param unknown $pid
	 * @param unknown $countryId
	 */
	static function doNotifySign( $teamId, $serverId, $pid, $countryId )
	{	
		if(!CountryWarUtil::isStage(CountryWarStage::SINGUP))
		{
			throw new InterException('now not signtime,want to sign');
		}
		self::doCheckAndCreateRoom($teamId, $countryId);
		Logger::info('doNotifySignAndCreateRoom, teamId:%s, serverId:%s, pid:%s, countryId:%s',$teamId,$serverId,$pid,$countryId);
	}
	
	static function doCheckAndCreateRoom( $teamId, $coutryId=0 )
	{
		$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
		$roomNum = $teamContent->getRoomNum();
		$maxSignNum = $teamContent->getMaxSignNumOfCountry();
		$moreRoomNum = $teamContent->getNeedMoreRoomNum();
		Logger::info('roomnum:%s,signnum:%s, moreroomnum:%s',$roomNum,$maxSignNum,$moreRoomNum);
		if( $moreRoomNum > 0 )
		{
			for ($i = 1;$i <= $moreRoomNum; $i++)
			{
				$nowRoomNum = $teamContent->getRoomNum();
				$roomId = $roomNum + $i;
				$teamRoomId = CountryWarUtil::getTeamRoomId($teamId,$roomId);
				$teamContent->addRoomNum(1);
				Logger::debug('add one room');
				$notifyRet = CountryWarUtil::notifyLcCreate($teamRoomId,$nowRoomNum);
				if( !$notifyRet )
				{
					//创建房间失败,下一个人来的时候触发,在分房的时候再确认一把
					throw new InterException('create room by lc failed, teamId:%s,teamRoomId:%s,roomNum:%s,moreRoomNum:%s', $teamId,$teamRoomId,$roomNum,$moreRoomNum);
				}
			}
			$teamContent->update();
			Logger::info('create room,before:%s,now:%s', $roomNum, $roomNum + $moreRoomNum);
		}
		else
		{
			Logger::info('create room no need,now:%s signnum:%s', $roomNum,$maxSignNum);
		}
		
	}
	
	static function scrRangeRoom( $force = false, $specialArr = array() )
	{
		Logger::info('scrRangeRoom, args:%s,%s,begin...', $force, $specialArr);
		if(!$force && !CountryWarUtil::isStage(CountryWarStage::RANGE_ROOM))
		{
			Logger::fatal('now not rangeroom');
			return;
		}
		if( !empty( $specialArr ) )
		{
			$allTeamId = array_keys( $specialArr );
		}
		else
		{
			$allTeamId = CountryWarTeamObj::getInstance()->getAllTeamId();
		}
		
		$failedArr = array();
		foreach ( $allTeamId as $oneTeamId )
		{
			$rangeDone = self::rangeOneTeam( $oneTeamId,$specialArr );
			if( !$rangeDone )
			{
				Logger::fatal('range room one team:%s failed', $oneTeamId);
				$failedArr[] = $oneTeamId;
				continue;
			}
		}
		Logger::info('range room final done, failed array:%s', $failedArr);
	}
	
	static function rangeOneTeam( $oneTeamId, $specialArr )
	{
		//此刻再check一把创建的房间和人数的匹配问题
		self::doCheckAndCreateRoom($oneTeamId);
		
		$ret = true;
		if( isset( $specialArr[$oneTeamId] ) )
		{
			$allCountryId = $specialArr[$oneTeamId];
		}
		else
		{
			$allCountryId = CountryWarCountryId::$ALL;
		}
		foreach ( $allCountryId as $countryId )
		{
			try 
			{
				self::rangeOneCountry($oneTeamId, $countryId);
				Logger::info('range room one country success,teamId:%s,countryId:%s',$oneTeamId,$countryId);
			}
			catch (Exception $e)
			{
				$ret = false;
				Logger::fatal('range room one country failed,teamId:%s,countryId:%s',$oneTeamId,$countryId);
			}
		}
		
		return $ret;
	}
	
	static function rangeOneCountry( $teamId, $countryId, $booked = true )
	{
		Logger::info('rangeOneCountry, args:%s,%s,%s,begin...', $teamId, $countryId, $booked);
		$teamContentInstance = CountryWarCrossTeamContent::getInstance($teamId);
		$signTime = CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SINGUP);
		$allServer = CountryWarTeamObj::getInstance()->getAllServerInTeam($teamId);
		$roomNum = $teamContentInstance->getRoomNum();
		$firstTeamRoomId = CountryWarUtil::getTeamRoomId($teamId, CountryWarConf::FIRST_ROOM_ID);
		while(true)
		{
			$crossUserArr = CountryWarCrossUserDao::getUnrangeUserInServerArr( $allServer, $signTime, $countryId );
			Logger::debug('unrange users:%s', $crossUserArr);
			$num = count( $crossUserArr );
			if( $num <= 0 )
			{
				break;
			}
			shuffle( $crossUserArr );
			foreach ( $crossUserArr as $index => $crossUserInfo )
			{
				$roomAndSide = $teamContentInstance->getLastDealRoomIdAndSide($countryId);
				Logger::debug('LastDealRoomIdAndSide:%s', $roomAndSide);
				if( empty( $roomAndSide ) )
				{
					$nextTeamRoomId = $firstTeamRoomId;
					$nextSide = CountryWarConf::SIDE_A;
				}
				else
				{
					list( $lastTeamRoomId, $lastSide ) = $roomAndSide;
					if( $lastSide == CountryWarConf::SIDE_A )
					{
						$nextTeamRoomId = $lastTeamRoomId;
						$nextSide = CountryWarConf::SIDE_B;
					}
					else
					{
						$nextSide = CountryWarConf::SIDE_A;
						$nextTeamRoomId = $lastTeamRoomId + 1;
						if( $nextTeamRoomId > CountryWarUtil::getTeamRoomId($teamId, $roomNum) )
						{
							$nextTeamRoomId = $firstTeamRoomId;
						}
					}
				}
				try 
				{
					$teamContentInstance->divideOneUser( $countryId, $nextTeamRoomId, $nextSide );
					CountryWarCrossUser::divideOneUser( $crossUserInfo[CountryWarCrossUserField::SERVER_ID], $crossUserInfo[CountryWarCrossUserField::PID],
					array( CountryWarCrossUserField::TEAM_ROOM_ID => $nextTeamRoomId, CountryWarCrossUserField::SIDE => $nextSide ));
					$teamContentInstance->update();
					Logger::info('range room one user success,serverId:%s,pid:%s,teamRoomId:%s,side:%s', $crossUserInfo[CountryWarCrossUserField::SERVER_ID], $crossUserInfo[CountryWarCrossUserField::PID],$nextTeamRoomId, $nextSide);
				}catch ( Exception $e )
				{
					Logger::fatal('range room one user fail,serverId:%s,pid:%s,teamRoomId:%s,side:%s', $crossUserInfo[CountryWarCrossUserField::SERVER_ID], $crossUserInfo[CountryWarCrossUserField::PID],$nextTeamRoomId, $nextSide);
				}
			}
			
			if( $num < DataDef::MAX_FETCH )
			{
				break;
			}
		}
	}
	
	/**
	 * 这是从服内到跨服的联系接口，有些东西不方便在跨服做的可以在这里搞一把
	 * @param unknown $uid
	 * @throws InterException
	 * @return multitype:string |unknown
	 */
	static function getLoginInfo( $uid )
	{
		$retArr = CountryWarDef::$RETARR;
		if( (!CountryWarUtil::isStage( CountryWarStage::AUDITION )&&!CountryWarUtil::isStage( CountryWarStage::FINALTION ) ))
		{
			throw new InterException( 'invalid time' );
		}
		CountryWarUtil::checkTeamId();
		
		$serverId = Util::getServerIdOfConnection();
		$pid = EnUser::getUserObj($uid)->getPid();
		$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId($serverId);
		list( $serverIp, $port ) = CountryWarUtil::getHostInfoByTeamId($teamId);
		
		$db = RPCContext::getInstance()->getFramework()->getDb();
		$serialKey = CountryWarUtil::getSerialKey( $serverId, $pid );
		if(CountryWarUtil::crossMemKeyExist($serialKey))
		{
			return $retArr;
		}
		//只是为了初始化，这样保证了在使用crosslc的时候不再回初始化user的信息
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$crossUser->setBaseInfo();
		$crossUser->update();
		
		$serialInfo = CountryWarUtil::getSerialInfo($db);
		$tokenKey = CountryWarUtil::getTokenKey($serverId, $pid);
		$tokenString = CountryWarUtil::getTokenString($serverId, $pid);
		CountryWarUtil::setCrossMem($serialKey, $serialInfo, CountryWarConf::SERIAL_EXPIREDTIME);
		CountryWarUtil::setCrossMem($tokenKey, $tokenString, CountryWarConf::TOKEN_EXPIREDTIME);
		
		$retArr[CountryWarFrontField::RETCODE] = 'ok';
		$retArr[CountryWarFrontField::SERVER_IP] = $serverIp;
		$retArr[CountryWarFrontField::PORT] = $port;
		$retArr[CountryWarFrontField::TOKEN] = $tokenString;
		$retArr[CountryWarFrontField::UUID] = $crossUser->getUuid();
		return $retArr;
	}
	
	static function loginCross($serverId, $pid, $token)
	{
		Logger::trace('loginCross one user: %s,%s,%s',$serverId,$pid,$token);
		
		$retArr = CountryWarDef::$RETARR;
		if( (!CountryWarUtil::isStage( CountryWarStage::AUDITION )&&!CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
				|| !CountryWarUtil::isTeamIdRight($serverId))
		{
			return $retArr;
		}
		$tokenKey = CountryWarUtil::getTokenKey($serverId, $pid);
		$tokenString = CountryWarUtil::getCrossMem($tokenKey);
		if( empty( $tokenKey ) || $token != $tokenString )
		{
			Logger::fatal('err login token:%s, %s', $token, $tokenString);
			return $retArr;
		}
		$serialKey = CountryWarUtil::getSerialKey($serverId, $pid);
		$serialInfo = CountryWarUtil::getCrossMem($serialKey);
		if( empty( $serialInfo['db'] ) )
		{
			Logger::fatal('empty db, serial info:%s', $serialInfo);
			return $retArr;
		}
		CountryWarUtil::delCrossMem($tokenKey);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$uuid = $crossUser->getUuid();
		
		// 检测跨服是否在线，在线的话直接踢掉，sleep一下，继续登陆
		$proxy = new ServerProxy();
		$ret = $proxy->checkUser($uuid);
		if ($ret)
		{
			usleep(UserConf::LOGIN_RC_INTERVAL);
			Logger::info('uuid:%d conn exist already, kick', $uuid);
		}
		
		RPCContext::getInstance()->setSession( CountryWarSessionKey::UUID, $uuid );
		RPCContext::getInstance()->setSession( CountryWarSessionKey::MY_INNER_DB, $serialInfo['db'] );
		RPCContext::getInstance()->setSession( CountryWarSessionKey::MY_INNER_SERVERID, $serverId );
		RPCContext::getInstance()->setSession( CountryWarSessionKey::MY_INNER_PID, $pid );
		RPCContext::getInstance()->setSession( UserDef::SESSION_KEY_SERVER_ID, $serverId );
		RPCContext::getInstance()->setSession( UserDef::SESSION_KEY_PID, $pid );
		RPCContext::getInstance ()->addListener ( 'countrywarcross.onLogoff',array());
		RPCContext::getInstance ()->addConnection ();
		
		$retArr[CountryWarFrontField::RETCODE] = 'ok';
		Logger::trace('loginCross one user done: %s,%s,%s',$serverId,$pid,$token);
		return $retArr;
	}
	
	static function enter($countryId = NULL)
	{
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$teamRoomId = $crossUser->getTeamRoomId();
		$myCountryId = $crossUser->getCountryId();
		$side = 0;
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			if( $teamRoomId <= 0  )
			{
				if( empty( $countryId ) )
				{
					throw new InterException( 'no teamRoomId, need countryId' );
				}
				$sign = $crossUser->getSignTime();
				$country = $crossUser->getCountryId();
				if( $sign > 0 && $country > 0 )
				{
					Logger::fatal('signtime:%s, countryId:%s, no teanRoomId', $sign, $country);
					$countryId = $country;
				}
				
				$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId($serverId);
				$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
				if( $teamContent->isNobodyJoin($countryId) )
				{
					return 'noone';
				}
				
				if( $teamContent->isCountryFull($countryId) )
				{
					return 'full';
				}
				$rangeRet = self::rangeUnsignUser( $countryId, $serverId, $pid);
				if( !$rangeRet )
				{
					throw new InterException('range unsign user failed,countryId:%s,serverId:%s,pid:%s', $countryId, $serverId, $pid);
				}
				CountryWarCrossUser::releaseInstance();
				$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
				$teamRoomId = $crossUser->getTeamRoomId();
				$myCountryId = $countryId;
				if( $teamRoomId <= 0 )
				{
					throw new InterException('no roomId,countryId:%s,serverId:%s,pid:%s', $countryId, $serverId, $pid);
				}
			}
			$battleId = CountryWarUtil::getBattIdByTeamRoomIdCountryId( $teamRoomId, $myCountryId );
			$side = $crossUser->getSide();
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			if( $teamRoomId <= 0  )
			{
				throw new InterException( 'isStageRight teamroomid:%s', $teamRoomId );
			}
			if( !$crossUser->isFinalMember() )
			{
				throw new InterException( 'not a final member' );
			}
			$teamId = CountryWarUtil::getTeamIdByTeamRoomId($teamRoomId);
			$battleId = CountryWarUtil::getFinalBattleIdByTeamId($teamId);
			$side = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $crossUser->getCountryId());
		}
		else
		{
			Logger::warning('invalid stage');
			return 'expired';
		}
		
		$userData = array
		(
				'uid' => $crossUser->getUuid(),
				'uname' => $crossUser->getUname(),
				'master_htid' => $crossUser->getHtid(),
				'groupId' => $side,
		);
		RPCContext::getInstance()->enterCountryBattle($battleId, $userData);
		Logger::trace("serverId[%d] pid[%d] enter battle[%d]:notify lcserver to enterGroupBattle ok, param : battleId[%d] userData[%s]",
		$serverId,$pid,$battleId,$battleId,$userData);
		return 'ok';
	}
	
	static function rangeUnsignUser($countryId, $serverId, $pid)
	{
		$ret = false;
		$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId($serverId);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$lock = new Locker();
		try
		{
			$lock->lock(CountryWarUtil::getEnterLockKey($teamId));
			CountryWarCrossTeamContent::releaseInstance();
			$teamContentInstance = CountryWarCrossTeamContent::getInstance($teamId);
			if( !$teamContentInstance->isCountryFull($countryId) )
			{
				$crossUser->sign($countryId);
				$teamContentInstance->addPeopleForCountry($countryId, 1);
				$teamContentInstance->update();
				$crossUser->update();
				self::rangeOneCountry($teamId, $countryId, false);
				$ret = true;
			}
		}
		catch (Exception $e)
		{
			Logger::warning('lock failed,teamId:%s,serverId:%s,pid:%s', $teamId,$serverId,$pid);
			$lock->unlock(CountryWarUtil::getEnterLockKey($teamId));
		}
		$lock->unlock(CountryWarUtil::getEnterLockKey($teamId));
		
		return $ret;
	}
	
	static function getEnterInfo()
	{
		$retArr = CountryWarDef::$RETARR;
		// 从session获取战场id
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		CountryWarUtil::checkBattleId($battleId);
		
 		if( (!CountryWarUtil::isStage( CountryWarStage::AUDITION )&&!CountryWarUtil::isStage( CountryWarStage::FINALTION ) ))
		{
			return $retArr;
		} 
		RPCContext::getInstance()->setSession(CountryWarSessionKey::AUTO_RECOVER , CountryWarConf::AUTO_RECOVER_OFF);
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$recoverPara = $crossUser->getRecoverPercent();
		// 传给前端该玩家的一些信息，调用lcserver获取战场其他信息
		$userData = array(
				'info' => array( 
						'attackLevel' => $crossUser->getInspireLevel(),
						'defendLevel' => 0,
						'auto_recover' => CountryWarConf::AUTO_RECOVER_OFF,
						'recover_percent' => $recoverPara,
				),
				);
		RPCContext::getInstance()->getCountryBattleEnterInfo($battleId, $userData);
		Logger::trace("serverId[%d] pid[%d] getEnterInfo battleId[%d]:notify lcserver to getGroupBattleEnterInfo, param : battleId[%d] userData[%s]", $serverId, $pid, $battleId, $battleId, $userData);
	}
	
	static function joinTransfer($transferId)
	{
		$retArr = CountryWarDef::$RETARR;
		
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$uuid = RPCContext::getInstance()->getUid();
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$side = 0;
		if( CountryWarUtil::isStage( CountryWarStage::AUDITION ))
		{
			$side = $crossUser->getSide();
		}
		elseif( CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
		{
			$side = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $crossUser->getCountryId());
			Logger::debug('side and country in final:%s, %s',$side,$crossUser->getCountryId());
		}
		else
		{
			throw new InterException( 'invalid stage' );
		}
		CountryWarUtil::checkTransferId($transferId,$side);
		
		$quitBattleTime = RPCContext::getInstance ()->getSession(CountryWarSessionKey::QUIT_BATTLE_TIME);
		$leaveBattleTime = RPCContext::getInstance ()->getSession(CountryWarSessionKey::LEAVE_BATTLE_TIME);
		list($quitWaitTime,$leaveWaitTime) = CountryWarUtil::getQuitAndLeaveWaitTime($quitBattleTime, $leaveBattleTime );
		if( $quitWaitTime>0||$leaveWaitTime>0 )
		{
			$retArr[CountryWarFrontField::RETCODE] = 'waitTime';
			$retArr['waitTime'] = $quitWaitTime>$leaveWaitTime?$quitWaitTime:$leaveWaitTime;
			Logger::trace("serverId[%d] pid[%d] join battleId[%d]:wait time is not ok,need wait:%s, quitWaitTime:%s,leaveBattleTime:%s ",$serverId, $pid, $battleId,$retArr['waitTime'], $quitWaitTime, $leaveWaitTime );
			return $retArr;
		}
		
		// 获得战斗数据
		$battleData = self::getBattleData($serverId, $pid, $side == CountryWarConf::SIDE_A);
		
		$ret = CountryWarUtil::notifyLcJoin($uuid,$battleId, $transferId, $battleData);
		if($ret['ret'] != 'ok')
		{
			Logger::warning('serverId:%d,pid:%s,uuid:%s join failed: %s', $serverId,$pid,$uuid,$ret);
			$retArr[CountryWarFrontField::RETCODE] = $ret['ret'];
			return $retArr;
		}
		$outTime = $ret['outTime'];
		Logger::trace("serverId[%d] pid[%d] join battle[%d]:notify lcserver join ok, outTime[%s], now[%s]", 
					$serverId,$pid,$battleId,strftime("%Y%m%d-%H%M%S", $outTime), strftime("%Y%m%d-%H%M%S", Util::getTime()));
		$reward = self::rewardWhenJoin($serverId, $pid);
		$retArr[CountryWarFrontField::RETCODE] = 'ok';
		$retArr['outTime'] = $outTime;
		Logger::trace("serverId[%d] pid[%d] join battle[%d]:all ok, ret:%s, reward:%s", $serverId, $pid, $battleId, $retArr, $reward);
		
		return $retArr;
	}
	
	static function getBattleData($serverId, $pid, $isAttacker = true)
	{
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$countryId = $crossUser->getCountryId();
		$countryAdd = array();
		if( CountryWarUtil::isStage( CountryWarStage::AUDITION ) )
		{
			$countryAdd = CountryWarConfig::countryAddition();
			$countryAdd = HeroUtil::adaptAttr($countryAdd);
		}
		$battleData = EnUser::getBattleFormationByOtherGroup($serverId, $pid);
		$arrHero = $battleData ['arrHero'];
		$arrHero = BattleUtil::unsetEmpty($arrHero);
		
		foreach($arrHero as &$hero)
		{
			$hero[PropertyKey::CURR_HP] = $hero[PropertyKey::MAX_HP];
			$countryIdOfHero = Creature::getCreatureConf($hero[PropertyKey::HTID], CreatureAttr::COUNTRY);
			if( $countryIdOfHero == $countryId )
			{
				Logger::debug('addtion:%s for country:%s,hero:%s',$countryAdd,$countryId,$hero[PropertyKey::HTID]);
				foreach ( $countryAdd as $key => $val )
				{
					if( !isset( $hero[$key] ) )
					{
						Logger::debug('no property:%s', $key);
						$hero[$key] = 0;
					}
					$hero[$key] += $val;
				}
			}
			unset( $hero );
		}
		
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$formation = array
		(
				'name' => $crossUser->getUname(),
				'level' => $crossUser->getLevel(),
				'isPlayer' => true,
				'uid' => $crossUser->getUuid(),
				'arrHero' => $arrHero,
				'craft' => $battleData ['craft'],
				'fightForce' => $battleData ['fightForce'],
		);
		
		if (!empty($battleData['arrCar']))
		{
			$formation['arrCar'] = $battleData['arrCar'];
			$carIdOffset = $isAttacker ? BattleDef::$CAR_ID_OFFSET[1] : BattleDef::$CAR_ID_OFFSET[2];
			foreach ($formation['arrCar'] as $index => $aCarInfo)
			{
				$formation['arrCar'][$index]['cid'] = ++$carIdOffset;
			}
		}
	
		$arrClientformation = BattleUtil::prepareClientFormation($formation, array());
		$arrHero = BattleUtil::prepareBattleFormation($formation);
	
		return array('formation' => $arrClientformation, 'arrHero' => $arrHero, 'xianshou' => $battleData ['fightForce']);
	}
	
	static function leave()
	{
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$uuid = RPCContext::getInstance()->getUid();
		RPCContext::getInstance()->setSession(CountryWarSessionKey::AUTO_RECOVER , CountryWarConf::AUTO_RECOVER_OFF);
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		if ($battleId <= 0)
		{
			Logger::fatal("serverId[%d] do not have battleId in session when getEnterInfo", $serverId);
		}
		RPCContext::getInstance()->leaveCountryBattle();
	}
	
	static function onLogoff()
	{
		return;
	}
	
	static function onFightWin($battleId, $attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $brid, $replayData, $fightEndTime, $isWinnerOut, $winnerHpRageArr, $winnerUname, $loserUname)
	{
		Logger::trace('onFightWin uid[%d] win uid[%d] in battleId[%d], winStreak[%d] terminalStreak[%d] brid[%d] begin...',
		$winnerId, $loserId, $battleId, $winStreak, $terminalStreak, $brid);
	
		if( FrameworkConfig::DEBUG )
		{
			$innerDb = RPCContext::getInstance()->getSession( CountryWarSessionKey::MY_INNER_DB );
			$newBrid = IdGenerator::nextId('brid', $innerDb);
			BattleDao::addRecord ( $newBrid, $replayData, $innerDb );
			Logger::debug('onFightWin brid:%s', $newBrid);
		}
		
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		$uuid = RPCContext::getInstance()->getUid();
		if( $winnerId != $uuid )
		{
			throw new InterException( 'invalid th, winid:%s,uuid:%s',$winnerId,$uuid );
		}
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		$winnerUser = CountryWarCrossUser::getInstance($serverId, $pid);
		
		// 发奖励
		$reward = self::rewardWhenKill($attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $fightEndTime);
		$winnerReward = $reward['winner'];
		$loserReward = $reward['loser'];
		Logger::trace('onFightWin winuid:%d loseuid:%d. reward:%s', $winnerId, $loserId, $reward);

		$winnerUname = $winnerUser->getUname();
		// 给败者发送消息
		$loserMsg = array
		(
				'reward' => $loserReward,
				'extra' => array
				(
						'adversaryName' =>$winnerUname ,
						'joinCd' => $fightEndTime + CountryWarConfig::joinCd(),
				),
		);
		Logger::trace('onFightWin uid:%d win uid:%d. send loser message:%s', $winnerId, $loserId, $loserMsg);
		RPCContext::getInstance()->sendMsg(array($loserId), PushInterfaceDef::COUNTRY_WAR_FIGHT_LOSE, $loserMsg);
		
		if( empty( $winnerHpRageArr ) )
		{
			throw new InterException( 'empty winnerHpArr from lc' );
		}
		CountryWarUtil::setHpRageFromLcToSession($winnerHpRageArr);
		$recoverPara = $winnerUser->getRecoverPercent();
		$winnerMsg = array
		(
				'reward' => $winnerReward,
				'extra' => array
				(
						'adversaryName' => $loserUname,
				),
		);
		if ($isWinnerOut)
		{
			$winnerMsg['extra']['winnerOut'] = TRUE;
			$winnerMsg['extra']['joinCd'] = $fightEndTime + CountryWarConfig::joinCd();
		}
		if(
		!$isWinnerOut
		&&CountryWarUtil::isAutoRecoverBySession()
		&&CountryWarUtil::needRecover($recoverPara) 
		&& $winnerUser->subCocoin( CountryWarConfig::recoverCocoin(), StatisticsDef::ST_FUNCKEY_COUNTRYWAR_AUTO_RECOVER ) )
		{
			$winnerUser->update();
			$hpRageInfo = CountryWarUtil::recoverHpRageInSession();
			$winnerMsg['hpRecover'] = array('cost' => CountryWarConfig::recoverCocoin());//这里必须是map，要传给Lcserver
			RPCContext::getInstance()->hpRecoverCountryBattle($winnerMsg);//没啥要返回的
		}
		else 
		{
			return $winnerMsg;
		}
	}
	
	static function onFightLose($uid, $fightEndTime)
	{
	}
	
	static function onTouchDown( $battleId, $groupId, $touchUuid,$touchDownTime )
	{
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		$uuid = RPCContext::getInstance()->getUid();
		if( $uuid != $touchUuid )
		{
			throw new InterException( 'invalid touchuuid:%s,uuid:%s',$touchUuid,$uuid );
		}
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		$rewardInfo = self::rewardTouchDown($serverId, $pid, $battleId);
		$ret = array
		(
				'reward' => $rewardInfo,
				'extra' => array
				(
						'joinCd' => $touchDownTime + CountryWarConfig::joinCd(),
				),
		);
		Logger::trace('teamId:%d pid:%d touch down at time:%d in battle:%d,ret:%s end...', $serverId, $pid, $touchDownTime, $battleId, $ret);
		
		return $ret;
	}
	
	static function onBattleEnd($battleId, $battleDuration, $attackerResource, $defenderResource)
	{
		Logger::trace('battle end[%d], begin...', $battleId);
		
		// 给所有用户发送结算数据
		if( CountryWarUtil::isFinalBattleId($battleId) )
		{
			$teamContent = CountryWarCrossTeamContent::getInstance(CountryWarUtil::getTeamIdByBattleId($battleId));
			$teamContent->setResource( $attackerResource, $defenderResource );
			$teamContent->update();
		}
		$qualifyUserList = self::sendSettleMsg($battleId);
		if( CountryWarUtil::isAuditionBattleId($battleId) )
		{
			Logger::trace("audition battle end, battleId:%s", $battleId);
			CountryWarCrossUser::markFinalMembers($battleId, $qualifyUserList);
		}
		
		// 通知lcserver释放战斗
		RPCContext::getInstance()->freeCountryBattle($battleId);
		Logger::trace('rob battle end[%d]: notify lcserver to free rob battle', $battleId);
		
		Logger::trace('battleId:%d end at time:%d end...', $battleId, $battleDuration);
	}
	
	static function getRankList()
	{
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		$type = CountryWarRankType::AUDITION;
		if( CountryWarUtil::isFinalBattleId($battleId) )
		{
			$type = CountryWarRankType::FINALTION;
		}
		$topN = CountryWarCrossUser::getTopNByBattleId($battleId,$type);
		return $topN;
	}
	
	static function sendSettleMsg( $battleId )
	{
		$type = CountryWarRankType::AUDITION;
		if( CountryWarUtil::isFinalBattleId($battleId) )
		{
			$type = CountryWarRankType::FINALTION;
		}
		
		$countryId = CountryWarUtil::getCountryIdByBattleId($battleId);
		$topAll = CountryWarCrossUser::getTopNByBattleId($battleId,$type,NULL,true,false);
		$winSide = 0;
		if( CountryWarUtil::isFinalBattleId( $battleId ) )
		{
			$teamContent = CountryWarCrossTeamContent::getInstance(CountryWarUtil::getTeamIdByBattleId($battleId));
			$winSide = $teamContent->getWinSide();
		}
		
		foreach ( $topAll as $rank => $rankInfo )
		{
			if( CountryWarUtil::isFinalBattleId( $battleId ) )
			{
				$endMsg = array(
					'rank' => $rank,
					'point'=> $rankInfo[CountryWarCrossUserField::FINAL_POINT],
					'isSideWin' => CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $rankInfo[CountryWarCrossUserField::COUNTRY_ID]) == $winSide?1:0,
				);
			}
			else 
			{
				$endMsg = array
				(
						'rank' => $rank,
						'point'=> $rankInfo[CountryWarCrossUserField::AUDITION_POINT],
				);
			}
			RPCContext::getInstance()->sendMsg(array($rankInfo[CountryWarCrossUserField::UUID]), PushInterfaceDef::COUNTRY_WAR_RECKON, $endMsg);
			Logger::trace('battle end[%d]: send msg for user[%d], msg:%s', $battleId, $rankInfo[CountryWarCrossUserField::UUID], $endMsg);
			if($endMsg['point'] <= 0)
			{
				unset($topAll[$rank]);
			}
		}
		$topAll = array_merge( $topAll );
		$topN = array_slice( $topAll , 0, CountryWarConfig::qualifyNumPerAuditionBattle());
		return $topN;
	}
	
	static function clearJoinCd()
	{
		Logger::trace('CountrywarLogic::clearJoinCd begin...');
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		// 检查cd
		$leaveBattleTime = RPCContext::getInstance ()->getSession(CountryWarSessionKey::LEAVE_BATTLE_TIME);
		$quitBattleTime = RPCContext::getInstance ()->getSession(CountryWarSessionKey::QUIT_BATTLE_TIME);
		list($quitWaitTime,$leaveWaitTime) = CountryWarUtil::getQuitAndLeaveWaitTime($quitBattleTime, $leaveBattleTime );
		if ($leaveWaitTime <=0 )
		{
			Logger::warning("clearJoinCd in battle[%d] :no cd, leaveBattleTime:%d quitBattleTime:%d", $battleId, $leaveBattleTime,$quitBattleTime);
			return 'cooled';
		}
		$needCocoin = CountryWarConfig::clearJoinCdCocoin();
		Logger::trace("clearJoinCd in battle[%d]:need cocoin, num:%d",$battleId, $needCocoin);
		
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		if (FALSE == $crossUser->subCocoin($needCocoin, StatisticsDef::ST_FUNCKEY_COUNTRYWAR_CLEAR_CD))
		{
			return 'poor';
		}
		$crossUser->update();
		RPCContext::getInstance()->removeCountryBattleJoinCd();
		Logger::trace('CountrywarLogic::clearJoinCd end...');
		return 'ok';
		
	}
	
	static function recoverByUser()
	{
		if( !CountryWarUtil::isStage( CountryWarStage::AUDITION ) && !CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
		{
			throw new InterException( 'invalid stage' );
		}
		
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		if(!CountryWarUtil::canRecoverConsiderCurCondition() )
		{
			throw new InterException( 'no need to recover' );
		}
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		if ( FALSE === $crossUser->subCocoin(CountryWarConfig::recoverCocoin(), StatisticsDef::ST_FUNCKEY_COUNTRYWAR_RECOVER) )
		{
			throw new InterException( 'lack cocoin' );
		}
		$message['hpRecover'] = array('cost' => CountryWarConfig::recoverCocoin());
		$ret = CountryWarUtil::notifyLcRecoverByUser();
		if( $ret == 'ok' )
		{
			$crossUser->update();
			$hpRageInfo = CountryWarUtil::recoverHpRageInSession();
			return $message;
		}
		else
		{
			Logger::warning('recover by user fail');
			return 'fail';
		}
	}
	
	static function setRecoverPara( $percent )
	{
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		CountryWarUtil::checkRecoverPara($percent);
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$crossUser->setRecoverPara( $percent );
		$crossUser->update();
		return 'ok';
	}
	
	static function turnAutoRecover( $onOrOff )
	{
		$battleId = RPCContext::getInstance()->getSession(CountryWarSessionKey::BATTLEID);
		CountryWarUtil::checkBattleId($battleId);
		RPCContext::getInstance()->setSession(CountryWarSessionKey::AUTO_RECOVER, $onOrOff);
		return 'ok';
	}
	
	static function getFinalMembers()
	{
		if( !CountryWarUtil::isStage(CountryWarStage::SUPPORT) && !CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			throw new InterException( 'stage err,not allowd to getFinalMembers');
		}
		list( $serverId, $pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId(Util::getServerIdOfConnection());
		$allFinalMembers = CountryWarCrossUser::getAllFinalMembers( CountryWarConfig::roundStartTime(Util::getTime()),$teamId);
		$frontArr = array();
		foreach ( $allFinalMembers as $index => $member  )
		{
			$frontArr[$member[CountryWarCrossUserField::COUNTRY_ID]][] = $member;
		}
		return array( CountryWarFrontField::MEMBER_INFO => $frontArr);
	}
	
	static function supportOneUser($pid, $serverId)
	{
		$return = CountryWarDef::$RETARR;
		if( !CountryWarUtil::isStage( CountryWarStage::SUPPORT ) )
		{
			Logger::warning('invalid stage');
			$return[CountryWarFrontField::RETCODE] = 'expired';
			return  $return;
		}
		
		CountryWarUtil::checkStage(Util::getTime(), CountryWarStage::SUPPORT);
		list( $myServerId, $mypid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$innerUser = CountryWarInnerUser::getInstance( $myServerId, $mypid );
		if( $innerUser->alreadySupportOneUser() )
		{
			throw new FakeException( 'already support one' );
		}
		$beSupportCrossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		if( !$beSupportCrossUser->isFinalMember() )
		{
			throw new FakeException( 'invalid supprt serverId:%s pid:%s',$serverId, $pid );
		}
		$innerUser->supportOneUser($serverId, $pid);
		$beSupportCrossUser->addFans(1);
		$innerUser->update();
		$beSupportCrossUser->update();
		
		$return[CountryWarFrontField::RETCODE] = 'ok';
		return $return;
	}
	
	static function supportFinalSide($side)
	{
		$return = CountryWarDef::$RETARR;
		if( !CountryWarUtil::isStage( CountryWarStage::SUPPORT ) )
		{
			Logger::warning('invalid stage');
			$return[CountryWarFrontField::RETCODE] = 'expired';
			return  $return;
		}
		CountryWarUtil::checkStage(Util::getTime(), CountryWarStage::SUPPORT);
		list( $myServerId, $mypid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$innerUser = CountryWarInnerUser::getInstance( $myServerId, $mypid );
		if( $innerUser->alreadySupportFinalSide() )
		{
			throw new FakeException( 'already support one' );
		}
		$finalList = CountryWarCrossUser::getAllFinalMembers(CountryWarConfig::roundStartTime(Util::getTime()), CountryWarTeamObj::getInstance()->getTeamIdByServerId(Util::getServerIdOfConnection()));
		if( count( $finalList ) <=0 )
		{
			$return[CountryWarFrontField::RETCODE] = 'noone';
			return $return;
			//return 'noone';
		}
		$innerUser->supportFinalSide($side);
		$innerUser->update();
		$return[CountryWarFrontField::RETCODE] = 'ok';
		return $return;
	}
	
	static function getMySupport()
	{
		list( $myServerId, $mypid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$innerUser = CountryWarInnerUser::getInstance( $myServerId, $mypid );
		$supportUserInfo = array();
		if( $innerUser->alreadySupportOneUser() )
		{
			$supportServerId = $innerUser->getSupportServerId();
			$supportPid = $innerUser->getSupportPid();
			$beSupportCrossUser = CountryWarCrossUser::getInstance($supportServerId, $supportPid);
			$supportUserInfo = $beSupportCrossUser->getBaseInfo();
			$serverName = ServerInfoManager::getInstance()->getServerNameByServerId($supportServerId);
			$supportUserInfo[CountryWarFrontField::SERVER_NAME] = $serverName;
		}
		else
		{
			$supportServerId = 0;
			$supportPid = 0;
		}
		$supportSide = $innerUser->getSupportFinalSide();
		return array(
				CountryWarFrontField::USER => $supportUserInfo, 
				CountryWarFrontField::SIDE => $supportSide
		);
	}
	
	static function inspire()
	{
		list($serverId, $pid) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$inspireNeedCocoin = CountryWarConfig::inspireCocoin();
		if( !CountryWarUtil::isStage( CountryWarStage::AUDITION ) && !CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
		{
			throw new InterException( 'invalid stage' );
		}
		if(!$crossUser->subCocoin($inspireNeedCocoin, StatisticsDef::ST_FUNCKEY_COUNTRYWAR_INSPIRE))
		{
			throw new InterException( 'lack cocoin, need:%s', $inspireNeedCocoin );
		}
		if($crossUser->inspireFull())
		{
			throw new InterException( 'inspire atk full' );
		}
		$crossUser->inspire();
		$crossUser->update();
		$level = $crossUser->getInspireLevel();
		RPCContext::getInstance()->inspireCountryBattle(CountryWarConf::INSPIRE_ATK, $inspireNeedCocoin, $level);
	}
	
	static function exchangeCocoin($amount)
	{
		$user = EnUser::getUserObj();
		list( $serverId, $pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$canExchangeGoldNum = $crossUser->getCanExchangeGoldNum();
		$canExchangeGoldNum = $canExchangeGoldNum >$amount?$amount:$canExchangeGoldNum;
		if(!$user->subGold( $canExchangeGoldNum , StatisticsDef::ST_FUNCKEY_COUNTRYWAR_EXCHANGE_COCOIN))
		{
			throw new InterException( 'lack gold' );
		}
		$gainCocoin = intval( $canExchangeGoldNum * CountryWarConfig::exchangeRatio());
		$crossUser->addCocoin($gainCocoin);
		$user->update();
		Logger::info('exchangeGoldNum:%s',$canExchangeGoldNum);
		$crossUser->update();
		Logger::info('gainCocoin:%s',$gainCocoin);
		return 'ok';
	}
	
	static function worship()
	{
		//CountryWarUtil::checkStage(Util::getTime(), CountryWarStage::WORSHIP);
		if( !CountryWarUtil::isStage(CountryWarStage::WORSHIP) )
		{
			Logger::warning('expired');
			return 'expired';
		}
		
		list( $serverId, $pid ) = CountryWarUtil::getServerIdAndPidFromInner();
		$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
		if ( $innerUser->isWorshipToday() ) 
		{
			throw new InterException( 'already worship today' );
		}
		$innerUser->worship();
		$innerUser->update();
		self::rewardWorship();
		
		return 'ok';
	}
	
	static function checkAndReward( $serverId,$pid )
	{
		$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
		if( CountryWarUtil::isStage(CountryWarStage::WORSHIP) 
			&& CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::WORSHIP) + CountryWarConf::BEGIN_REWARD_OFFSET < Util::getTime() )
		{
			if( !$innerUser->alreadyGainSupportReward() )
			{
				self::rewardSupportAndWinSide($serverId,$pid);
			}
			if( !$innerUser->alreadyGainAuditionRankReward() )
			{
				self::rewardRank(CountryWarStage::AUDITION, $serverId,$pid );
			}
			if( !$innerUser->alreadyGainFinaltionRankReward() )
			{
				self::rewardRank(CountryWarStage::FINALTION, $serverId,$pid );
			}
			
		}
		elseif( CountryWarConfig::getStageStartTime(Util::getTime(), CountryWarStage::SUPPORT) < Util::getTime() )
		{
			if( !$innerUser->alreadyGainAuditionRankReward() )
			{
				//self::rewardRank(CountryWarStage::AUDITION, $serverId,$pid );
			}
		}
	}
	
	static function rewardWhenJoin( $serverId, $pid )
	{
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		if( CountryWarUtil::isStage(CountryWarStage::AUDITION) )
		{
			$crossUser->addAuditionPoint(CountryWarConfig::joinPoint());
			Logger::debug('reward when join,stage:%s,point:%s',CountryWarStage::AUDITION,CountryWarConfig::joinPoint());
		}
		elseif( CountryWarUtil::isStage(CountryWarStage::FINALTION) )
		{
			$crossUser->addFinaltionPoint(CountryWarConfig::joinPoint());
			Logger::debug('reward when join,stage:%s,point:%s',CountryWarStage::FINALTION,CountryWarConfig::joinPoint());
		}
		else
		{
			throw new InterException( 'invalid stage to join' );
		}
		$crossUser->update();
	}
	
	static function rewardTouchDown($serverId, $pid, $battleId)
	{
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$rewardInfo = array();
		if( CountryWarUtil::isStage( CountryWarStage::AUDITION ) )
		{
			$crossUser->addAuditionPoint(CountryWarConfig::touchdownPoint());
			$crossUser->update();
			$rewardInfo['point'] = CountryWarConfig::touchdownPoint();
			$rewardInfo['resource'] = 0;
		}
		elseif ( CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
		{
			$teamId = CountryWarUtil::getTeamIdByBattleId($battleId);
			$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
			$crossUser->addFinaltionPoint(CountryWarConfig::touchdownPoint());
			$side = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $crossUser->getCountryId());
			$realRobResource = $teamContent->sideRobSide($side, CountryWarConfig::touchdownRobResource());
			$teamContent->update();
			$crossUser->update();
			$rewardInfo['point'] = CountryWarConfig::touchdownPoint();
			$rewardInfo['resource'] = $realRobResource;
		}
		else
		{
			throw new InterException("serverId[%d] pid[%d] not in battle time when onTouchDown", $serverId,$pid);
		}
		Logger::debug('reward when touchdown,reward:%s',$rewardInfo);
	
		return $rewardInfo;
	}
	
	static function rewardWhenKill($attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $fightEndTime)
	{
		list( $serverId, $pid ) = CountryWarUtil::getServerIdPidFromSession();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		if( CountryWarUtil::isStage( CountryWarStage::AUDITION ) )
		{
			$crossUser->addAuditionPoint( CountryWarConfig::killPoint($winStreak) );
			$crossUser->addAuditionPoint( CountryWarConfig::terminalKillPoint($terminalStreak) );
			$crossUser->update();
		}
		elseif ( CountryWarUtil::isStage( CountryWarStage::FINALTION ) )
		{
			$crossUser->addFinaltionPoint( CountryWarConfig::killPoint($winStreak) );
			$crossUser->addFinaltionPoint( CountryWarConfig::terminalKillPoint($terminalStreak) );
			$crossUser->update();
		}
		else
		{
			throw new InterException("serverId[%d] pid[%d] not in battle time when onTouchDown", $serverId,$pid);
		}
		$rewardInfo['winner']['point'] = CountryWarConfig::killPoint($winStreak) + CountryWarConfig::terminalKillPoint($terminalStreak);
		$rewardInfo['loser'] = array();
	
		return $rewardInfo;
	}
	
	static function rewardWorship()
	{
		$uid = RPCContext::getInstance()->getUid();
		RewardUtil::reward3DArr($uid, CountryWarConfig::worshipReward(), StatisticsDef::ST_FUNCKEY_COUNTRYWAR_RANK_REWARD_WORSHIP);
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
	}
	
	static function rewardSupportAndWinSide( $serverId,$pid )
	{
		$uid = RPCContext::getInstance()->getUid();
		$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$teamRoomId = $crossUser->getTeamRoomId();
		$teamId = CountryWarTeamObj::getInstance()->getTeamIdByServerId(Util::getServerIdOfConnection());
		$rank = -1;
		$isSupportSideWin = false;
		$isMySideWin = false;
		if( $innerUser->alreadySupportOneUser() )
		{
			$supportServerId = $innerUser->getSupportServerId();
			$supportPid = $innerUser->getSupportPid();
			$battleId = CountryWarUtil::getFinalBattleIdByTeamId($teamId);
			$rank = CountryWarCrossUser::getUserRank($battleId, CountryWarRankType::FINALTION, $supportServerId, $supportPid);
			Logger::debug('support rank:%s', $rank);
		}
		if( $innerUser->alreadySupportFinalSide() )
		{
			$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
			$supportSide = $innerUser->getSupportFinalSide();
			$isSupportSideWin = $teamContent->isSideWin($supportSide);
			Logger::debug('is support side win:%s', $isSupportSideWin);
		}
		if( $crossUser->isFinalMember() )
		{
			$mySide = CountryWarUtil::getFinalSideByCountryId(Util::getTime(), $crossUser->getCountryId());
			$teamContent = CountryWarCrossTeamContent::getInstance($teamId);
			$isMySideWin = $teamContent->isSideWin($mySide);
			Logger::debug('is my side win:%s', $isSupportSideWin);
		}
		
		if( $rank == 0 || $isSupportSideWin || $isMySideWin )
		{
			$innerUser->rewardSupport();
			$innerUser->update();
			Logger::info('reward mark,uid:%s',$uid);
			if( $rank == 0 )
			{
				RewardUtil::reward3DtoCenter($uid, array(CountryWarConfig::memberSupportReward()), RewardSource::COUNTRY_WAR_SUPPORT_REWARD_USER);
				Logger::info('reward support user success,uid:%s,spportserverid:%s,supportpid:%s',$uid,$supportServerId,$supportPid);
			}
			if( $isSupportSideWin )
			{
				RewardUtil::reward3DtoCenter($uid, array(CountryWarConfig::countrySupportReward()), RewardSource::COUNTRY_WAR_SUPPORT_REWARD_COUNTRY);
				Logger::info('reward support side success,uid:%s,spportside:%s',$uid,$supportSide);
			}
			if( $isMySideWin )
			{
				RewardUtil::reward3DtoCenter($uid, array( CountryWarConfig::winSideReward() ), RewardSource::COUNTRY_WAR_REWARD_WIN_SIDE);
				Logger::info('reward side success,uid:%s,side:%s',$uid,$mySide);
			}
			Logger::info('reward SupportAndWinSide success,uid:%s',$uid);
		}
		
	}
	static function rewardRank( $stage, $serverId,$pid )
	{
		$uid = RPCContext::getInstance()->getUid();
		$innerUser = CountryWarInnerUser::getInstance($serverId, $pid);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$teamRoomId = $crossUser->getTeamRoomId();
		$countryId = $crossUser->getCountryId();
		$teamId = CountryWarUtil::getTeamIdByTeamRoomId($teamRoomId);
		
		if( $stage == CountryWarStage::AUDITION && $teamRoomId > 0 )
		{
			$battleId = CountryWarUtil::getBattIdByTeamRoomIdCountryId($teamRoomId, $countryId);
			$rank = CountryWarCrossUser::getUserRank($battleId,CountryWarRankType::AUDITION, $serverId, $pid);
			$innerUser->rewardAudition();
			$innerUser->update();
			Logger::info('reward rank audition mark,uid:%s',$uid);
			if( $rank >= 0 )
			{
				RewardUtil::reward3DtoCenter($uid, array(CountryWarConfig::rankReward($stage,$rank)), RewardSource::COUNTRY_WAR_RANK_REWARD_AUDITION,array('rank' => $rank+1) );
				Logger::info('reward rank audition success,uid:%s,rank:%s',$uid,$rank);
			}
		}
		elseif( $stage == CountryWarStage::FINALTION && $teamRoomId > 0 )
		{
			$battleId = CountryWarUtil::getFinalBattleIdByTeamId($teamId);
			$rank = CountryWarCrossUser::getUserRank($battleId,CountryWarRankType::FINALTION, $serverId, $pid);
			$innerUser->rewardFinaltion();
			$innerUser->update();
			Logger::info('reward rank finaltion mark,uid:%s',$uid);
			if( $rank >= 0 )
			{
				RewardUtil::reward3DtoCenter($uid, array(CountryWarConfig::rankReward($stage,$rank)), RewardSource::COUNTRY_WAR_RANK_REWARD_FINALTION ,array('rank' => $rank+1));
				Logger::info('reward rank finaltion success,uid:%s,rank:%s',$uid,$rank);
			}
		}
		
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
