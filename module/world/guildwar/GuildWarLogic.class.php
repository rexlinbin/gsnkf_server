<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarLogic.class.php 191070 2015-08-14 02:04:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/GuildWarLogic.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-14 02:04:41 +0000 (Fri, 14 Aug 2015) $
 * @version $Revision: 191070 $
 * @brief 
 *  
 **/
 
class GuildWarLogic
{
	/**
	 * 进入跨服军团战场景
	 * 
	 * @param int $uid
	 * @return string
	 */
	public static function enter($uid)
	{
		RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::GUILDWAR);
		return 'ok';
	}
	
	/**
	 * 退出跨服军团战场景
	 * 
	 * @param int $uid
	 * @return string
	 */
	public static function leave($uid)
	{
		RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
		return 'ok';
	}
	
	/**
	 * 报名
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @throws InterException
	 * @throws Exception
	 * @return string
	 */
	public static function signUp($uid)
	{
		Logger::trace('GuildWarLogic::signUp begin...');
		
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance();
		
		// 是否处于一届跨服军团战中
		$session = $confObj->getSession();
		if (empty($session)) 
		{
			throw new FakeException('GuildWarLogic::signUp failed, not in any session.');
		}
		
		// 是否在报名时间
		if (!$confObj->inSignUpTime())
		{
			throw new FakeException('GuildWarLogic::signUp failed, not in sign up time, now[%s], signUpStart[%s], signUpEnd[%s].', strftime('%Y%m%d-%H%M%S', Util::getTime()), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpStartTime()), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpEndTime()));
		}
		
		// 玩家所在服是否在一个分组内
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		if (empty($teamId))
		{
			throw new FakeException('GuildWarLogic::signUp failed, not in any team, serverId[%d].', $serverId);
		}
		
		// 是否属于一个军团
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		if (empty($guildId)) 
		{
			throw new FakeException("GuildWarLogic::signUp failed, not in any guild.");
		}
		
		// 等级是否够
		$guildObj = GuildObj::getInstance($guildId);
		$guildLevel = $guildObj->getGuildLevel();
		$needLevel = $confObj->getNeedLevel();
		if ($guildLevel < $needLevel)
		{
			throw new FakeException('GuildWarLogic::signUp failed, level too low, curr[%d] need[%d]', $guildLevel, $needLevel);
		}
			
		// 获得军团成员，按贡献降序排列
		$arrMember = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::MEMBER_TYPE));
		
		// 人数是否够
		$memCount = count($arrMember);
		$needMemCount = $confObj->getNeedMemberCount();
		if ($memCount < $needMemCount)
		{
			throw new FakeException('GuildWarLogic::signUp failed, member count too low, curr[%d] need[%d]', $memCount, $needMemCount);
		}
			
		// 获得玩家职位
		$memberType = -1;
		if (!isset($arrMember[$uid]))
		{
			throw new InterException('GuildWarLogic::signUp failed, can not get member type of user[%d]', $uid);
		}
		$memberType = $arrMember[$uid][GuildDef::MEMBER_TYPE];
			
		// 权限是否够
		if ($memberType != GuildMemberType::PRESIDENT
			&& $memberType != GuildMemberType::VICE_PRESIDENT)
		{
			throw new FakeException('GuildWarLogic::signUp failed, no priviledge, need type[president or vice], curr[%d]', $memberType);
		}
			
		// 获得参赛人员的人数
		$candidatesCount = $confObj->getCandidatesCount();
		
		// 获得参赛人员信息
		$presidentUid = 0;
		$count = 0;
		$arrCandidates = array();
		foreach ($arrMember as $aUid => $aMember)
		{
			if ($aMember[GuildDef::MEMBER_TYPE] == GuildMemberType::PRESIDENT)
			{
				$presidentUid = $aUid;
			}
		
			if (++$count <= $candidatesCount)
			{
				$arrCandidates[] = $aUid;
			}
			else if ($presidentUid != 0)
			{
				if (!empty($arrCandidates) && !in_array($presidentUid, $arrCandidates))
				{
					$arrCandidates[count($arrCandidates) - 1] = $presidentUid;
				}
				break;
			}
			else
			{
				continue;
			}
		}
			
		if (empty($arrCandidates)
			|| $presidentUid == 0
			|| !in_array($presidentUid, $arrCandidates))
		{
			throw new InterException('GuildWarLogic::signUp failed, get candidates error candidates[%s], president[%d]', $arrCandidates, $presidentUid);
		}
		
		// 加锁
		$locker = new Locker();
		$key = 'guildwar.signup.' . $guildId;
		$locker->lock($key);
		try 
		{
			// 是否已经报名
			$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId, TRUE); 
			if ($guildWarServerObj->isSignUp())
			{
				$locker->unlock($key);
				Logger::info('GuildWarLogic::signUp failed, already sign up of serverId[%d] guildId[%d].', $serverId, $guildId);
				return 'already';
			}
			
			// 报名
			$guildWarServerObj->signUp($guildLevel, $guildObj->getGuildIcon(), $guildObj->getGuildName(), $arrCandidates);
			$guildWarServerObj->update();
			
			// 将报名时间设置到memcache中，服内取该玩家的报名状态时候，就不需要去跨服机上取啦，不写在对象里
			GuildWarUtil::setSignUpTimeInMem($guildId, $serverId, Util::getTime());
						
			// 在军团每个成员线程里初始化成员信息
			foreach ($arrCandidates as $aUid)
			{
				RPCContext::getInstance()->executeTask($aUid, 'guildwar.initUserGuildWarByUid', array($serverId, $aUid));
			}
		} 
		catch (Exception $e)
		{
			Logger::fatal('GuildWarLogic::signUp failed, exception:%s', $e->getMessage());
			$locker->unlock($key);
			throw $e;
		}
		
		$locker->unlock($key);
		Logger::trace('GuildWarLogic::signUp end...');
		return 'ok';
	}
	
	/**
	 * 更新玩家战斗数据
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function updateFormation($uid)
	{
		Logger::trace('GuildWarLogic::updateFormation begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_UPD_FMT, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
		
		// 检查玩家是否已经上阵
		if ($guildWarServerObj->isFighter($uid))
		{
			return 'fighting';
		}
		
		// 检查时间
		$cdTime = 0;
		if (!GuildWarUtil::canUpdateFmt(Util::getTime(), $session, $teamId, $cdTime))
		{
			throw new FakeException('GuildWarLogic::updateFormation failed, not the right time.');
		}
		
		// 检查玩家跨服战信息是否初始化，没初始化就记录下日志
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		if (!$guildWarUserObj->isArmed())
		{
			$guildWarUserObj->setUname($userObj->getUname());
			//Logger::warning("GuildWarLogic::updateFormation, guild user not init, why?");
		}
		
		// 检查玩家是否冷却
		if ($guildWarUserObj->inCd($cdTime))
		{
			return 'cd';
		}
		
		// 更新玩家战斗信息
		$battleFmt = $userObj->getBattleFormation();
		$guildWarUserObj->updateBattleFmt($battleFmt);
		$guildWarUserObj->update();

		Logger::trace('GuildWarLogic::updateFormation end...');
		return $guildWarUserObj->getFightForce();
	}
	
	/**
	 * 清除更新战斗数据cd
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function clearUpdFmtCdByGold($uid)
	{
		Logger::trace('GuildWarLogic::clearUpdFmtCdByGold begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_CLEAR_UPD_CD, $uid);

		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		
		// 检查玩家是否初始化
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		if (!$guildWarUserObj->isArmed())
		{
			throw new FakeException("GuildWarLogic::clearUpdFmtCdByGold failed,, not init.");
		}
		
		// 检查时间
		$cdTime = 0;
		if (!GuildWarUtil::canUpdateFmt(Util::getTime(), $session, $teamId, $cdTime))
		{
			throw new FakeException('GuildWarLogic::clearUpdFmtCdByGold failed, not the update fmt time.');
		}
		
		// 检查是否有cd时间,如果没有cd时间，则返回0
		$curTime = Util::getTime();
		if ($curTime >= ($guildWarUserObj->getUpdateFmtTime() + $cdTime))
		{
			return 0;
		}
		
		// 扣除金币，金币数和剩余的cd时间有关系
		$lastCdTime = $guildWarUserObj->getUpdateFmtTime() + $cdTime - $curTime;
		$baseCost = $confObj->getClearCdBaseCost();
		$needGold = intval(ceil($lastCdTime / 60)) * $baseCost;
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_WAR_CLEAR_CD))
		{
			return 'lack';
		}
		$userObj->update();
		Logger::trace('GuildWarLogic::clearUpdFmtCdByGold uid[%d], sub gold[%d]', $uid, $needGold);
		
		// 清空cd时间
		$guildWarUserObj->clearUpdateFmtTimeCd();
		$guildWarUserObj->update();
		
		Logger::trace('GuildWarLogic::clearUpdFmtCdByGold end...');
		return $needGold;
	}
	
	public static function buyMaxWinTimes($uid)
	{
		Logger::trace('GuildWarLogic::buyMaxWinTimes begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_BUY_MAX_WIN, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		$nextRound = $confObj->getNextRound($curRound);
		
		// 是不是在可以购买的时间段内
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_BUY_MAX_WIN))
		{
			throw new FakeException('GuildWarLogic::buyMaxWinTimes failed, not the right time.');
		}
		
		// 判断购买次数是否超限
		$maxBuyWinNum = $confObj->getMaxBuyWinCount();
		$curBuyWinNum = $guildWarUserObj->getBuyMaxWinNum($curRound, $guildWarServerObj->getLastFightTime());
		if ($curBuyWinNum >= $maxBuyWinNum) 
		{
			throw new FakeException("GuildWarLogic::buyMaxWinTimes failed, buy time exceed, curr[%d], limit[%d]", $curBuyWinNum, $maxBuyWinNum);
		}
		
		// 扣金币
		$needGold = $confObj->getBuyMaxWinCost($curBuyWinNum);
		if (!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_WAR_BUY_MAX_WIN))
		{
			throw new FakeException("GuildWarLogic::buyMaxWinTimes failed, not enough gold, need[%d] curr[%d]", $needGold, $userObj->getGold());
		}
		$userObj->update();
		Logger::trace('GuildWarLogic::buyMaxWinTimes uid[%d], round[%d], lastFightTime[%s], already buy time[%d], need gold[%d]', $uid, $curRound, strftime('%Y%m%d-%H%M%S', $guildWarServerObj->getLastFightTime()), $curBuyWinNum, $needGold);
		
		// 更新购买最大次数时间
		$guildWarUserObj->increBuyMaxWinNum();
		$guildWarUserObj->update();
		
		Logger::trace('GuildWarLogic::buyMaxWinTimes end...');
		return 'ok';
	}
	
	public static function getUserGuildWarInfo($uid)
	{
		Logger::trace('GuildWarLogic::getUserGuildWarInfo begin...');
		
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance();
		
		// 检查session
		$session = $confObj->getSession();
		if (empty($session))
		{
			return array('ret' => 'no');
		}
		
		// 玩家所在服是否在一个组内
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		if (empty($teamId))
		{
			return array('ret' => 'no');
		}
		
		// 获取玩家信息
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		$userInfo = $guildWarUserObj->getUserInfo();
		unset($userInfo[GuildWarUserField::TBL_FIELD_UID]);
		unset($userInfo[GuildWarUserField::TBL_FIELD_UNAME]);
		unset($userInfo[GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME]);
		unset($userInfo[GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME]);
		unset($userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA]);
		
		// 获得round和status
		$confRound = $confObj->getCurRound();
		$confSubRound = $confObj->getCurSubRound();
		
		if ($confRound == GuildWarRound::SIGNUP 
			&& $confObj->inSignUpTime()) 
		{
			$round = GuildWarRound::SIGNUP;
			$status = GuildWarStatus::WAIT_TIME_END;
			$subRound = 0;
			$subStatus = GuildWarSubStatus::NO;
		}
		else 
		{
			$procedureObj = GuildWarProcedureObj::getInstance($session);
			$teamObj = $procedureObj->getTeamObj($teamId);
			
			$round = $teamObj->getCurRound();
			$status = $teamObj->getCurStatus();
			$subRound = $teamObj->getCurSubRound();
			$subStatus = $teamObj->getCurSubStatus();
		}
		
		// 返回值添加别的字段
		$userInfo['ret'] = 'ok';
		$userInfo['session'] = $session;
		$userInfo['sign_time'] = 0;
		$userInfo['round'] = $round;
		$userInfo['status'] = $status;
		$userInfo['sub_round'] = $subRound;
		$userInfo['sub_status'] = $subStatus;
		$userInfo['server_id'] = $serverId;
		
		// 获取军团报名时间
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		if (!empty($guildId))
		{
			if (GuildWarServerObj::isGuildSignUp($session, $serverId, $guildId)) 
			{
				$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
				$userInfo['sign_time'] = $guildWarServerObj->getSignUpTime();
				$userInfo['buy_max_win_num'] = $guildWarUserObj->getBuyMaxWinNum($round, $guildWarServerObj->getLastFightTime());
			}
		}
		
		// 获得报名军团个数
		$userInfo['sign_up_count'] = GuildWarDao::getCountOfSignUp($session, $teamId, $confObj->getSignUpStartTime());
			
		Logger::trace('GuildWarLogic::getUserGuildWarInfo ret[%s] end...', $userInfo);
		return $userInfo;
	}
	
	/**
	 * 获得报名的军团参战人员信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @throws InterException
	 * @return array
	 */
	public static function getGuildWarMemberList($uid)
	{
		Logger::trace('GuildWarLogic::getGuildWarMemberList begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_MEMBER_LIST, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
		
		// 获得参战人员信息
		$arrCandidates = $guildWarServerObj->getCandidates();
		if (empty($arrCandidates))
		{
			throw new FakeException('GuildWarLogic::getGuildWarMemberList, no candidates info!');
		}

		// 获得基础数据 , 前端自己排序去，排序规则是 战力和uid
		$arrMemberInfo = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::CONTRI_TOTAL, GuildDef::MEMBER_TYPE));
		$arrUserInfo = EnUser::getArrUserBasicInfo(Util::arrayExtract($arrMemberInfo, GuildDef::USER_ID), array('uid', 'uname', 'level', 'fight_force', 'vip', 'htid', 'dress'));
		$arrField = array
		(
				GuildWarUserField::TBL_FIELD_UID,
				GuildWarUserField::TBL_FIELD_UNAME,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME,
				GuildWarUserField::TBL_FIELD_FIGHT_FORCE,
		);
		$arrGuildWarUserInfo = GuildWarDao::getArrGuildWarUserInfo($arrCandidates, $arrField);    
		
		// 前端自己排序

		// 合并数据
		foreach ($arrUserInfo as $aUid => $aInfo)
		{
		    if (isset($arrGuildWarUserInfo[$aUid]))
		    {
		        $arrUserInfo[$aUid]['fight_force'] = $arrGuildWarUserInfo[$aUid][GuildWarUserField::TBL_FIELD_FIGHT_FORCE];
		        $arrUserInfo[$aUid]['state'] = 1;
		        
		        /**** 20150306 add 和战报中的一致，使用报名的时候的名字 *****/
		        if (!empty($arrGuildWarUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UNAME])) 
		        {
		        	$arrUserInfo[$aUid]['uname'] = $arrGuildWarUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UNAME];
		        }
		    }
		    else 
		    {
		    	$arrUserInfo[$aUid]['state'] = 0;
		    }
		    
		    $arrUserInfo[$aUid]['contr_num'] = $arrMemberInfo[$aUid][GuildDef::CONTRI_TOTAL];
		    $arrUserInfo[$aUid]['member_type'] = $arrMemberInfo[$aUid][GuildDef::MEMBER_TYPE];
		}
		
		$arrUserInfo = array_values($arrUserInfo);
		Logger::trace('GuildWarLogic::getGuildWarMemberList ret[%s] end...', $arrUserInfo);
		return $arrUserInfo;
	}
	
	public static function changeCandidate($uid, $type, $memberUid)
	{
		Logger::trace('GuildWarLogic::changeCandidate begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_CHANGE_CANDIDATE, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_CHANGE_CANDIDATE))
		{
			throw new FakeException('GuildWarLogic::changeCandidate failed, not the right time.');
		}
		
		// 不能调整自己
		if ($uid == $memberUid) 
		{
			throw new FakeException('GuildWarLogic::changeCandidate failed, for self.');
		}
		
		// 判断军团是否一致
		$memberGuildId = EnGuild::getGuildId($memberUid);
		if (empty($memberGuildId) || $guildId != $memberGuildId) 
		{
			throw new FakeException('GuildWarLogic::changeCandidate failed, not same guildId, curGuildId[%d], memGuildId[%d].', $guildId, $memberGuildId);
		}
		
		// 换上操作
		if ($type == 1) 
		{
			// 参战人员数量超过最大限
			$candidatesCount = count($guildWarServerObj->getCandidates());
			$maxCandidatesCount = $confObj->getCandidatesCount();
			if ($candidatesCount >= $maxCandidatesCount) 
			{
				throw new FakeException('GuildWarLogic::changeCandidate add failed, count exceed, curCount[%d], maxCount[%d].', $candidatesCount, $maxCandidatesCount);
			}
			
			// 已经是参展人员啦
			if ($guildWarServerObj->isCandidate($memberUid)) 
			{
				throw new FakeException('GuildWarLogic::changeCandidate add failed, memberUid[%d], already is a candidate[%s].', $memberUid, $guildWarServerObj->getCandidates());
			}
			
			$guildWarServerObj->addCandidate($memberUid);
			RPCContext::getInstance()->executeTask($memberUid, 'guildwar.initUserGuildWarByUid', array($serverId, $memberUid));
		}
		else // 换下操作
		{
			// 已经没有参战人员啦
			$arrCandidates = $guildWarServerObj->getCandidates();
			if (empty($arrCandidates)) 
			{
				throw new FakeException('GuildWarLogic::changeCandidate sub failed, no candidates at all.');
			}
			
			// 不是现有的参战人员
			if (!$guildWarServerObj->isCandidate($memberUid)) 
			{
				throw new FakeException('GuildWarLogic::changeCandidate sub failed, memberUid[%d] not a candidate, curr candidates[%s].', $memberUid, $guildWarServerObj->getCandidates());
			}
			
			$arrField = array
			(
					GuildWarUserField::TBL_FIELD_UID,
					GuildWarUserField::TBL_FIELD_FIGHT_FORCE,
			);
			$arrGuildWarUserInfo = GuildWarDao::getArrGuildWarUserInfo($arrCandidates, $arrField);
			
			// 去除掉军团长
			unset($arrGuildWarUserInfo[$uid]);
			
			// 根据战斗力和玩家uid排序
			$sortCmp = new SortByFieldFunc(array(GuildWarUserField::TBL_FIELD_FIGHT_FORCE => SortByFieldFunc::DESC, GuildWarUserField::TBL_FIELD_UID => SortByFieldFunc::DESC));
			usort($arrGuildWarUserInfo, array($sortCmp, 'cmp'));
			Logger::trace('GuildWarLogic::changeCandidate curr guild war user info[%s], sub candidate[%d]', $arrGuildWarUserInfo, $memberUid);
			
			// 战力排名前15不能下场
			$rank = 0;
			foreach ($arrGuildWarUserInfo as $aUserInfo)
			{
				$aUid = $aUserInfo[GuildWarUserField::TBL_FIELD_UID];
				if (++$rank <= GuildWarConf::CAN_NOT_CHANGE_NUM && $aUid == $memberUid) 
				{
					throw new FakeException('GuildWarLogic::changeCandidate sub failed, fight force rank[%d], less than[%d].', $rank, GuildWarConf::CAN_NOT_CHANGE_NUM);
				}
			}
			
			$guildWarServerObj->subCandidate($memberUid);
		}
		
		$guildWarServerObj->update();
		Logger::trace('GuildWarLogic::changeCandidate ok...');
		return 'ok';
	}
	
	public static function getMyTeamInfo($uid)
	{
		Logger::trace('GuildWarLogic::getMyTeamInfo begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_MY_TEAM_INFO, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$teamMgr = TeamManager::getInstance(WolrdActivityName::GUILDWAR, $session);
		
		Logger::trace('GuildWarLogic::getMyTeamInfo basic info, session[%d], serverId[%d], $teamId[%d]', $session, $serverId, $teamId);
		
		// 获得同组所有服务器Id
		$arrServerId = $teamMgr->getServersByTeamId($teamId);
		if (empty($arrServerId))
		{
			return array();
		}
		
		// 获得服务器名称
		$ret = array();
		$serverMgr = ServerInfoManager::getInstance();
		$ret = $serverMgr->getArrServerName($arrServerId);
		
		Logger::trace('GuildWarLogic::getMyTeamInfo ret[%s] end...', $ret);
		return $ret;
	}
	
	public static function getGuildWarInfo($uid)
	{
		Logger::trace('GuildWarLogic::getGuildWarInfo begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_GUILD_WAR_INFO, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_GET_GUILD_WAR_INFO))
		{
			throw new FakeException('GuildWarLogic::getGuildWarInfo failed, not the right time.');
		}
		
		// 获得晋级赛军团信息
		$arrField = array
		(
				GuildWarServerField::TBL_FIELD_POS,
				GuildWarServerField::TBL_FIELD_GUILD_ID,
				GuildWarServerField::TBL_FIELD_GUILD_NAME,
				GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID,
				GuildWarServerField::TBL_FIELD_GUILD_SERVER_NAME,
				GuildWarServerField::TBL_FIELD_SIGN_TIME,
				GuildWarServerField::TBL_FIELD_FINAL_RANK,
				GuildWarServerField::TBL_FIELD_FIGHT_FORCE,
				GuildWarServerField::TBL_FIELD_GUILD_LEVEL,
				GuildWarServerField::TBL_FIELD_GUILD_BADGE,
		);
		$ret = GuildWarDao::selectFinalsGuildInfo($arrField, $session, $teamId, $confObj->getFailNum(), $confObj->getSignUpStartTime());
	
		Logger::trace('GuildWarLogic::getGuildWarInfo ret[%s] end...', $ret);
		return $ret;
	}
	
	public static function getHistoryCheerInfo($uid)
	{
		Logger::trace('GuildWarLogic::getHistoryCheerInfo begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_HISTORY_CHEER_INFO, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_GET_HISTORY_CHEER_INFO))
		{
			throw new FakeException('GuildWarLogic::getHistoryCheerInfo failed, not the right time.');
		}
		
		// 获得历史助威信息
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		$arrCheerInfo = $guildWarUserObj->getAllCheerInfo();
		
		// 整理数据
		foreach ($arrCheerInfo as $round => $aCheerInfo)
		{
			if ($aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME] > 0) // 已经发助威奖，则代表所助威军团已经晋级
			{
				$arrCheerInfo[$round]['guildState'] = 2; // 晋级
				$arrCheerInfo[$round]['rewardState'] = 1; // 已经发奖
			}
			else if ($aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME] < 0) // 助威军团没有晋级
			{
				$arrCheerInfo[$round]['guildState'] = 3; // 淘汰
				$arrCheerInfo[$round]['rewardState'] = 0; // 未发助威奖
			}
			else 
			{
				$procedureObj = GuildWarProcedureObj::getInstance($session);
				$teamObj = $procedureObj->getTeamObj($teamId);
				$curRound = $teamObj->getCurRound();
				$curStatus = $teamObj->getCurStatus();
				
				if ($round > $curRound) 
				{
					$arrCheerInfo[$round]['guildState'] = 0; // 还未比赛
				}
				else if ($round == $curRound) 
				{
					if ($curStatus != GuildWarStatus::DONE)
					{
						$arrCheerInfo[$round]['guildState'] = 1; // 正在比赛中
					}
					else
					{
						$aGuildId = $aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_GUILD_ID];
						$aServerId = $aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_SERVER_ID];
						$guildWarServerObj = GuildWarServerObj::getInstance($session, $aServerId, $aGuildId);
						if ($guildWarServerObj->getFinalRank() > GuildWarConf::$all_rank[$round])
						{
							$arrCheerInfo[$round]['guildState'] = 3; // 淘汰
						}
						else
						{
							$arrCheerInfo[$round]['guildState'] = 2; // 晋级
						}
					}
				}
				else 
				{
					$aGuildId = $aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_GUILD_ID];
					$aServerId = $aCheerInfo[GuildWarUserField::TBL_VA_EXTRA_SERVER_ID];
					$guildWarServerObj = GuildWarServerObj::getInstance($session, $aServerId, $aGuildId);
					if ($guildWarServerObj->getFinalRank() > GuildWarConf::$all_rank[$round])
					{
						$arrCheerInfo[$round]['guildState'] = 3; // 淘汰
					}
					else
					{
						$arrCheerInfo[$round]['guildState'] = 2; // 晋级
					}
					Logger::warning('GuildWarLogic::getHistoryCheerInfo error, round[%d] user[%d] not deal support reward', $round, $uid);
				}
				
				$arrCheerInfo[$round]['rewardState'] = 0; // 没有发奖
			}
			
			unset($arrCheerInfo[$round][GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME]);
		}

		Logger::trace('GuildWarLogic::getHistoryCheerInfo ret[%s] end...', $arrCheerInfo);
		return $arrCheerInfo;
	}
	
	public static function cheer($uid, $cheerGuildId, $cheerServerId)
	{
		Logger::trace('GuildWarLogic::cheer begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_CHEER, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_CHEER))
		{
			throw new FakeException('GuildWarLogic::cheer failed, not the right time.');
		}
		
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		$nextRound = $confObj->getNextRound($curRound);
		
		// 判断在不在助威限制时间段内
		$nextRoundStartTime = $confObj->getRoundStartTime($nextRound);
		$cheerLimitTime = $confObj->getCheerLimit();
		if (Util::getTime() >= ($nextRoundStartTime - $cheerLimitTime)) 
		{
			throw new FakeException('GuildWarLogic::cheer failed, in cheer limit time, now[%s], nextRoundStartTime[%s], cheerLimit[%d]', strftime('%Y%m%d-%H%M%S', Util::getTime()), strftime('%Y%m%d-%H%M%S', $nextRoundStartTime), $cheerLimitTime);
		}
	
		// 看看他助威的军团是否是轮空
		if (self::directPromotion($teamId, $session, $nextRound, $cheerGuildId, $cheerServerId))
		{
			throw new FakeException("GuildWarLogic::cheer failed, directPromotion can not cheer.");
		}
		
		// 助威的军团是不是处于正确的排名
		$cheerGuildWarServerObj = GuildWarServerObj::getInstance($session, $cheerServerId, $cheerGuildId);
		$cheerRank = $cheerGuildWarServerObj->getFinalRank();
		if ($cheerRank != GuildWarConf::$all_rank[$curRound]) 
		{
			throw new FakeException("GuildWarLogic::cheer failed, curRound[%d], cheerRound[%d], cheerRank[%d] not right, should rank[%d].", $curRound, $confObj->getNextRound($curRound), $cheerRank, GuildWarConf::$all_rank[$curRound]);
		}
		
		// 如果当前轮次已经是最后一轮啦，无法助威
		if ($curRound == GuildWarRound::ADVANCED_2) 
		{
			throw new FakeException("GuildWarLogic::cheer failed, lastRound[%d], can not cheer.", $curRound);
		}
		
		// 玩家是否已经助威过这轮啦
		$cheerRound = $confObj->getNextRound($curRound);
		$cheerInfo = $guildWarUserObj->getCheerInfo($cheerRound);
		if (!empty($cheerInfo))
		{
			throw new FakeException('GuildWarLogic::cheer failed, already cheer for this round[%d], cheer info[%s]', $cheerRound, $cheerInfo);
		}
		
		// 扣银币，和等级挂钩
		$needSilver = $confObj->getCheerBaseCost() * $userObj->getLevel();
		if(!$userObj->subSilver($needSilver))
		{
			throw new FakeException("GuildWarLogic::cheer failed, Can not cheer silver not enough, need[%d], cur[%d]", $needSilver, $userObj->getSilver());
		}
		$userObj->update();
	
		// 助威
		$cheerGuildName = $cheerGuildWarServerObj->getGuildName();
		$cheerServerName = $cheerGuildWarServerObj->getServerName();
		$guildWarUserObj->cheer($cheerRound, $cheerGuildId, $cheerGuildName, $cheerServerId, $cheerServerName);
		$guildWarUserObj->update();
		
		Logger::trace('GuildWarLogic::cheer end...');
		return 'ok';
	}
	
	public static function getTempleInfo($uid)
	{
		Logger::trace('GuildWarLogic::getTempleInfo begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_TEMPLE_INFO, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_GET_TEMPLE_INFO))
		{
			throw new FakeException('GuildWarLogic::getTempleInfo failed, not the right time.');
		}
		
		// 先从服内表获取膜拜信息
		$templeInfo = GuildWarDao::selectGuildWarTemple($session);
		
		// 服内膜拜对象为空的话，再去跨服机器上找
		if (empty($templeInfo)) 
		{
			// 获得数据
			$arrField = array
			(
					GuildWarServerField::TBL_FIELD_GUILD_ID,
					GuildWarServerField::TBL_FIELD_GUILD_NAME,
					GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID,
					GuildWarServerField::TBL_FIELD_GUILD_SERVER_NAME,
					GuildWarServerField::TBL_FIELD_GUILD_BADGE,
					GuildWarServerField::TBL_FIELD_VA_EXTRA,
			);
			$arrRet = GuildWarDao::selectFinalsGuildInfoByRank($arrField, $session, $teamId, $confObj->getFailNum(), $confObj->getSignUpStartTime(), GuildWarConf::GUILD_WAR_RANK_1);
			if (empty($arrRet))
			{
				throw new InterException('GuildWarLogic::getTempleInfo failed, can not get champion info of teamId[%d].', $teamId);
			}
			$templeInfo = $arrRet[0];
			
			// 整理数据
			if (!empty($templeInfo[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_PRESIDENT_INFO])) 
			{
				$presidentInfo = $templeInfo[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_PRESIDENT_INFO];
			}
			else 
			{
				$presidentInfo = GuildWarLogic::getChampionPresidentInfo($templeInfo[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $templeInfo[GuildWarServerField::TBL_FIELD_GUILD_ID]);
				Logger::warning('GuildWarLogic::getTempleInfo failed, can not get champion guild president info from cross server, re get info[%s].', $presidentInfo);
				if (empty($presidentInfo)) 
				{
					throw new InterException('GuildWarLogic::getTempleInfo failed, can not get champion guild president info of serverId[%d] guildId[%d]', $templeInfo[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $templeInfo[GuildWarServerField::TBL_FIELD_GUILD_ID]);
				}
			}
			
			$templeInfo = array_merge($templeInfo, $presidentInfo);
			$templeInfo['session'] = $session;
			unset($templeInfo[GuildWarServerField::TBL_FIELD_VA_EXTRA]);
			
			// 插入服内的膜拜表
			$arrExtra = $templeInfo;
			unset($arrExtra['session']);
			$arrValue = array
			(
					GuildWarTempleField::TBL_FIELD_SESSION => $session,
					GuildWarTempleField::TBL_FIELD_VA_EXTRA => $arrExtra,
			);
			GuildWarDao::updateGuildWarTemple($arrValue);
		}
		else 
		{
			$templeInfo = array_merge($templeInfo, $templeInfo[GuildWarTempleField::TBL_FIELD_VA_EXTRA]);
			unset($templeInfo[GuildWarTempleField::TBL_FIELD_VA_EXTRA]);
		}
		
		Logger::trace('GuildWarLogic::getTempleInfo ret[%s] end...', $templeInfo);
		return $templeInfo;
	}
	
	public static function worship($uid, $type)
	{
		Logger::trace('GuildWarLogic::worship begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_WORSHIP, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_WORSHIP))
		{
			throw new FakeException('GuildWarLogic::worship failed, not the right time.');
		}
		
		// 检查今天是否已经膜拜过
		if (Util::isSameDay($guildWarUserObj->getWorshipTime())) 
		{
			throw new FakeException('GuildWarLogic::worship failed, already worship today, worship time[%s].', strftime('%Y%m%d-%H%M%S', $guildWarUserObj->getWorshipTime()));
		}
		
		// 获取膜拜需要的金币
		$needGold = $confObj->getWorshipCost($type);
		if (!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_WAR_WORSHIP_COST)) 
		{
			throw new FakeException('GuildWarLogic::worship failed, lack gold, need[%d] cur[%d].', $needGold, $userObj->getGold());
		}
		
		// 获取膜拜奖励
		$arrReward = $confObj->getWorshipPrize($type);
		RewardUtil::reward3DArr($uid, $arrReward, StatisticsDef::ST_FUNCKEY_GUILD_WAR_WORSHIP_PRIZE);
		
		// 标记
		$guildWarUserObj->worship();
		$guildWarUserObj->update(); 
		
		// 同步到数据库
		$userObj->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		Logger::trace('GuildWarLogic::worship end...');
		return 'ok';
	}
	
	public static function getHistoryFightInfo($uid)
	{
		Logger::trace('GuildWarLogic::getHistoryFightInfo begin...');
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_HISTORY_FIGHT_INFO, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_GET_HISTORY_FIGHT_INFO))
		{
			throw new FakeException('GuildWarLogic::getHistoryFightInfo failed, not the right time.');
		}
		
		// 获得自己的数据
		$selfInfo = $guildWarServerObj->getBasicInfo();
		
		// 获得海选赛记录，海选赛的战报中已经把自己的信息去掉啦
		$auditionInfo = $guildWarServerObj->getAuditionReplay();
		
		// 获取晋级赛记录
		$finalsInfo = $guildWarServerObj->getFinalsReplay();
		
		// 将晋级赛战报中自己的信息去掉
		foreach ($finalsInfo as $round => $info)
		{
			if ($info['attacker'][GuildWarServerField::TBL_FIELD_GUILD_ID] == $guildWarServerObj->getGuildId()
			&& $info['attacker'][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID] == $guildWarServerObj->getServerId())
			{
				$finalsInfo[$round]['attacker'] = array();
			}
			else
			{
				$finalsInfo[$round]['defender'] = array();
			}
		}
		
		$ret = array();
		$ret['self'] = $selfInfo;
		$ret['audition'] = $auditionInfo;
		$ret['finals'] = $finalsInfo;
		
		Logger::trace('GuildWarLogic::getHistoryFightInfo ret[%s] end...', $ret);
		return $ret;
	}
	
	public static function getReplay($uid, $guildId01, $serverId01, $guildId02, $serverId02)
	{
		Logger::trace('GuildWarLogic::getReplay param[uid:%d, guildId01:%d, serverId01:%d, $guildId02:%d, $serverId02:%d] begin...', $uid, $guildId01, $serverId01, $guildId02, $serverId02);
		
		// 检查操作有效性
		GuildWarUtil::checkBasic(GuildWarConf::CHECK_TYPE_GET_REPLAY, $uid);
		
		// 获得基本数据
		$confObj = GuildWarConfObj::getInstance();
		$serverId = GuildWarUtil::getMinServerId();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		$userObj = EnUser::getUserObj($uid);
		$guildId = $userObj->getGuildId();
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		
		// 检查时间
		if (!GuildWarUtil::checkStage($session, $teamId, GuildWarConf::CHECK_TYPE_GET_REPLAY))
		{
			throw new FakeException('GuildWarLogic::getReplay failed, not the right time.');
		}
		
		// 返回值
		$ret = array();
		
		// 获取两个军团的对象
		$guildWarServerObj01 = GuildWarServerObj::getInstance($session, $serverId01, $guildId01);
		$guildWarServerObj02 = GuildWarServerObj::getInstance($session, $serverId02, $guildId02);
		
		// 获取这两个军团的晋级赛战报
		$replayInfo = $guildWarServerObj01->getFinalsReplayByGuild($serverId02, $guildId02);
		if (empty($replayInfo)) 
		{
			$replayInfo = $guildWarServerObj02->getFinalsReplayByGuild($serverId01, $guildId01);
			if (!empty($replayInfo)) 
			{
				Logger::warning('GuildWarLogic::getReplay, not get replay from serverId01[%d] guildId01[%d], but get from serverId02[%d] guildId02[%d]', $serverId01, $guildId01, $serverId02, $guildId02);
			}
		}
		
		// 判断谁是攻方，谁是守方
		if (empty($replayInfo)) // 还没有开打，拿双方第一个出战人员的战斗力高低判断
		{
			$arrCandidates01 = $guildWarServerObj01->getCandidatesInfo();
			if (empty($arrCandidates01)) 
			{
				throw new InterException('GuildWarLogic::getReplay failed, empty candidates of serverId01[%d] guildId01[%d]', $serverId01, $guildId01);
			}
			$fightForce01 = $arrCandidates01[0][GuildWarUserField::TBL_FIELD_FIGHT_FORCE];
			
			$arrCandidates02 = $guildWarServerObj02->getCandidatesInfo();
			if (empty($arrCandidates02))
			{
				throw new InterException('GuildWarLogic::getReplay failed, empty candidates of serverId02[%d] guildId02[%d]', $serverId02, $guildId02);
			}
			$fightForce02 = $arrCandidates02[0][GuildWarUserField::TBL_FIELD_FIGHT_FORCE];
			
			if ($fightForce01 >= $fightForce02) 
			{
				$attackerObj = &$guildWarServerObj01;
				$defenferObj = &$guildWarServerObj02;
			}
			else 
			{
				$attackerObj = &$guildWarServerObj02;
				$defenferObj = &$guildWarServerObj01;
			}
			
			$replayInfo['attacker'] = $attackerObj->getBasicInfo();
			$replayInfo['defender'] = $defenferObj->getBasicInfo();
		}
		else // 如果已经打过啦，则战报里有
		{
			if ($replayInfo['attacker']['guild_server_id'] == $serverId01
				&& $replayInfo['attacker']['guild_id'] == $guildId01)
			{
				$attackerObj = &$guildWarServerObj01;
				$defenferObj = &$guildWarServerObj02;
			}
			else
			{
				$attackerObj = &$guildWarServerObj02;
				$defenferObj = &$guildWarServerObj01;
			}
		}
		
		// 获得攻击者成员信息   
		$arrAttackMemberInfo = array();
		$arrAttackLosers = $attackerObj->getLosers();
		$arrAttackCandidatesInfo = $attackerObj->getCandidatesInfo();
		$arrAttackUid = Util::arrayExtract($arrAttackCandidatesInfo, GuildWarUserField::TBL_FIELD_UID);
		Logger::trace('GuildWarLogic::getReplay arrAttackUid[%s].', $arrAttackUid);
			
		// 获得防守者成员信息
		$arrDefendMemberInfo = array();
		$arrDefendLosers = $defenferObj->getLosers();
		$arrDefendCandidatesInfo = $defenferObj->getCandidatesInfo();
		$arrDefendUid = Util::arrayExtract($arrDefendCandidatesInfo, GuildWarUserField::TBL_FIELD_UID);
		Logger::trace('GuildWarLogic::getReplay arrDefendUid[%s].', $arrAttackUid);
			
		// 获得htid
		$arrAttackUid2Htid = array();
		$arrDefendUid2Htid = array();
		if ($attackerObj->getServerId() == $defenferObj->getServerId()) 
		{
			$arrAllUid = array_merge($arrAttackUid, $arrDefendUid);
			$arrUserInfo = EnUser::getArrUserBasicInfo($arrAllUid, array('uid', 'htid'), $attackerObj->getServerDb());
			foreach ($arrUserInfo as $aUid => $info)
			{
				if (in_array($aUid, $arrAttackUid)) 
				{
					$arrAttackUid2Htid[$aUid] = $info['htid'];
				}
				else 
				{
					$arrDefendUid2Htid[$aUid] = $info['htid'];
				}
			}
		}
		else 
		{
			$arrAttackUserInfo = EnUser::getArrUserBasicInfo($arrAttackUid, array('uid', 'htid'), $attackerObj->getServerDb());
			foreach ($arrAttackUserInfo as $aUid => $info)
			{
				$arrAttackUid2Htid[$aUid] = $info['htid'];
			}
				
			$arrDefendUserInfo = EnUser::getArrUserBasicInfo($arrDefendUid, array('uid', 'htid'), $defenferObj->getServerDb());
			foreach ($arrDefendUserInfo as $aUid => $info)
			{
				$arrDefendUid2Htid[$aUid] = $info['htid'];
			}
		}
		Logger::trace('GuildWarLogic::getReplay arrAttackUid2Htid[%s] arrDefendUid2Htid[%s]', $arrAttackUid2Htid, $arrDefendUid2Htid);
			
		// 整理数据
		foreach ($arrAttackCandidatesInfo as $index => $aUserInfo)
		{
			$arrAttackMemberInfo[] = array
			(
					'state' => in_array($aUserInfo[GuildWarUserField::TBL_FIELD_UID], $arrAttackLosers) ? 0 : 1, // 0无法上场1可以上场
					'htid' => $arrAttackUid2Htid[$aUserInfo[GuildWarUserField::TBL_FIELD_UID]],
					'uname' => $aUserInfo[GuildWarUserField::TBL_FIELD_UNAME],
					'fight_force' => $aUserInfo[GuildWarUserField::TBL_FIELD_FIGHT_FORCE],
			);
		}
		$replayInfo['attacker']['member'] = $arrAttackMemberInfo;
		foreach ($arrDefendCandidatesInfo as $index => $aUserInfo)
		{
			$arrDefendMemberInfo[] = array
			(
					'state' => in_array($aUserInfo[GuildWarUserField::TBL_FIELD_UID], $arrDefendLosers) ? 0 : 1, // 0无法上场1可以上场
					'htid' => $arrDefendUid2Htid[$aUserInfo[GuildWarUserField::TBL_FIELD_UID]],
					'uname' => $aUserInfo[GuildWarUserField::TBL_FIELD_UNAME],
					'fight_force' => $aUserInfo[GuildWarUserField::TBL_FIELD_FIGHT_FORCE],
			);
		}
		$replayInfo['defender']['member'] = $arrDefendMemberInfo;
			
		if (!empty($replayInfo) && isset($replayInfo['sub_round']))
		{
			// 解析主战报
			$arrReplayId = Util::arrayExtract($replayInfo['sub_round'], 'replay_id');
			unset($replayInfo['sub_round']);
			$arrProcess = self::getReplayDetail($uid, $arrReplayId);
			foreach ($arrProcess as $aReplayId => $aReplayInfo)
			{
				$replayInfo['sub_round'][] = array
				(
						'replay_id' => $aReplayId,
						'arrProcess' => $aReplayInfo,
				);
			}
		}
		
		if (!empty($replayInfo) && isset($replayInfo['result']) && isset($replayInfo['left_user'])) 
		{
			$arrLeftUserAfterResult = array();
			$arrLeftUser = $replayInfo['left_user'];
			foreach ($arrLeftUser as $subRoundIndex => $arrSubRoundUsers)
			{
				if ($replayInfo['result'] == 2)
				{
					foreach ($arrAttackCandidatesInfo as $index => $aUserInfo)
					{
						if (in_array($aUserInfo[GuildWarUserField::TBL_FIELD_UID], $arrSubRoundUsers)) 
						{
							$arrLeftUserAfterResult[$subRoundIndex][] = array
							(
									'htid' => $arrAttackUid2Htid[$aUserInfo[GuildWarUserField::TBL_FIELD_UID]],
									'uname' => $aUserInfo[GuildWarUserField::TBL_FIELD_UNAME],
									'fight_force' => $aUserInfo[GuildWarUserField::TBL_FIELD_FIGHT_FORCE],
							);
						}
					}
				}
				else 
				{
					foreach ($arrDefendCandidatesInfo as $index => $aUserInfo)
					{
						if (in_array($aUserInfo[GuildWarUserField::TBL_FIELD_UID], $arrSubRoundUsers)) 
						{
							$arrLeftUserAfterResult[$subRoundIndex][] = array
							(
									'htid' => $arrDefendUid2Htid[$aUserInfo[GuildWarUserField::TBL_FIELD_UID]],
									'uname' => $aUserInfo[GuildWarUserField::TBL_FIELD_UNAME],
									'fight_force' => $aUserInfo[GuildWarUserField::TBL_FIELD_FIGHT_FORCE],
							);
						}
					}
				}					
			}
			$replayInfo['left_user'] = $arrLeftUserAfterResult;
		}
		
		Logger::trace('GuildWarLogic::getReplay ret[%s] end...', $replayInfo);
		return $replayInfo;
	}
	
	public static function getReplayDetail($uid, $arrReplayId)
	{
		Logger::trace('GuildWarLogic::getReplayDetail param[%s] begin...', $arrReplayId);
		
		$ret = EnBattle::getArrRecord($arrReplayId);
		$ret = BattleManager::genBattleProcess($ret);
		
		Logger::trace('GuildWarLogic::getReplayDetail ret[%s] end...', $ret);
		return $ret;
	}

	public static function directPromotion($teamId, $session, $round, $guildId, $serverId)
	{	
		Logger::trace('GuildWarLogic::directPromotion param[teamId:%d, session:%d, round:%d, guildId:%d, serverId:%d] begin...', $teamId, $session, $round, $guildId, $serverId);
		
		// 标记参数对应的军团是否在这轮晋级的军团内
		$isRightGuild = FALSE;
		
		// 获取晋级赛军团信息
		$arrGuildInfo = GuildWarLogic::getFinalsGuildInfo($session, $teamId);		
		$rank = GuildWarConf::$round_rank[$round];
		$nextRank = GuildWarConf::$next_rank[$rank];
		$step = GuildWarConf::$step[$round];
		for ($i = 1; $i <= GuildWarConf::MAX_JOIN_NUM; $i += $step)
		{
			// 获取两个对战军团信息
			list($fighterObjA, $fighterObjB) = GuildWarLogic::getFightPair($arrGuildInfo, $i, $step, $rank, $nextRank);
			$figherObjAInfo = ($fighterObjA instanceof GuildWarServerObj ? $fighterObjA->getServerInfo() : array());
			$figherObjBInfo = ($fighterObjB instanceof GuildWarServerObj ? $fighterObjB->getServerInfo() : array());
			Logger::trace('GuildWarLogic::directPromotion, after getFightPair, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
			
			// 两方都为空
			if (empty($fighterObjA) && empty($fighterObjB)) 
			{
				continue;
			}
			
			// 一方为空，另一方不为当前军团
			if ((empty($fighterObjA) && ($fighterObjB->getGuildId() != $guildId || $fighterObjB->getServerId() != $serverId))
				|| (empty($fighterObjB) && ($fighterObjA->getGuildId() != $guildId || $fighterObjA->getServerId() != $serverId)))
			{
				continue;
			}
			
			// 一方为空，另一方为当前军团，属于轮空的情况
			if ((empty($fighterObjA) && $fighterObjB->getGuildId() == $guildId && $fighterObjB->getServerId() == $serverId)
				|| (empty($fighterObjB) && $fighterObjA->getGuildId() == $guildId && $fighterObjA->getServerId() == $serverId))
			{
				return TRUE;
			}
			
			// 标记该军团是不是属于16强
			if (($fighterObjA->getGuildId() == $guildId && $fighterObjA->getServerId() == $serverId)
				|| ($fighterObjB->getGuildId() == $guildId && $fighterObjB->getServerId() == $serverId))
			{
				$isRightGuild = TRUE;
			}
		}
		
		if (!$isRightGuild)
		{
			throw new InterException('GuildWarLogic::directPromotion failed, round[%d] rank[%d] guildId[%d] serverId[%d] not in right winners[%s]', $round, $rank, $guildId, $serverId, $arrGuildInfo);
		}
		
		return FALSE;
	}
	
	public static function getChampionPresidentInfo($serverId, $guildId)
	{
		try
		{
			$group = Util::getGroupByServerId($serverId);
			$proxy = new ServerProxy();
			$proxy->init($group, Util::genLogId());
			return $proxy->getChampionPresidentInfo($serverId, $guildId);
		}
		catch (Exception $e)
		{
			Logger::fatal('getChampionPresidentInfo error serverGroup:%s', $serverId);
			return array();
		}
	}
	
	public static function doGetChampionPresidentInfo($serverId, $guildId)
	{
		$arrMemberList = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::MEMBER_TYPE));
		
		$presidentUid = 0;
		foreach ($arrMemberList as $uid => $memberInfo)
		{
			if ($memberInfo[GuildDef::MEMBER_TYPE] == GuildMemberType::PRESIDENT)
			{
				$presidentUid = $uid;
				break;
			}
		}
		
		if (empty($presidentUid)) 
		{
			return array();
		}
		
		$presidentUserObj = EnUser::getUserObj($presidentUid);
		$ret = array();
		$ret['president_uname'] = $presidentUserObj->getUname();
		$ret['president_htid'] = $presidentUserObj->getHeroManager()->getMasterHeroObj()->getHtid();
		$ret['president_level'] = $presidentUserObj->getLevel();
		$ret['president_vip_level'] = $presidentUserObj->getVip();
		$ret['president_fight_force'] = $presidentUserObj->getFightForce();
		$ret['president_dress'] = $presidentUserObj->getDressInfo();
		
		return $ret;
	}

	/**
	 * 同步获取玩家战斗信息，跨服机器上
	 * 
	 * @param int $serverId
	 * @param int $uid
	 * @return array:
	 */
	public static function reGetUserGuildWarInfo($serverId, $uid)
	{
		try 
		{	
			$group = Util::getGroupByServerId($serverId);
			$proxy = new ServerProxy();
			$proxy->init($group, Util::genLogId());
			return $proxy->initUserGuildwarInfo($serverId, $uid);
		}
		catch (Exception $e)
		{
			Logger::fatal('initUserGuildwarInfo error serverGroup:%s', $serverId);
			return array();
		}
	}
	
	/**
	 * 获取玩家的战斗数据，本服上，但是不在用户线程
	 * 
	 * @param int $serverId
	 * @param int $uid
	 * @return array
	 */
	public static function reInitUserGuildWarInfo($serverId, $uid)
	{
		// 如果已经初始化过，则直接返回
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		if ($guildWarUserObj->isArmed())
		{
			return array($guildWarUserObj->getUname(), $guildWarUserObj->getBattleFmt());
		}
		
		// 获得玩家的战斗数据
		$userObj = EnUser::getUserObj($uid);
		$battleFmt = $userObj->getBattleFormation();
		
		// 抛到玩家自己的线程去更新数据库
		RPCContext::getInstance()->executeTask($uid, 'guildwar.initUserGuildWarByUid', array($serverId, $uid));
		
		// 返回用户的战斗信息
		return array($userObj->getUname(), $battleFmt);
	}
	
	/**
	 * 初始化玩家自己的跨服战信息，在玩家自己的线程里
	 *
	 * @param int $uid
	 */
	public static function initUserGuildWarByUid($serverId, $uid)
	{
		Logger::trace('GuildWarLogic::initUserGuildWarByUid param[serverId:%d,uid:%d] begin...', $serverId, $uid);
	
		if (RPCContext::getInstance()->getUid() == 0)
		{
			RPCContext::getInstance()->setSession('global.uid', $uid);
		}
	
		// 如果已经初始化过，则直接返回
		$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
		if ($guildWarUserObj->isArmed())
		{
			return;
		}
	
		// 获得战斗数据
		$userObj = EnUser::getUserObj($uid);
		$battleFmt = $userObj->getBattleFormation();
	
		// 装备战斗数据
		$guildWarUserObj->arm($battleFmt);
		$guildWarUserObj->setUname($userObj->getUname());
		$guildWarUserObj->update();
	
		Logger::trace('GuildWarLogic::initUserGuildWarByUid param[serverId:%d,uid:%d] end...', $serverId, $uid);
	}
	
	/**
	 * 调用战斗模块进行战斗
	 * 
	 * @param array $infoCur
	 * @param array $infoObj
	 * @return array
	 */
	public static function doFight($infoCur, $infoObj)
	{
		Logger::trace('GuildWarLogic::doFight begin...');
		
		Logger::trace('GuildWarLogic::doFight Battle play1 is %s.', $infoCur);
		Logger::trace('GuildWarLogic::doFight Battle play2 is %s.', $infoObj);
	
		// 谁第一个人的战斗力靠前谁先手
		if ($infoCur['members'][0]['fightForce'] >= $infoObj['members'][0]['fightForce'])
		{
			$attackBattleFmt = GuildWarUtil::changeIdsForBattleFmt($infoCur, GuildWarConf::OFFSET_ONE);
			$defendBattleFmt = GuildWarUtil::changeIdsForBattleFmt($infoObj, GuildWarConf::OFFSET_TWO);
		}
		else
		{
			$attackBattleFmt = GuildWarUtil::changeIdsForBattleFmt($infoObj, GuildWarConf::OFFSET_TWO);
			$defendBattleFmt = GuildWarUtil::changeIdsForBattleFmt($infoCur, GuildWarConf::OFFSET_ONE);
		}
		
		$mapUidInitWin = array();
		if (isset($attackBattleFmt['mapUidInitWin']))
		{
			foreach ($attackBattleFmt['mapUidInitWin'] as $key => $value)
			{
				$mapUidInitWin[$key] = $value;
			}
			Logger::trace('GuildWarLogic::doFight mapUidInitWin from attack[%s].', $attackBattleFmt['mapUidInitWin']);
		}
		if (isset($defendBattleFmt['mapUidInitWin']))
		{
			foreach ($defendBattleFmt['mapUidInitWin'] as $key => $value)
			{
				$mapUidInitWin[$key] = $value;
			}
			Logger::trace('GuildWarLogic::doFight mapUidInitWin from defend[%s].', $defendBattleFmt['mapUidInitWin']);
		}
		Logger::trace('GuildWarLogic::doFight mapUidInitWin total[%s].', $mapUidInitWin);
	
		// 执行一场战斗操作
		$atkRet = 'no fucking battle result because of exception';
		for ($i = 0; $i < 5; ++$i)
		{
			try
			{
				$arrDamageIncreConf = array
				(
						array(BattleDamageIncreType::Fix, 10, 14, 5000),
						array(BattleDamageIncreType::Fix, 15, 19, 10000),
						array(BattleDamageIncreType::Fix, 20, 24, 15000),
						array(BattleDamageIncreType::Fix, 25, 29, 20000),
						array(BattleDamageIncreType::Fix, 30, 30, 25000),
				);
				
				$atkRet = EnBattle::doMultiHero($attackBattleFmt,
												$defendBattleFmt,
												GuildWarConf::MAX_ARENA_COUNT,
												GuildWarConfObj::getInstance(GuildWarField::CROSS)->getDefaultMaxWinTimes(),
												array(
														'arrEndCondition' => 0,
														'mainBgid' => GuildWarConf::BACK_GROUND_M,
														'subBgid' => GuildWarConf::BACK_GROUND_S,
														'mainMusicId' => GuildWarConf::MUSIC_ID_M,
														'subMusicId' => GuildWarConf::MUSIC_ID_S,
														'mainCallback' => NULL,
														'subCallback' => NULL,
														'mainType' => BattleType::GUILD_WAR,
														'subType' => BattleType::GUILD_WAR,
														'isGuildWar' => TRUE,
														'db' => GuildWarUtil::getCrossDbName(),
														'mapUidInitWin' => $mapUidInitWin,
														'stopWhenBattleFailed' => TRUE,
														'damageIncreConf' => $arrDamageIncreConf,
													));
				break;
			}
			catch (Exception $e)
			{
				sleep(5);
				Logger::warning('GuildWarLogic::doFight Battle exeception:%s', $e->getMessage());
			}
		}
		Logger::trace('GuildWarLogic::doFight ret from battle is %s.', $atkRet);
		
		// 设置返回值
		$ret = array
		(
				'server' => $atkRet['server'],
				'replay' => $atkRet['server']['brid'],
				'mapUidWin' => $atkRet['mapUidWin'],
				'mapSurvivorHpInfo' => $atkRet['mapSurvivorHpInfo'],
				'arrLeftUid' => $atkRet['arrLeftUid'],
		);
	
		// 胜负判定, 有两种情况算是获胜 1. 本人先手，且获胜; 2. 对方先手， 失败了
		if (($atkRet['server']['result'] && $attackBattleFmt['guild_id'] . $attackBattleFmt['server_id'] == $infoCur['guild_id'] . $infoCur['server_id']) 
			||(!$atkRet['server']['result'] && $attackBattleFmt['guild_id'] . $attackBattleFmt['server_id'] == $infoObj['guild_id'] . $infoObj['server_id']))
		{
			$ret['winer'] = array('guild_id' => $infoCur['guild_id'], 'server_id' => $infoCur['server_id']);
			$ret['loser'] = array('guild_id' => $infoObj['guild_id'], 'server_id' => $infoObj['server_id']);
		}
		else
		{
			$ret['winer'] = array('guild_id' => $infoObj['guild_id'], 'server_id' => $infoObj['server_id']);
			$ret['loser'] = array('guild_id' => $infoCur['guild_id'], 'server_id' => $infoCur['server_id']);
		}
		
		Logger::trace('GuildWarLogic::doFight ret[%s] end...', $ret);
		return $ret;
	}
	
	/**
	 *  获取两个战斗的对手 —— 有可能会有轮空情况
	 *  
	 * @param array $arrGuildInfo
	 * @param int $start
	 * @param int $range
	 * @param int $rank
	 * @param int $nextRank
	 * @throws FakeException
	 * @return array(GuildWarServerObj, GuildWarServerObj)
	 */
	public static function getFightPair($arrGuildInfo, $start, $range, $rank, $nextRank)
	{
		Logger::trace('GuildWarLogic::getFightPair param[arrGuildInfo[%s], start[%d], range[%d], rank[%d], nextRank[%d]] begin...', $arrGuildInfo, $start, $range, $rank, $nextRank);
		
		$ret = array();
		$index = 0;
		foreach ($arrGuildInfo as $aGuildInfo)
		{
			// 获得obj
			$guildWarServerObj = GuildWarServerObj::getInstanceFromInfo($aGuildInfo);

			// 位置不在范围内，忽略
			$curPos = $guildWarServerObj->getPos();
			if ($curPos < $start || $curPos >= $start + $range) 
			{
				continue;
			}
			
			// 判断排名
			$finalRank = $guildWarServerObj->getFinalRank();
			if ($finalRank == $rank || $finalRank == $nextRank)
			{
				$ret[] = $guildWarServerObj;
			}
			else if ($finalRank < $nextRank)
			{
				throw new InterException('GuildWarLogic::getFightPair, invalid rank[%d], cur rank[%d], next rank[%d]', $finalRank, $rank, $nextRank);
			}
			
			if (count($ret) >= 2)
			{
				break;
			}
		}
		
		if (empty($ret))
		{
			$ret = array(array(), array());
		}
		
		if (count($ret) == 1) 
		{
			$ret[] = array();
		}
		
		Logger::trace("GuildWarLogic::getFightPair end...");
		return $ret;
	}
	
	public static function getAuditionGuildInfo($session, $teamId, $failNum, $signUpStartTime)
	{		
		return GuildWarDao::selectAllGuildWarServer($session, $teamId, $failNum, $signUpStartTime, array());
	}
	
	public static function getFinalsGuildInfo($session, $teamId)
	{
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		
		// 数据库拉取入围晋级赛的军团
		$arrGuildInfo = GuildWarDao::selectFinalsGuildInfo(array(), $session, $teamId, $confObj->getFailNum(), $confObj->getSignUpStartTime());
		
		// 第一次拉取晋级赛军团列表，需要打乱顺序，并且将顺序固定死，存入数据库，正常情况下，在打完海选赛，位置就固定啦
		if (!empty($arrGuildInfo) && $arrGuildInfo[0][GuildWarServerField::TBL_FIELD_POS] == 0) 
		{
			shuffle($arrGuildInfo);
			$pos = 0;
			foreach ($arrGuildInfo as $index => $aGuildInfo)
			{
				$aGuildWarServerObj = GuildWarServerObj::getInstanceFromInfo($aGuildInfo);
				$aGuildWarServerObj->setPos(++$pos);
				$aGuildWarServerObj->update();
				$arrGuildInfo[$index][GuildWarServerField::TBL_FIELD_POS] = $aGuildWarServerObj->getPos();
			}
			Logger::warning('GuildWarLogic::getFinalsGuildInfo, not rand pos when audition end, rand pos now, teamId[%d]', $teamId);
		}
		
		return $arrGuildInfo; 
	}
	
	/**
	 * 状态及信息推送
	 * 
	 * @param int $field 	服内推还是跨服推
	 * @param int $type 	推的是信息还是状态 1 状态 2 信息
	 * @param array $msg 	信息 如果type为1的话 则该字段会被换为,round和status信息
	 */
	public static function push($field, $type, $data = array())
	{
		if (!in_array($type, GuildWarPush::$ALL_TYPE)) 
		{
			throw new InterException('GuildWarLogic::push failed, invalid push type[%d], all valid type[%s]', $type, GuildWarPush::$ALL_TYPE);
		}
		
		try
		{
			if ($field == GuildWarField::INNER )
			{
				self::pushInner($type, $data);
			}
			else
			{
				self::pushCross($type, $data);
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('GuildWarLogic::push failed, field[%s], type[%d], data[%s], err msg[%s]', $field, $type, $data, $e->getMessage());
		}
	}
	
	public static function pushInner($type, $data)
	{
		Logger::trace('GuildWarLogic::pushInner begin...');
		
		if ($type == GuildWarPush::NOW_STATUS)
		{
			// 跨服军团战没有服内阶段， 无须在服内推
			Logger::fatal('GuildWarLogic::pushInner, not need push GuildWarPush::NOW_STATUS in inner machine');
		}
		else if ($type == GuildWarPush::NEW_REWARD)
		{
			$serverId = GuildWarUtil::getMinServerId();
			if (empty($data['arrRewardServerUid'][$serverId]))
			{
				Logger::warning('GuildWarLogic::pushInner, no uid to send when push new reward');
				return;
			}
			RPCContext::getInstance()->sendMsg($data['arrRewardServerUid'][$serverId], PushInterfaceDef::REWARD_NEW, array());
			Logger::trace('GuildWarLogic::pushInner, push new reward for serverId[%d]', $serverId);
		}
		else if ($type == GuildWarPush::NEW_MAIL)
		{
			$serverId = GuildWarUtil::getMinServerId();
			if (empty($data['arrRewardServerUid'][$serverId]))
			{
				Logger::warning('GuildWarLogic::pushInner, no uid to send when push new mail');
				return;
			}
			RPCContext::getInstance()->sendMsg($data['arrRewardServerUid'][$serverId], PushInterfaceDef::MAIL_CALLBACK, array());
			Logger::trace('GuildWarLogic::pushInner, push new mail for serverId[%d]', $serverId);
		}
		else
		{
			Logger::fatal('GuildWarLogic::pushInner, invalid type[%d]', $type);
		}
		
		Logger::trace('GuildWarLogic::pushInner end...');
	}
	
	public static function pushCross($type, $data)
	{
		Logger::trace('GuildWarLogic::pushCross begin...');
		
		// 获得数据
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		$session = $confObj->getSession();
		
		$arrTeamData = GuildWarUtil::getAllTeamData($session);
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		
		// 获得需要推送的组，默认是所有组
		$arrNeedPush = array_keys($arrTeamData);
		if (isset($data['needPushTeamArr']) 
			&& is_array($data['needPushTeamArr']))
		{
			$arrNeedPush = $data['needPushTeamArr'];
		}
		
		// 循环对组进行推送
		foreach ($arrTeamData as $teamId => $arrServer)
		{
			if (!in_array($teamId , $arrNeedPush))
			{
				continue;
			}
			
			if ($type == GuildWarPush::NOW_STATUS)
			{
				$teamObj = $procedureObj->getTeamObj($teamId);
				
				$curRound = $teamObj->getCurRound();
				$curStatus = $teamObj->getCurStatus();
				$curSubRound = $teamObj->getCurSubRound();
				$curSubStatus = $teamObj->getCurSubStatus();
			
				$arrMsgData = array
				(
						'callback' => array ('callbackName' => PushInterfaceDef::GUILDWAR_UPDATE),
						'err' => 'ok',
						'ret' => array
						(
								'round' => $curRound,
								'status' => $curStatus,
								'sub_round' => $curSubRound,
								'sub_status' => $curSubStatus,
						),
				);
			}
			else if ($type == GuildWarPush::NEW_REWARD)
			{
				$arrMsgData = array();
			}
			else if ($type == GuildWarPush::NEW_MAIL)
			{
				$arrMsgData = array();
			}
			else
			{
				Logger::fatal('GuildWarLogic::pushCross, invalid type[%d]', $type);
				return;
			}
			
			// 循环对这个组的每个服推送
			foreach ($arrServer as $serverId)
			{
				try 
				{
					$group = Util::getGroupByServerId($serverId);
					$proxy = new ServerProxy();
					$proxy->init($group, Util::genLogId());
				
					if ($type == GuildWarPush::NOW_STATUS)
					{
						$proxy->sendFilterMessage('arena', SPECIAL_ARENA_ID::GUILDWAR, $arrMsgData);
					}
					else if ($type == GuildWarPush::NEW_REWARD)
					{
						if (empty($data['arrRewardServerUid'][$serverId]))
						{
							Logger::warning('GuildWarLogic::pushCross, no uid to send when push new reward for server[%d]', $serverId);
						}
						else
						{
							$proxy->sendMessage($data['arrRewardServerUid'][$serverId], PushInterfaceDef::REWARD_NEW, array());
						}
					}
					else if ($type == GuildWarPush::NEW_MAIL)
					{
						if (empty($data['arrRewardServerUid'][$serverId]))
						{
							Logger::warning('GuildWarLogic::pushCross, no uid to send when push new mail for server[%d]', $serverId);
						}
						else
						{
							$proxy->sendMessage($data['arrRewardServerUid'][$serverId], PushInterfaceDef::MAIL_CALLBACK, array());
						}
					}
					else
					{
						Logger::fatal('GuildWarLogic::pushCross, invalid type[%d]', $type);
						return;
					}
					Logger::info('GuildWarLogic::pushCross for teamId[%d], type[%s], serverId[%d] success, arrMsgData[%s], group[%s]', $teamId, $type, $serverId, $arrMsgData, $group);
				}
				catch (Exception $e)
				{
					Logger::info('GuildWarLogic::pushCross for teamId[%d], type[%s], serverId[%d] failed, exception[%s]', $teamId, $type, $serverId, $e->getMessage());
				}
			}
			Logger::info('GuildWarLogic::pushCross done for teamId[%d], type[%s]', $teamId, $type);
		}
		Logger::trace('GuildWarLogic::pushCross end...');
	}
	
	public static function sendMailByMain($arrUid, $round, $finalRank, $isWin, $objGuildName, $objServerName)
	{
		foreach ($arrUid as $aUid)
		{
			MailTemplate::sendGuildWarResult($aUid, $round, $finalRank, $isWin, $objGuildName, $objServerName);
		}
	}
}

class GuildWarScriptLogic
{	
	/**
	 * 开启跨服军团战海选赛
	 * 
	 * @param bool $force
	 */
	public static function startOpenAudition($force = FALSE)
	{
		Logger::trace('GuildWarScriptLogic::startOpenAudition begin...');
		
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		
		// 检查session
		$session = $confObj->getSession();
		if (empty($session))
		{
			Logger::warning('GuildWarScriptLogic::startOpenAudition failed, not in any session');
			return;
		}
		
		// 检查是不是在海选阶段
		$curRound = $confObj->getCurRound();
		if ($curRound != GuildWarRound::AUDITION && !$force)
		{
			Logger::warning('GuildWarScriptLogic::startOpenAudition failed, not in openAudition round by conf.');
			return;
		}
		
		// 检查分组信息是否正确
		GuildWarUtil::checkTeamDistributionCross($session);

		// 每个组执行海选
		$arrTeamId = GuildWarUtil::getAllTeamId($session); 
		$teamCount = count($arrTeamId);
		Logger::trace('GuildWarScriptLogic::startOpenAudition all team id[%s]', $arrTeamId);
		
		if ($teamCount > 1 && GuildWarConf::PROCESS_TEAM_NUM > 0)
		{
			$chunkSize = ceil($teamCount / GuildWarConf::PROCESS_TEAM_NUM);
			$arrBatch = array_chunk($arrTeamId, $chunkSize);
			$eg = new ExecutionGroup();
			foreach($arrBatch as $batch)
			{
				$eg->addExecution('GuildWarScriptLogic::doOpenAudition', array($session, $batch, $force));
				Logger::trace('GuildWarScriptLogic::startOpenAudition fork process for batch[%s]', $batch);
			}
			$ret = $eg->execute();
				
			if(!empty($ret))
			{
				Logger::fatal('GuildWarScriptLogic::startOpenAudition, there are some teams audition faield');
				foreach($ret as $value)
				{
					Logger::fatal('batch:%s', $value);
				}
			}
		}
		else
		{
			self::doOpenAudition($session, $arrTeamId, $force);
			Logger::trace('GuildWarScriptLogic::startOpenAudition curr process for all team id[%s]', $arrTeamId);
		}
		
		// 推送状态
		GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NOW_STATUS);
		
		Logger::trace("GuildWarScriptLogic::startOpenAudition end...");
	}
	
	/**
	 * 一个单独的进程，用于执行若干组整个海选赛流程
	 * 
	 * @param int $session
	 * @param int $arrTeamId
	 */
	public static function doOpenAudition($session, $arrTeamId, $force = FALSE)
	{
		Logger::trace('GuildWarScriptLogic::doOpenAudition begin...');
		
		
		if (defined('GuildWarConf::RAND_SLEEP_WHEN_BEGIN_AUDITION') && GuildWarConf::RAND_SLEEP_WHEN_BEGIN_AUDITION === TRUE) 
		{
			// 随机sleep一段时间后，再启动，最大sleep时间为一轮海选赛的配置时间
			$gapTime = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getAuditionGap();
			$randSleepTime = mt_rand(0, $gapTime);
			Logger::info('GuildWarScriptLogic::doOpenAudition will begin after rand sleep[%d]', $randSleepTime);
			sleep($randSleepTime);
		}
		
		// 状态检查
		$prodedureObj = GuildWarProcedureObj::getInstance($session);
		$arrTeamData = array();
		foreach ($arrTeamId as $teamId)
		{
			$teamObj = $prodedureObj->getTeamObj($teamId);
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
			
			// 这个组是否处于海选阶段
			if ($curRound != GuildWarRound::AUDITION) 
			{
				Logger::fatal('GuildWarScriptLogic::doOpenAudition failed. teamId[%d], cur round[%d], status[%d]', $teamId, $curRound, $curStatus);
				continue;
			}
			
			// 这个组是否已经海选完啦
			if ($curStatus == GuildWarStatus::DONE)
			{
				Logger::info('GuildWarScriptLogic::doOpenAudition audition already done. teamId[%d], cur round[%d], status[%d]', $teamId, $curRound, $curStatus);
				continue;
			}
			
			// 这个组是否已经可以开战
			if ($curStatus != GuildWarStatus::FIGHTING) 
			{
				Logger::fatal('GuildWarScriptLogic::doOpenAudition audition info not prepared. teamId[%d], curr round[%d], status:%d', $teamId, $curRound, $curStatus);
				continue;
			}
			
			$arrTeamData[$teamId] = GuildWarLogic::getAuditionGuildInfo($session, $teamId, GuildWarConfObj::getInstance(GuildWarField::CROSS)->getFailNum(), GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSignUpStartTime());
		}
		
		// 判断数据是否为空
		if (empty($arrTeamData)) 
		{
			Logger::info('GuildWarScriptLogic::doOpenAudition no need run, empty team data');
			return;
		}
		Logger::trace('GuildWarScriptLogic::doOpenAudition need run, arrTeamData[%s]', $arrTeamData);
		
		// 获取海选每轮休息时间和最大失败次数
		$gapTime = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getAuditionGap();
		$failNum = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getFailNum();
		
		// 如果是强制的，就不让多余的sleep啦
		if ($force) 
		{
			$gapTime = 0;
		}
		
		// 开始海选
		$teamCount = count($arrTeamData);
		$arrFinishTeamId = array();
		while (TRUE)
		{
			$startTime = time();
			
			foreach ($arrTeamId as $teamId)
			{
				// 如果已经执行完海选，则不需要再执行
				if (isset($arrFinishTeamId[$teamId]) && $arrFinishTeamId[$teamId])
				{
					Logger::info('GuildWarScriptLogic::doOpenAudition audition of teamId[%d] already done', $teamId);
					continue;
				}
				
				// 判断是否有这个组的军团数据
				if (!isset($arrTeamData[$teamId])) 
				{
					Logger::warning('GuildWarScriptLogic::doOpenAudition audition of teamId[%d] no guild info', $teamId);
					continue;
				}
				
				// 执行一轮海选
				//=======================================================================================
				Logger::trace('GuildWarScriptLogic::doOpenAudition, before doOnceOpenAudition teamId[%d], teamData[%s]', $teamId, $arrTeamData[$teamId]);
				$arrTeamData[$teamId] = self::doOnceOpenAudition($session, $teamId, $arrTeamData[$teamId], $failNum);
				Logger::trace('GuildWarScriptLogic::doOpenAudition, after doOnceOpenAudition teamId[%d], teamData[%s]', $teamId, $arrTeamData[$teamId]);
				//=======================================================================================
				
				// 如果海选完了，更新状态表等
				if (count($arrTeamData[$teamId]) <= GuildWarConf::MAX_JOIN_NUM)
				{
					//更新final_rank到数据库
					$arrGuildInfo = $arrTeamData[$teamId];
					shuffle($arrGuildInfo);
					$pos = 0;
					foreach ($arrGuildInfo as $aGuildInfo)
					{
						$guildWarServerObj = GuildWarServerObj::getInstanceFromInfo($aGuildInfo);
						$guildWarServerObj->promotion(GuildWarConf::$all_rank[GuildWarRound::AUDITION]);
						$guildWarServerObj->setPos(++$pos);
						if ($aGuildInfo[GuildWarServerField::TBL_FIELD_FIGHT_FORCE] != 0) 
						{
							$guildWarServerObj->setFightForce($aGuildInfo[GuildWarServerField::TBL_FIELD_FIGHT_FORCE]);
						}
						else 
						{
							$guildWarServerObj->refreshFightForce();
						}
						$guildWarServerObj->update();
					}
					
					// 更新状态表
					$teamObj = $prodedureObj->getTeamObj($teamId);
					$teamRoundObj = $teamObj->getTeamRound(GuildWarRound::AUDITION);
					$teamRoundObj->setStatus(GuildWarStatus::DONE);
					$teamRoundObj->update();
					
					// 更新记录
					$arrFinishTeamId[$teamId] = TRUE;
				}
			}
			
			// 检查是否结束
			if (count($arrFinishTeamId) >= $teamCount)
			{
				Logger::info('GuildWarScriptLogic::startOpenAudition all team audition done.');
				break;
			}
		
			// 计算实际执行时间，并且进行sleep，等待下一轮海选赛的开始 ， 打卦重打不sleep
			$executeTime = time() - $startTime;
			$sleepTime = $gapTime - $executeTime;
			if($sleepTime <= 0)
			{
				$sleepTime = 0;
			}
			Logger::info("=======================");
			Logger::info("GuildWarScriptLogic::doOpenAudition once execute time[%d] sleep time[%d].", $executeTime, $sleepTime);
			Logger::info("=======================");
			sleep($sleepTime);
		}
		
		Logger::trace("GuildWarScriptLogic::startOpenAudition session[%d] arrTeamId[%s] end...", $session, $arrTeamId);
	}
	
	/**
	 * 执行一轮海选赛
	 * 
	 * @param int $session
	 * @param int $teamId
	 * @param array $arrGuildInfo
	 * @param int $failNum
	 * @return array
	 */
	public static function doOnceOpenAudition($session, $teamId, $arrGuildInfo, $failNum)
	{
		Logger::trace('GuildWarScriptLogic::doOnceOpenAudition begin...');

		// 获得军团数量，并且扰乱
		$guildNum = count($arrGuildInfo);
		shuffle($arrGuildInfo);
		
		// 对所有军团执行一场1对1的PK
		$curIndex = 0;
		while ($guildNum > GuildWarConf::MAX_JOIN_NUM)
		{
			// 如果发现没军团信息，就退出循环
			if (empty($arrGuildInfo[$curIndex]) || empty($arrGuildInfo[$curIndex + 1]))
			{
				break;
			}
			Logger::trace('GuildWarScriptLogic::doOnceOpenAudition, guildA index[%d], guildA info[%s], guildB index[%d], guildB info[%s]', $curIndex, $arrGuildInfo[$curIndex], $curIndex + 1, $arrGuildInfo[$curIndex + 1]);
			
			/**
			 * 这里之所以不从info获取obj，是因为海选赛各轮之间军团会调整出战成员，必须实时从数据库去参战人员数据，而且要releaseInstance，防止缓存。
			 */
			// 获得第一个军团的对象 
			//$curGuildWarServerObj = GuildWarServerObj::getInstanceFromInfo($arrGuildInfo[$curIndex]);
			GuildWarServerObj::releaseInstance($session, $arrGuildInfo[$curIndex][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $arrGuildInfo[$curIndex][GuildWarServerField::TBL_FIELD_GUILD_ID]);
			$curGuildWarServerObj = GuildWarServerObj::getInstance($session, $arrGuildInfo[$curIndex][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $arrGuildInfo[$curIndex][GuildWarServerField::TBL_FIELD_GUILD_ID]);
			
			// 获取第一个军团战斗信息，也就是这个军团所有参战成员的战斗信息
			$curGuildFightFmt = $curGuildWarServerObj->getAuditionFightFormation();
			if (FALSE == $curGuildFightFmt || empty($curGuildFightFmt['members']))
			{
				throw new InterException('GuildWarScriptLogic::doOnceOpenAudition serverId[%d] guildId[%d] have no candidates', $curGuildWarServerObj->getServerId(), $curGuildWarServerObj->getGuildId());
			}
			Logger::trace('GuildWarScriptLogic::doOnceOpenAudition, guild A fight fmt[%s]', $curGuildFightFmt);
			
			/**
			 * 这里之所以不从info获取obj，是因为海选赛各轮之间军团会调整出战成员，必须实时从数据库去参战人员数据，而且要releaseInstance，防止缓存。
			 */
			// 获得第二个军团的对象
			//$objGuildWarServerObj = GuildWarServerObj::getInstanceFromInfo($arrGuildInfo[$curIndex + 1]);
			GuildWarServerObj::releaseInstance($session, $arrGuildInfo[$curIndex + 1][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $arrGuildInfo[$curIndex + 1][GuildWarServerField::TBL_FIELD_GUILD_ID]);
			$objGuildWarServerObj = GuildWarServerObj::getInstance($session, $arrGuildInfo[$curIndex + 1][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID], $arrGuildInfo[$curIndex + 1][GuildWarServerField::TBL_FIELD_GUILD_ID]);
			
			// 获取第二个军团战斗信息，也就是这个军团所有参战成员的战斗信息
			$objGuildFightFmt = $objGuildWarServerObj->getAuditionFightFormation();
			if (FALSE == $objGuildFightFmt || empty($objGuildFightFmt['members']))
			{
				throw new InterException('GuildWarScriptLogic::doOnceOpenAudition serverId[%d] guildId[%d] have no candidates', $objGuildWarServerObj->getServerId(), $objGuildWarServerObj->getGuildId());
			}
			Logger::trace('GuildWarScriptLogic::doOnceOpenAudition, guild B fight fmt[%s]', $objGuildFightFmt);
				
			// 两个军团开战
			$ret = GuildWarLogic::doFight($curGuildFightFmt, $objGuildFightFmt);
			Logger::trace('GuildWarScriptLogic::doOnceOpenAudition, guild A fight guild B result[%s]', $ret);
				
			// 获取失败者和胜利者对象
			$loseIndex = 0;
			$winIndex = 0;
			if ($ret['loser']['guild_id'] == $curGuildFightFmt['guild_id'] 
				&& $ret['loser']['server_id'] == $curGuildFightFmt['server_id'])
			{
				$loseIndex = $curIndex;
				$winIndex = $curIndex + 1;
				$loseObj = &$curGuildWarServerObj;
				$winObj = &$objGuildWarServerObj;
			}
			else
			{
				$loseIndex = $curIndex + 1;
				$winIndex = $curIndex;
				$loseObj = &$objGuildWarServerObj;
				$winObj = &$curGuildWarServerObj;
			}
			
			// 失败者失败次数加1
			$loseObj->increLoseTimes();
			
			// 生成战报
			$auditionReplay = array();
			$auditionReplay['replay_id'] = $ret['replay'];
			$auditionReplay['result'] = ($loseIndex == $curIndex ? 0 : 2);
			$auditionReplay['attacker'] = $curGuildWarServerObj->getBasicInfo();
			$auditionReplay['defender'] = $objGuildWarServerObj->getBasicInfo();
				
			// 更新两者的战报信息，并且同步到数据库
			$loseObj->addAuditionReplay($auditionReplay);
			$winObj->addAuditionReplay($auditionReplay);
			$loseObj->update();
			$winObj->update();
			
			$arrGuildInfo[$winIndex] = $winObj->getServerInfo();
			$arrGuildInfo[$loseIndex] = $loseObj->getServerInfo();

			// 查看执行完这一轮之后是否可以截止了， 如果军团累积失败次数超限，就将被淘汰出局，直至决出最后16强。
			if ($loseObj->getLoseTimes() >= $failNum) 
			{
				--$guildNum;
				unset($arrGuildInfo[$loseIndex]);
			}
			
			// 计数
			$curIndex += 2;
		}
		
		Logger::trace('GuildWarScriptLogic::doOnceOpenAudition ret[%s] end...', $arrGuildInfo);
		return $arrGuildInfo;
	}
	
	/**
	 * 开启一轮晋级赛
	 * 
	 * @param bool $force
	 */
	public static function startFinals($round, $force = FALSE)
	{
		Logger::trace('GuildWarScriptLogic::startFinals begin...');
	
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		
		// 检查session
		$session = $confObj->getSession();
		if (empty($session))
		{
			Logger::warning('GuildWarScriptLogic::startFinals failed, not in any session');
			return;
		}
		
		// 检查round
		$curRound = $confObj->getCurRound();
		if ($curRound != $round && !$force)
		{
			Logger::warning('GuildWarScriptLogic::startFinals failed, diff, cur round[%d], conf round[%d].', $round, $curRound);
			return;
		}
		
		// 检查是否是有效的晋级赛round
		if (!in_array($round, GuildWarRound::$FinalsRound))
		{
			Logger::fatal('GuildWarScriptLogic::startFinals failed, not valid finals round[%d], valid round[%s]', $round, GuildWarRound::$FinalsRound);
			return;
		}
	
		// 获取所有teamId
		$arrTeamId = GuildWarUtil::getAllTeamId($session);
		$teamCount = count($arrTeamId);
		if ($teamCount > 1 && GuildWarConf::PROCESS_TEAM_NUM > 0)
		{
			$chunkSize = ceil($teamCount / GuildWarConf::PROCESS_TEAM_NUM);
			$arrBatch = array_chunk($arrTeamId, $chunkSize);
			$eg = new ExecutionGroup();
			foreach($arrBatch as $batch)
			{
				$eg->addExecution('GuildWarScriptLogic::doFinals', array($session, $batch, $round, $force));
			}
			$ret = $eg->execute();
		
			if(!empty($ret))
			{
				Logger::fatal('GuildWarScriptLogic::startFinals, there are some teams finals faield, round[%d]', $round);
				foreach($ret as $value)
				{
					Logger::fatal('batch:%s', $value);
				}
			}
		}
		else
		{
			self::doFinals($session, $arrTeamId, $round, $force);
		}
		
		Logger::trace("GuildWarScriptLogic::startFinals end...");
	}
	
	public static function doFinals($session, $arrTeamId, $round, $force = FALSE)
	{
		Logger::trace("GuildWarScriptLogic::doFinals begin...");
		
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS); 
		$arrRealTeamId = array();
		
		// 根据状态表获取真正需要处理的teamId
		foreach ($arrTeamId as $aTeamId)
		{
			$teamObj = $procedureObj->getTeamObj($aTeamId);
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
			
			// 是否处于这个阶段
			if ($curRound != $round)
			{
				Logger::fatal('GuildWarScriptLogic::doFinals, not in run round[%d], teamId[%d], curRound[%d], curStatus[%d].', $round, $aTeamId, $curRound, $curStatus);
				continue;
			}
			
			// 是否已经打完
			if ($curStatus >= GuildWarStatus::FIGHTEND)
			{
				Logger::info('GuildWarScriptLogic::doFinals, run round[%d] already done, teamId[%d], curRound[%d], curStatus[%d].', $round, $aTeamId, $curRound, $curStatus);
				continue;
			}
			
			$arrRealTeamId[] = $aTeamId;
		}
		
		if (empty($arrRealTeamId))
		{
			Logger::info('GuildWarScriptLogic::doFinals, all team finals done, no need run, round[%d]', $round);
			return;
		}

		Logger::info('GuildWarScriptLogic::doFinals, need run finals, round[%d], all team id[%s]', $round, $arrRealTeamId);
		
		// 获取配置：小组数和小组之间的间隔
		$subRoundNum = $confObj->getSubRoundCount();
		$gapTime = $confObj->getFinalsGap();
		
		// 如果是force，小组之间也不要sleep啦
		if ($force) 
		{
			$gapTime = 0;
		}
		
		// 以小轮循环
		$startTime = time();
		for ($subRound = 1; $subRound <= $subRoundNum; ++$subRound)
		{
			// 这个小组比赛开始时间
			
			
			// 对每个组执行一次小组比赛
			foreach ($arrRealTeamId as $aTeamId)
			{
				$teamObj = $procedureObj->getTeamObj($aTeamId);
				$teamRoundObj = $teamObj->getTeamRound($round);
				$curSubRound = $teamRoundObj->getSubRound();
				$curSubStatus = $teamRoundObj->getSubStatus();
				
				// 已经到下一小轮啦
				if ($curSubRound > $subRound)
				{
					Logger::info('GuildWarScriptLogic::doFinals, already run sub round, team[%d], curSubRound[%d], runSubRound[%d]', $aTeamId, $curSubRound, $subRound);
					continue;
				}
				
				// 这个小轮已经打完啦
				if ($curSubStatus == GuildWarSubStatus::FIGHTEND) 
				{
					Logger::info('GuildWarScriptLogic::doFinals, sub round fight end, team[%d], curSubRound[%d], curSubStatus[%d], runSubRound[%d]', $aTeamId, $curSubRound, $curSubStatus, $subRound);
					continue;
				}

				// 对一个组执行一次小轮比赛，subRound从1开始
				//=======================================================================================
				Logger::trace('GuildWarScriptLogic::doFinals, before doOnceFinals teamId[%d], round[%d], subRound[%d]', $aTeamId, $round, $subRound);
				self::doOnceFinals($aTeamId, $session, $round, $subRound);
				Logger::trace('GuildWarScriptLogic::doFinals, after doOnceFinals teamId[%d], round[%d], subRound[%d]', $aTeamId, $round, $subRound);
				//=======================================================================================
				
				// 标记状态
				$teamRoundObj->setSubStatus(GuildWarSubStatus::FIGHTEND);
				if ($subRound == $subRoundNum)
				{
					$teamRoundObj->setStatus(GuildWarStatus::FIGHTEND);
				}
				$teamRoundObj->update();
			}
			
			GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NOW_STATUS, array('needPushTeamArr' => $arrRealTeamId));
			
			// 小轮之间有间隔时间，sleep
			$executeTime = time() - $startTime;
			$sleepTime = $confObj->getSubRoundStartTime($round, $subRound) + $gapTime - time();
			if($sleepTime <= 0)
			{
				$sleepTime = 0;
			}
			if ($subRound ==  $subRoundNum)
			{
				$sleepTime = 0;
			}
			Logger::info("=======================");
			Logger::info('GuildWarScriptLogic::doFinals, sub round fight all done, subRound[%d], executeTime[%d], sleepTime[%d].', $subRound, $executeTime, $sleepTime);
			Logger::info("=======================");
			sleep($sleepTime);
		}
	}
	
	/**
	 * 对一组执行一轮晋级赛
	 *
	 * @param int $teamId						分组Id
	 * @param int $session						第几届
	 * @param int $round						第几轮
	 * @param int $curRound						今天打到了第几回合
	 */
	public static function doOnceFinals($teamId, $session, $round, $subRound)
	{
		Logger::trace('GuildWarScriptLogic::doOnceFinals param[teamId:%d, session:%d, round:%d, subRound:%d] begin...', $teamId, $session, $round, $subRound);
		
		// 获取晋级赛军团信息
		$arrGuildInfo = GuildWarLogic::getFinalsGuildInfo($session, $teamId);		
		$rank = GuildWarConf::$round_rank[$round];
		$nextRank = GuildWarConf::$next_rank[$rank];
		$step = GuildWarConf::$step[$round];
		for ($i = 1; $i <= GuildWarConf::MAX_JOIN_NUM; $i += $step)
		{
			/**********************************************************************************************************
			 * 获取对战双方
			**********************************************************************************************************/
			// 获取两个对战军团信息
			list($figherObjA, $figherObjB) = GuildWarLogic::getFightPair($arrGuildInfo, $i, $step, $rank, $nextRank);
			$figherObjAInfo = ($figherObjA instanceof GuildWarServerObj ? $figherObjA->getServerInfo() : array());
			$figherObjBInfo = ($figherObjB instanceof GuildWarServerObj ? $figherObjB->getServerInfo() : array());
			Logger::trace('GuildWarScriptLogic::doOnceFinals, after getFightPair, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
			
			// 都轮空了
			if (empty($figherObjA) && empty($figherObjB))
			{
				Logger::trace('GuildWarScriptLogic::doOnceFinals, both empty.');
				continue;
			}
			
			// 如果已经决出来了
			if (($figherObjA instanceof GuildWarServerObj && $figherObjA->getFinalRank() == $nextRank) 
				|| ($figherObjB instanceof GuildWarServerObj && $figherObjB->getFinalRank() == $nextRank))
			{
				Logger::warning('GuildWarScriptLogic::doOnceFinals, one of fighter already direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
				continue;
			}
			
			// 如果有一方轮空，也不需要进行比赛
			if (empty($figherObjA))
			{
				$figherObjB->promotion($nextRank);
				if ($nextRank == GuildWarConf::GUILD_WAR_RANK_1)
				{
					$presidentInfo = GuildWarLogic::getChampionPresidentInfo($figherObjB->getServerId(), $figherObjB->getGuildId());
					if (empty($presidentInfo))
					{
						Logger::warning('GuildWarScriptLogic::doOnceFinals, can not get champion president info when direct promotion, serverId[%d], guildId[%d]', $figherObjB->getServerId(), $figherObjB->getGuildId());
					}
					$figherObjB->setPresidentInfo($presidentInfo);
				}
				$figherObjB->update();
				
				Logger::trace('GuildWarScriptLogic::doOnceFinals, one of fighter direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
				continue;
			}
			
			// 如果有一方轮空，也不需要进行比赛
			if (empty($figherObjB))
			{
				$figherObjA->promotion($nextRank);
				if ($nextRank == GuildWarConf::GUILD_WAR_RANK_1)
				{
					$presidentInfo = GuildWarLogic::getChampionPresidentInfo($figherObjA->getServerId(), $figherObjA->getGuildId());
					if (empty($presidentInfo))
					{
						Logger::warning('GuildWarScriptLogic::doOnceFinals, can not get champion president info when direct promotion, serverId[%d], guildId[%d]', $figherObjA->getServerId(), $figherObjA->getGuildId());
					}
					$figherObjA->setPresidentInfo($presidentInfo);
				}
				$figherObjA->update();

				Logger::trace('GuildWarScriptLogic::doOnceFinals, one of fighter direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
				continue;
			}
			
			// 获取双方战斗数据
			$fighterAFmt = $figherObjA->getFinalsFightFormation($round, $subRound);
			Logger::trace('GuildWarScriptLogic::doOnceFinals, guild A fight fmt[%s]', $fighterAFmt);
			
			$fighterBFmt = $figherObjB->getFinalsFightFormation($round, $subRound);
			Logger::trace('GuildWarScriptLogic::doOnceFinals, guild B fight fmt[%s]', $fighterBFmt);
			
			$needFight = TRUE;
			
			/**
			 * 只可能有一方的members为空，既没有人可以出战，这种情况是因为上一小组赢啦，连胜次数到啦，也要下场。这一小组刚好也没有人可以出战啦
			 * 不可能双方members都为空，如果双方members都为空，上一小组，就肯定分出胜负啦
			 */
	
			// 如果发现获取到的是空，那么就需要作出特殊处理
			if (empty($fighterAFmt['members']))
			{
				$result = 0;
				$loseGuildObj = &$figherObjA;
				$winGuildObj = &$figherObjB;				
				$needFight = FALSE;				
				if (!$loseGuildObj->isTotalLose()) 
				{
					throw new InterException('GuildWarScriptLogic::doOnceFinals, fighterA empty member but not total lose, fighterB direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
				}
				Logger::trace('GuildWarScriptLogic::doOnceFinals, fighterA empty member, fighterB direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
			}
			
			// 如果发现获取到的是空，那么就需要作出特殊处理
			if (empty($fighterBFmt['members']))
			{
				$result = 2;
				$loseGuildObj = &$figherObjB;
				$winGuildObj = &$figherObjA;				
				$needFight = FALSE;
				if (!$loseGuildObj->isTotalLose())
				{
					throw new InterException('GuildWarScriptLogic::doOnceFinals, fighterB empty member but not total lose, fighterA direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
				}
				Logger::trace('GuildWarScriptLogic::doOnceFinals, fighterB empty member, fighterA direct promotion, teamId[%d], fighterA[%s], fighterB[%s].', $teamId, $figherObjAInfo, $figherObjBInfo);
			}
	
			/**********************************************************************************************************
			 * 开战并查看一下战斗结果
			**********************************************************************************************************/
			if ($needFight) 
			{
				$ret = GuildWarLogic::doFight($fighterAFmt, $fighterBFmt);
				Logger::trace('GuildWarScriptLogic::doOnceFinals, teamId[%d], guild A fight guild B result[%s]', $teamId, $ret);
					
				if ($ret['loser']['guild_id'] == $figherObjA->getGuildId()
				&& $ret['loser']['server_id'] == $figherObjA->getServerId())
				{
					$result = 0;
				
					$loseGuildObj = &$figherObjA;
					$winGuildObj = &$figherObjB;
				
					// A这一队人马都死光啦
					$figherObjA->allFightersLose();
				
					// B赢啦，要更血量， 有些玩家连胜次数到啦，也要下场
					$figherObjB->updateFighters($fighterBFmt['members'], $ret['mapSurvivorHpInfo'], $ret['arrLeftUid'], $ret['mapUidWin'], GuildWarConf::OFFSET_TWO);
					Logger::trace('GuildWarScriptLogic::doOnceFinals, teamId[%d], guild A fight guild B, lose, update guild B', $teamId);
				}
				else
				{
					$result = 2;
				
					$loseGuildObj = &$figherObjB;
					$winGuildObj = &$figherObjA;
				
					// B这一队人马都死光啦
					$figherObjB->allFightersLose();
				
					// A赢啦，要更血量， 有些玩家连胜次数到啦，也要下场
					$figherObjA->updateFighters($fighterAFmt['members'], $ret['mapSurvivorHpInfo'], $ret['arrLeftUid'], $ret['mapUidWin'], GuildWarConf::OFFSET_ONE);
					Logger::trace('GuildWarScriptLogic::doOnceFinals, teamId[%d], guild A fight guild B, win, update guild A', $teamId);
				}
					
					
				// 生成战报
				$finalsReplay = array();
				$finalsReplay['attacker'] = $figherObjA->getBasicInfo();
				$finalsReplay['defender'] = $figherObjB->getBasicInfo();
					
				// replay_id
				$replayId = $ret['replay'];
				
				// 添加一个晋级赛战报
				$figherObjA->addFinalsReplay($round, $replayId, $finalsReplay);
				$figherObjB->addFinalsReplay($round, $replayId, $finalsReplay);
					
				Logger::trace('GuildWarScriptLogic::doOnceFinals, teamId[%d], round[%d], guild A fight guild B, replay_id[%s], replay_info[%s]', $teamId, $round, $replayId, $finalsReplay);
				
			}
			
			/**********************************************************************************************************
			 * 打完了，更新数据库
			**********************************************************************************************************/
			// 计算是否完结 —— 如果全军覆灭就是完结了
			if ($loseGuildObj->isTotalLose()) 
			{
				Logger::trace('GuildWarScriptLogic::doOnceFinals, total lose, teamId[%d], [serverId:%d,guildId:%d] lose to [serverId:%d,guildId:%d]', $teamId, $loseGuildObj->getServerId(), $loseGuildObj->getGuildId(), $winGuildObj->getServerId(), $winGuildObj->getGuildId());
				
				if (!$needFight) 
				{
					$arrFighterInfo = $winGuildObj->getFinalsFighters($subRound);
					$arrEstimateFighter = Util::arrayExtract($arrFighterInfo, GuildWarUserField::TBL_FIELD_UID);
					$winGuildObj->setFinalsLeftUser($round, $subRound, $arrEstimateFighter);
					$loseGuildObj->setFinalsLeftUser($round, $subRound, $arrEstimateFighter);
				}
				else 
				{
					$arrLeftUserAfterFight = $ret['arrLeftUid'];
					foreach ($arrLeftUserAfterFight as $index => $aUid)
					{
						$arrLeftUserAfterFight[$index] = GuildWarUtil::reChangeID($aUid);
					}
					$winGuildObj->setFinalsLeftUser($round, $subRound, $arrLeftUserAfterFight);
					$loseGuildObj->setFinalsLeftUser($round, $subRound, $arrLeftUserAfterFight);
				}

				for ($magicIndex = $subRound + 1; $magicIndex <= GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSubRoundCount(); ++$magicIndex)
				{
					$arrEstimateFighter = $winGuildObj->getEstimateFinalFighters($magicIndex);
					$winGuildObj->setFinalsLeftUser($round, $magicIndex, $arrEstimateFighter);
					$loseGuildObj->setFinalsLeftUser($round, $magicIndex, $arrEstimateFighter);
				}
				
				// 赢了的军团就清空，要不下一轮开始前调getReplay信息的时候会有问题
				if ($round < GuildWarRound::ADVANCED_2) 
				{
					$winGuildObj->clearMemberInfo();
				}

				// 刷新军团战斗力
				$winGuildObj->refreshFightForce();
				
				// 晋级吧
				$winGuildObj->promotion($nextRank);
				
				// 设置战报中的胜者
				$winGuildObj->setFinalsReplayWinner($round, $result);
				$loseGuildObj->setFinalsReplayWinner($round, $result);
				
				// 胜者和败者都得发个邮件，现在不发战斗结果邮件啦，注释点
				//$winGuildObj->sendMail($round, TRUE, $loseGuildObj->getGuildName(), $loseGuildObj->getServerName());
				//$loseGuildObj->sendMail($round, FALSE, $winGuildObj->getGuildName(), $winGuildObj->getServerName());
				
				// 如果是决出冠军，需要拉出军团长信息让别人膜拜
				if ($nextRank == GuildWarConf::GUILD_WAR_RANK_1) 
				{
					$presidentInfo = GuildWarLogic::getChampionPresidentInfo($winGuildObj->getServerId(), $winGuildObj->getGuildId());
					if (empty($presidentInfo)) 
					{
						Logger::warning('GuildWarScriptLogic::doOnceFinals, can not get champion president info, serverId[%d], guildId[%d]', $winGuildObj->getServerId(), $winGuildObj->getGuildId());
					}
					$winGuildObj->setPresidentInfo($presidentInfo);
				}
			}
			
			$winGuildObj->update();
			$loseGuildObj->update();
		}
		
		Logger::trace('GuildWarScriptLogic::doOnceFinals end...');
	}
	
	public static function sendCheerReward()
	{
		Logger::trace('GuildWarScriptLogic::sendCheerReward begin...');
		
		// 检查session
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new FakeException('GuildWarScriptLogic::sendCheerReward failed, not in any session.');
		}
		
		// 检查team
		$serverId = GuildWarUtil::getMinServerId();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		if (empty($teamId))
		{
			throw new FakeException('GuildWarScriptLogic::sendCheerReward failed, not in any team, serverId[%d].', $serverId);
		}
		
		// 检查轮次
		$confRound = $confObj->getCurRound();
		if (!in_array($confRound, GuildWarRound::$FinalsRound)) 
		{
			Logger::warning('GuildWarScriptLogic::sendCheerReward failed, not in any round of session[%d] currTime[%s] or not final round[%d].', $session, strftime('%Y%m%d-%H%M%S', Util::getTime()), $confRound);
			return;
		}
		
		// 获取状态控制表
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		// 检查是否在需要发送助威奖的阶段
		if (!in_array($curRound, GuildWarRound::$FinalsRound))
		{
			Logger::warning('GuildWarScriptLogic::sendCheerReward failed, cur round[%d] is not finals round.', $curRound);
			return;
		}
		
		// 检查状态是否正常
		if ($curStatus != GuildWarStatus::FIGHTEND)
		{
			Logger::warning('GuildWarScriptLogic::sendCheerReward failed, cur round[%d], status[%d] != FIGHTEND.', $curRound, $curStatus);
			return;
		}
		
		// 检查配置的round和状态表中的round是否一致
		if ($confRound != $curRound) 
		{
			Logger::warning('GuildWarScriptLogic::sendCheerReward failed, cur round[%d], conf round[%d], different.', $curRound, $confRound);
			return;
		}
		
		// 获取本轮开始时间和本轮赢了的军团名次
		$startTime = $confObj->getRoundStartTime($curRound);
		$lastRoundEndTime = $confObj->getPreRoundEndTime($curRound);
		$winRank = GuildWarConf::$all_rank[$curRound];
	
		// 避免并发，这里进行随机
		//sleep(rand(1, 60));

		// 获取这一轮赢了的军团
		$arrField = array
		(
				GuildWarServerField::TBL_FIELD_GUILD_ID,
				GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID,
		);
		$arrWinGuild = GuildWarDao::selectFinalsGuildInfoByRank($arrField, $session, $teamId, $confObj->getFailNum(), $confObj->getSignUpStartTime(), $winRank);
		if(empty($arrWinGuild))
		{
			Logger::warning('GuildWarScriptLogic::sendCheerReward failed, the winner guild is empty, winRank[%d], session[%d], teamId[%d].', $winRank, $session, $teamId);
			return;
		}
		Logger::trace('GuildWarScriptLogic::sendCheerReward, the winner guild[%s], winRank[%d], session[%d], teamId[%d].', $arrWinGuild, $winRank, $session, $teamId);
		
		// 获取这一轮所有助威的玩家信息
		$arrField = array
		(
				GuildWarUserField::TBL_FIELD_UID,
				GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID,
				GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID,
		);
		$arrCheerUserInfo = GuildWarDao::getAllCheerUserInfo($arrField, $curRound, $lastRoundEndTime);
		Logger::trace('GuildWarScriptLogic::sendCheerReward, all cheer user[%s] in round[%d], lastRoundEnd[%s]', $arrCheerUserInfo, $curRound, strftime('%Y%m%d-%H%M%S', $lastRoundEndTime));
		
		// 获取奖励内容
		$rewardArr = $confObj->getCheerPrize($curRound);
		Logger::trace('GuildWarScriptLogic::sendCheerReward, round[%d] cheer reward arr[%s]', $curRound, $rewardArr);
		
		// 奖励和邮件集中推送
		RewardCfg::$NO_CALLBACK = TRUE;
		MailConf::$NO_CALLBACK = TRUE;
		
		// 发放助威奖励
		$curTime = Util::getTime();
		$arrRewardUid = array();
		foreach ($arrCheerUserInfo as $aUid => $aCheerInfo)
		{
			$aCheerGuildId = $aCheerInfo[GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID];
			$aCheerServerId = $aCheerInfo[GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID];
			Logger::trace('GuildWarScriptLogic::sendCheerReward, round[%d] deal uid[%d]', $curRound, $aUid);
			
			try
			{
				// 将本轮助威信息置空
				$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $aUid);
				$guildWarUserObj->cheerRewardEnd();
				
				// 判断玩家助威的军团是否晋级
				$shouldReward = FALSE;
				foreach ($arrWinGuild as $aWinGuild)
				{
					if ($aWinGuild[GuildWarServerField::TBL_FIELD_GUILD_ID] == $aCheerGuildId
						&& $aWinGuild[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID] == $aCheerServerId) 
					{
						$shouldReward = TRUE;
						break;
					}
				}
				
				// 如果助威的军团晋级啦，发助威奖到奖励中心，发邮件
				if ($shouldReward) 
				{
					$guildWarUserObj->setCheerRewardTime($curRound, $curTime);
					RewardUtil::reward3DtoCenter($aUid, array($rewardArr), RewardSource::GUILDWAR_SUPPORT, array('round' => $curRound));
					MailTemplate::sendGuildWarSupportReward($aUid, $curRound, $rewardArr);
					$arrRewardUid[] = $aUid;
					Logger::info('GuildWarScriptLogic::sendCheerReward, user[%d] curRound[%d] cheerGuildId[%d] cheerServerId[%d] get cheer reward.', $aUid, $curRound, $aCheerGuildId, $aCheerServerId);
				}
				else 
				{
					$guildWarUserObj->setCheerRewardTime($curRound, -1);
					Logger::info('GuildWarScriptLogic::sendCheerReward, user[%d] curRound[%d] cheerGuildId[%d] cheerServerId[%d] no cheer reward.', $aUid, $curRound, $aCheerGuildId, $aCheerServerId);
				}
				
				// 同步到数据库
				$guildWarUserObj->update();
				
				// 释放内存
				GuildWarUserObj::releaseInstance($serverId, $aUid);
			}
			catch(Exception $e)
			{
				Logger::fatal('GuildWarScriptLogic::sendCheerReward failed, curRound[%d], serverId[%d], uid[%d], msg[%s]', $curRound, $serverId, $aUid, $e->getMessage());
			}
		}
		
		// 这里不更新状态表状态为'发奖完毕'状态，在跨服机器上check完再更新
		
		// 给前端推送消息
		if(!empty($arrRewardUid))
		{
			$arrRewardServerUid = array($serverId => $arrRewardUid);
			GuildWarLogic::push(GuildWarField::INNER, GuildWarPush::NEW_REWARD, array('arrRewardServerUid' => $arrRewardServerUid));
			GuildWarLogic::push(GuildWarField::INNER, GuildWarPush::NEW_MAIL, array('arrRewardServerUid' => $arrRewardServerUid));
		}
		
		Logger::trace('GuildWarScriptLogic::sendCheerReward end...');
	}
	
	public static function checkCheerReward()
	{
		Logger::trace('GuildWarScriptLogic::checkCheerReward begin...');
		
		// 检查是不是在一届跨服赛中
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		$session = $confObj->getSession();
		if (empty($session)) 
		{
			throw new FakeException('GuildWarScriptLogic::checkCheerReward failed, not in any session.');
		}
		
		// 获得进度管理对象
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		
		// 循环对每一个组进行检查，检查是否发完了助威奖
		$arrTeamInfo = GuildWarUtil::getAllTeamData($session);
		foreach ($arrTeamInfo as $teamId => $arrServerId)
		{
			$teamObj = $procedureObj->getTeamObj($teamId);
			$round = $teamObj->getCurRound();
			$status = $teamObj->getCurStatus();
			
			// 不是晋级赛round
			if ($round <= GuildWarRound::AUDITION) 
			{
				Logger::warning('GuildWarScriptLogic::checkCheerReward, check cheer reward send end on cross. teamId[%d] round[%d] <= AUDITION', $teamId, $round);
				continue;
			}
			
			// 还没有打完
			if ($status != GuildWarStatus::FIGHTEND)
			{
				Logger::warning('GuildWarScriptLogic::checkCheerReward, check cheer reward send end on cross. teamId[%d] status[%d] != FIGTHEND', $teamId, $status);
				continue;
			}
			
			// 获得上一轮结束结时间，所有在上轮结束后助威的玩家才有效
			$lastRoundEndTime = $confObj->getPreRoundEndTime($round);
			
			// 获得这个组所有服的db
			$arrDbName = GuildWarUtil::getArrServerDbByArrServerId($arrServerId);
			
			// 循环检查这个组每个服有多少玩家没有处理助威奖
			$errCount = 0;
			foreach ($arrServerId as $serverId)
			{
				$leftNum = GuildWarDao::getAllCheerUserInfoCount($round, $lastRoundEndTime, $arrDbName[$serverId]);
				if ($leftNum < GuildWarConf::ACCEPT_NO_DEAL_SUPPORT_USER)
				{
					Logger::info('GuildWarScriptLogic::checkCheerReward, teamId[%d], serverId[%d], round[%d], leftNum[%d]', $teamId, $serverId, $round, $leftNum);
				}
				else
				{
					$errCount++;
					Logger::info('GuildWarScriptLogic::checkCheerReward, teamId[%d], serverId[%d], round[%d], leftNum[%d], errCount[%d]', $teamId, $serverId, $round, $leftNum, $errCount);
				}
			}
			
			// 这个组所有服都处理完助威奖了，才能更新状态
			if ($errCount == 0)
			{
				if ($round == GuildWarRound::ADVANCED_2)
				{
					$nextStatus = GuildWarStatus::REWARDEND;
				}
				else
				{
					$nextStatus = GuildWarStatus::DONE;
				}

				$teamObj->getTeamRound($round)->setStatus($nextStatus);
				$teamObj->update();
				Logger::info('GuildWarScriptLogic::checkCheerReward, check cross cheer reward done, set next status. round[%d], nextStatus[%d]', $round, $nextStatus);
			}
			else 
			{
				Logger::info('GuildWarScriptLogic::checkCheerReward, check cross cheer reward done, failed, round[%d], errCount[%d]', $round, $errCount);
			}
		}
		
		// 推送状态
		GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NOW_STATUS);
		
		Logger::trace('GuildWarScriptLogic::checkCheerReward end...');
	}
	
	public static function sendFinalReward($force = FALSE)
	{
		Logger::trace('GuildWarScriptLogic::sendFinalReward begin...');
		
		// 检查届数
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		$session = $confObj->getSession();
		if (empty($session))
		{
			Logger::warning('GuildWarScriptLogic::sendFinalReward failed, not in any session.');
			return;
		}
		
		// 检查轮次
		$confRound = $confObj->getCurRound();
		if ($confRound != GuildWarRound::ADVANCED_2)
		{
			Logger::warning('GuildWarScriptLogic::sendFinalReward failed, not in any round of session[%d] currTime[%s] or not last round[%d].', $session, strftime('%Y%m%d-%H%M%S', Util::getTime()), $confRound);
			return;
		}
		
		// 状态控制对象
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		
		// 奖励和邮件集中推送
		RewardCfg::$NO_CALLBACK = TRUE;
		MailConf::$NO_CALLBACK = TRUE;
		
		// 循环对每个组发排名奖和全服奖励
		$arrRewardServerUid = array();
		$arrRewardWholeServer = array();
		$rewardTime = Util::getTime();
		$arrTeamId = GuildWarUtil::getAllTeamId($session);
		foreach ($arrTeamId as $aTeamId)
		{
			$teamObj = $procedureObj->getTeamObj($aTeamId);
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
			
			// 根据状态表检查轮次是不是最终的决赛
			if ($curRound != GuildWarRound::ADVANCED_2) 
			{
				Logger::warning('GuildWarScriptLogic::sendFinalReward failed, teamId[%d] cur round[%d] is not finals round.', $aTeamId, $curRound);
				continue;
			}
			
			// 根据状态表检查状态是不是 助威奖发送完毕 状态
			if ($curStatus != GuildWarStatus::REWARDEND) 
			{
				Logger::warning('GuildWarScriptLogic::sendFinalReward failed, teamId[%d] cur round[%d] cur status[%d], not REWARDEND status.', $aTeamId, $curRound, $curStatus);
				continue;
			}
			
			try
			{
				// 获得一个组所有进入晋级赛的军团
				$arrGuildInfo = GuildWarDao::selectFinalsGuildInfo(array(), $session, $aTeamId, $confObj->getFailNum(), $confObj->getSignUpStartTime());
				foreach ($arrGuildInfo as $aGuildInfo)
				{
					// 获得这个军团的对象，用对象搞，比较方便
					$guildId = $aGuildInfo[GuildWarServerField::TBL_FIELD_GUILD_ID];
					$guildServerId = $aGuildInfo[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID];
					$guildWarObj = GuildWarServerObj::getInstanceFromInfo($aGuildInfo);
					
					// 获得军团排名和参战的所有军团成员
					$rank = $guildWarObj->getFinalRank();
					$arrCandidates = $guildWarObj->getCandidates();
					
					// 获取军团所有成员
					$guildServerDb = $guildWarObj->getServerDb();
					$arrMemberList = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID), $guildServerDb);
					$arrMemberList = array_keys($arrMemberList);
					Logger::trace('GuildWarScriptLogic::sendFinalReward, teamId[%d] guildId[%d] serverId[%d] memberList[%s]', $aTeamId, $guildId, $guildServerId, $arrMemberList);
					
					// 获取参战人员奖励和未参战人员奖励
					$rewardFight = $confObj->getRankReward($rank, TRUE);
					$rewardNotFight = $confObj->getRankReward($rank, FALSE);
					
					// 循环对军团成员发排名奖
					foreach ($arrMemberList as $aUid)
					{
						// 获取排名奖励
						$rewardArr = (in_array($aUid, $arrCandidates) ? $rewardFight : $rewardNotFight);
						
						// 判断该成员是否已经领取了排名奖 ,这里取军团的成员，未参战的会初始化数据，有待优化
						$guildWarUserObj = GuildWarUserObj::getInstance($guildServerId, $aUid, TRUE);
						if ($guildWarUserObj->isReceiveRankReward())
						{
							// 已领奖
							$rewardTime = $guildWarUserObj->getRankRewardTime();
							Logger::info('GuildWarScriptLogic::sendFinalReward, serverId[%d] guildId[%d] user[%d] already receive rank reward, reward time[%s]', $guildServerId, $guildId, $aUid, strftime('%Y%m%d-%H%M%S', $rewardTime));
						}
						else 
						{
							// 先标记
							$guildWarUserObj->receiveRankReward();
							$guildWarUserObj->update();
							
							// 未领奖，将奖励发到奖励中心，并且发邮件
							if ($rank == 1) 
							{
								$rewardSource = RewardSource::GUILDWAR_RANK_FIRST;
							}
							else if ($rank == 2) 
							{
								$rewardSource = RewardSource::GUILDWAR_RANK_SECOND;
							}
							else 
							{
								$rewardSource = RewardSource::GUILDWAR_RANK_NORMAL;
							}
							RewardUtil::reward3DtoCenter($aUid, array($rewardArr), $rewardSource, array('rank' => $rank), $guildServerDb);
							MailTemplate::sendGuildWarRankReward($aUid, $curRound, $rank, $rewardArr, $guildServerDb);
							Logger::info('GuildWarScriptLogic::sendFinalReward, teamId[%d] serverId[%d] guildId[%d] user[%d] rank[%d] receive rank reward[%s] now', $aTeamId, $guildServerId, $guildId, $aUid, $rank, $rewardArr);
							
							// 记录这个服领奖的人，准备发送推送
							if (!isset($arrRewardServerUid[$guildServerId]))
							{
								$arrRewardServerUid[$guildServerId] = array($aUid);
							}
							else
							{
								$arrRewardServerUid[$guildServerId][] = $aUid;
							}
						}
						
						// 释放内存
						GuildWarUserObj::releaseInstance($guildServerId, $aUid);
					}
					
					// 如果是排名第一的军团，则发全服奖励     重发的时候要判断是否发过  force下先不发全服奖励
					if ($rank == 1 && !$force) 
					{
						try
						{
							// 记录一下哪些服有全服奖励
							if (isset($arrRewardWholeServer[$aTeamId]))
							{
								throw new InterException('GuildWarScriptLogic::sendFinalReward serverId[%d] already get whole server reward in teamId[%d]', $arrRewardWholeServer[$arrTeamId], $arrTeamId);
							}
							
							// 获取全服奖励的内容
							$worldPrize = $confObj->getAllServerPrize();
							$arrReward = RewardUtil::format3DtoCenter($worldPrize);
							$arrReward[PayBackDef::PAYBACK_TYPE] = PayBackType::GUILDWAR_WHOLDWORLD;
							
							// 利用全服补偿发送全服奖励
							PaybackLogic::insertPayBackInfo($rewardTime, $rewardTime + GuildWarConf::REWARD_WHOLEWORLD_LAST_TIME, $arrReward, TRUE, $guildServerDb);
							Logger::info('GuildWarScriptLogic::sendFinalReward, reward whole on cross server ok, teamId[%d] serverId[%d] guildId[%d].', $aTeamId, $guildServerId, $guildId);
								
							$arrRewardWholeServer[$aTeamId] = $guildServerId;
						}
						catch(Exception $e)
						{
							Logger::fatal("GuildWarScriptLogic::sendFinalReward, reward whole on cross server failed, teamId[%d] serverId[%d] guildId[%d] msg[%s]", $aTeamId, $guildServerId, $guildId, $e->getMessage());
						}
					}
				}
				
				// 更新状态表这个组的状态为DONE,这个组完事啦
				$teamObj->getTeamRound(GuildWarRound::ADVANCED_2)->setStatus(GuildWarStatus::DONE);
				$teamObj->update();
			}
			catch (Exception $e)
			{
				Logger::fatal('GuildWarScriptLogic::sendFinalReward, send rank reward failed. teamId[%d], serverId[%d], guildId[%d], rank[%d], msg[%s]', $aTeamId, $guildServerId, $guildId, $rank, $e->getMessage());
			}
		}
		
		// 向前端推送
		if (!empty($arrRewardServerUid))
		{
			//将有全服奖励的服的uid数组改成array(0)
			$arr = $arrRewardServerUid;
			foreach ($arrRewardWholeServer as $teamId => $serverId)
			{
				$arr[$serverId] = array( 0 );
			}
			
			GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NEW_REWARD, array('arrRewardServerUid' => $arrRewardServerUid) );
			GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NEW_MAIL, array('arrRewardServerUid' => $arrRewardServerUid));
		}
		
		// 推送状态
		GuildWarLogic::push(GuildWarField::CROSS, GuildWarPush::NOW_STATUS);
		
		Logger::trace('GuildWarScriptLogic::sendFinalReward end...');
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */