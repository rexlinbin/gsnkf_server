<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarServerObj.class.php 208937 2015-11-12 03:01:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/obj/GuildWarServerObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-11-12 03:01:41 +0000 (Thu, 12 Nov 2015) $
 * @version $Revision: 208937 $
 * @brief 
 *  
 **/
 
class GuildWarServerObj
{
	/**
	 * 缓存
	 * @var array
	 */
	private static $sArrInstance = array();
	
	/**
	 * 届
	 * @var int
	 */
	private $mSession;
	
	/**
	 * 服务器Id
	 * @var int
	 */
	private $mServerId;
	
	/**
	 * 军团Id
	 * @var int
	 */
	private $mGuildId;
	
	/**
	 * 原始数据
	 * @var array
	 */
	private $mObj = array();
	
	/**
	 * 数据
	 * @var array
	 */
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $session 届
	 * @param int $serverId 服务器Id
	 * @param int $guildId 军团Id
	 * @static
	 * @access public
	 * @return GuildWarServerObj
	*/
	public static function getInstance($session, $serverId, $guildId, $init = FALSE)
	{
		if (!isset(self::$sArrInstance[$session][$serverId][$guildId]))
		{
			self::$sArrInstance[$session][$serverId][$guildId] = new self($session, $serverId, $guildId, $init);
		}

		return self::$sArrInstance[$session][$serverId][$guildId];
	}
	
	/**
	 * 根据info返回obj
	 * 
	 * @param array $info
	 * @throws InterException
	 * @return GuildWarServerObj
	 */
	public static function getInstanceFromInfo($info)
	{
		foreach (GuildWarServerField::$ALL_FIELDS as $aField)
		{
			if (!isset($info[$aField])) 
			{
				throw new InterException('GuildWarServerObj::getInstanceFromInfo failed, info[%s] do not have field[%s]', $info, $aField);
			}
		}

		$session = $info[GuildWarServerField::TBL_FIELD_SESSION];
		$serverId = $info[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID];
		$guildId = $info[GuildWarServerField::TBL_FIELD_GUILD_ID];
		
		self::$sArrInstance[$session][$serverId][$guildId] = new self($session, $serverId, $guildId, FALSE, $info);
		return self::$sArrInstance[$session][$serverId][$guildId];
	}
	
	/**
	 * 静态函数，判断一个军团是否报名了一届军团跨服战，以免不必要的在跨服机上初始化数据
	 * 
	 * @param int $session
	 * @param int $serverId
	 * @param int $guildId
	 * @return boolean
	 */
	public static function isGuildSignUp($session, $serverId, $guildId)
	{
		$info = GuildWarDao::selectGuildWarServer($session, $serverId, $guildId);
		if (empty($info)) 
		{
			return FALSE;
		}
		
		$confObj = GuildWarConfObj::getInstance();
		$signUpStartTime = $confObj->getSignUpStartTime();
		$signUpTime = $info[GuildWarServerField::TBL_FIELD_SIGN_TIME];
		if ($signUpTime >= $signUpStartTime)
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public static function releaseInstance($session, $serverId, $guildId)
	{
		if (isset(self::$sArrInstance[$session][$serverId][$guildId]))
		{
			unset(self::$sArrInstance[$session][$serverId][$guildId]);
		}
	}

	private function __construct($session, $serverId, $guildId, $init, $info = array())
	{
		$this->mSession = $session;
		$this->mServerId = $serverId;
		$this->mGuildId = $guildId;
		
		if (empty($info)) 
		{
			$info = $this->getInfo($session, $serverId, $guildId);
			if (empty($info)) 
			{
				if ($init) 
				{
					$info = $this->createInfo();
				}
				else 
				{
					throw new FakeException('not found guild war server obj, session[%d], serverId[%d], guildId[%d]', $session, $serverId, $guildId);
				}
			}
		}
		
		$this->mObj = $info;
		$this->mObjModify = $info;
		$this->refresh();
	}
	
	public function refresh()
	{
		$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
		if ($this->getSignUpTime() < $confObj->getActivityStartTime())
		{
			$this->mObjModify = $this->getInitInfo();
		}
	}

	public function getInfo($session, $serverId, $guildId)
	{
		return GuildWarDao::selectGuildWarServer($session, $serverId, $guildId);
	}
	
	public function createInfo()
	{
		$initInfo = $this->getInitInfo();
		GuildWarDao::insertGuildWarServer($initInfo);
		
		return $initInfo;
	}
	
	public function getSession()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_SESSION];
	}
	
	public function isSignUp()
	{
		$confObj = GuildWarConfObj::getInstance();
		$signUpStartTime = $confObj->getSignUpStartTime();
		$signUpTime = $this->getSignUpTime();
		if (!empty($signUpTime) && $signUpTime > $signUpStartTime) 
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function getSignUpTime()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_SIGN_TIME];
	}
	
	public function increLoseTimes()
	{
		++$this->mObjModify[GuildWarServerField::TBL_FIELD_LOSE_TIMES];
	}
	
	public function getLoseTimes()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_LOSE_TIMES];
	}
	
	public function isCandidate($uid)
	{
		return in_array($uid, $this->getCandidates());
	}
	
	public function isTotalLose()
	{
		return count($this->getLosers()) >= count($this->getCandidates());
	}
	
	public function isFighter($uid)
	{
		$arrFighter = $this->getFighters();
		return isset($arrFighter[$uid]);
	}
	
	public function isLoser($uid)
	{
		return in_array($uid, $this->getLosers());
	}
	
	public function signUp($guildLevel, $guildBadge, $guildName, $arrDandidates)
	{		
		$this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_LEVEL] = $guildLevel;
		$this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_BADGE] = $guildBadge;
		$this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_NAME] = $guildName;
		$this->mObjModify[GuildWarServerField::TBL_FIELD_SIGN_TIME] = Util::getTime();
		$this->setCandidateInfo($arrDandidates);
	}
	
	private function getInitInfo()
	{
		return array
		(
				GuildWarServerField::TBL_FIELD_SESSION => $this->mSession,
				GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID => $this->mServerId,
				GuildWarServerField::TBL_FIELD_GUILD_ID => $this->mGuildId,
				GuildWarServerField::TBL_FIELD_TEAM_ID => GuildWarUtil::getTeamIdByServerId($this->mSession, $this->mServerId),
				GuildWarServerField::TBL_FIELD_GUILD_LEVEL => 0,
				GuildWarServerField::TBL_FIELD_GUILD_BADGE => 0,
				GuildWarServerField::TBL_FIELD_GUILD_NAME => '',
				GuildWarServerField::TBL_FIELD_GUILD_SERVER_NAME => GuildWarUtil::getServerNameByServerId($this->mServerId),
				GuildWarServerField::TBL_FIELD_SIGN_TIME => 0,
				GuildWarServerField::TBL_FIELD_FINAL_RANK => 0,
				GuildWarServerField::TBL_FIELD_LOSE_TIMES => 0,
				GuildWarServerField::TBL_FIELD_POS => 0,
				GuildWarServerField::TBL_FIELD_FIGHT_FORCE => 0,
				GuildWarServerField::TBL_FIELD_LAST_FIGHT_TIME => 0,
				GuildWarServerField::TBL_FIELD_VA_REPLAY => array
													(
															GuildWarServerField::TBL_VA_REPLAY_FIELD_AUDITION => array(),
															GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS => array(),
													),
				GuildWarServerField::TBL_FIELD_VA_EXTRA => array
													(
															GuildWarServerField::TBL_VA_EXTRA_FIELD_CANDIDATES => array(),
															GuildWarServerField::TBL_VA_EXTRA_FIELD_LOSERS => array(),
															GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS => array(),
															GuildWarServerField::TBL_VA_EXTRA_FIELD_HP => array(),
															GuildWarServerField::TBL_VA_EXTRA_FIELD_PRESIDENT_INFO => array(),
													),
		);
	}
	
	public function getServerInfo()
	{
		return $this->mObjModify;
	}
	
	public function getCandidates()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_CANDIDATES];
	}
	
	public function addCandidate($uid)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_CANDIDATES][] = $uid;
	}
	
	public function subCandidate($uid)
	{
		$arrCandidates = $this->getCandidates();
		foreach ($arrCandidates as $index => $aCandi)
		{
			if ($aCandi == $uid) 
			{
				unset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_CANDIDATES][$index]);
				return;
			}
		}
	}
	
	public function addFinalsReplay($round, $replayId, $finalsReplay)
	{
		if (!isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round])) 
		{
			$finalsReplay['sub_round'][] = array('replay_id' => $replayId);
			$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round] = $finalsReplay;
		}
		else 
		{
			$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round]['sub_round'][] = array('replay_id' => $replayId);			
		}
	}
	
	public function setFinalsReplayWinner($round, $result)
	{
		if (!isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round])) 
		{
			throw new InterException('GuildWarServerObj.setFinalsReplayWinner failed, no any finals replay of round[%d], but set winner');
		}

		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round]['result'] = $result;
		
		// 设置最后战斗时间
		$this->setLastFightTime(time());
	}
	
	public function setFinalsLeftUser($round, $subRound, $arrEstimateFighter)
	{
		if (!isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round]))
		{
			throw new InterException('GuildWarServerObj.setFinalsLeftUser failed, no any finals replay of round[%d], but set left user');
		}
		
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS][$round]['left_user'][$subRound] = $arrEstimateFighter;
	}
	
	public function getFinalsReplay()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_FINALS];
	}
	
	public function getFinalsReplayByRound($round)
	{
		$arrReplayInfo = $this->getFinalsReplay();
		return isset($arrReplayInfo[$round]) ? $arrReplayInfo[$round] : array();
	}
	
	public function getFinalsReplayByGuild($serverId, $guildId)
	{
		$arrReplayInfo = $this->getFinalsReplay();
		foreach ($arrReplayInfo as $round => $replayInfo)
		{
			$attackerInfo = $replayInfo['attacker'];
			if ($attackerInfo['guild_server_id'] == $serverId
				&& $attackerInfo['guild_id'] == $guildId) 
			{
				return $this->getFinalsReplayByRound($round);
			}
			
			$defenderInfo = $replayInfo['defender'];
			if ($defenderInfo['guild_server_id'] == $serverId
			&& $defenderInfo['guild_id'] == $guildId)
			{
				return $this->getFinalsReplayByRound($round);
			}
		}
		
		return array();
	}
	
	public function addAuditionReplay($replay)
	{
		// 海选赛战报把自己的信息去掉吧，浪费地方
		if ($replay['attacker'][GuildWarServerField::TBL_FIELD_GUILD_ID] == $this->getGuildId()
			&& $replay['attacker'][GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID] == $this->getServerId())
		{
			$replay['attacker'] = array();
		}
		else
		{
			$replay['defender'] = array();
		}
		
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_AUDITION][] = $replay;
		
		// 设置最后战斗时间
		$this->setLastFightTime(time());
	}
	
	public function getAuditionReplay()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY][GuildWarServerField::TBL_VA_REPLAY_FIELD_AUDITION];
	}
	
	public function refreshFightForce()
	{
		$fightForce = 0;
		$arrField = array
		(
				GuildWarUserField::TBL_FIELD_UID,
				GuildWarUserField::TBL_FIELD_UNAME,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME,
				GuildWarUserField::TBL_FIELD_FIGHT_FORCE,
		);
		$usersInfo = GuildWarDao::getArrGuildWarUserInfo($this->getCandidates(), $arrField, $this->getServerDb());
		foreach ($usersInfo as $key => $userInfo)
		{
			$fightForce += $userInfo[GuildWarUserField::TBL_FIELD_FIGHT_FORCE];
		}
		
		$this->setFightForce($fightForce);
	}
	
	public function getLosers()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_LOSERS];
	}
	
	public function addLosers($uid)
	{
		if (!in_array($uid, $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_LOSERS])) 
		{
			$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_LOSERS][] = $uid;
		}
		
		if (isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid])) 
		{
			unset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid]);
		}
	}
	
	public function clearMemberInfo()
	{
		$this->setFighters(array());
		$this->setLosers(array());
		$this->setHp(array());
	}
	
	public function promotion($rank)
	{
		if ($rank != GuildWarConf::$next_rank[$this->getFinalRank()]) 
		{
			throw new FakeException('GuildWarServerObj.promotion failed, promotion rank[%d], cur rank[%d], next rank[%d]', $rank, $this->getFinalRank(), GuildWarConf::$next_rank[$this->getFinalRank()]);
		}
		
		$this->setFinalRank($rank);
	}
	
	private function setLosers($losers)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_LOSERS] = $losers;
	}
	
	public function getFighters()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS];
	}
	
	public function allFightersLose()
	{
		$arrFighters = $this->getFighters();
		foreach ($arrFighters as $aUid => $winTimes)
		{
			$this->addLosers($aUid);
		}
		$this->setFighters(array());
	}
	
	public function updateFighters($fightersInfo, $fightHpInfo, $fightLeftInfo, $fightWinInfo, $offset)
	{
		Logger::trace('GuildWarServerObj.updateFighters begin...');
		
		Logger::trace('GuildWarServerObj.updateFighters fightersInfo[%s], fightHpInfo[%s], fightLeftInfo[%s], fightWinInfo[%s], offset[%d]', $fightersInfo, $fightHpInfo, $fightLeftInfo, $fightWinInfo, $offset);
		
		Logger::trace('GuildWarServerObj.updateFighters before update, arrCandidates[%s]', $this->getCandidates());
		
		$hp = array();
		foreach ($fightersInfo as $userInfo)
		{
			// 更新血量信息
			$uid = $userInfo[GuildWarUserField::TBL_FIELD_UID];
			$fightUid = $uid * 10 + $offset;
			if (!empty($fightHpInfo[$fightUid])) // 计算这个玩家的残血
			{
				$userFightInfo = $fightHpInfo[$fightUid];
				$dead = true;
				foreach ($userFightInfo as $heroInfo)
				{
					if ($heroInfo['hp'] > 0)
					{
						$dead = false;
						$hp[$uid][GuildWarUtil::reChangeID($heroInfo['hid'])] = $heroInfo['hp'];
					}
				}
				if ($dead)
				{
					$this->addLosers($uid);
					Logger::trace('GuildWarServerObj.updateFighters uid[%d] hp all zero, dead', $uid);
				}
			}
			else if (in_array($fightUid, $fightLeftInfo)) // 这个玩家压根没机会上场，前面的人太厉害
			{
				Logger::trace('GuildWarServerObj.updateFighters uid[%d] in fightLeftInfo[%s], alive.', $uid, $fightLeftInfo);
			}
			else // 战死啦
			{
				$this->addLosers($uid);
				Logger::trace('GuildWarServerObj.updateFighters uid[%d] not in hp or left info, dead.', $uid);
			}
			
			// 查看这个人的连胜次数
			if (!empty($fightWinInfo[$fightUid]))
			{
				$winTimes = $fightWinInfo[$fightUid];
				$this->addFighterWin($uid, $winTimes);
				$currWin = $this->getFighterWin($uid);
				$maxWin = $userInfo['buyWin'];
				if ($currWin >= $maxWin)
				{
					$this->addLosers($uid);
					Logger::trace('GuildWarServerObj.updateFighters uid[%d] win times exceed, currWin[%d], maxWin[%d], dead.', $uid, $currWin, $maxWin);
				}
			}
		}
		$this->setHp($hp);
		
		Logger::trace('GuildWarServerObj.updateFighters after update, arrCandidates[%s] losers[%s], hp[%s]', $this->getCandidates(), $this->getLosers(), $hp);
		
		Logger::trace('GuildWarServerObj.updateFighters end...');
	}
	
	private function setFighters($fighters)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS] = $fighters;
	}
	
	public function setPresidentInfo($presidentInfo)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_PRESIDENT_INFO] = $presidentInfo;
	}
	
	public function getPresidentInfo()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_PRESIDENT_INFO];
	}
	
	public function addFighter($uid)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid] = 0;
	}
	
	public function addFighterWin($uid, $win)
	{
		if (!isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid])) 
		{
			$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid] = 0;
		}
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid] += $win;
	}
	
	public function getFighterWin($uid)
	{
		if (!isset($this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid])) 
		{
			$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid] = 0;
		}
		
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_FIGHTERS][$uid];
	}
	
	public function getHp()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_HP];
	}
	
	private function setHp($hp)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_HP] = $hp;
	}
	
	/**
	 * 获取军团所有参战人员的战斗信息，按照战力从小到大排列
	 * 
	 * @return array
	 */
	public function getCandidatesInfo()
	{		
		$arrCandidatesInfo = GuildWarDao::getArrGuildWarUserInfo($this->getCandidates(), array(), $this->getServerDb());
		
		$sortCmp = new SortByFieldFunc(array(GuildWarUserField::TBL_FIELD_FIGHT_FORCE => SortByFieldFunc::ASC, GuildWarUserField::TBL_FIELD_UID => SortByFieldFunc::ASC));
		usort($arrCandidatesInfo, array($sortCmp, 'cmp'));
		
		return $arrCandidatesInfo;
	}
	
	public function getReplay()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_REPLAY];
	}
	
	public function setCandidateInfo($info)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA][GuildWarServerField::TBL_VA_EXTRA_FIELD_CANDIDATES] = $info;
	}
	
	public function setPos($pos)
	{
		if ($this->getPos() > 0) 
		{
			throw new InterException('GuildWarServerObj.setPos failed, already set pos[%d], can not set pos[%d]', $this->getPos(), $pos);
		}
		
		$this->mObjModify[GuildWarServerField::TBL_FIELD_POS] = $pos;
	}
	
	public function getPos()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_POS];
	}
	
	public function getFinalRank()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_FINAL_RANK];
	}
	
	private function setFinalRank($rank)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_FINAL_RANK] = $rank;
	}
	
	public function getExtra()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_VA_EXTRA];
	}
	
	public function getAuditionFightFormation()
	{			
		// 军团参战者战斗信息，已按照战斗力从小到大排序
		$arrCandidatesInfo = $this->getCandidatesInfo();
		Logger::trace('GuildWarServerObj.getAuditionFightFormation, candidateInfo[%s]', $arrCandidatesInfo);
		
		// 如果没有参战人员，返回FALSE
		if (empty($arrCandidatesInfo))
		{
			return FALSE;
		}
		
		// 获得军团信息
		$formations = array();
		$formations['guild_id'] = $this->getGuildId();
		$formations['server_id'] = $this->getServerId();
		$formations['name'] = $this->getGuildName();
		$formations['level'] = $this->getGuildLevel();
		$formations['members'] = array();
		
		$arrCandidates = $this->getCandidates();
		$arrCandidatesInfo = Util::arrayIndex($arrCandidatesInfo, GuildWarUserField::TBL_FIELD_UID);
		$confObj = GuildWarConfObj::getInstance();
		foreach ($arrCandidates as $aUid)
		{
			if (!isset($arrCandidatesInfo[$aUid]) || empty($arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]) || $arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME] < $confObj->getActivityStartTime()) 
			{
				$ret = GuildWarLogic::reGetUserGuildWarInfo($this->getServerId(), $aUid);
				Logger::warning('GuildWarServerObj.getAuditionFightFormation re get user info of uid[%d], serverId[%d], guildId[%d], ret[%s]', $aUid, $this->getServerId(), $this->getGuildId(), $ret);
				if (!empty($ret))
				{
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_UID] = $aUid;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_UNAME] = $ret[0];
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_ROUND] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_WORSHIP_TIME] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_FIGHT_FORCE] = $ret[1]['fightForce'];
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME] = 0;
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME] = Util::getTime();
					$arrCandidatesInfo[$aUid][GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT] = $ret[1];
				}
				else 
				{
					throw new InterException('GuildWarServerObj.getAuditionFightFormation can not get user info of uid[%d], serverId[%d], guildId[%d]', $aUid, $this->getServerId(), $this->getGuildId());
				}
			}
		}
		
		$sortCmp = new SortByFieldFunc(array(GuildWarUserField::TBL_FIELD_FIGHT_FORCE => SortByFieldFunc::ASC, GuildWarUserField::TBL_FIELD_UID => SortByFieldFunc::ASC));
		usort($arrCandidatesInfo, array($sortCmp, 'cmp'));
		Logger::trace('GuildWarServerObj.getAuditionFightFormation after re get arrCandidatesInfo[%s]', $arrCandidatesInfo);
		
		// 循环获取参战者的信息
		$fightForce = 0;
		foreach ($arrCandidatesInfo as $aUserInfo)
		{
			// 需要修复的数据
			$guildWarUserObj = GuildWarUserObj::getInstanceFromInfo($this->getServerId(), $aUserInfo);
			if (!$guildWarUserObj->isArmed()) 
			{
				throw new InterException('GuildWarServerObj::getAuditionFightFormation, impossible, already re get user guild war info, uid[%d], serverId[%d], guildId[%d].', $aUserInfo[GuildWarUserField::TBL_FIELD_UID], $this->getServerId(), $this->getGuildId());
			}
		
			// 获取最大连胜次数，放在战斗数据里
			$aBattleFmt = $guildWarUserObj->getBattleFmt();
			$aBattleFmt['maxWin'] = $guildWarUserObj->getMaxWinNum(GuildWarRound::AUDITION, $this->getLastFightTime());
			$formations['members'][] = $aBattleFmt;
			$fightForce += $guildWarUserObj->getFightForce();
			GuildWarUserObj::releaseInstance($this->getServerId(), $aUserInfo[GuildWarUserField::TBL_FIELD_UID]);
		}
		
		$this->setFightForce($fightForce);
		return $formations;
	}
	
	public function getFinalsFightFormation($round, $subRound)
	{
		Logger::trace('GuildWarServerObj.getFinalsFightFormation begin...');
		
		// 可以上场的人
		$arrFighters = $this->getFinalsFighters($subRound);
			
		// 战斗数据
		$formations = array();
		$formations['guild_id'] = $this->getGuildId();
		$formations['server_id'] = $this->getServerId();
		$formations['name'] = $this->getGuildName();
		$formations['level'] = $this->getGuildLevel();
		foreach ($arrFighters as $aUserInfo)
		{	
			$aUid = $aUserInfo[GuildWarUserField::TBL_FIELD_UID];
					
			// 检查连胜
			$guildWarUserObj = GuildWarUserObj::getInstanceFromInfo($this->getServerId(), $aUserInfo);
			$battleFmt = $guildWarUserObj->getBattleFmt();
			$battleFmt['buyWin'] = $guildWarUserObj->getMaxWinNum($round, $this->getLastFightTime());
		
			// 获取最大连胜次数
			if (!$this->isFighter($aUid))
			{
				$this->addFighter($aUid, 0);
				$battleFmt['maxWin'] = $battleFmt['buyWin'];	 
			}
			else
			{
				$battleFmt['maxWin'] = $battleFmt['buyWin'] - $this->getFighterWin($aUid);
				
				// 把玩家在上个小轮的连胜次数记录下来
				if ($this->getFighterWin($aUid) > 0) 
				{
					$formations['mapUidInitWin'][$aUid] = $this->getFighterWin($aUid);
					Logger::trace('GuildWarServerObj.getFinalsFightFormation uid[%d] init win is[%d]', $aUid, $this->getFighterWin($aUid));
				}
			}
		
			// 设置战斗参数
			$formations['members'][] = $battleFmt;
			// 释放内存，血一样的教训。。。
			GuildWarUserObj::releaseInstance($this->getServerId(), $aUserInfo[GuildWarUserField::TBL_FIELD_UID]);
		}
		
		Logger::trace('GuildWarServerObj.getFinalsFightFormation ret[%s] end...', $formations);
		return $formations;
	}
	
	public function getFinalsFighters($subRound)
	{
		Logger::trace('GuildWarServerObj.getFinalsFighters begin...');
		
		// 获取基本信息
		$arrCandidate = $this->getCandidates();
		$arrLoser = $this->getLosers();
		$arrFighter = $this->getFighters();
		$hp = $this->getHp();
		
		Logger::trace('GuildWarServerObj.getFinalsFighters, arrCandidates[%s], arrLoser[%s], arrFighter[%s], hp[%s]', $arrCandidate, $arrLoser, $arrFighter, $hp);
		
		// 删掉已经阵亡的人
		$arrCandidate = array_diff($arrCandidate, $arrLoser);
		if (empty($arrCandidate))
		{
			return array();
		}
		
		// 再删一次
		foreach ($arrFighter as $key => $value)
		{
			if (in_array($key, $arrLoser)) 
			{
				unset($arrFighter[$key]);
			}
		}
		
		// 获取玩家战斗信息，并且补充没有获得的玩家信息
		$arrUserInfo = GuildWarDao::getArrGuildWarUserInfo($arrCandidate, array(), $this->getServerDb());
		foreach ($arrCandidate as $aUid)
		{
			if (!isset($arrUserInfo[$aUid]) || empty($arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]))
			{
				Logger::warning('GuildWarServerObj.getFinalsFighters re get user info of uid[%d], serverId[%d], guildId[%d]', $aUid, $this->getServerId(), $this->getGuildId());
				$ret = GuildWarLogic::reGetUserGuildWarInfo($this->getServerId(), $aUid);
				if (!empty($ret))
				{
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UID] = $aUid;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UNAME] = $ret[0];
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_CHEER_ROUND] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_WORSHIP_TIME] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_FIGHT_FORCE] = $ret[1]['fightForce'];
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME] = 0;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME] = Util::getTime();
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT] = $ret[1];
				}
				else 
				{
					throw new InterException('GuildWarServerObj.getFinalsFighters can not get user info of uid[%d], serverId[%d], guildId[%d]', $aUid, $this->getServerId(), $this->getGuildId());
				}
			}
		}
		
		// 根据战斗力和玩家uid排序
		$sortCmp = new SortByFieldFunc(array(GuildWarUserField::TBL_FIELD_FIGHT_FORCE => SortByFieldFunc::ASC, GuildWarUserField::TBL_FIELD_UID => SortByFieldFunc::ASC));
		usort($arrUserInfo, array($sortCmp, 'cmp'));
				
		// 遍历所有用户
		$index = 0;
		$ret = array();
		foreach ($arrUserInfo as $userInfo)
		{
			$aUid = $userInfo[GuildWarUserField::TBL_FIELD_UID];
			
			$masterHero = array();
			$formation = $userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT];
			foreach($formation['arrHero'] as $heroInfo)
			{
				$htid = $heroInfo[PropertyKey::HTID];
				if(HeroUtil::isMasterHtid($htid))
				{
					$masterHero = $heroInfo;
				}
			}
			if(empty($masterHero))
			{
				throw new InterException('not found master hero:%s', $formation);
			}
			else
			{
				$arrDress = HeroUtil::simplifyDressInfo($masterHero[PropertyKey::EQUIP_INFO]);
				$formation['masterHeroInfo'] = array(
						'dress' => $arrDress,
						'htid' => $masterHero[PropertyKey::HTID],
				);
			}
			$userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT] = $formation;
			
			// 更新血量
			if (isset($hp[$aUid]))
			{
				$curHeroInfo = $hp[$aUid];
				foreach ($userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]['arrHero'] as $pos => $heroInfo)
				{
					if (empty($curHeroInfo[$heroInfo['hid']]))
					{
						unset($userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]['arrHero'][$pos]);
					}
					else
					{
						$userInfo[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]['arrHero'][$pos]['currHp'] = $curHeroInfo[$heroInfo['hid']];
					}
				}
			}
				
			// 查看需要打多少轮
			$allRound = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSubRoundCount();
				
			if (isset($arrFighter[$aUid]))// 如果这个人还活着呢，就上场
			{
				$ret[] = $userInfo;
			}
			else if ($subRound != $allRound && ++$index <= GuildWarConf::ONE_TIME_PLAYER)// 否则就加入
			{
				$ret[] = $userInfo;
			}
			else if ($subRound == $allRound)// 最后一轮了，都上
			{
				$ret[] = $userInfo;
			}
		}
		
		Logger::trace('GuildWarServerObj.getFinalsFighters ret[%s] end...', $ret);
		return $ret;
	}
	
	public function getEstimateFinalFighters($subRound)
	{
		Logger::trace('GuildWarServerObj.getEstimateFinalFighters param[subRound:%d] begin...', $subRound);
		
		$arrCandidate = $this->getCandidates();
		$arrCandidate = array_merge($arrCandidate);
		$subRoundCount = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSubRoundCount();
		
		if ($subRound > $subRoundCount || $subRound < 1) 
		{
			throw new InterException('GuildWarServerObj.getEstimateFinalFighters failed, subRound[%d], subRoundCount[%d]', $subRound, $subRoundCount);
		}
		
		if (count($arrCandidate) <= ($subRound - 1) * GuildWarConf::ONE_TIME_PLAYER) 
		{
			Logger::trace('GuildWarServerObj.getEstimateFinalFighters, count of candidates[%d], subRound[%d], per sub round count[%d]', count($arrCandidate), $subRound, GuildWarConf::ONE_TIME_PLAYER);
			return array();
		}
		
		$arrField = array
		(
				GuildWarUserField::TBL_FIELD_UID,
				GuildWarUserField::TBL_FIELD_FIGHT_FORCE,
				GuildWarUserField::TBL_FIELD_VA_EXTRA,
		);
		
		$arrUserInfo = GuildWarDao::getArrGuildWarUserInfo($arrCandidate, $arrField, $this->getServerDb());
		foreach ($arrCandidate as $aUid)
		{
			if (!isset($arrUserInfo[$aUid]) || empty($arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT]))
			{
				Logger::warning('GuildWarServerObj.getEstimateFinalFighters re get user info of uid[%d], serverId[%d], guildId[%d]', $aUid, $this->getServerId(), $this->getGuildId());
				$ret = GuildWarLogic::reGetUserGuildWarInfo($this->getServerId(), $aUid);
				if (!empty($ret))
				{
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_UID] = $aUid;
					$arrUserInfo[$aUid][GuildWarUserField::TBL_FIELD_FIGHT_FORCE] = $ret[1]['fightForce'];
				}
				else
				{
					throw new InterException('GuildWarServerObj.getEstimateFinalFighters can not get user info of uid[%d], serverId[%d], guildId[%d]', $aUid, $this->getServerId(), $this->getGuildId());
				}
			}
		}
		
		$sortCmp = new SortByFieldFunc(array(GuildWarUserField::TBL_FIELD_FIGHT_FORCE => SortByFieldFunc::ASC, GuildWarUserField::TBL_FIELD_UID => SortByFieldFunc::ASC));
		usort($arrUserInfo, array($sortCmp, 'cmp'));		
		$arrEstimateFinalFighters = array_slice($arrUserInfo, ($subRound - 1) * GuildWarConf::ONE_TIME_PLAYER, GuildWarConf::ONE_TIME_PLAYER);
		$ret = Util::arrayExtract($arrEstimateFinalFighters, GuildWarUserField::TBL_FIELD_UID);
		
		Logger::trace('GuildWarServerObj.getEstimateFinalFighters $arrUserInfo[%s], ret[%s] end...', $arrUserInfo, $ret);
		return $ret;
	}
	
	public function getGuildId()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_ID];
	}
	
	public function getGuildLevel()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_LEVEL];
	}
	
	public function getGuildBadge()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_BADGE];
	}
	
	public function getGuildName()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_NAME];
	}
	
	public function getServerId()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID];
	}
	
	public function getBasicInfo()
	{
		return array
		(
				'guild_id' => $this->getGuildId(),
				'guild_name' => $this->getGuildName(),
				'guild_server_id' => $this->getServerId(),
				'guild_server_name' => $this->getServerName(),
				'guild_badge' => $this->getGuildBadge(),
		);
	}
	
	public function getServerName()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_GUILD_SERVER_NAME];
	}
	
	public function getFightForce()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_FIGHT_FORCE];
	}
	
	public function setFightForce($fightForce)
	{
		$this->mObjModify[GuildWarServerField::TBL_FIELD_FIGHT_FORCE] = $fightForce;
	}
	
	public function getLastFightTime()
	{
		return $this->mObjModify[GuildWarServerField::TBL_FIELD_LAST_FIGHT_TIME];
	}
	
	private function setLastFightTime($time = 0)
	{
		if ($time == 0) 
		{
			$time = Util::getTime();
		}
		
		$this->mObjModify[GuildWarServerField::TBL_FIELD_LAST_FIGHT_TIME] = $time;
	}
	
	public function getServerDb()
	{
		return GuildWarUtil::getServerDbByServerId($this->getServerId());
	}
	
	public function getServerGroup()
	{
		return Util::getGroupByServerId($this->getServerId());
	}
	
	public function getAllMember()
	{
		return Util::arrayExtract(EnGuild::getMemberList($this->getGuildId(), array(GuildDef::USER_ID), $this->getServerDb()), GuildDef::USER_ID);
	}
	
	public function sendMail($round, $isWin, $objGuildName, $objServerName)
	{
		$arrUid = $this->getAllMember();
		$lcProxyObj = new ServerProxy();
		$lcProxyObj->init($this->getServerGroup(), Util::genLogId());
		$lcProxyObj->asyncExecuteRequest(SPECIAL_UID::GUILD_WAR, 'guildwar.sendMail', array($arrUid, $round, $this->getFinalRank(), $isWin, $objGuildName, $objServerName));
		Logger::trace('GuildWarServerObj.sendMail for arrUid[%s], round[%d], rank[%d], isWin[%d], objGuildName[%s], objServerName[%s]', 
					$arrUid, $round, $this->getFinalRank(), $isWin, $objGuildName, $objServerName);
	}
	
	public function update()
	{
		if ($this->mObjModify == $this->mObj)
		{
			Logger::trace('GuildWarServerObj.update, nothing change, no need update, mObjModify[%s]', $this->mObjModify);
			return;
		}
		
		$arrUpdate = array();
		foreach ($this->mObjModify as $key => $info)
		{
			if($info != $this->mObj[$key])
			{
				$arrUpdate[$key] = $info;
			}
		}
		
		if (empty($arrUpdate))
		{
			Logger::fatal('GuildWarServerObj.update, nothing change? mObjModify[%s]', $this->mObjModify);
			return;
		}
		
		$arrCond = array
		(
				array(GuildWarServerField::TBL_FIELD_SESSION, '=', $this->mSession),
				array(GuildWarServerField::TBL_FIELD_GUILD_SERVER_ID, '=', $this->mServerId),
				array(GuildWarServerField::TBL_FIELD_GUILD_ID, '=', $this->mGuildId),
		);
		GuildWarDao::updateGuildWarServer($arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
