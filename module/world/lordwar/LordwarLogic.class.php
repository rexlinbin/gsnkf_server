<?php
/**************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarLogic.class.php 241173 2016-05-05 13:23:46Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/LordwarLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-05 13:23:46 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241173 $
 * @brief 
 *  
 **/
class LordwarLogic
{
	public static function enterLordwar()
	{
		RPCContext::getInstance()->setSession( SPECIAL_ARENA_ID::SESSION_KEY , SPECIAL_ARENA_ID::LORDWAR );
	}
	
	public static function leaveLordwar()
	{
		RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
	}
	
	public static function getMyTeamInfo($uid)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		
		$serverId = Util::getServerIdOfConnection();
		$allServerIdsInThisTeam = $teamMgr->getServersByServerId($serverId);
		if( empty( $allServerIdsInThisTeam ) )
		{
			return array();
		}
		
		$ret = array();
		$serverMgr = ServerInfoManager::getInstance(LordwarUtil::getCrossDbName());
		$ret = $serverMgr->getArrServerName($allServerIdsInThisTeam);
		
		return $ret;
	}
	
	public static function getLordInfo($uid)
	{
		
		$serverId = Util::getServerIdOfConnection();
		$pid = self::getPid($uid);
		
		$lordObj = LordObj::getInstance($serverId,$pid);
		$lordInfo = $lordObj->getLordInfo();
		
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		
		if( !LordwarUtil::isServerIn($serverId, $sess) )
		{
			Logger::debug('this server not in any team');
			return array( 'ret' => 'no' );
			//throw new FakeException( 'this server not in' );
		}
		
		$curRoundByConf = $confMgr->getRound();
		if( $curRoundByConf == LordwarRound::REGISTER && $confMgr->isRegisterTime() )
		{
			$realRound = LordwarRound::REGISTER;
			$realStatus = LordwarStatus::WAIT_TIME_END;
		}
		else 
		{
			$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
			
			
			$teamObj = $procedure->getTeamObj($teamId);
			$realRound = $teamObj->getCurRound();
			$realStatus = $teamObj->getCurStatus();
		}
		
		$needLordInfo['ret'] = 'ok';
		$needLordInfo['round'] = $realRound;
		$needLordInfo['status'] = $realStatus;
		$needLordInfo['team_type'] = $lordInfo['team_type'];
		$needLordInfo['worship_time'] = $lordInfo['worship_time'];
		$needLordInfo['update_fmt_time'] = $lordInfo['update_fmt_time'];
		$needLordInfo['bless_receive_time'] = $lordInfo['bless_receive_time'];
		$needLordInfo['register_time'] = $lordInfo['register_time'];
		$needLordInfo['server_id'] = $lordInfo['server_id'];
		$needLordInfo['support_serverid'] = 0;
		$needLordInfo['support_uid'] = 0;
		
		$showSupportRound = $realRound;
		if( $realStatus >= LordwarStatus::REWARDEND )
		{
			$showSupportRound = LordwarUtil::getNextRound($realRound);
		}
		
		if( isset( $lordInfo['va_lord']['supportList'][$showSupportRound] ) )
		{
			$needLordInfo['support_serverid'] = $lordInfo['va_lord']['supportList'][$showSupportRound]['serverId'];
			$needLordInfo['support_uid'] = $lordInfo['va_lord']['supportList'][$showSupportRound]['uid'];
		}
		
		if( in_array( $realRound , LordwarRound::$INNER_PROMO) || in_array( $realRound , LordwarRound::$CROSS_PROMO) )
		{
			$winTeamRecordInfo = LordwarTeam::getPromotionBtlView($teamId, LordwarTeamType::WIN, $sess, $realRound);
			$loseTeamRecordInfo =  LordwarTeam::getPromotionBtlView($teamId, LordwarTeamType::LOSE, $sess, $realRound);
			$subRound = count( $winTeamRecordInfo ) < count( $loseTeamRecordInfo )? count( $winTeamRecordInfo ) : count( $loseTeamRecordInfo );
			
			$needLordInfo['subRound'] = $subRound-1;//可能是-1
		}
		
		//按照约定要给前端助威的人的信息，并且可能这一轮没有一个助威，所以从助威历史里找
		
		$needLordInfo['teamInfo'] = self::getMyTeamInfo($uid);
		
		$needLordInfo['worship_num'] = LordwarUtil::getWorshiNum($needLordInfo['worship_time']);
		unset( $needLordInfo['worship_time'] );
		
		return $needLordInfo;
	}
	
	public static function register( $uid )
	{
		$confMgr = LordwarConfMgr::getInstance();
		$needLevel = $confMgr->getRegisterLevel();
		$userLevel = EnUser::getUserObj($uid)->getLevel();
		if( $userLevel < $needLevel )
		{
			throw new FakeException( 'level: %d < needLevel: %d', $userLevel, $needLevel );
		}
		
		$sess = $confMgr->getSess();
		if( !$confMgr->isRegisterTime() )
		{
			throw new FakeException( 'not register time' );
		}

		$crossDb = LordwarUtil::getCrossDbName();
		$serverMgr = ServerInfoManager::getInstance($crossDb);
		$serverId = Util::getServerIdOfConnection();
		$pid = self::getPid($uid);
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		if( $teamId == -1 )
		{
			throw new FakeException( 'no info of distribution' );
		}
		$lordObj = LordObj::getInstance($serverId,$pid);
		$lordInfo = $lordObj->getLordInfo();
		
		if($confMgr->isRegisterTime($lordInfo['register_time'] ))
		{
			throw new FakeException( 'already register in: %d', $lordInfo['register_time'] );
		}
		//TODO add level limit
			
		$lordObj->register();
		$lordObj->uploadFmt(false);
		$lordObj->update();
		
		return Util::getTime();
	}
	
	
	public static function getMyRecord($uid)
	{
		$pid = self::getPid($uid);
		$serverId = Util::getServerIdOfConnection();
		
		$lordObj = LordObj::getInstance($serverId, $pid);
		$vaLordExtra = $lordObj->getLordVaExtra();
		
		if( empty( $vaLordExtra['record'] ) )
		{
			return array();
		}
		return $vaLordExtra['record'];
		
	}
	
	
	public static function getPid($uid)
	{
		$sessUid = RPCContext::getInstance()->getUid();
		if( $sessUid != $uid )
		{
			throw new FakeException( 'not in myconnection' );
		}
		$pid = EnUser::getUserObj($uid)->getPid();
		
		return $pid;
	}
	
	public static function updateFightInfo($uid)
	{
		$confMgr = LordwarConfMgr::getInstance();
		if( !$confMgr->isUpFmtTime() )
		{
			throw new FakeException( 'can not up fight info now' );
		}
		
		$serverId = Util::getServerIdOfConnection();
		$pid  = self::getPid($uid);
		$lordObj = LordObj::getInstance($serverId, $pid);
		$lordInfo = $lordObj->getLordInfo();
		$registerStartTime = $confMgr->getRoundStartTime(LordwarRound::REGISTER);
		if( $lordInfo['register_time'] < $registerStartTime )
		{
			throw new FakeException( 'not allowed to update, not register' );
		}
		
		$lastUpTime = $lordObj->getUpdateFmtTime();
		$sess = $confMgr->getSess();
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		$teamObj = $procedure->getTeamObj($teamId);
		$curTime = Util::getTime();
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus(); 
		$curCd = $confMgr->getFmtCd($curRound, $curStatus);
		
		$curTime = Util::getTime();
		if( $lastUpTime + $curCd > $curTime )
		{
			throw new FakeException( 'in cd last up time:%d, cd:%d', $lastUpTime, $curCd );
		}
		
  		//更严格的更新战斗信息的判定shiyu，这里要全部做的话比较蛋疼，所以后端只做了海选的，晋级赛的让前段来拦截
  		$auditionLoseNum = $confMgr->getAuditionOutLoseNum( LordwarField::INNER );
  		if( $lordInfo['winner_losenum'] >= $auditionLoseNum && $lordInfo['loser_losenum'] >= $auditionLoseNum )
  		{
  			Logger::debug('inner audition totaly lose');
  			return array('update' => 'innerLose');
  		}
  		if( $curRound >= LordwarRound::CROSS_AUDITION )
  		{
  			//这里用了服内海选的时间，不充分但是可以满足需求
  			$lordCrossInfo = LordwarCrossDao::getLordInfoFromCrossAuditon($serverId, $pid, $registerStartTime );
  			if( empty($lordCrossInfo) )
  			{
  				Logger::debug('not on cross audition list');
  				return array('update' => 'innerLose');
  			}
  			$crossAuditionOutNum = $confMgr->getAuditionOutLoseNum(LordwarField::CROSS);
  			if ( $lordCrossInfo['winner_losenum'] >= $crossAuditionOutNum && $lordCrossInfo['loser_losenum'] >= $crossAuditionOutNum )
  			{
  				Logger::debug('cross audition totaly lose');
  				return array('update' => 'crossLose');
  			}
  		}		 
		
		$lordObj->uploadFmt();
		$lordObj->update();
		
		return array('update' => 'ok','time' => Util::getTime());
		
	}
	
	
	public static function clearFmtCd($uid)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$serverId = Util::getServerIdOfConnection();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		$curRound = $procedure->getTeamObj($teamId)->getCurRound();
		$curStatus = $procedure->getTeamObj($teamId)->getCurStatus();
		
		if( $curRound >= LordwarRound::CROSS_2TO1 && $curStatus >= LordwarStatus::FIGHTEND )//在战斗中是否可以更新cd
		{
			throw new FakeException( 'not allowed to clear' );
		}
		
		$pid = self::getPid($uid);
		$lordObj = LordObj::getInstance($serverId, $pid);
		$lastUpTime = $lordObj->getUpdateFmtTime();
		$curTime = Util::getTime();
		$cdTime = $confMgr->getFmtCd($curRound, $curStatus);
		
		Logger::debug('$cdTime %s, $lastUpTime %s',$cdTime, $lastUpTime );
		if( $curTime >= $lastUpTime + $cdTime )
		{
			throw new FakeException( 'no cd, no need clr' );
		}
		$needGold = $confMgr->getClearCdGold();
		
		if ( $needGold < 0 )
		{
			throw new ConfigException( 'need gold is negtive' );
		}
		
		$userObj = EnUser::getUserObj($uid);
		if(!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_LORDWAR_CLR_CD))
		{
			throw new FakeException( 'lack gold' );
		}
		
		$lordObj->setFmtTime($curTime - $cdTime);
		
		$userObj->update();
		$lordObj->update();
		
		return array('time' => $curTime - $cdTime);
	}
	
	public static function support($pos, $teamType )
	{
		if( $teamType != LordwarTeamType::LOSE && $teamType != LordwarTeamType::WIN )
		{
			throw new FakeException( 'invalid teamtype: %d', $teamType );
		}
		
		$baseInfo = self::getSupportBaseInfo($pos, $teamType);
		if( !$baseInfo['canSupport'] )
		{
			throw new FakeException( 'can not support' );
		}
		$baseInfo = $baseInfo['baseInfo'];
		
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		
		$serverId = Util::getServerIdOfConnection();
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		
		if( $teamId < 0 )
		{
			throw new FakeException('this server not in any team');
		}
	
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$round = $procedure->getTeamObj($teamId)->getCurRound();
		
		$myServerId = Util::getServerIdOfConnection();
		$myUid = RPCContext::getInstance()->getUid();
		$myPid = self::getPid($myUid);
		$lordObj = LordObj::getInstance($myServerId, $myPid);
		
		$needInfo['serverId'] = $baseInfo['serverId'];
		$needInfo['pid'] = $baseInfo['pid']; 
		$needInfo['uid'] = $baseInfo['uid'];
		$needInfo['uname'] = $baseInfo['uname'];
		$needInfo['serverName'] = $baseInfo['serverName'];//助威历史里也记录下了服务器的名字给前段显示用
		$needInfo['teamType'] = $teamType;
		
		$nextRound = LordwarUtil::getNextRound($round);
		if( in_array( $nextRound , LordwarRound::$INNER_ROUND) )
		{
			$field = LordwarField::INNER;
		}
		else
		{
			$field = LordwarField::CROSS;
		}
		$costBaseArr = $confMgr->getSupportCostBase();
		if(empty( $costBaseArr[$field] ))
		{
			throw new ConfigException( 'no conf ,support basecost' );
		}
		else
		{
			$costBase = $costBaseArr[$field];
		}
		
		$userObj = EnUser::getUserObj();
		$level = $userObj->getLevel();
		if( $costBase[1] < 0 )
		{
			throw new ConfigException( 'need value is negtive' );
		}
		if( $costBase[0] == 1 && !$userObj->subSilver($costBase[1] * $level) )
		{
			throw new FakeException( 'lack silver' );
		}
		
		if(  $costBase[0] == 2 && !$userObj->subGold( $costBase[1]*$level, StatisticsDef::ST_FUNCKEY_LORDWAR_SUPPORT_COST) )
		{
			throw new FakeException( 'lack gold' );
		}
		
		$lordObj->support($nextRound, $needInfo);
		
		$userObj->update();
		$lordObj->update();
	}
	
	public static function getSupportBaseInfo($pos, $teamType)
	{
		$return = array('canSupport' => false, 'baseInfo' => array() );
		
		if ( $pos < 0 || $pos > LordwarConf::AUDITION_PROMOTED_NUM-1 ) 
		{
			throw new FakeException( 'invalid pos: %d', $pos );
		}
		
		$myServerId = Util::getServerIdOfConnection();
		$uid = RPCContext::getInstance()->getUid();
		$myPid = self::getPid($uid);
		
		$confMgr = LordwarConfMgr::getInstance();
		$sess =  $confMgr->getSess();
		
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$teamId = $teamMgr->getTeamIdByServerId($myServerId);
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		
		$teamObj = $procedure->getTeamObj($teamId);
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		if( $curStatus < LordwarStatus::REWARDEND || $curRound < LordwarRound::INNER_AUDITION || $curRound == LordwarRound::INNER_2TO1 || $curRound == LordwarRound::CROSS_2TO1 )
		{
			Logger::debug('can not support 1');
			return $return;
			//throw new FakeException( 'invalid round: %d to support', $curRound );
		}

		//不用开始时间判定，用状态就覆盖了$roundStartTime = $confMgr->getRoundStartTime( $curRound );
		$lordObj = LordObj::getInstance($myServerId, $myPid);
		$supportRound = $lordObj->getSupportRound();
 		//已经助威过了
		if( $supportRound > 0 )
		{
			Logger::fatal('can not support 3,curRound: %d', $supportRound);
			//throw new FakeException( 'already support, support time: %d', $supportRound );
		} 
		
		$teamRoundObj = $teamObj->getTeamRound($curRound, $teamType);
		$procedureInfo = $teamRoundObj->getData();
		if( empty( $procedureInfo['va_procedure']['lordArr'] ) )
		{
			Logger::debug('can not support 4');
			return $return;
			//throw new InterException( 'no promotionfo ' );
		}
		$promotionInfo =  $procedureInfo['va_procedure']['lordArr'] ;
		
		foreach ( $promotionInfo as $index => $supportPromotee )
		{
			if( $myPid == $supportPromotee['pid'] && $myServerId == $supportPromotee['serverId'])
			{
				Logger::debug('can not support 5');
				return $return;
			}
		}
		
		//判定这个人此轮是不是轮空,这里经过过滤，下一轮一定是有意义的
		$nextRound = LordwarUtil::getNextRound($curRound);
		$step = LordwarConf::AUDITION_PROMOTED_NUM/LordwarRound::$ROUND_RET_NUM[$nextRound];
		$promoteeIndex = $pos;
		$offset = floor( $promoteeIndex/$step ) * $step ;
		
		if( $promotionInfo[$pos]['rank'] > 2*LordwarRound::$ROUND_RET_NUM[$nextRound] )
		{
			Logger::debug('can not be support 7');
			return $return;
		}
		$fighters = self::getPromotionFighters($promotionInfo, LordwarRound::$ROUND_RET_NUM[$nextRound], $offset);
		
		if( count( $fighters['fightArr'] ) != 2 )//轮空啦，计算错误啦
		{
			Logger::debug('can not support 6, %s',$fighters['fightArr']  );
			return $return;
			throw new FakeException( 'this round: %d, pos: %d is round free', $curRound, $pos );
		}
		$return['canSupport'] = true;
		$return['baseInfo'] = $promotionInfo[$pos];
		return $return;
	}
	
	public static function getMySupport($uid)
	{
		$serverId = Util::getServerIdOfConnection();
		$pid = LordwarLogic::getPid($uid);
		
		$lordObj = LordObj::getInstance($serverId, $pid);
		$ret = $lordObj->getMySupport();		
		return $ret;
	}
	
	public static function getPromotionInfo()
	{
		$confMgr = LordwarConfMgr::getInstance();
		$serverId = Util::getServerIdOfConnection();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance( WolrdActivityName::LORDWAR, $sess );
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		
		$field = LordwarProcedure::getDefaultField();
		$procedure= LordwarProcedure::getInstance($sess, $field);
		$teamObj = $procedure->getTeamObj($teamId);
		$round = $teamObj->getCurRound();
		$status = $teamObj->getCurStatus();
		
		if( ! ($round >= LordwarRound::INNER_AUDITION && $round <= LordwarRound::INNER_2TO1)
			&&  ! ($round >= LordwarRound::CROSS_AUDITION && $round <= LordwarRound::CROSS_2TO1) )
		{
			throw new FakeException( 'not in promotion round:%d', $round );
		}

		$teamRoundWin = $teamObj->getTeamRound($round, LordwarTeamType::WIN);
		$teamRoundLose = $teamObj->getTeamRound($round, LordwarTeamType::LOSE);
		
		$dataWin = $teamRoundWin->getData();
		$dataLose = $teamRoundLose->getData();
		
		if(  ( empty($dataWin['va_procedure']['lordArr']) || empty($dataLose['va_procedure']['lordArr']) )
				&&  $round != LordwarRound::INNER_AUDITION && $round != LordwarRound::CROSS_AUDITION )
		{
			$preRound = LordwarUtil::getPreRound($round);
			Logger::info('no data in curRound:%d, get data from lastRound:%d', $round, $preRound);
			
			//$dataWin = $teamObj->getTeamRound($preRound, LordwarTeamType::WIN)->getData();
			//$dataLose = $teamObj->getTeamRound($preRound, LordwarTeamType::LOSE)->getData();
			/*
			 这里不能使用接口getTeamRound获取数据。当curRound的胜者组数据有了，败者组数据还没有写入
			 此时不能通过getTeamRound获取数据
			*/
			$arrRet = LordwarTeam::getTeamRoundData($teamId, $sess, $preRound);
			if( count($arrRet) < 2)
			{
				throw new InterException('not found data. teamId:%d, round:%d', $teamId, $preRound);
			}
			$dataWin = $arrRet[LordwarTeamType::WIN];
			$dataLose = $arrRet[LordwarTeamType::LOSE];
		} 
		
		Logger::debug('data info is: %s, %s, round: %d,status', $dataWin,$dataLose, $round, $status);
		
		if( empty($dataWin['va_procedure']['lordArr']) || empty($dataLose['va_procedure']['lordArr']) )
		{
			throw new InterException('no data');
		}
		
		$res = array(
				'round' => $round,
				'status' => $status,
				'winLord' => $dataWin['va_procedure']['lordArr'],
				'loseLord' => $dataLose['va_procedure']['lordArr'],
		);
		
		return $res;
	}
	

	public static function getPromotionHistory( $round )
	{
		$serverId = Util::getServerIdOfConnection();
		$sess = LordwarConfMgr::getInstance()->getSess();
		
		$roundStartTime = LordwarConfMgr::getInstance()->getRoundStartTime( LordwarRound::INNER_32TO16 );
		if (defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$lastMergeTime = strtotime( GameConf::MERGE_SERVER_OPEN_DATE );
			if( $lastMergeTime >= $roundStartTime )
			{
				return 'merged';
			}
		}
		
		$groupId = Util::getGroupByServerId($serverId);
		
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		
		
		$roundInfo = LordwarTeam::getTeamRoundData($teamId, $sess, $round);
		
		$return = array('winLord' => array(), 'loseLord' => array());
		if( !empty( $roundInfo[LordwarTeamType::WIN]['va_procedure']['lordArr'] ) )
		{
			$return['winLord'] = $roundInfo[LordwarTeamType::WIN]['va_procedure']['lordArr'];
		}
		else
		{
			Logger::fatal('cant get win team data. round:%d', $round);
		}
		if( !empty( $roundInfo[LordwarTeamType::LOSE]['va_procedure']['lordArr'] ) )
		{
			$return['loseLord'] = $roundInfo[LordwarTeamType::LOSE]['va_procedure']['lordArr'];
		}
		else
		{
			Logger::fatal('cant get lose team data. round:%d', $round);
		}
		
		return $return;
	}
	
	public static function getTempleInfo()
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$serverId = Util::getServerIdOfConnection();
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$realRound = $procedure->getTeamObj($teamId)->getCurRound();
		$realStatus = $procedure->getTeamObj($teamId)->getCurStatus();
		if( $realRound < LordwarRound::CROSS_2TO1 || $realStatus < LordwarStatus::FIGHTEND )
		{
			throw new FakeException( 'invalid time to getInfo' );
		}
		
		$templeInfo = LordwarInnerDao::getTempleInfo($sess);
		Logger::debug('temple info: %s', $templeInfo);
		
		$templeInfoMonk = array();
		if( !empty( $templeInfo ) )
		{
			$templeInfoMonk = $templeInfo['va_temple'];
		}
		
		if ( empty( $templeInfoMonk ))
		{
			//之前是检查过一定是某一届的最后一轮结束阶段才能到这里，也就说明肯定是需要有数据的
			$templeInfoMonk = self::refreshTemple();
		}
		
/* 		// need deal server name 
		foreach ( $templeInfoMonk as $monkIndex => $oneMonk )
		{
			if( !empty( $oneMonk['serverId'] ) )
			{
				$templeInfoMonk[$monkIndex]['serverName'] = 'bitch01区-liu痢膨';
			}
		}
		 */
		Logger::debug('after refresh temple info: %s', $templeInfo);
		return $templeInfoMonk;
		
	}
	
	public static function refreshTemple()
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR,$sess);
		$serverId = Util::getServerIdOfConnection();
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$teamObj = $procedure->getTeamObj($teamId);
		$allTempleInfo = array_fill(0, 3, array());
		
		$retWin = $teamObj->getTeamRound(LordwarRound::CROSS_2TO1, LordwarTeamType::WIN)->getData();
		$lordArrWin = $retWin['va_procedure']['lordArr'];
		
		foreach ( $lordArrWin as $posWin => $promoteeWin )
		{
			if( !empty( $promoteeWin['serverId'] ) && !empty( $promoteeWin['pid'] )&& ( $promoteeWin['rank'] == 1 || $promoteeWin['rank'] == 2 ) ) 
			{
				$allTempleInfo[$promoteeWin['rank']-1] = $promoteeWin;
			}
		}
		
		$retLose = $teamObj->getTeamRound(LordwarRound::CROSS_2TO1, LordwarTeamType::LOSE)->getData();
		$lordArrLose = $retLose['va_procedure']['lordArr'];
		
		foreach ( $lordArrLose as $posLose => $promoteeLose )
		{
			if( !empty( $promoteeLose['serverId'] ) && !empty( $promoteeLose['pid'] )&& $promoteeLose['rank'] == 1 )
			{
				$allTempleInfo[2] = $promoteeLose;
			}
		}

		foreach ( $allTempleInfo as $pos => $oneMonk )
		{
			if( !empty( $oneMonk ) )
			{
				// 20160504 膜拜增加称号
				$title = 0;
				try
				{
					$worshipPid = $allTempleInfo[$pos]['pid'];
					$worshipServerId = $allTempleInfo[$pos]['serverId'];
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
				$allTempleInfo[$pos]['title'] = $title;
				
				unset( $allTempleInfo[$pos]['rank'] );
				unset( $allTempleInfo[$pos]['loseNum'] );
				unset( $allTempleInfo[$pos]['pid'] );
			}
		}
		Logger::debug('temple info will update to db: %s', $allTempleInfo);
		LordwarInnerDao::updateTempleInfo($sess, $allTempleInfo);
		
		return $allTempleInfo;
		
	}
	

	public static function getPromotionBtl($round,$teamType, $serverId1, $uid1, $serverId2, $uid2)
	{	
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		
		$serverId = Util::getServerIdOfConnection();
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		
		$recordArr = LordwarTeam::getPromotionBtlView($teamId, $teamType, $sess, $round);
		
		if( empty( $recordArr ) )
		{
			return array();
		}
		
		$return = array();
		Logger::debug('recordArr from db are: %s', $recordArr);
		
		foreach ( $recordArr as $subRound => $recordInfo )
		{
			foreach ( $recordInfo as $subSubRound => $oneRecordInfo )
			{
				if( in_array( $oneRecordInfo[ 'atk' ]['serverId']  , array( $serverId1, $serverId2 )) 
				&& in_array( $oneRecordInfo[ 'def' ]['serverId']  , array( $serverId1, $serverId2 )) 
				&& in_array( $oneRecordInfo[ 'atk' ]['uid']  , array( $uid1, $uid2 ))
				&& in_array( $oneRecordInfo[ 'def' ]['uid']  , array( $uid1, $uid2 )))
				{
					$return[] = $oneRecordInfo;
				}
			}
		}
		return $return;
		
	}
	
	/**
	 * 
	 * @param string $field
	 * @param bool $force  当跑挂了，已经过来海选阶段。这将这个变量设置成true。可忽略时间检查
	 */
	public static function audition($field, $force = false)
	{
		
		$confMgr = LordwarConfMgr::getInstance();
		$curRoundByConf = $confMgr->getRound();
		Logger::debug('current round by conf are: %d', $curRoundByConf);
		
		$needRound = LordwarRound::OUT_RANGE;
		if( $field == LordwarField::INNER )
		{
			$needRound = LordwarRound::INNER_AUDITION;
		}
		else if( $field == LordwarField::CROSS )
		{
			$needRound = LordwarRound::CROSS_AUDITION;
		}
		else
		{
			Logger::fatal('invalid field:%s', $field);
			return;
		}
		
		if( !$force && $curRoundByConf != $needRound )
		{
			Logger::info('not in audition time');
			return;
		}
		
	
		//获取分组
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR,$sess);
	
		if( $field == LordwarField::INNER)
		{
			$serverId = Util::getFirstServerIdOfGroup();
			$myTeamId = $teamMgr->getTeamIdByServerId($serverId);
			if( $myTeamId < 0 )
			{
				Logger::info('this server not in any team. serverId:%d', $serverId);
				return;
			}
			$allTeamId = array($myTeamId);
		}
		else 
		{
			$allTeamInfo = $teamMgr->getAllTeam();
			$allTeamId = array_keys($allTeamInfo);
		}
		
		$teamCount = count($allTeamId);
		// 完毕的组和出错的组
		$finishTeam = array();
		$errTeam = array();
		
		$procedure = LordwarProcedure::getInstance($sess, $field);
		
		/*
		 	这里的多进程实现和海贼不一样。在每个子进程中会完整的执行完若干team的海选
		 */
		if( $teamCount > 1  && LordwarConf::PROCESS_TEAM_NUM > 0 )
		{
			$chunkSize = ceil( count( $allTeamId ) / LordwarConf::PROCESS_TEAM_NUM);
			$arrBatch = array_chunk( $allTeamId, $chunkSize );
			Logger::debug('multi-process arrBactch are : %s', $arrBatch);
			$eg = new ExecutionGroup();
			foreach( $arrBatch as $batch )
			{
				$eg->addExecution('LordwarLogic::doTeamAudition', array($sess, $field, $needRound,$batch) );
			}
			$ret = $eg->execute();
			
			if( !empty($ret) )
			{
				Logger::fatal('there some team audition faield:');
				foreach( $ret as $value )
				{
					Logger::fatal('batch:%s', $value);
				}
			}
		}
		else
		{
			self::doTeamAudition($sess, $field, $needRound, $allTeamId);
		}

		Logger::info("audition end");
		
	}
	
	public static function doTeamAudition($sess, $field, $round, $arrTeamId)
	{
		self::doAudition($sess, $field, $round, LordwarTeamType::WIN, $arrTeamId);
		self::doAudition($sess, $field, $round, LordwarTeamType::LOSE, $arrTeamId);
	}
	
	
	/**
	 * 处理若干组的胜者组或者败者组的海选
	 */
	public static function doAudition($sess, $field, $round, $teamType, $arrTeamId)
	{

		//获取所有组参赛人的信息： { teamId=>[ {pid,server_id, loseNum}] }

		$procedure = LordwarProcedure::getInstance($sess, $field);
		
		$arrTeamData = array();
		foreach( $arrTeamId as $teamId )
		{
			$teamObj = $procedure->getTeamObj($teamId);
			if( $teamObj->getCurRound() != $round  )
			{
				Logger::fatal('not in audtion round. teamId:%d, round:%d, status:%d', $teamId, $teamObj->getCurRound(), $teamObj->getCurStatus());
				continue;
			}

			if(  $teamObj->getTeamRound($round, $teamType)->getStatus() == LordwarStatus::DONE )
			{
				Logger::info('audition already done. teamId:%d, round:%d, teamType:%d', $teamId, $round, $teamType);
				continue;
			}
			
			if(  $teamObj->getTeamRound($round, $teamType)->getStatus() != LordwarStatus::FIGHTING )
			{
				Logger::fatal('audition info not prepared. teamId:%d, round:%d, teamType:%d', $teamId, $round, $teamType);
				continue;
			}
			$arrTeamData[$teamId] = array(
				'teamType' => $teamType,
				'arrPlayer' => self::getUserForAudition($teamId, $teamType, $field), 
			);
			
			Logger::info('get audition data. teamId:%d, teamType:%d', $teamId, $teamType);
			Logger::debug('$arrTeamData are: %s',$arrTeamData);
		}
		
		if( empty($arrTeamData) )
		{
			Logger::info('all team audition done. field:%s, sess:%d, teamType:%d, teamNum:%d', $field, $sess, $teamType, count($arrTeamId));
			return;
		}
		
		$confMgr = LordwarConfMgr::getInstance();
		$outLoseNum = $confMgr->getAuditionOutLoseNum( $field );
		$auditionBreakTime = $confMgr->getAuditionBreakTime();
		
		$teamCount = count($arrTeamData);
		$arrFinishTeamId = array();
		while (true)
		{
			$startTime = time();
		
			foreach( $arrTeamData as $teamId => $teamData )
			{
				$teamType = $teamData['teamType'];
				if( isset( $arrFinishTeamId[$teamId] ) && $arrFinishTeamId[$teamId] )
				{
					Logger::info('audition of teamId:%d already done. teamType:%d', $teamId, $teamType);
					continue;
				}

				$arrRet = self::doAuditionOnce($field, $teamId, $round, $teamType, $teamData['arrPlayer'], $outLoseNum);
				
				$arrPlayer = $arrRet['arrPlayer'];
				$arrTeamData[$teamId]['arrPlayer'] = $arrPlayer;
				if( count( $arrPlayer ) <= LordwarConf::AUDITION_PROMOTED_NUM )
				{
					
					foreach ( $arrPlayer as $playerIndex =>  $player )
					{
						self::updateTeamType( $player['server_id'], $player['pid'], $teamType, $field);
						
						$lordObj = LordObj::getInstance($player['server_id'], $player['pid']);
						$arrPlayer[$playerIndex] = $lordObj->getRecordInfo();//array_merge( $player, );
						$arrPlayer[$playerIndex]['rank'] = 32;
						$arrPlayer[$playerIndex]['loseNum'] = 0;
						//这里就不用release
					}
					
					// 将32强写入数据库
					$lordArr = array_merge( $arrPlayer );
					$appendNum = LordwarConf::AUDITION_PROMOTED_NUM - count($lordArr);
					$appendArr = array();
					if($appendNum > 0)
					{
						$appendArr = array_fill(count($lordArr)-1, $appendNum , array('serverId' => 0,'pid' => 0,'uid' => 0, 'rank' => 32, 'loseNum' => 0 ));
					}
					$lordArr = array_merge( $lordArr,$appendArr );
					shuffle($lordArr);
					$procedure = LordwarProcedure::getInstance($sess, $field);
					$teamObj = $procedure->getTeamObj($teamId);
					$teamRoundObj = $teamObj->getTeamRound($round, $teamType);
					$auditionInfo = $teamRoundObj->getData();
					$auditionInfo['sess'] = $sess;
					$auditionInfo['status'] = LordwarStatus::DONE;//直接一写就是end
					$auditionInfo['va_procedure']['lordArr'] = $lordArr;
					$auditionInfo['va_procedure']['recordArr'] = array();
					$teamRoundObj->setData($auditionInfo);
					$teamRoundObj->update();
					
					$arrFinishTeamId[$teamId] = true;
					Logger::info('one team audition done. teamId:%d, chosed:%d', $teamId, count($arrPlayer) );
				}
				
			}
		
			// 检查是否结束
			if ( count($arrFinishTeamId) >= $teamCount)
			{
				Logger::info('all team audition over');
				break;
			}
		
			$executeTime = time() - $startTime;
			$sleepTime = $auditionBreakTime - $executeTime;
			if($sleepTime <= 0)
			{
				$sleepTime = 0;
			}
			
			Logger::info("audition once cost time:%d, sleep:%d", $executeTime, $sleepTime);
			sleep($sleepTime);
		}
		
		Logger::info('doAudition done. field:%s, sess:%d, teamType:%d, teamNum:%d', $field, $sess, $teamType, count($arrTeamId));
	}
	
	
	
	/**
	 * 海选赛中，一一配对打一次
	 * @param array $arrPlayer
	 */
	public static function doAuditionOnce( $field, $teamId, $round, $teamType, $arrPlayer, $outLoseNum)
	{
		$arrId = array_keys( $arrPlayer);
		shuffle( $arrId );
		
		$playerNum = count( $arrPlayer );
		Logger::debug('play num: %d',$playerNum);
		
		$index = 0;
		while( $playerNum > LordwarConf::AUDITION_PROMOTED_NUM )
		{
			if( empty($arrId[$index]) || empty($arrId[$index+1]) )
    		{
    			Logger::debug('no more player');
    			break;
    		}
			
    		$player1 = $arrPlayer[ $arrId[$index] ];
    		$player2 = $arrPlayer[ $arrId[$index + 1] ];
    		$lord1 = LordObj::getInstance( $player1['server_id'], $player1['pid']);
    		$lord2 = LordObj::getInstance( $player2['server_id'], $player2['pid']);
		
    		$btRet = self::fight( $player1['server_id'], $player1['pid'], $player2['server_id'], $player2['pid'], $round);
		
    		$losePid = 0;
    		$loseServerId = 0;
    		if( $btRet['res'] == 0 )
    		{
    			$losePid = $btRet['def']['pid'];
    			$loseServerId = $btRet['def']['serverId'];
    		}
    		else
    		{
    			$losePid = $btRet['atk']['pid'];
    			$loseServerId = $btRet['atk']['serverId'];
    		}
    		
    		if( $player1['pid'] == $losePid && $player1['server_id'] == $loseServerId )
    		{
    			$loseId = $arrId[$index];
    		}
    		else if( $player2['pid'] == $losePid && $player2['server_id'] == $loseServerId )
    		{
    			$loseId = $arrId[$index+1];
    		}
    		else
    		{
    			throw new InterException('invalid btRet:%s, player1:%s, player2:%s', $btRet, $player1, $player2);
    		}
    		
    		$arrPlayer[$loseId]['loseNum'] ++;
    		
			
			if( $field == LordwarField::INNER )
			{
				$lord1->setLosenum( $teamType, $arrPlayer[ $arrId[$index] ]['loseNum'] );
				$lord2->setLosenum( $teamType, $arrPlayer[ $arrId[$index+1] ]['loseNum'] );
			}
			else
			{
				self::updateCrossLoseNum($arrPlayer[$loseId]['server_id'], $arrPlayer[$loseId]['pid'], $arrPlayer[$loseId]['loseNum'], $teamType, $field);
			}
			
			if( $arrPlayer[$loseId]['loseNum'] >= $outLoseNum)
			{
				unset( $arrPlayer[$loseId] );
				$playerNum--;
			}
			
			//保存下战报
			$lord1->saveBattleRecord($round, $btRet, $teamType);
			$lord2->saveBattleRecord($round, $btRet, $teamType);
			$lord1->update();
			$lord2->update();
			
			LordObj::release($player1['server_id'], $player1['pid']);
			LordObj::release($player2['server_id'], $player2['pid']);
			
			$index += 2;
		}
		
		return array(
			'arrPlayer' => $arrPlayer,
		);
	}
	
	
	public static function updatePromoteInfo($lordArr,$recordArr, $teamId, $teamType, $round)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$dateYmd = date( 'yyyymmdd',Util::getTime() );
		
		$updateArr = array( 
				'team_id'=> $teamId,
				'team_type' => $teamType,
				'round' => $round,
				'sess' => $sess,
				'status' => LordwarStatus::FIGHTING,
				'date_ymd' => $dateYmd,
				'va_procedure' => array('lordArr' => $lordArr,'recordArr' => $recordArr ),
		);
		
		LordwarDao::updateLordwar($updateArr);
	}
	
	public static function updateCrossLoseNum($serverId,$pid,$loseNum,$teamType,$field)
	{
		$updateArr = array();
		if( $teamType == LordwarTeamType::WIN )
		{
			$updateArr = array('winner_losenum' => $loseNum);
		}
		elseif ( $teamType == LordwarTeamType::LOSE )
		{
			$updateArr = array('loser_losenum' => $loseNum);
		}
		LordwarCrossDao::updateLordInfo($serverId,$pid,$updateArr);
	}
	
	
	
	public static function updateTeamType($serverId,$pid, $teamType, $field)
	{
		if( $field == LordwarField::INNER )
		{
			$lordObj = LordObj::getInstance($serverId, $pid);
			$lordObj->setTeamType($teamType);
			$lordObj->update();
		}
		elseif ( $field == LordwarField::CROSS )
		{
			$updateArr = array('team_type' => $teamType);
			LordwarCrossDao::updateLordInfo($serverId,$pid,$updateArr);
		}
	}
	
	public static function getUserForAudition($teamId,$teamType,$field) 
	{
		$confMgr = LordwarConfMgr::getInstance();
		
		$offset = 0;
		$lordArr = array();
		$registerTime = $confMgr->getRoundStartTime(LordwarRound::REGISTER);
		Logger::debug('register time when get audition user : %s', $registerTime);
		if( empty( $registerTime ) )
		{
			Logger::fatal('empty registertime ');
			return array();
		}
		if( $field != LordwarField::INNER && $field != LordwarField::CROSS )
		{
			return array();
		}
		
		do
		{
			$partLord = LordwarDao::getSignUserForAudition( $teamId, $teamType,$field, $registerTime, $offset);
			Logger::debug('partLord are: %s', $partLord);
			if ( empty($partLord ) )
			{
				break;
			}
			$lordArr = array_merge( $lordArr,$partLord);
			$offset += count( $partLord );
		}
		while (count($partLord) >= CData::MAX_FETCH_SIZE);
		
		if( empty( $lordArr ) )
		{
			return array();
		}
		
		$lordArrAtfter = array();
		foreach ( $lordArr as $index => $lordInfo )
		{
			$serverId = $lordInfo['server_id'];
			$pid = $lordInfo['pid'];
			$key = LordwarUtil::getKey($serverId, $pid);
			$lordArrAtfter[$key] = $lordInfo;
		}
		Logger::debug('return user for audition are: %s', $lordArr);
		
		return $lordArrAtfter;
	}
	
	
	/**
	 * 一定是要有两个人打的时候才调用改函数
	 * 战报结构已经改了，这个函数的返回是不是应该改一下
	 * 
	 * @return
	 * 	{
	 * 		replay:int
	 * 		atk:{server_id=>int, pid=>int}
	 * 		def:{server_id=>int, pid=>int}
	 * 		res:int  0:atk胜； 1:def胜
	 * 	}
	 */
	public static function fight($fServerId,$fPid,$bServerId,$bPid,$round)
	{
		$return = array(
				'atk' => array(), 'def' => array(),'res' => array(),'replyId' => 0,
		);
		
		$lordObjFront = LordObj::getInstance($fServerId, $fPid);
		$lordObjBack = LordObj::getInstance( $bServerId, $bPid);
		$frontLordVa = $lordObjFront->getVa();
		$backLordVa = $lordObjBack->getVa();
		
		if( $frontLordVa['fightPara']['fightForce']  >=  $backLordVa['fightPara']['fightForce'])
		{
			$fighter1 = $lordObjFront;
			$fighter2 = $lordObjBack;
		}
		else 
		{
			$fighter2 = $lordObjFront;
			$fighter1 = $lordObjBack;
		}
		
		$arrDamageIncreConf = array
		(
				array(BattleDamageIncreType::Fix, 10, 14, 5000),
				array(BattleDamageIncreType::Fix, 15, 19, 10000),
				array(BattleDamageIncreType::Fix, 20, 24, 15000),
				array(BattleDamageIncreType::Fix, 25, 29, 20000),
				array(BattleDamageIncreType::Fix, 30, 30, 25000),
		);
		
		$arrExtra = array(
				'isLRD' => true,
				'type' => BattleType::LORD_WAR,
				'damageIncreConf' => $arrDamageIncreConf,
		);
		
		$fighterPara1 = $fighter1->getVa();
		$fighterPara2 = $fighter2->getVa();
		
		if( in_array( $round , LordwarRound::$INNER_ROUND) )
		{
			$db = null;
		}
		else
		{
			$db = LordwarUtil::getCrossDbName();
		}
		
		
		for( $i = 0; $i < 3; $i++ )
		{
			try 
			{
				$battleRet = EnBattle::doHero( $fighterPara1['fightPara'] , $fighterPara2['fightPara'], 0, NULL, NULL, $arrExtra, $db );
				break;
			}
			catch ( Exception $e )
			{
				Logger::fatal('doHero failed. fServerId:%d, fPid:%d, bServerId:%d, bPid:%d, round:%d, msg:%s', 
						$fServerId, $fPid, $bServerId, $bPid, $round, $e->getMessage());
				continue;
			}
			
		}
		if( empty($battleRet) )
		{
			throw new InterException('doHero failed. fix it');
		}
		
		$appraisal = $battleRet['server']['appraisal'];
		
		$return['atk'] = $fighter1->getRecordInfo();
		$return['def'] = $fighter2->getRecordInfo();
		$return['res'] = BattleDef::$APPRAISAL[$appraisal]<= BattleDef::$APPRAISAL['D'] ? LordwarDef::BTL_RET_WIN: LordwarDef::BTL_RET_LOSE;
		$return['replyId'] = $battleRet['server']['brid'];
		if( in_array( $round , LordwarRound::$CROSS_ROUND) )
		{
			$return['replyId'] = RecordType::LRD_PREFIX.$return['replyId'];
		} 

		return $return;
	}
	

	public static function getAllTeamId()
	{
		$allTeamId = array();
		$sess = LordwarUtil::getSess();
		$field = LordwarUtil::getField();
		if( $field == LordwarField::INNER )
		{
			return $allTeamId[0]['team_id'] = 0;
		}
		else if( $field == LordwarField::CROSS )
		{
			$allTeamId = LordwarCrossDao::getAllTeamId($sess);
			return $allTeamId;
		}
		else
		{
			return array();
		}
	}
	
	public static function promotion($field, $wantRound, $force = FALSE)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$confRound = $confMgr->getRound();
		if( !$force )
		{
			if( $wantRound != $confRound )
			{
				Logger::info('want to run round: %d, conf round: %d', $wantRound, $confRound);
				return;
			}
		}
		
		if( in_array($wantRound, LordwarRound::$INNER_PROMO))
		{
			if( $field != LordwarField::INNER )
			{
				Logger::fatal('invalid field: %d round: %d', $field, $wantRound );
				return;
			}
		}
		elseif( in_array($wantRound , LordwarRound::$CROSS_PROMO)) 
		{
			if( $field != LordwarField::CROSS )
			{
				Logger::fatal('invalid field: %d round: %d', $field, $wantRound );
				return;
			}
		}
		else 
		{
			Logger::fatal('invalid field: %d round: %d', $field, $wantRound );
				return;
		}
		
		//获取分组
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR,$sess);
		
		if( in_array($wantRound, LordwarRound::$INNER_PROMO))
		{
			$serverId = Util::getFirstServerIdOfGroup();
			$myTeamId = $teamMgr->getTeamIdByServerId($serverId);
			if( $myTeamId < 0 )
			{
				Logger::info('this server not in any team. serverId:%d', $serverId);
				return;
			}
			$allTeamId = array($myTeamId);
		}
		else
		{
			$allTeamInfo = $teamMgr->getAllTeam();
			$allTeamId = array_keys($allTeamInfo);
		}
		
		$teamCount = count($allTeamId);
		// 完毕的组和出错的组
		$finishTeam = array();
		$errTeam = array();
		$procedure = LordwarProcedure::getInstance($sess, $field);
		
		/*
		 这里的多进程实现和海贼不一样。在每个子进程中会完整的执行完若干team的晋级赛
		*/
		if( $teamCount > 1  && LordwarConf::PROCESS_TEAM_NUM > 0 )
		{
			$chunkSize = ceil( count( $allTeamId ) / LordwarConf::PROCESS_TEAM_NUM);
			$arrBatch = array_chunk( $allTeamId, $chunkSize );
			Logger::debug('multi-process arrBactch are : %s', $arrBatch);
			$eg = new ExecutionGroup();
			foreach( $arrBatch as $batch )
			{
				$eg->addExecution('LordwarLogic::doPromotionTeam', array($sess,$wantRound,$field, $batch) );
			}
			$ret = $eg->execute();
				
			if( !empty($ret) )
			{
				Logger::fatal('there some team promotion faield:');
				foreach( $ret as $value )
				{
					Logger::fatal('batch:%s', $value[0]);
				}
			}
		}
		else
		{
			self::doPromotionTeam($sess,$wantRound,$field,$allTeamId);
		}
		
		Logger::info("promotion end");
		
		return true;
	
	}
	
	public static function doPromotionTeam($sess, $round, $field, $arrTeamId)
	{
		$procedure = LordwarProcedure::getInstance($sess, $field);
		$preRound = LordwarUtil::getPreRound($round);
		
		$arrTeamType = array(LordwarTeamType::WIN, LordwarTeamType::LOSE);
		
		$arrTeamIdToRun = array();
		
		foreach( $arrTeamId as $teamId )
		{
			$teamObj = $procedure->getTeamObj($teamId);
			//检查状态
			if( $teamObj->getCurRound() != $round  )
			{
				Logger::fatal('not in promotion round. teamId:%d, roundcur:%d, roundwant:%d, status:%d', 
							$teamId, $teamObj->getCurRound(), $round, $teamObj->getCurStatus());
				continue;
			}
		
			if(  $teamObj->getCurStatus() >= LordwarStatus::FIGHTEND )
			{
				Logger::info('promotion already done. teamId:%d, round:%d', $teamId, $round);
				continue;
			}

			/*
			 * 此处不用检查，TODO 确认一下  
			$preStatus = $teamObj->getStatusByRound($preRound);
			if( $preStatus != LordwarStatus::DONE )
			{
				Logger::fatal('preround: %d not done, teamId: %d, round: %d, teamType: %d ', $preRound, $teamId, $round );
				continue;
			}
			*/
			
			//准备数据
			foreach( $arrTeamType as $teamType )
			{
				$teamRoundObj = $teamObj->getTeamRound($round, $teamType);
				
				if( $teamRoundObj->getSubRound() == 0 )
				{
					$preRoundObj = $teamObj->getTeamRound($preRound, $teamType);
					$teamRoundObj->initDataFromLastRound($preRoundObj->getData());
					Logger::info('init data from last round. teamId:%d, curRound:%d', $teamId, $round);
				}
				else
				{
					Logger::info('no need to init. teamId:%d, curRound:%d, subRound:%d', $teamId, $round, $teamRoundObj->getSubRound());
				}
			}
			
			$arrTeamIdToRun[] = $teamId;
		}
		
		if( empty($arrTeamIdToRun) )
		{
			Logger::info('all team promotion done. round:%d', $round);
			return;
		}
		
		$confMgr = LordwarConfMgr::getInstance();
		$promotionLoseNum = $confMgr->getPromotionOutLoseNum($field);

		$subRoundNum = LordwarUtil::getPromotionSubroundNum($field);
		for( $subRound = 0;  $subRound < $subRoundNum; $subRound++ )
		{
			$startTime = time();
			
			foreach ( $arrTeamIdToRun as $teamId )
			{
				$teamObj = $procedure->getTeamObj($teamId);
				
				foreach( $arrTeamType as $teamType )
				{
					Logger::info('team sub promotion start. round:%d, teamId:%d, teamType:%d, subRound:%d', $round, $teamId, $teamType, $subRound);
					$teamRoundObj = $teamObj->getTeamRound($round, $teamType);
					$curSubRound = $teamRoundObj->getSubRound();
					if( $curSubRound > $subRound )
					{
						Logger::info('curRound:%d ignore', $curSubRound);
						continue;
					}
					
					$data = $teamRoundObj->getData();
					$ret = self::doPromotionOnce($data['va_procedure']['lordArr'], $teamId, $teamType, $round, $subRound, $promotionLoseNum);
					
					$data['va_procedure']['lordArr'] = $ret['lordArr'];
					$data['va_procedure']['recordArr'][$subRound] = $ret['recordArr'];
					$data['sess'] = $sess;//XXX 要不要保存上届数据的问题，如果需要保存的话还需要改表
					if($subRound == $subRoundNum -1 )
					{
						$data['status'] = LordwarStatus::FIGHTEND;
						Logger::info('team promotion done. round:%d, teamId:%d, teamType:%d', $round, $teamId, $teamType);
						//TODO: 要不要检查一下va中的数据，确认该进阶的都进阶了
					}
					$teamRoundObj->setData($data);
					$teamRoundObj->update();
					
				}
			}

			Logger::debug('zhanshiyu now push');
			self::push($field, LordwarPush::NOW_STATUS, array( 'needPushTeamArr' => $arrTeamIdToRun) );
			
			$executeTime = time() - $startTime;
			$sleepTime = $confMgr->getPromotionSleepTime($round, $subRound);//TODO 这个时间计算的方式要不要和海选保持一致
			
			if( $subRound ==  $subRoundNum -1 )
			{
				$sleepTime = 0;
			}
			Logger::info("sub promotion cost time:%d, sleep:%d, subRound:%d", $executeTime, $sleepTime, $subRound);
			sleep($sleepTime);
		}
		
		Logger::info('doPromotionTeam done. field:%s, sess:%d, teamType:%d, teamNum:%d', $field, $sess, $teamType, count($arrTeamId));
	
	}
	
	public static function doPromotionOnce( $lordArr, $teamId, $teamType, $round, $subRound, $promoOutLoseNum )
	{
		Logger::debug('now begin doPromotionOnce,  lordArr %s, $round %d, subRound %d, promoOutLoseNum %d ',  $lordArr, $round, $subRound, $promoOutLoseNum );
		$recordArr = array();
		$fightForTopX = LordwarRound::$ROUND_RET_NUM[$round];
		$step = LordwarConf::AUDITION_PROMOTED_NUM/$fightForTopX;
		
		$arrRefreshRecordField = array('uid', 'uname', 'htid', 'level', 'vip', 'dress', 'fightForce', 'serverName');
		
		for ($offset = 0; $offset < LordwarConf::AUDITION_PROMOTED_NUM; $offset += $step)
		{
			$fightersArr = array();
			$fightersPair = self::getPromotionFighters($lordArr, $fightForTopX, $offset);
			if( $fightersPair['done'] )
			{
				continue;
			}
			$fightersArr = $fightersPair['fightArr'];
			$fighterCount = count( $fightersArr );
			
			foreach( $fightersArr as $fighterIndex )
			{
				$lordObj = LordObj::getInstance($lordArr[$fighterIndex]['serverId'], $lordArr[$fighterIndex]['pid']);
				$recordInfo = $lordObj->getRecordInfo();
				foreach( $arrRefreshRecordField as $field  )
				{
					$lordArr[$fighterIndex][$field] = $recordInfo[$field];
				}
			}
			if ( $fighterCount == 0 ) 
			{
				continue;//两个人都是轮空的
			}
			elseif( $fighterCount > 2 )
			{
				Logger::fatal('got more than 2 fighters in one fight,all promotee info: %s',$lordArr);
			}
			elseif( $fighterCount == 1 )
			{
				$lordArr[$fightersArr[0]]['rank'] = $fightForTopX;
				Logger::info('one player promoted because no opponent. teamId:%d, teamType:%d, round:%d, subRound:%d, pid:%d, serverId:%d, rank:%d',
					$teamId, $teamType, $round, $subRound, $lordArr[$fightersArr[0]]['pid'], $lordArr[$fightersArr[0]]['serverId'], $fightForTopX);
				
				LordObj::release($lordArr[$fightersArr[0]]['serverId'], $lordArr[$fightersArr[0]]['pid']);
			}
			else 
			{
				$serverId1 = $lordArr[$fightersArr[0]]['serverId'];
				$serverId2 = $lordArr[$fightersArr[1]]['serverId'];
				$pid1 = $lordArr[$fightersArr[0]]['pid'];
				$pid2 = $lordArr[$fightersArr[1]]['pid'];
				//这是没有决出这step个人中的top而真正需要战斗的地方
				$lordObjFront = LordObj::getInstance($serverId1, $pid1);
				$lordObjBack = LordObj::getInstance($serverId2, $pid2);
				
				$btlRet = self::fight($serverId1,$pid1,$serverId2,$pid2,$round); 
				
				if(($btlRet['atk']['serverId'] == $serverId1 && $btlRet['atk']['pid'] == $pid1 && $btlRet['res'] == LordwarDef::BTL_RET_WIN)
				||($btlRet['def']['serverId'] == $serverId1 && $btlRet['def']['pid'] == $pid1 && $btlRet['res'] == LordwarDef::BTL_RET_LOSE)
					 )
				{
					$lordArr[$fightersArr[1]]['loseNum'] ++;
				}
				else
				{
					$lordArr[$fightersArr[0]]['loseNum'] ++;
				}
				
				if( $lordArr[$fightersArr[0]]['loseNum'] >= $promoOutLoseNum )
				{
					$lordArr[$fightersArr[1]]['rank'] = $fightForTopX;
					Logger::info('one player promoted. teamId:%d, teamType:%d, round:%d, subRound:%d, pid:%d, serverId:%d, rank:%d',
						$teamId, $teamType, $round, $subRound, $lordArr[$fightersArr[1]]['pid'], $lordArr[$fightersArr[1]]['serverId'], $fightForTopX);
				}
				if( $lordArr[$fightersArr[1]]['loseNum'] >= $promoOutLoseNum  ) 
				{
					$lordArr[$fightersArr[0]]['rank'] = $fightForTopX;
					Logger::info('one player promoted. teamId:%d, teamType:%d, round:%d, subRound:%d, pid:%d, serverId:%d, rank:%d',
						$teamId, $teamType, $round, $subRound, $lordArr[$fightersArr[0]]['pid'], $lordArr[$fightersArr[0]]['serverId'], $fightForTopX);
				}
				
				//add servername username ...
				$btlRetSlim['atk'] = array( 
						'serverId' => $btlRet['atk']['serverId'],
						'pid' => $btlRet['atk']['pid'],
						'uid' => $btlRet['atk']['uid'],
				 );
				$btlRetSlim['def'] = array( 
						'serverId' => $btlRet['def']['serverId'],
						'pid' => $btlRet['def']['pid'],
						'uid' => $btlRet['def']['uid'],
				 );
				$btlRetSlim['res'] = $btlRet['res'];
				$btlRetSlim['replyId'] = $btlRet['replyId'];
				
				$recordArr[] = $btlRetSlim;//这里晋级赛存的信息是简略的，玩家自己保存的信息是完整的（在对象更新的时候有进一步的优化）
				$lordObjFront->saveBattleRecord($round,$btlRet,$teamType,$subRound );
				$lordObjBack->saveBattleRecord($round,$btlRet,$teamType,$subRound );
				$lordObjFront->update();
				$lordObjBack->update();
				
				LordObj::release($serverId1, $pid1);
				LordObj::release($serverId2, $pid2);
			}
		}
		
 		return array('lordArr' =>$lordArr, 'recordArr' => $recordArr );
	}
	
	
	public static function getPromotionFighters($lordArr,$fightForTopX,$offset)
	{
		$step = LordwarConf::AUDITION_PROMOTED_NUM/$fightForTopX;
		$fightersArr = array();
		$alreadyDone = false;
		for ( $j= $offset; $j < $offset+$step ; $j++  )
		{
			if( $lordArr[$j]['pid'] == 0 )
			{
				continue;
			}
			elseif( $lordArr[$j]['rank'] > $fightForTopX*2 )
			{
				continue;
			}
			elseif( $lordArr[$j]['rank'] <=  $fightForTopX )
			{
				Logger::debug('some one has got higher rank than :%d, info: %s',$fightForTopX,$lordArr[$j]);
				$alreadyDone = true;
				break;
			}
			else
			{
				$fightersArr[] = $j;
			}
		}
		
		return array('done' => $alreadyDone,'fightArr' => $fightersArr);
	}
	
	public static function registerForCross( $specTeamId = 0 )
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$teamMgr->checkTeamDistributionCross();
		$serverMgr = ServerInfoManager::getInstance(LordwarUtil::getCrossDbName());
		$serverInfoArr = array();
		//获取分组信息
		if( $specTeamId != 0 )
		{
			$serversInOneTeam = $teamMgr->getServersByTeamId($specTeamId);
			if( empty( $serversInOneTeam ) )
			{
				throw new ConfigException( 'no servers for team %s', $specTeamId );
			}
			$allTeamInfo = array( $specTeamId => $serversInOneTeam );
		}
		else 
		{
			$allTeamInfo = $teamMgr->getAllTeam();
		}
		
		Logger::debug('allteaminfo are: %s', $allTeamInfo);
		
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::CROSS);
		
		foreach ($allTeamInfo as $teamId => $allServerIds)
		{
			$teamObj = $procedure->getTeamObj($teamId);
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
			if( $curRound > LordwarRound::CROSS_AUDITION 
				|| ( $curRound == LordwarRound::CROSS_AUDITION && $curStatus >= LordwarStatus::FIGHTING )  )
			{
				Logger::fatal('already do later round. round:%d, status:%d', $curRound, $curStatus);
				continue;
			}
			
			$serverInfoArr = $serverMgr->getArrServerInfo($allServerIds);
			Logger::debug('all serverInfo is: %s', $serverInfoArr);
			
			$count = 0;
			$notDone = false;
			$alreadyExist = array();
			foreach ($allServerIds as $serverId)
			{	
				try
				{
					if( in_array( $serverInfoArr[$serverId]['db_name'] , $alreadyExist) )
					{
						continue;
					}
					else 
					{
						$alreadyExist[] = $serverInfoArr[$serverId]['db_name'];
					}
					$lordwarRoundInfo = LordwarDao::getLastRoundData($serverInfoArr[$serverId]['db_name'], $sess, $teamId);
					
					if( count( $lordwarRoundInfo ) < 2 )
					{
						Logger::debug('lordwarRoundInfo is: %s', $lordwarRoundInfo);
						Logger::fatal('this team not done, cos round num, teamId %d serverId %d', $teamId, $serverId);
						$notDone = true;
						continue;
					}
					
					if( $lordwarRoundInfo[LordwarTeamType::WIN]['round'] != LordwarRound::INNER_2TO1 ||
							$lordwarRoundInfo[LordwarTeamType::LOSE]['round'] != LordwarRound::INNER_2TO1 )
					{
						Logger::fatal('this team not done, cos tatus, teamId %d serverId %d', $teamId, $serverId);
						$notDone = true;
						continue;
					}
					
					if( $lordwarRoundInfo[LordwarTeamType::WIN]['status'] != LordwarStatus::DONE||
						$lordwarRoundInfo[LordwarTeamType::LOSE]['status'] != LordwarStatus::DONE )
					{
						Logger::fatal('this team not done, cos tatus, teamId %d serverId %d', $teamId, $serverId);
						$notDone = true;
						continue;
					}
										
					$lordArr = array();
					if(  !isset( $lordwarRoundInfo[LordwarTeamType::WIN]['va_procedure']['lordArr'] ) 
					||  !isset( $lordwarRoundInfo[LordwarTeamType::LOSE]['va_procedure']['lordArr'] ) )
					{
						Logger::fatal('this team not done, cos empty lordArr, teamId %d serverId %d', $teamId, $serverId);
						$notDone = true;
						continue;
					}
									
					$lordArr[] = $lordwarRoundInfo[LordwarTeamType::WIN]['va_procedure']['lordArr'];
					$lordArr[] = $lordwarRoundInfo[LordwarTeamType::LOSE]['va_procedure']['lordArr'];
				}
				catch (Exception $e)
				{
					Logger::warning("Can not get info for serverId: %d. teamId %d", $serverId,$teamId );
					continue;
				}
		
				Logger::debug('lordArr before insert are: %s', $lordArr);
				// 一个库应该有胜者组和负者组两条数据
				foreach ($lordArr as $onePiece)
				{
					foreach ($onePiece as $lordInfo)
					{
						//以防加别的字段
						if (!empty($lordInfo['pid']))
						{
							LordwarCrossDao::insertForCross(  $lordInfo['serverId'], $lordInfo['pid'], $teamId);
							++$count;
						}
						
					}
				}
			}
			
			if( $notDone )
			{
				Logger::fatal('inner not done. teamId:%d', $teamId);
				continue;
			}
			
			
			$initTeamRoundWin = $teamObj->getInitTeamRoundData($teamId, LordwarTeamType::WIN , LordwarRound::CROSS_AUDITION, $sess, LordwarStatus::PREPARE);
			$initTeamRoundLose = $teamObj->getInitTeamRoundData($teamId, LordwarTeamType::LOSE , LordwarRound::CROSS_AUDITION, $sess, LordwarStatus::PREPARE);
			$teamRoundObjWin = $teamObj->getTeamRound(LordwarRound::CROSS_AUDITION, LordwarTeamType::WIN);
			$teamRoundObjLose = $teamObj->getTeamRound(LordwarRound::CROSS_AUDITION, LordwarTeamType::LOSE);
			$teamRoundObjWin->setData( $initTeamRoundWin );
			$teamRoundObjLose->setData( $initTeamRoundLose );
			
			$teamRoundObjWin->update();
			$teamRoundObjLose->update();//TODO 更新一半的问题
			Logger::info("team: %d, fighters' count is %d.", $teamId, $count);
		}
		
		Logger::info('register for cross done, all teaminfo is: %s', $allTeamInfo);
	}
	
	/**
	 * 状态及信息推送
	 * @param int $field 服内推还是跨服推
	 * @param int $type 推的是信息还是状态 1 状态 2 信息
	 * @param array $msg 信息 如果type为1的话 则该字段会被换为,round和status信息
	 */
	public static function push( $field, $type, $data = array() )
	{
		try 
		{
			if ( $field == LordwarField::INNER )
			{
				self::pushInner($type, $data);
			}
			else
			{
				self::pushCross($type, $data);
			}
		}
		catch ( Exception $e )
		{
			Logger::fatal('push err, field:%s, type:%d, date:%s , err msg: %s ', $field, $type, $data, $e->getMessage());
		}
	}
	
	public static function pushCross( $type, $data )
	{
		$confMgr = LordwarConfMgr::getInstance(LordwarField::CROSS);
		$sess = $confMgr->getSess();
		
		$teamMgr= TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		$allTeam = $teamMgr->getAllTeam();
		
		//shiyu
		$needPushTeamArr = array_keys( $allTeam );
		if( isset( $data['needPushTeamArr'] ) && is_array( $data['needPushTeamArr'] ) )
		{
			$needPushTeamArr =  $data['needPushTeamArr'];
		}
		
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::CROSS);
	
		foreach ( $allTeam as $teamId => $serverIdArr )
		{
			if( !in_array( $teamId , $needPushTeamArr) )
			{
				continue;
			}
			
			if( $type == LordwarPush::NOW_STATUS )
			{
				$teamObj = $procedure->getTeamObj($teamId);
				$curRound = $teamObj->getCurRound();
				$curStatus = $teamObj->getCurStatus();
				
				$msg = array(
						'round' => $curRound,
						'status' => $curStatus,
				);
				//shiyu
				if( in_array( $curRound , LordwarRound::$INNER_PROMO ) || in_array( $curRound , LordwarRound::$CROSS_PROMO) )
				{
					$winTeamRoundObj = $teamObj->getTeamRound($curRound, LordwarTeamType::WIN);
					$loseTeamRoundObj = $teamObj->getTeamRound($curRound, LordwarTeamType::LOSE);
					$winSubRound = $winTeamRoundObj->getSubRound();
					$loseSubRound = $loseTeamRoundObj->getSubRound();
					$pushSubRound = $winSubRound> $loseSubRound? $loseSubRound:$winSubRound;
					$msg['subRound'] = $pushSubRound-1;
				}
				
				$arrMsgData = array(
						'callback' => array ('callbackName' => PushInterfaceDef::LORDWAR_UPDATE ),
						'err' => 'ok',
						'ret' => $msg,
				);	
			}
			else if( $type == LordwarPush::NEW_REWARD )
			{
				$arrMsgData = array();
			}
			elseif ( $type == LordwarPush::NEW_MAIL )
			{
				$arrMsgData = array();
			}
			else
			{
				Logger::fatal('invalid type:%d', $type);
				return;
			}
	
			foreach ( $serverIdArr as $serverId )
			{	
				$group = Util::getGroupByServerId($serverId);
				$proxy = new ServerProxy();
				$proxy->init($group, Util::genLogId());
				
				if( $type == LordwarPush::NOW_STATUS )
				{
					$proxy->sendFilterMessage('arena', SPECIAL_ARENA_ID::LORDWAR, $arrMsgData);
				}
				else if( $type == LordwarPush::NEW_REWARD )
				{
					if( empty(  $data['arrRewardServerUid'][$serverId] )  )
					{
						Logger::info('no uid to send. serverId:%d', $serverId);
					}
					else
					{
						$proxy->sendMessage( $data['arrRewardServerUid'][$serverId], PushInterfaceDef::REWARD_NEW, array() );
					}
				}
				else if( $type == LordwarPush::NEW_MAIL )
				{
					//这里用的是和发奖励相同的玩家信息
					if( empty(  $data['arrRewardServerUid'][$serverId] )  )
					{
						Logger::info('no uid to send mail. serverId:%d', $serverId);
					}
					else
					{
						$proxy->sendMessage( $data['arrRewardServerUid'][$serverId], PushInterfaceDef::MAIL_CALLBACK, array() );
					}
				}
				else
				{
					Logger::fatal('invalid type:%d', $type);
					return;
				}
			}
			Logger::info('pushCross done. teamId:%d, type:%s', $teamId, $type);
		}
		
	}
		
	public static function pushInner($type, $data)
	{
		if( $type == LordwarPush::NOW_STATUS )
		{
			$confMgr = LordwarConfMgr::getInstance(LordwarField::CROSS);
			$sess = $confMgr->getSess();
			$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
			$serverId = Util::getServerId();
			$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
			$teamObj = $procedure->getTeamObj($teamId);
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
		
			$msg = array(
					'round' => $curRound,
					'status' => $curStatus,
			);
			
			//shiyu
			if( in_array( $curRound , LordwarRound::$INNER_PROMO ) || in_array( $curRound , LordwarRound::$CROSS_PROMO) )
			{
				$winTeamRoundObj = $teamObj->getTeamRound($curRound, LordwarTeamType::WIN);
				$loseTeamRoundObj = $teamObj->getTeamRound($curRound, LordwarTeamType::LOSE);
				$winSubRound = $winTeamRoundObj->getSubRound();
				$loseSubRound = $loseTeamRoundObj->getSubRound();
				$pushSubRound = $winSubRound> $loseSubRound? $loseSubRound:$winSubRound;
				$msg['subRound'] = $pushSubRound-1;
			}
			
			$arrMsgData = array(
					'callback' => array ('callbackName' => PushInterfaceDef::LORDWAR_UPDATE ),
					'err' => 'ok',
					'ret' => $msg,
			);
			$proxy = new ServerProxy();
			$proxy->sendFilterMessage('arena', SPECIAL_ARENA_ID::LORDWAR, $arrMsgData);
			//RPCContext::getInstance()->sendFilterMessage('arena', SPECIAL_ARENA_ID::LORDWAR, PushInterfaceDef::LORDWAR_UPDATE, $msg	);
		}
		else if( $type == LordwarPush::NEW_REWARD )
		{
			$serverId = Util::getServerId();
			if( empty($data['arrRewardServerUid'][$serverId]) )
			{
				Logger::warning('no uid to send');
				return;
			}
			RPCContext::getInstance()->sendMsg($data['arrRewardServerUid'][$serverId], PushInterfaceDef::REWARD_NEW, array() );
		}
		else if( $type == LordwarPush::NEW_MAIL )
		{
			$serverId = Util::getServerId();
			if( empty($data['arrRewardServerUid'][$serverId]) )
			{
				Logger::warning('no uid to send mail');
				return;
			}
			RPCContext::getInstance()->sendMsg($data['arrRewardServerUid'][$serverId], PushInterfaceDef::MAIL_CALLBACK, array() );
		}
		else 
		{
			Logger::fatal('invalid type:%d', $type);
		}
		
		Logger::info('pushInner done. type:%d', $type);
	}
	
	
	public static function reward( $field, $rewardType)
	{
		if( $rewardType == LordwarReward::SUPPORT )
		{
			self::rewardSupport($field);
		}
		elseif ( $rewardType == LordwarReward::RPOMOTION )
		{
			self::rewardPromotion($field);
		}
		else
		{
			Logger::fatal('invalid reward type: %d', $rewardType);
		}
	}
	
	public static function rewardSupport($field)
	{
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		
		if( $field != LordwarField::INNER )
		{
			Logger::fatal('reward must run in inner machine');
			return;
		}
		$procedure = LordwarProcedure::getInstance($sess, $field);
		
		$serverId = Util::getServerId();
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		$teamObj = $procedure->getTeamObj($teamId);
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		//检查是否在需要发送助威奖的阶段
		if ( !in_array($curRound, LordwarRound::$INNER_PROMO) && 
			!in_array($curRound, LordwarRound::$CROSS_PROMO) )
		{
			Logger::INFO("cur round:%d is not need send support reward!", $curRound);
			return;
		}

		//检查状态是否正常
		if ( $curStatus != LordwarStatus::FIGHTEND )
		{
			Logger::FATAL("in inner server, round:%d, status:%d != FIGHTEND", $curRound, $curStatus);
			return;
		}
		
		Logger::info('start send support reward. field:%s, round:%d', $field, $curRound);

		//通过活动开始时间，排除上一轮遗留的错误数据
		$activityStartTime = $confMgr->getBaseConf('start_time');
		$list = LordwarInnerDao::getSupportList($curRound, $activityStartTime);
		
		Logger::info('there %d user has supported', count($list) );
		
		$roundResult = array();
		foreach ( LordwarTeamType::$TEAM_TYPE_ALL as $teamType )
		{
			$data = $teamObj->getTeamRound($curRound, $teamType)->getData();
			foreach ( $data['va_procedure']['lordArr'] as $user )
			{
				$key = $user['serverId'].LordwarKey::FIELD_LINK.$user['pid'];
				$user['teamType'] = $teamType;
				$roundResult[$key] = $user;
			}
		}
		
		//服内，跨服助威奖励都在服内机器上发。所以field都是inner，但是服内跨服的阶段区分
		$stage = LordwarConfMgr::getStageByRound($curRound);
		$rewardArr = $confMgr->getReward(LordwarReward::SUPPORT, $stage);
		
		if( $stage == LordwarField::INNER )
		{
			$rewardSource = RewardSource::LORDWAR_SUPPORT_INNER;
		}
		else if( $stage == LordwarField::CROSS )
		{
			$rewardSource = RewardSource::LORDWAR_SUPPORT_CROSS;
		}
		else
		{
			throw new InterException('invalid stage:%s', $stage);
		}
		
		//新奖励的消息集中推送
		RewardCfg::$NO_CALLBACK = true;
		MailConf::$NO_CALLBACK = true;
		$arrRewardUid = array();
		
		$rewardTime = Util::getTime();
		
		$allServerId = Util::getAllServerId();
		foreach($list as $user)
		{
			$userServerId = $user['server_id'];
			if( !in_array($userServerId, $allServerId) )
			{
				Logger::fatal('serverId not match. user:%d, evn:%d', $userServerId, $serverId);
				continue;
			}
			$uid = $user['uid'];
			$pid = $user['pid'];
			$supportServerId = $user['support_serverid'];
			$supportPid = $user['support_pid'];
			try 
			{
				$key = $supportServerId.LordwarKey::FIELD_LINK.$supportPid;
				if ( !isset($roundResult[$key]) )
				{
					Logger::FATAL("invalid support data. round:%d, serverId:%d, pid:%d, uid:%d, supportServerId:%d, supportPid:%d",
						 $curRound, $userServerId, $pid, $uid, $supportServerId, $supportPid);
				}
				else 
				{
					$lordObj = new LordObj($userServerId, $pid);
					$lordObj->supportRewardEnd($curRound, $rewardTime);
					$lordObj->update();
						
					if ( $roundResult[$key]['rank'] == LordwarRound::$ROUND_RET_NUM[$curRound] )
					{
						RewardUtil::reward3DtoCenter($uid, array( $rewardArr ), $rewardSource, array());
						MailTemplate::sendLordwarSupport($uid, $curRound);
						
						Logger::INFO("send support reward. round:%d, serverId:%d, pid:%d, uid:%d, supportServerId:%d, supportPid:%d",
						$curRound, $userServerId, $pid, $uid, $supportServerId, $supportPid);
							
						$arrRewardUid[] = $uid;
					}
					else
					{
						Logger::INFO("no support reward. round:%d, serverId:%d, pid:%d, uid:%d, supportServerId:%d, supportPid:%d, rank:%d",
						$curRound, $userServerId, $pid, $uid, $supportServerId, $supportPid, $roundResult[$key]['rank']);
					}
				}
			}
			catch(Exception $e)
			{
				Logger::FATAL("send support reward failed. round:%d, serverId:%d, pid:%d, uid:%d, msg:%s", $curRound, $userServerId, $pid, $uid, $e->getMessage());
			}	
		}
		
		//如果是服内的话，直接修改状态。 跨服需要在跨服机器上check完后再修改状态
		if ( $stage == LordwarField::INNER )
		{
			if ( $curRound == LordwarRound::INNER_2TO1 )
			{
				$nextStatus = LordwarStatus::REWARDEND;
			}
			else
			{
				$nextStatus = LordwarStatus::DONE;
			}
			foreach(LordwarTeamType::$TEAM_TYPE_ALL as $teamType)
			{
				$teamObj->getTeamRound($curRound, $teamType)->setStatus($nextStatus);
			}
			$teamObj->update();
			Logger::info('send inner support reward done, set next status. round:%d, nextStatus:%d', $curRound, $nextStatus);
		}
		else 
		{
			Logger::info('send cross support reward done');
		}
		
	
		if( !empty($arrRewardUid) )
		{
			$arrRewardServerUid = array($serverId => $arrRewardUid);
			self::push($field, LordwarPush::NEW_REWARD, array('arrRewardServerUid' => $arrRewardServerUid) );
			self::push($field, LordwarPush::NEW_MAIL, array('arrRewardServerUid' => $arrRewardServerUid) );
		}
	}

	public static function rewardPromotion($field)
	{
		$confMgr = LordwarConfMgr::getInstance($field);
		$sess = $confMgr->getSess();
		$innerAuditionStartTime = $confMgr->getRoundStartTime(LordwarRound::INNER_AUDITION);

		$procedure = LordwarProcedure::getInstance($sess, $field);
		
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		if( $field == LordwarField::INNER )
		{
			$serverId = Util::getFirstServerIdOfGroup();
			$myTeamId = $teamMgr->getTeamIdByServerId($serverId);
			if( $myTeamId < 0 )
			{
				Logger::info('this server not in any team. serverId:%d', $serverId);
				return;
			}
			$allTeamId = array($myTeamId);
		}
		else
		{
			$allTeamInfo = $teamMgr->getAllTeam();
			$allTeamId = array_keys($allTeamInfo);
		}
		
		//新奖励的消息集中推送
		RewardCfg::$NO_CALLBACK = true;
		MailConf::$NO_CALLBACK = true;
		$arrRewardServerUid = array();
		$arrRewardWholeServer = array();

		
		Logger::info('start send promotion reward, filed:%s', $field);
		
		$rewardTime = Util::getTime();
		foreach( $allTeamId as $teamId )
		{
			Logger::info('start send promotion reward. field:%s, teamId:%d', $field, $teamId);
			$teamObj = $procedure->getTeamObj($teamId);
			
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
				
			if( !( $curRound == LordwarRound::INNER_2TO1 && $field == LordwarField::INNER )
				&& !( $curRound == LordwarRound::CROSS_2TO1 && $field == LordwarField::CROSS ) )
			{
				Logger::fatal('cant send promotion reward, wrong round. field:%s, teamId:%d, round:%d', $field, $teamId, $curRound);
				continue;
			}
			//等助威奖励发完，再发排名奖励
			if( $curStatus != LordwarStatus::REWARDEND )
			{
				Logger::fatal('cant send promotion reward, wrong status. field:%s, teamId:%d, round:%d, status:%d', $field, $teamId, $curRound, $curStatus);
				continue;
			}
			
			$arrFinalUser = array();
			$arrServerId = array();
			foreach ( LordwarTeamType::$TEAM_TYPE_ALL as $teamType )
			{
				$data = $teamObj->getTeamRound($curRound, $teamType)->getData();
				foreach ( $data['va_procedure']['lordArr'] as $user )
				{
					if( !empty($user['pid']) )
					{
						$user['teamType'] = $teamType;
						$arrFinalUser[] = $user;
						$arrServerId[] = $user['serverId'];
					}
				}
			}
			$arrServerId = array_merge( array_unique($arrServerId) );

			$arrDbName = ServerInfoManager::getInstance()->getArrDbName($arrServerId);
			foreach ( $arrFinalUser as $user )
			{
				$rewardUserServerId = $user['serverId'];
				$rewardUserPid = $user['pid'];
				$rewardUserUid = $user['uid'];
				$rewardRank = $user['rank'];
				$rewardTeamType = $user['teamType'];
				try
				{
					$rewardArr = $confMgr->getReward(LordwarReward::RPOMOTION, $field, $rewardTeamType, $rewardRank);
					$arrExtra = array('rank' => $rewardRank);
			
					$db = '';
					if ( $field == LordwarField::CROSS )
					{
						$db = $arrDbName[$rewardUserServerId];
					}

					$lordObj = new LordObj($rewardUserServerId, $rewardUserPid);
					
					$va_extra = $lordObj->getLordVaExtra();
					/*
					 * 检查这个用户这一轮是否发送过奖励.
					 * 存在问题:如果这个用户的奖励发送了并且这个人是第一名，但是全服礼包不会尝试从新发送，需要单独处理
					 */
					if ( !empty($va_extra['promotionList'][$curRound])
						 && $va_extra['promotionList'][$curRound]['rewardTime'] > $innerAuditionStartTime )
					{
						Logger::warning("have sended promotion reward. serverId:%d, pid:%d, uid:%d, teamType:%d, rank:%d, time:%d", 
							$rewardUserServerId, $rewardUserPid, $rewardUserUid, $rewardTeamType, $rewardRank,
							$va_extra['promotionList'][$curRound]['rewardTime']);
						continue;
					}
					
					$lordObj->promotionRewardEnd($curRound, $rewardTime, $rewardRank);
					$lordObj->update();
					
					//跨服时，不支持按用户等级发奖
					$rewardSource = LordwarUtil::getPromotionRewardSource($field, $rewardTeamType, $rewardRank);
					RewardUtil::reward3DtoCenter($rewardUserUid, array($rewardArr), $rewardSource, $arrExtra, $db);
					MailTemplate::sendLordwarRank($rewardUserUid, $curRound, $user['teamType'], $user['rank'],$db);
					
					Logger::INFO("send promotion reward ok. serverId:%d, pid:%d, uid:%d, teamType:%d, rank:%d", 
							$rewardUserServerId, $rewardUserPid, $rewardUserUid, $rewardTeamType, $rewardRank);
					
					//发一下跨服冠军所在服的全服奖励
					if ( $rewardTeamType == LordwarTeamType::WIN
							&& $rewardRank == LordwarRound::$ROUND_RET_NUM[LordwarRound::CROSS_2TO1]
							&& $curRound == LordwarRound::CROSS_2TO1 
							&& $field == LordwarField::CROSS )
					{
						try
						{
							//记录一下哪些服有全服奖励
							if( isset($arrRewardWholeServer[$teamId]) )
							{
								throw new InterException('serverId:%d already get whole server reward. teamId:%d', $arrRewardWholeServer[$teamId], $teamId);
							}
								
							$worldPrize = $confMgr->getReward(LordwarReward::WHOLEWORLD, $field);
							$arrReward = RewardUtil::format3DtoCenter($worldPrize);
							$arrReward[ PayBackDef::PAYBACK_TYPE ] = PayBackType::LORDWAR_WHOLEWORLD;
							
							PaybackLogic::insertPayBackInfo( $rewardTime, $rewardTime + LordwarConf::REWARD_WHOLEWORLD_LAST_TIME, $arrReward, true, $db);
							
							Logger::info("reward whole on cross server ok. serverId:%d, pid:%d, uid:%d",
								$rewardUserServerId, $rewardUserPid, $rewardUserUid);
							
							$arrRewardWholeServer[$teamId] = $rewardUserServerId;
						}
						catch(Exception $e)
						{
							Logger::fatal("reward whole on cross server failed. serverId:%d, pid:%d, uid:%d, msg:%s",
								$rewardUserServerId, $rewardUserPid, $rewardUserUid, $e->getMessage());
						}
					}
					
					if( !isset( $arrRewardServerUid[$rewardUserServerId] ) )
					{
						$arrRewardServerUid[$rewardUserServerId] = array($rewardUserUid);
					}
					else
					{
						$arrRewardServerUid[$rewardUserServerId][] = $rewardUserUid;
					}
				}
				catch(Exception $e)
				{
					Logger::fatal("send promotion reward failed. serverId:%d, pid:%d, uid:%d, teamType:%d, rank:%d, msg:%s", 
							$rewardUserServerId, $rewardUserPid, $rewardUserPid, $rewardTeamType, $rewardRank, $e->getMessage());
				}
					
			}
			
			foreach(LordwarTeamType::$TEAM_TYPE_ALL as $teamType)
			{
				$teamObj->getTeamRound($curRound, $teamType)->setStatus(LordwarStatus::DONE);
			}
			$teamObj->update();
			
			Logger::info('send promotion reward of team done. field:%s, teamId:%d', $field, $teamId);
		}
		
		if( !empty($arrRewardServerUid) )
		{
			//将有全服奖励的服的uid数组改成array(0)
			$arr = $arrRewardServerUid;
			foreach ($arrRewardWholeServer as $teamId => $serverId)
			{
				$arr[$serverId] = array( 0 );
			}
			self::push($field, LordwarPush::NEW_REWARD, array('arrRewardServerUid' => $arrRewardServerUid) );
			self::push($field, LordwarPush::NEW_MAIL, array('arrRewardServerUid' => $arrRewardServerUid));
		}
		
		Logger::info('send promotion reward all done. field:%s', $field);
	}
	
	public static function checkSupportRewardSendEndOnCross()
	{
		$confMgr = LordwarConfMgr::getInstance(LordwarField::CROSS);
		$sess = $confMgr->getSess();
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::CROSS);
		$allTeam = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getAllTeam();
		
		//通过活动开始时间，排除上一轮遗留的错误数据
		$activityStartTime = $confMgr->getBaseConf('start_time');
		foreach ( $allTeam as $teamId => $arrServerId )
		{
			$teamObj = $procedure->getTeamObj($teamId);
			$round = $teamObj->getCurRound();
			$status = $teamObj->getCurStatus();
			
			if ( $status != LordwarStatus::FIGHTEND )
			{
				Logger::warning('check support reward send end on cross. teamId:%d status:%d != FIGTHEND', $teamId, $status );
				continue;
			}
			$arrDbName = ServerInfoManager::getInstance()->getArrDbName($arrServerId);
			
			if ( LordwarUtil::isCrossRound($round) )
			{
				$errCount = 0;
				foreach ( $arrServerId as $serverId )
				{
					$leftNum = LordwarInnerDao::getSupportListNum($round, $activityStartTime, $arrDbName[$serverId]);
					
					if ( $leftNum < LordwarConf::ACCEPT_NO_DEAL_SUPPORT_USER )
					{
						Logger::info('teamId:%d, serverId:%d, round:%d, leftNum:%d', $teamId, $serverId, $round, $leftNum);
					}
					else
					{
						$errCount++;
						Logger::info('teamId:%d, serverId:%d, round:%d, leftNum:%d, errCount:%d', $teamId, $serverId, $round, $leftNum, $errCount);
					}
				}
				if ( $errCount == 0 )
				{
					if ( $round == LordwarRound::CROSS_2TO1 )
					{
						$nextStatus = LordwarStatus::REWARDEND;
					}
					else
					{
						$nextStatus = LordwarStatus::DONE;
					}
					foreach ( LordwarTeamType::$TEAM_TYPE_ALL as $teamType )
					{
						$teamObj->getTeamRound($round, $teamType)->setStatus( $nextStatus );
					}
					$teamObj->update();
					Logger::info('check cross support reward done, set next status. round:%d, nextStatus:%d', $round, $nextStatus);
				}
			}
			else
			{
				Logger::fatal('cant checkSupportRewardSendEndOnCross in round:%d', $round);
			}	
		}

	}
	
	public static function worship($pos, $type)
	{

		if( $pos > 2 )
		{
			throw new FakeException( 'pos: %d > 2', $pos );
		}
		
		$confMgr = LordwarConfMgr::getInstance();
		$sess = $confMgr->getSess();
		$serverId = Util::getServerIdOfConnection();
		$costArr = $confMgr->getWorshipCostArr();
		$prizeArr = $confMgr->getWorshipPrizeArr();
		if( empty( $costArr[$type]) || empty( $prizeArr[$type] ) )
		{
			throw new FakeException( 'invalid type: %d, costArr: %s, prizeArr : %s', $type, $costArr, $prizeArr );
		}
		
		$realCost = $costArr[$type];
		$realPrizeId = $prizeArr[$type];
		
		$userObj = EnUser::getUserObj();
		if( $realCost[2] < 0 )
		{
			throw new ConfigException( 'cost negtive' );
		}
		
		if( $realCost[0] == 1 && !$userObj->subSilver( $realCost[2] )  )
		{
			throw new FakeException( 'lack silver: %d', $realCost[2] );
		}
		
		if( $realCost[2] < 0 || ($realCost[0] == 3 && !$userObj->subGold( $realCost[2], StatisticsDef::ST_FUNCKEY_LORDWAR_WORSHIP_COST ))   )
		{
			throw new FakeException( 'lack gold: %d', $realCost[2] );
		}
		
		$teamId = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess)->getTeamIdByServerId($serverId);
		$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
		$curRound = $procedure->getTeamObj($teamId)->getCurRound();
		$curStatus = $procedure->getTeamObj($teamId)->getCurStatus();
		
 		if( $curRound != LordwarRound::CROSS_2TO1 || $curStatus < LordwarStatus::FIGHTEND )
		{
			throw new FakeException( 'can not worship now, round: %d. status: %d', $curRound, $curStatus );
		}
		
		$monks = self::getTempleInfo(); 		
		if( empty( $monks[$pos] ) )
		{
			throw new FakeException( 'nobody in pos %d, all monks are %s', $pos,$monks );	
		}
		
		$uid = RPCContext::getInstance()->getUid();
		
		if( !LordwarUtil::isServerIn($serverId, $sess) )
		{
			throw new FakeException( 'server not in' );	
		}
		$pid = self::getPid($uid);
		
		$lordObj = LordObj::getInstance($serverId, $pid);
		$lordObj->worship(Util::getTime());
		
		$realPrize = $confMgr->getWorshipRewardById($realPrizeId);
		if( empty( $realPrize ) )
		{
			throw new ConfigException( 'nothing reward this user, pos: %d, type: %d', $pos, $type );
		}
		Logger::debug('real prize for ship: %s', $realPrize);
		RewardUtil::reward3DArr($uid, $realPrize,StatisticsDef::ST_FUNCKEY_LORDWAR_WORSHIP_PRIZE );
		$bag = BagManager::getInstance()->getBag();
		$lordObj->update();
		$userObj->update();
		$bag->update();
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */