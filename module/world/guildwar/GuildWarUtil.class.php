<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarUtil.class.php 159920 2015-03-03 15:35:08Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/GuildWarUtil.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-03 15:35:08 +0000 (Tue, 03 Mar 2015) $
 * @version $Revision: 159920 $
 * @brief 
 *  
 **/
 
class GuildWarUtil
{
	/**
	 * 获取teamId
	 * 
	 * @param number $session
	 * @param number $serverId
	 * @return number
	 */
	public static function getTeamIdByServerId($session, $serverId)
	{
		$teamId = TeamManager::getInstance(WolrdActivityName::GUILDWAR, $session)->getTeamIdByServerId($serverId);
		return intval($teamId) <= 0 ? 0 : $teamId;
	}
	
	/**
	 * 获得serverId
	 * 
	 * @return number
	 */
	public static function getMinServerId()
	{
		$serverName = RPCContext::getInstance()->getFramework()->getGroup();
		return Util::getServerIdByGroup($serverName);
	}
	
	/**
	 * 获得这届跨服军团战所有的teamId
	 * 
	 * @param number $session
	 * @return array:
	 */
	public static function getAllTeamId($session)
	{
		return array_keys(TeamManager::getInstance(WolrdActivityName::GUILDWAR, $session)->getAllTeam());
	}
	
	/**
	 * 获得这届跨服军团战所有的teamId以及每个team包含的server
	 *
	 * @param number $session
	 * @return array:
	 */
	public static function getAllTeamData($session)
	{
		return TeamManager::getInstance(WolrdActivityName::GUILDWAR, $session)->getAllTeam();
	}
	
	/**
	 * 检查分组信息是否正确
	 * 
	 * @param int $session
	 */
	public static function checkTeamDistributionCross($session)
	{
		return TeamManager::getInstance(WolrdActivityName::GUILDWAR, $session)->checkTeamDistributionCross();
	}
	
	/**
	 * 根据serverId返回serverName
	 * 
	 * @param int $serverId
	 * @return int
	 */
	public static function getServerNameByServerId($serverId)
	{
		return ServerInfoManager::getInstance()->getServerNameByServerId($serverId);
	}
	
	/**
	 * 根据serverId返回db
	 * 
	 * @param int $serverId
	 * @return int
	 */
	public static function getServerDbByServerId($serverId)
	{
		return ServerInfoManager::getInstance()->getDbNameByServerId($serverId);
	}
	
	/**
	 * 根据一组serverId，获取一组db
	 * 
	 * @param array $arrServerId
	 * @return array
	 */
	public static function getArrServerDbByArrServerId($arrServerId)
	{
		return ServerInfoManager::getInstance()->getArrDbName($arrServerId);
	}
	
	/**
	 * 修改传入战斗模块的参数，修改uid和hid，增加偏移量，让其不一致
	 * 
	 * @param array $battleFmt
	 * @param int $offset
	 * @return array
	 */
	public static function changeIdsForBattleFmt($battleFmt, $offset)
	{
		foreach ($battleFmt['members'] as $key => $value)
		{
			$battleFmt['members'][$key]['uid'] = $value['uid'] * 10 + $offset;
			foreach ($value['arrHero'] as $pos => $hero)
			{
				$battleFmt['members'][$key]['arrHero'][$pos]['hid'] = $hero['hid'] * 10 + $offset;
			}
		}
		
		if (isset($battleFmt['mapUidInitWin'])) 
		{
			foreach ($battleFmt['mapUidInitWin'] as $aUid => $initWin)
			{
				$battleFmt['mapUidInitWin'][$aUid * 10 + $offset] = $initWin;
				unset($battleFmt['mapUidInitWin'][$aUid]);
			}
		}
		
		return $battleFmt;
	}
	
	/**
	 * 返回修改前的uid或者hid
	 *
	 * @param int $fakeId
	 * @return int
	 */
	public static function reChangeID($fakeId)
	{
		return (($fakeId % 2) == 1 ? ($fakeId - 1) / 10 : ($fakeId - 2) / 10);
	}
	
	/**
	 * 判断serverId是不是在本服上
	 * 
	 * @param int $serverId
	 * @return boolean
	 */
	public static function isMyServer($serverId)
	{
		$group = RPCContext::getInstance ()->getFramework()->getGroup();
		if (empty($group))
		{
			return FALSE;
		}
	
		$arrServerId = Util::getAllServerId();
		return in_array($serverId, $arrServerId);
	}
	
	/**
	 * 获得跨服db
	 * 
	 * @return string
	 */
	public static function getCrossDbName()
	{
		return GuildWarDef::GDW_DB_PREFIX . PlatformConfig::PLAT_NAME;
	}

	/**
	 * 能否更新战斗力，能够清除更新战斗力cd
	 * 
	 * @param int $time
	 * @param int $session
	 * @param int $teamId
	 * @param int $cdTime 如果可以更新战斗力，返回更新完后的cd时间
	 * @return boolean
	 */
	public static function canUpdateFmt($time, $session, $teamId, &$cdTime)
	{
		if ($time == 0)
		{
			$time = Util::getTime();
		}
				
		$confObj = GuildWarConfObj::getInstance();		
		$session = $confObj->getSession();
		
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		$curSubRound = $teamObj->getCurSubRound();
		$curSubStatus = $teamObj->getCurSubStatus();
		
		if ($curRound == GuildWarRound::SIGNUP)
		{	
			$auditionStartTime = $confObj->getRoundStartTime(GuildWarRound::AUDITION);
			$auditionUpdLimit = $confObj->getAuditionUpdLimit();
			if ($time < ($auditionStartTime - $auditionUpdLimit)) // 【报名开始】 到 【 海选赛开始时间前3分钟（举例）】这段时间可以更新战斗数据
			{
				$cdTime = $confObj->getAuditionUpdCd();
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], auditionStartTime[%s], auditionUpdLimit[%d], return TRUE, cdTime[%d]',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $auditionStartTime), $auditionUpdLimit, $cdTime);
				return TRUE;
			}
			else // 【 海选赛开始时间前3分钟（举例）】 到 【海选赛开始】这段时间不可以更新战斗数据
			{
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], auditionStartTime[%s], auditionUpdLimit[%d], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $auditionStartTime), $auditionUpdLimit);
				return FALSE;
			}
		}
		else if ($curRound == GuildWarRound::AUDITION) 
		{
			$auditioneEndTime = $confObj->getRoundEndTime(GuildWarRound::AUDITION);
			if ($time <= $auditioneEndTime) // 配置的海选赛结束时间还没有到，不能更新战斗数据 
			{
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], auditioneEndTime[%s], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $auditioneEndTime));
				return FALSE;
			}
			
			if ($curStatus != GuildWarStatus::DONE) // 海选赛没有打完，不能更新战斗数据
			{
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], auditioneEndTime[%s], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $auditioneEndTime));
				return FALSE;
			}
			
			$finals16StartTime = $confObj->getRoundStartTime(GuildWarRound::ADVANCED_16);
			$finalUpdLimit = $confObj->getFinalsUpdLimit();
			if ($time < ($finals16StartTime - $finalUpdLimit)) // 【海选结束】 到 【 晋级赛开始时间前10分钟（举例）】这段时间可以更新战斗数据
			{
				$cdTime = $confObj->getFinalsUpdCd();
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], finals16StartTime[%s], finalUpdLimit[%d], return TRUE, cdTime[%d]',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $finals16StartTime), $finalUpdLimit, $cdTime);
				return TRUE;
			}
			else //【 晋级赛开始时间前10分钟（举例）】 到 【晋级赛开始】这段时间不可以更新战斗数据
			{
				Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], finals16StartTime[%s], finalUpdLimit[%d], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $finals16StartTime), $finalUpdLimit);
				return FALSE;
			}
		}
		else if ($curRound >= GuildWarRound::ADVANCED_16) 
		{
			if ($curStatus >= GuildWarStatus::FIGHTEND) 
			{
				$curRoundEndTime = $confObj->getRoundEndTime($curRound);
				if ($time < $curRoundEndTime) 
				{
					$cdTime = $confObj->getFinalsUpdCd();
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], curRoundEndTime[%s], return TRUE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $curRoundEndTime));
					return TRUE;
				}
				
				$nextRound = $confObj->getNextRound($curRound);
				if ($nextRound == $curRound)//当前大轮是最后一大轮
				{
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], curRoundEndTime[%s], nextRound[%d], return FALSE',
								strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $curRoundEndTime), $nextRound);
					return FALSE;
				}
				$nextRoundStartTime = $confObj->getRoundStartTime($nextRound);
				$finalUpdLimit = $confObj->getFinalsUpdLimit();
				if ($time < ($nextRoundStartTime - $finalUpdLimit)) // 【这轮结束】 到 【 下轮开始时间前10分钟（举例）】这段时间可以更新战斗数据
				{
					$cdTime = $confObj->getFinalsUpdCd();
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], curRoundEndTime[%s], nextRound[%d], nextRoundStartTime[%s], finalUpdLimit[%d], return TRUE, cdTime[%d]',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $curRoundEndTime), $nextRound, strftime('%Y%m%d-%H%M%S', $nextRoundStartTime), $finalUpdLimit, $cdTime);
					return TRUE;
				}
				else //【下轮开始时间前10分钟（举例）】 到 【下轮开始】这段时间不可以更新战斗数据
				{
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], curRoundEndTime[%s], nextRound[%d], nextRoundStartTime[%s], finalUpdLimit[%d], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $curRoundEndTime), $nextRound, strftime('%Y%m%d-%H%M%S', $nextRoundStartTime), $finalUpdLimit);
					return FALSE;
				}
			}
			else 
			{
				if ($curSubStatus != GuildWarSubStatus::FIGHTEND) // 这个小轮正在战斗，不能更新战斗数据 
				{
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus);
					return FALSE;
				}
				
				$nextSubRound = $confObj->getNextSubRound($curSubRound);
				/*if ($nextSubRound == $curSubRound) 
				{
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], nextSubRound[%d] return FALSE',
								strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, $nextSubRound);
					return FALSE;
				}*/
				$nextSubRoundStartTime = $confObj->getSubRoundStartTime($curRound, $nextSubRound);
				$finalTeamUpdLimit = $confObj->getFinalsTeamUpdLimit();
				if ($time < ($nextSubRoundStartTime - $finalTeamUpdLimit)) // 【这小轮结束】 到 【 下小轮开始时间前3分钟（举例）】这段时间可以更新战斗数据
				{
					$cdTime = $confObj->getFinalsTeamUpdCd();
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], nextSubRound[%d], nextSubRoundStartTime[%s], finalTeamUpdLimit[%s], return TRUE, cdTime[%d]',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, $nextSubRound, strftime('%Y%m%d-%H%M%S', $nextSubRoundStartTime), $finalTeamUpdLimit, $cdTime);
					return TRUE;
				}
				else //【下小轮开始时间前10分钟（举例）】 到 【下小轮开始】这段时间不可以更新战斗数据
				{
					Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], nextSubRound[%d], nextSubRoundStartTime[%s], finalTeamUpdLimit[%s], return FALSE',
							strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus, $nextSubRound, strftime('%Y%m%d-%H%M%S', $nextSubRoundStartTime), $finalTeamUpdLimit);
					return FALSE;
				}
			}
		}
		
		Logger::trace('GuildWarUtil::canUpdateFmt, time[%s], session[%d], teamId[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], return FALSE',
					strftime('%Y%m%d-%H%M%S', $time), $session, $teamId, $curRound, $curStatus, $curSubRound, $curSubStatus);			
		return FALSE; // 其他所有阶段都不能更新战斗数据
	}
	
	/**
	 * 判断一个操作是否在正确的时间段内，根据配置
	 * 
	 * @param int $session
	 * @param int $teamId
	 * @param int $type
	 * @param int $time
	 * @return boolean
	 */
	public static function checkStage($session, $teamId, $type, $time = 0)
	{
		// 检测的时间
		if ($time == 0)
		{
			$time = time();//用实际的时间
		}
		
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		
		// 获得进度对象
		$procedureObj = GuildWarProcedureObj::getInstance($session);
		$teamObj = $procedureObj->getTeamObj($teamId);
		
		// 当前大轮次和大轮次状态
		$curRound = $teamObj->getCurRound();
		$curStatus = $teamObj->getCurStatus();
		
		// 当前小轮次和小轮次状态
		$curSubRound = $teamObj->getCurSubRound();
		$curSubStatus = $teamObj->getCurSubStatus();
		
		// 报名阶段-海选赛开始
		if ($curRound == GuildWarRound::SIGNUP)
		{
			// 在报名时间内
			if ($confObj->inSignUpTime($time)) 
			{
				if (in_array($type, GuildWarConf::$checkStage['BetweenSignUp'])) 
				{
					if (GuildWarConf::$checkStage['BetweenSignUp'][$type]) 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenSignUp], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], signStartTime[%s], signEndTime[%s], return TRUE', 
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpStartTime()), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpEndTime()));
						return TRUE;
					}
					else 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenSignUp], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], signStartTime[%s], signEndTime[%s], return FALSE', 
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpStartTime()), strftime('%Y%m%d-%H%M%S', $confObj->getSignUpEndTime()));
						return FALSE;
					}
				}
			}
			
			// 报名结束，海选赛开始前
			$auditionStartTime = $confObj->getRoundStartTime(GuildWarRound::AUDITION);
			if ($time < $auditionStartTime)
			{
				if (in_array($type, GuildWarConf::$checkStage['BeforeAuditionStart'])) 
				{
					if (GuildWarConf::$checkStage['BeforeAuditionStart'][$type]) 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAuditionStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], $auditionStartTime[%s], return TRUE', 
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $auditionStartTime));
						return TRUE;
					}
					else 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAuditionStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], $auditionStartTime[%s], return FALSE', 
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $auditionStartTime));
						return FALSE;
					}
				}
			}
			else
			{
				throw new FakeException('GuildWarUtil::checkStage, something wrong, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], $auditionStartTime[%s], return FALSE',
							$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $auditionStartTime));
				//return FALSE;
			}
		}
		// 海选赛阶段
		else if ($curRound == GuildWarRound::AUDITION)
		{
			// 海选赛还没有开始打
			if ($curStatus < GuildWarStatus::FIGHTING) 
			{
				throw new FakeException('GuildWarUtil::checkStage, something wrong, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return FALSE',
							$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
				//return FALSE;
			}
			// 海选赛还没有打完
			else if ($curStatus == GuildWarStatus::FIGHTING) 
			{
				if (in_array($type, GuildWarConf::$checkStage['BetweenAudition']))
				{
					if (GuildWarConf::$checkStage['BetweenAudition'][$type])
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenAudition], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return TRUE',
									$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
						return TRUE;
					}
					else
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenAudition], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return FALSE',
									$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
						return FALSE;
					}
				}
			}
			else
			{
				// 海选赛已经打完-海选赛结束时间
				$auditionEndTime = $confObj->getRoundEndTime(GuildWarRound::AUDITION);
				if ($time < $auditionEndTime)
				{
					if (in_array($type, GuildWarConf::$checkStage['BeforeAuditionEnd']))
					{
						if (GuildWarConf::$checkStage['BeforeAuditionEnd'][$type])
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAuditionEnd], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], auditionEndTime[%s], return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $auditionEndTime));
							return TRUE;
						}
						else
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAuditionEnd], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], auditionEndTime[%s], return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $auditionEndTime));
							return FALSE;
						}
					}
				}
				
				// 海选赛打完到晋级赛开始
				$finals16StartTime = $confObj->getRoundStartTime(GuildWarRound::ADVANCED_16);
				if ($time < $finals16StartTime)
				{
					if (in_array($type, GuildWarConf::$checkStage['BeforeAdvancedStart']))
					{
						if (GuildWarConf::$checkStage['BeforeAdvancedStart'][$type])
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAdvancedStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], finals16StartTime[%s], return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $finals16StartTime));
							return TRUE;
						}
						else
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAdvancedStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], finals16StartTime[%s], return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $finals16StartTime));
							return FALSE;
						}
					}
				}
				else
				{
					throw new FakeException('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], finals16StartTime[%s], return FALSE',
								$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $finals16StartTime));
					//return FALSE;
				}
			}
		}
		// 晋级赛阶段
		else if ($curRound >= GuildWarRound::ADVANCED_16)
		{
			// 这几个操作只要是晋级赛都可以
			if ($type == GuildWarConf::CHECK_TYPE_GET_GUILD_WAR_INFO
				|| $type == GuildWarConf::CHECK_TYPE_GET_HISTORY_FIGHT_INFO
				|| $type == GuildWarConf::CHECK_TYPE_GET_HISTORY_CHEER_INFO
				|| $type == GuildWarConf::CHECK_TYPE_GET_REPLAY)
			{
				return TRUE;
			}
			
			// 大轮正在打
			if ($curStatus == GuildWarStatus::FIGHTING) 
			{
				// 小轮正在打
				if ($curSubStatus == GuildWarSubStatus::FIGHTING)
				{
					if (in_array($type, GuildWarConf::$checkStage['BetweenSubRound']))
					{
						if (GuildWarConf::$checkStage['BetweenSubRound'][$type])
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenSubRound], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
							return TRUE;
						}
						else
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BetweenSubRound], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
							return FALSE;
						}
					}
				}
				
				// 这个小轮打完啦，下个下轮还没有开始
				$nextSubRound = $confObj->getNextSubRound($curSubRound);
				$nextSubRoundStartTime = $confObj->getSubRoundStartTime($curRound, $nextSubRound);
				if ($nextSubRound == $curSubRound)//当前小轮是最后一个小轮 
				{
					$nextSubRoundStartTime += $confObj->getFinalsGap();
				}
				if ($time < $nextSubRoundStartTime)
				{
					if (in_array($type, GuildWarConf::$checkStage['BeforeNextSubRound']))
					{
						if (GuildWarConf::$checkStage['BeforeNextSubRound'][$type])
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeNextSubRound], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextSubRound[%d], nextSubRoundStartTime[%s] return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextSubRound, strftime('%Y%m%d-%H%M%S', $nextSubRoundStartTime));
							return TRUE;
						}
						else
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeNextSubRound], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextSubRound[%d], nextSubRoundStartTime[%s] return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextSubRound, strftime('%Y%m%d-%H%M%S', $nextSubRoundStartTime));
							return FALSE;
						}
					}
				}
				else
				{
					throw new FakeException('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextSubRound[%d], nextSubRoundStartTime[%s] return FALSE',
								$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextSubRound, strftime('%Y%m%d-%H%M%S', $nextSubRoundStartTime));
					//return FALSE;
				}
			}
			else
			{
				// 这两个操作要特殊处理下
				if ($type == GuildWarConf::CHECK_TYPE_GET_TEMPLE_INFO
					|| $type == GuildWarConf::CHECK_TYPE_WORSHIP) 
				{
					if ($curRound == GuildWarRound::ADVANCED_2) 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return TRUE',
									$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
						return TRUE;
					}
					else 
					{
						Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], return FALSE',
									$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
						return FALSE;
					}
				}
				
				// 大轮打完啦，但是大轮的结束时间还没有到
				$curRoundEndTime = $confObj->getRoundEndTime($curRound);
				if ($time < $curRoundEndTime)
				{
					if (in_array($type, GuildWarConf::$checkStage['BeforeAdvancedEnd'])) 
					{
						if (GuildWarConf::$checkStage['BeforeAdvancedEnd'][$type]) 
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAdvancedEnd], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], curRoundEndTime[%s] return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $curRoundEndTime));
							return TRUE;
						}
						else 
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeAdvancedEnd], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], curRoundEndTime[%s] return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), strftime('%Y%m%d-%H%M%S', $curRoundEndTime));	
							return FALSE;
						}
					}
				}
				
				// 下个晋级赛还没有开始
				$nextRound = $confObj->getNextRound($curRound);
				$nextRoundStartTime = $confObj->getRoundStartTime($nextRound);
				if ($nextRound == $curRound)//当前大轮是最后一大轮
				{
					$nextRoundStartTime = $confObj->getActivityEndTime();
				}
				if ($time < $nextRoundStartTime)
				{
					if (in_array($type, GuildWarConf::$checkStage['BeforeNextAdvancedStart'])) 
					{
						if (GuildWarConf::$checkStage['BeforeNextAdvancedStart'][$type]) 
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeNextAdvancedStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextRound[%d], nextRoundStartTime[%s] return TRUE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextRound, strftime('%Y%m%d-%H%M%S', $nextRoundStartTime));	
							return TRUE;
						}
						else 
						{
							Logger::trace('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[BeforeNextAdvancedStart], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextRound[%d], nextRoundStartTime[%s] return FALSE',
										$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextRound, strftime('%Y%m%d-%H%M%S', $nextRoundStartTime));
							return FALSE;
						}
					}
				}
				else
				{
					throw new FakeException('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s], nextRound[%d], nextRoundStartTime[%s] return FALSE',
								$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time), $nextRound, strftime('%Y%m%d-%H%M%S', $nextRoundStartTime));
					//return FALSE;
				}
			}
		}
		
		throw new FakeException('GuildWarUtil::checkStage, session[%d], teamId[%d], stage[undefined], checkType[%d], curRound[%d], curStatus[%d], curSubRound[%d], curSubStatus[%d], time[%s] return FALSE',
					$session, $teamId, $type, $curRound, $curStatus, $curSubRound, $curSubStatus, strftime('%Y%m%d-%H%M%S', $time));
		//return FALSE;
	}
	
	/**
	 * 判断一个操作是否满足条件，根据配置
	 * 
	 * @param int $type
	 * @param int $uid
	 * @throws FakeException
	 */
	public static function checkBasic($type, $uid)
	{
		// 获得配置对象
		$confObj = GuildWarConfObj::getInstance();
		
		// 检查session
		if (in_array($type, GuildWarConf::$checkType['checkInSession'])) 
		{
			$session = $confObj->getSession();
			if (empty($session))
			{
				throw new FakeException('GuildWarUtil::checkBasic failed, not in any session.');
			}
		}
		
		// 玩家所在服是否在一个组内
		if (in_array($type, GuildWarConf::$checkType['checkInTeam']))
		{
			$serverId =GuildWarUtil::getMinServerId();
			$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
			if (empty($teamId))
			{
				throw new FakeException('GuildWarUtil::checkBasic failed, not in any team, serverId[%d].', $serverId);
			}
		}
		
		// 检查玩家是否在一个军团
		if (in_array($type, GuildWarConf::$checkType['checkInGuild']))
		{
			$userObj = EnUser::getUserObj($uid);
			$guildId = $userObj->getGuildId();
			if (empty($guildId))
			{
				throw new FakeException('GuildWarUtil::checkBasic failed, not in any guild.');
			}
		}
		
		// 检查玩家所在军团是否报名
		if (in_array($type, GuildWarConf::$checkType['checkIsSignUp']))
		{
			$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
			if (!$guildWarServerObj->isSignUp())
			{
				throw new FakeException('GuildWarUtil::checkBasic failed, guild not sign up.');
			}
		}
		
		// 是否过了查看战斗信息的冷却时间，现在配置的是300秒
		if (in_array($type, GuildWarConf::$checkType['checkCdAfterSignUp']))
		{
			$signUpTime = $guildWarServerObj->getSignUpTime();
			$curTime = Util::getTime();
			if ($curTime < ($signUpTime + GuildWarConf::CD_AFTER_SIGN_UP_TIME))
			{
				throw new FakeException("GuildWarUtil::checkBasic failed, in cd after sign up, curTime[%s], cdTime[%d], signUpTime[%s].", strftime('%Y%m%d-%H%M%S', $curTime), GuildWarConf::CD_AFTER_SIGN_UP_TIME, strftime('%Y%m%d-%H%M%S', $signUpTime));
			}
		}
		
		// 检查玩家是否在候选范围内
		if (in_array($type, GuildWarConf::$checkType['checkInCandidates']))
		{
			if (!$guildWarServerObj->isCandidate($uid))
			{
				throw new FakeException("GuildWarUtil::checkBasic failed, not candidate.");
			}
		}
		
		// 检查玩家是否初始化
		if (in_array($type, GuildWarConf::$checkType['checkIsArmed']))
		{
			$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid);
			if (!$guildWarUserObj->isArmed())
			{
				throw new FakeException("GuildWarUtil::checkBasic failed, guild user not armed.");
			}
		}
		
		// 检查是不是军团长
		if (in_array($type, GuildWarConf::$checkType['checkIsPresident']))
		{
			$guildMemberObj = GuildMemberObj::getInstance($uid);
			if ($guildMemberObj->getMemberType() != GuildMemberType::PRESIDENT)
			{
				throw new FakeException("GuildWarUtil::checkBasic failed, not president.");
			}
		}
	}
	
	/**
	 * 获得memcache中军团报名时间的key
	 * 
	 * @param int $guildId
	 * @param int $serverId
	 * @return string
	 */
	public static function getSignUpTimeMemKey($guildId, $serverId)
	{
		return 'guildwar_signup_time_' . $guildId . '_' . $serverId;
	}
	
	/**
	 * 设置memcache中军团报名时间
	 * 
	 * @param int $guildId
	 * @param int $serverId
	 * @param int $signUpTime
	 */
	public static function setSignUpTimeInMem($guildId, $serverId, $signUpTime)
	{
		$key = self::getSignUpTimeMemKey($guildId, $serverId);
		if (McClient::set($key, $signUpTime, SECONDS_OF_DAY) != 'STORED')
		{
			Logger::warning('GuildWarUtil::setSignUpTimeInMem failed, key[%s], value[%s]', $key, $signUpTime);
		}
	}
	
	/**
	 * 获取memcache中军团报名时间
	 * 
	 * @param int $guildId
	 * @param int $serverId
	 * @return int
	 */
	public static function getSignUpTimeInMem($guildId, $serverId)
	{
		$key = self::getSignUpTimeMemKey($guildId, $serverId);
		$ret = McClient::get($key);
		return empty($ret) ? 0 : intval($ret);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */