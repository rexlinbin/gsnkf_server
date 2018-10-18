<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalLogic.class.php 199617 2015-09-21 11:17:56Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalLogic.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-21 11:17:56 +0000 (Mon, 21 Sep 2015) $
 * @version $Revision: 199617 $
 * @brief 
 *  
 **/
 
class WorldCarnivalLogic
{
	/**
	 * 获得跨服嘉年华的信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @throws ConfigException
	 * @throws InterException
	 * @return array
	 */
	public static function getCarnivalInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家的serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCarnivalUtil::getPid($uid);
		
		// 检查是否在一届比赛中
		$confObj = WorldCarnivalConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session)) 
		{
			throw new FakeException('not in any session');
		}
		
		// 检查玩家的类别，是参赛者，围观者或者其他，其他则返回
		if (!$confObj->isFighter($serverId, $pid)
			&& !$confObj->isWatcher($serverId, $pid)) 
		{
			return array('ret' => 'invalid');
		}
		
		// 检查玩家是否可视
		if (!$confObj->isVisible($serverId, $pid)) 
		{
			return array('ret' => 'invalid');
		}		  
		
		// 获得进度管理对象
		$procedureObj = WorldCarnivalProcedureObj::getInstance($session, $confObj->getActivityStartTime());
		
		// 判断是否是活动开打前的阶段
		$isBeforeFight = FALSE;
		if (Util::getTime() >= $confObj->getActivityStartTime()
			&& Util::getTime() < $confObj->getBeginTime(WorldCarnivalRound::ROUND_1, 1)) 
		{
			$isBeforeFight = TRUE;
		}
		
		// 拉取进度数据
		$ret = array();
		$ret['ret'] = $confObj->isFighter($serverId, $pid) ? 'fighter' : 'watcher';
		$ret['round'] = $isBeforeFight ? 0 : $procedureObj->getCurRound();//当前时间还没有到开始比赛的时间，给前端返回阶段0
		$ret['status'] = $isBeforeFight ? 0 : $procedureObj->getCurStatus();
		$ret['sub_round'] = $isBeforeFight ? 0 : $procedureObj->getCurSubRound();
		$ret['sub_status'] = $isBeforeFight ? 0 : $procedureObj->getCurSubStatus();
		$ret['next_fight_time'] = $procedureObj->getNextFightTime($confObj->getBeginTime(), $confObj->getNormalPeriod(), $confObj->getFinalPeriod());
		$ret['normal_period'] = $confObj->getNormalPeriod();
		$ret['final_period'] = $confObj->getFinalPeriod();
		
		// 拉取参赛者基础信息
		$arrFighters = $confObj->getFighters();
		$arrServerId = array_unique(Util::arrayExtract($arrFighters, 'server_id'));
		$arrServerNameInfo = ServerInfoManager::getInstance(WorldCarnivalUtil::getCrossDbName())->getArrServerName($arrServerId);
		$arrFighterBasicInfo = array();
		foreach ($arrFighters as $aPos => $aFighter)
		{
			$aServerId = $aFighter['server_id'];
			$aPid = $aFighter['pid'];
			$aUserObj = WorldCarnivalCrossUserObj::getInstance($aServerId, $aPid, $confObj->getActivityStartTime());
			
			// 获得服务器名称
			if (!isset($arrServerNameInfo[$aServerId]))
			{
				throw new ConfigException('not valid server id[%d], no server name info', $aServerId);
			}
			$aServerName =  $arrServerNameInfo[$aServerId];

			//TODO 现在是人数少，一个一个拉，如果多的话，可以批量拉
			$arrBasicUserInfo = array();
			try
			{
				$group = Util::getGroupByServerId($aServerId);
				$proxy = new ServerProxy();
				$proxy->init($group, Util::genLogId());
				$arrBasicUserInfo = $proxy->syncExecuteRequest('worldcarnival.getUserBasicInfo', array($aServerId, $aPid));
			}
			catch (Exception $e)
			{
				Logger::fatal('worldcarnival.getUserBasicInfo error serverGroup:%s', $aServerId);
				$arrBasicUserInfo = array();
			}
			if (empty($arrBasicUserInfo)) 
			{
				throw new InterException('not valid server id[%d] or pid[%d], no user info', $aServerId, $aPid);
			}
			
			$arrFighterBasicInfo[$aPos] = array
			(
					'rank' => $aUserObj->getRank(),
					'server_id' => $aServerId,
					'server_name' => $aServerName,
					'pid' => $aPid,
					'uname' => $arrBasicUserInfo['uname'],
					'htid' => $arrBasicUserInfo['htid'],
					'level' => $arrBasicUserInfo['level'],
					'vip' => $arrBasicUserInfo['vip'],
					'fight_force' => intval($aUserObj->getFightForce()),
					'dress' => $arrBasicUserInfo['dress'],
			);
		}
		$ret['fighters'] = $arrFighterBasicInfo;
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 参赛者更新战斗信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function updateFmt($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家的serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCarnivalUtil::getPid($uid);
		
		// 检查是否在一届比赛中
		$confObj = WorldCarnivalConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new FakeException('not in any session');
		}
		
		// 检查玩家是否是参赛者
		if (!$confObj->isFighter($serverId, $pid))
		{
			throw new FakeException('not a fighter');
		}
		
		// 检查玩家是否可视
		if (!$confObj->isVisible($serverId, $pid))
		{
			throw new FakeException('not visible');
		}
		
		// 更新战斗信息
		$aUserObj = WorldCarnivalCrossUserObj::getInstance($serverId, $pid, $confObj->getActivityStartTime());
		$aUserObj->updateFmt(EnUser::getUserObj($uid)->getBattleFormation());
		$aUserObj->update();
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 获得某个大轮次的战报详情
	 * 
	 * @param int $uid
	 * @param int $round
	 * @throws FakeException
	 * @return array
	 */
	public static function getRecord($uid, $round)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家的serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCarnivalUtil::getPid($uid);
		
		// 检查是否在一届比赛中
		$confObj = WorldCarnivalConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new FakeException('not in any session');
		}
		
		// 检查玩家的类别，是参赛者，围观者或者其他，其他则返回
		if (!$confObj->isFighter($serverId, $pid)
			&& !$confObj->isWatcher($serverId, $pid))
		{
			throw new FakeException('not a fighter or watcher');
		}
		
		// 检查玩家是否可视
		if (!$confObj->isVisible($serverId, $pid))
		{
			throw new FakeException('not visible');
		}
		
		// 获得战报列表
		$ret = array();
		$procedureObj = WorldCarnivalProcedureObj::getInstance($session, $confObj->getActivityStartTime());
		$ret = $procedureObj->getBattleRecord($round);
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 获得某个参赛者的阵容信息
	 * 
	 * @param int $uid
	 * @param int $aServerId
	 * @param int $aPid
	 * @throws FakeException
	 * @throws InterException
	 * @return array
	 */
	public static function getFighterDetail($uid, $aServerId, $aPid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家的serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCarnivalUtil::getPid($uid);
		
		// 检查是否在一届比赛中
		$confObj = WorldCarnivalConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new FakeException('not in any session');
		}
		
		// 检查玩家的类别，是参赛者，围观者或者其他，其他则返回
		if (!$confObj->isFighter($serverId, $pid)
			&& !$confObj->isWatcher($serverId, $pid))
		{
			throw new FakeException('not a fighter or watcher');
		}
		
		// 检查玩家是否可视
		if (!$confObj->isVisible($serverId, $pid))
		{
			throw new FakeException('not visible');
		}
		
		// 检查要查看的玩家是不是参赛者
		if (!$confObj->isFighter($aServerId, $aPid)) 
		{
			throw new FakeException('not fighter of param');
		}
		
		// 调对方的各种阵容数据
		$ret = array();
		try
		{
			$group = Util::getGroupByServerId($aServerId);
			$proxy = new ServerProxy();
			$proxy->init($group, Util::genLogId());
			$ret = $proxy->syncExecuteRequest('worldcarnival.getBattleDataOfUsers', array($aServerId, $aPid));
		}
		catch (Exception $e)
		{
			Logger::fatal('worldcarnival.getBattleDataOfUsers error serverGroup:%s', $serverId);
			$ret = array();
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 获得玩家的战斗数据，用于战斗的时候跨服机实时拉取
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @throws InterException
	 * @return array
	 */
	public static function getBattleFmt($serverId, $pid)
	{
		// 获得uid
		$arrUserInfo = UserDao::getArrUserByArrPid(array($pid), array('uid'));
		if (empty($arrUserInfo))
		{
			throw new InterException('not valid server id[%d] or pid[%d], no user info', $serverId, $pid);
		}
		$uid = $arrUserInfo[0]['uid'];
		
		// 获得玩家的战斗数据
		$userObj = EnUser::getUserObj($uid);
		$battleFmt = $userObj->getBattleFormation();
		
		return $battleFmt;
	}
	
	/**
	 * 获取玩家的阵容信息
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @throws InterException
	 * @return array
	 */
	public static function getBattleDataOfUsers($serverId, $pid)
	{
		// 获得uid
		$arrUserInfo = UserDao::getArrUserByArrPid(array($pid), array('uid'));
		if (empty($arrUserInfo))
		{
			throw new InterException('not valid server id[%d] or pid[%d], no user info', $serverId, $pid);
		}
		$uid = $arrUserInfo[0]['uid'];
		
		$user = new User();
		return $user->getBattleDataOfUsers(array($uid));
	}
	
	/**
	 * 给一批pid推送数据
	 * 
	 * @param int $serverId
	 * @param array $arrPid
	 * @param array $arrData
	 */
	public static function push($serverId, $arrPid, $arrData)
	{
		// 获得uid
		$arrUserInfo = UserDao::getArrUserByArrPid($arrPid, array('uid'));
		$arrUid = Util::arrayExtract($arrUserInfo, 'uid');
		
		// 推送
		RPCContext::getInstance()->sendMsg($arrUid, PushInterfaceDef::WORLD_CARNIVAL_PUSH_STATUS, $arrData);
		return 'ok';
	}
	
	/**
	 * 根据玩家的serverId和pid获取玩家的基础信息
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @throws InterException
	 * @return array
	 */
	public static function getUserBasicInfo($serverId, $pid)
	{
		// 获得uid
		$arrUserInfo = UserDao::getArrUserByArrPid(array($pid), array('uid'));
		if (empty($arrUserInfo))
		{
			throw new InterException('not valid server id[%d] or pid[%d], no user info', $serverId, $pid);
		}
		$uid = $arrUserInfo[0]['uid'];
		
		$arrBasicUserInfo = EnUser::getArrUserBasicInfo(array($uid), array('uid', 'uname', 'htid', 'level', 'vip', 'fight_force', 'dress'));
		if (empty($arrBasicUserInfo)) 
		{
			throw new InterException('not valid server id[%d] or pid[%d] or uid[%d], no user info', $serverId, $pid, $uid);
		}
		return $arrBasicUserInfo[$uid];
	}
}

class WorldCarnivalScriptLogic
{
	/**
	 * 根据当前的大轮次，获得比赛双方的pos
	 * 
	 * @param int $round
	 * @param WorldCarnivalConfObj $confObj
	 * @throws InterException
	 * @return array
	 */
	public static function getCompetitorPosByRound($round, $confObj)
	{
		if ($round < WorldCarnivalRound::ROUND_1 || $round > WorldCarnivalRound::ROUND_3) 
		{
			throw new InterException('getCompetitorPosByRound : invalid round[%d]', $round);
		}
		
		$arrFighterInfo = $confObj->getFighters();
		
		if ($round == WorldCarnivalRound::ROUND_1) // 第一大回合
		{
			return array($arrFighterInfo[1], $arrFighterInfo[2]);
		}
		else if ($round == WorldCarnivalRound::ROUND_2) // 第二大回合
		{
			return array($arrFighterInfo[3], $arrFighterInfo[4]);
		}
		else // 第三大回合
		{
			$arrDbFighterInfo = WorldCarnivalDao::getFightersList(WorldCarnivalConf::$curRank[$round], $confObj->getActivityStartTime());
			if (count($arrDbFighterInfo) != 2) 
			{
				throw new InterException('getCompetitorPosByRound : invalid fighters count[%d]', count($arrDbFighterInfo));
			}
			
			$ret = array();
			foreach ($arrDbFighterInfo as $aDbFighterInfo)
			{
				$curServerId = $aDbFighterInfo[WorldCarnivalCrossUserField::TBL_FIELD_SERVER_ID];
				$curPid = $aDbFighterInfo[WorldCarnivalCrossUserField::TBL_FIELD_PID];
				$curPos = $confObj->getFighterPos($curServerId, $curPid);
				if (empty($curPos)) 
				{
					throw new InterException('no pos info of serverId[%d], pid[%d]', $curServerId, $curPid);
				}
				$ret[] = array('server_id' => $curServerId, 'pid' => $curPid, 'pos' => $curPos);
			}
			
			return $ret;
		}
	}
	
	/**
	 * 每个小轮次打完以后向参赛者和围观者推送消息
	 * 
	 * @param int $round
	 * @param int $status
	 * @param int $subRound
	 * @param int $subStatus
	 * @param int $winPos
	 * @param int $nextFightTime
	 */
	public static function push($round, $status, $subRound ,$subStatus, $winPos, $nextFightTime)
	{
		try 
		{
			// 检查是否在一届比赛中
			$confObj = WorldCarnivalConfObj::getInstance(WorldCarnivalField::CROSS);
			$session = $confObj->getSession();
			if (empty($session))
			{
				throw new FakeException('not in any session');
			}
			
			// 获得所有需要推送的玩家，以serverId为key
			$arrMergePidInfo = array();
			$arrFighters = $confObj->getFighters();
			$arrWatchers = $confObj->getWatchers();
			foreach ($arrFighters as $aFighterInfo)
			{
				$aServerId = $aFighterInfo['server_id'];
				$aPid = $aFighterInfo['pid'];
				if (empty($arrMergePidInfo[$aServerId])) 
				{
					$arrMergePidInfo[$aServerId] = array();
				}
				$arrMergePidInfo[$aServerId][] = $aPid;
			}
			foreach ($arrWatchers as $aWatcherInfo)
			{
				$aServerId = $aWatcherInfo['server_id'];
				$aPid = $aWatcherInfo['pid'];
				if (empty($arrMergePidInfo[$aServerId])) 
				{
					$arrMergePidInfo[$aServerId] = array();
				}
				$arrMergePidInfo[$aServerId][] = $aPid;
			}
			
			// 推送
			$arrData = array
			(
					'round' => $round,
					'status' => $status,
					'sub_round' => $subRound,
					'sub_status' => $subStatus,
					'win_pos' => $winPos,
					'next_fight_time' => $nextFightTime,
			);
			foreach ($arrMergePidInfo as $aServerId => $arrPid)
			{
				try
				{
					$group = Util::getGroupByServerId($aServerId);
					$proxy = new ServerProxy();
					$proxy->init($group, Util::genLogId());
					$proxy->syncExecuteRequest('worldcarnival.push', array($aServerId, $arrPid, $arrData));
				}
				catch (Exception $e)
				{
					Logger::warning('push failed for serverId[%d], exception[%s]', $aServerId, $e->getMessage());
				}
			}
		}
		catch (Exception $e)
		{
			Logger::warning('push failed, round[%d], status[%d], subRound[%d], subStatus[%d]', $round, $status, $subRound, $subStatus);
		}
	}
	
	/**
	 * 战斗，脚本的入口
	 * 
	 * @throws FakeException
	 * @throws InterException
	 * @throws Exception
	 */
	public static function fight()
	{
		// 检查是否在一届比赛中
		$confObj = WorldCarnivalConfObj::getInstance(WorldCarnivalField::CROSS);
		$session = $confObj->getSession();
		if (empty($session))
		{
			Logger::info('not in any session, return');
			return ;
		}
		Logger::info('FIGHT : cur session[%d]', $session);
		
		// 进度管理对象
		$procedureObj = WorldCarnivalProcedureObj::getInstance($session, $confObj->getActivityStartTime());
		
		// 获得所有参赛者的信息
		$arrFighter = $confObj->getFighters();
		Logger::info('FIGHT : cur fighters[%s]', $arrFighter);
		
		// 开始顺序执行一对一的战斗，直到所有轮次都结束
		while (TRUE)
		{
			try
			{
				// 获得当前大轮，大轮状态，小轮，小轮状态
				$curRound = $procedureObj->getCurRound();
				$curStatus = $procedureObj->getCurStatus();
				$curSubRound = $procedureObj->getCurSubRound();
				$curSubStatus = $procedureObj->getCurSubStatus();
				
				// 如果最后一大轮打完了，就退出
				if ($curRound == WorldCarnivalRound::ROUND_3
					&& $curStatus == WorldCarnivalProcedureStatus::FIGHTEND)
				{
					Logger::info('FIGHT : all round fight end.');
					break;
				}
				
				// 获得下次战斗的时间
				$nextFightTime = $procedureObj->getNextFightTime($confObj->getBeginTime(), $confObj->getNormalPeriod(), $confObj->getFinalPeriod());
				if (time() < $nextFightTime) 
				{
					$sleepSeconds = $nextFightTime - time();
					Logger::info('FIGHT : sleep [%d] seconds for next fight.', $sleepSeconds);
					sleep($sleepSeconds);
					continue;
				}
				else 
				{
					Logger::info('FIGHT : no need sleep, next fight time[%s], cur time[%s].', strftime('%Y%m%d %H%M%S', $nextFightTime), strftime('%Y%m%d %H%M%S', time()));
				}
				
				// 如果当前大轮比完啦，则初始化下个大轮
				$isInitNewRound = FALSE;
				if ($curStatus == WorldCarnivalProcedureStatus::FIGHTEND) 
				{
					Logger::info('FIGHT : cur round[%d] done, init next round', $curRound);
					$procedureObj->initNextRound(++$curRound);
					$curStatus = WorldCarnivalProcedureStatus::FIGHTING;
					$curSubRound = 1;
					$curSubStatus = WorldCarnivalProcedureSubStatus::FIGHTING;
					$isInitNewRound = TRUE;
				}
				
				// 如果当前小轮比赛完啦，则初始化下个小轮
				if ($curSubStatus == WorldCarnivalProcedureSubStatus::FIGHTEND)
				{
					Logger::info('FIGHT : cur round[%d] cur sub round[%d] done, init next sub round', $curRound, $curSubRound);
					$procedureObj->initNextSubRound(++$curSubRound);
					$curSubStatus = WorldCarnivalProcedureSubStatus::FIGHTING;
				}
				
				Logger::info('FIGHT : begin to run once fight, cur round[%d], cur status[%d], cur sub round[%d], cur sub status[%d]', $curRound, $curStatus, $curSubRound, $curSubStatus);
				
				// 获取两个对手的信息
				list($fighterInfo1, $fighterInfo2) = self::getCompetitorPosByRound($curRound, $confObj);
				Logger::info('FIGHT : fighterInfo1[%s], fighterInfo2[%s]', $fighterInfo1, $fighterInfo2);
				
				// 获取两个对手的战斗数据，安全起见，要重试
				for ($i = 1; $i <= 5; ++$i)
				{
					try
					{
						$fighterObj1 = WorldCarnivalCrossUserObj::getInstance($fighterInfo1['server_id'], $fighterInfo1['pid'], $confObj->getActivityStartTime());
						$fighterObj2 = WorldCarnivalCrossUserObj::getInstance($fighterInfo2['server_id'], $fighterInfo2['pid'], $confObj->getActivityStartTime());
						if ($isInitNewRound) 
						{
							$fighterObj1->resetLoseTimes();
							$fighterObj2->resetLoseTimes();
						}
						$battleFmt1 = $fighterObj1->getFmt();
						$battleFmt2 = $fighterObj2->getFmt();
						if (empty($battleFmt1) || empty($battleFmt2)) 
						{
							throw new InterException('can not get battle fmt, battle fmt1[%s], battle fmt2[%s]', $battleFmt1, $battleFmt2);
						}
						Logger::trace('FIGHT : fighter1 battle fmt[%s]', $battleFmt1);
						Logger::trace('FIGHT : fighter2 battle fmt[%s]', $battleFmt2);
						break;
					}
					catch (Exception $e)
					{
						if ($i == 5)
						{
							throw $e;
						}
						else
						{
							usleep(1000);
							Logger::warning('get battle fmt failed, exception[%s], retry', $e->getMessage());
						}
					}
				}
				
				// 开始战斗，战力高的先出手，支持异常重算
				for ($i = 1; $i <= 5; ++$i)
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
						if ($fighterObj1->getFightForce() >= $fighterObj2->getFightForce()) 
						{
							$atkObj = &$fighterObj1;
							$defObj = &$fighterObj2;
							$atkPos = $fighterInfo1['pos'];
							$defPos = $fighterInfo2['pos'];
						}
						else 
						{
							$atkObj = &$fighterObj2;
							$defObj = &$fighterObj1;
							$atkPos = $fighterInfo2['pos'];
							$defPos = $fighterInfo1['pos'];
						}
						$atkRet = EnBattle::doHero($atkObj->getFmt(), $defObj->getFmt(), 0, NULL, NULL, array('isWCN' => true, 'type' => BattleType::WORLD_CARNIVAL, 'damageIncreConf' => $arrDamageIncreConf), WorldCarnivalUtil::getCrossDbName());
						break;
					}
					catch (Exception $e)
					{
						if ($i == 5) 
						{
							throw $e;
						}
						else 
						{
							usleep(1000);
							Logger::warning('fighter failed, exception[%s], retry', $e->getMessage());
						}
					}
				}
				
				// 处理失败次数
				$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
				if ($kill)
				{
					$defObj->increLoseTimes();
					$winObj = &$atkObj;
					$loseObj = &$defObj;
					$winPos = $atkPos;
					$losePos = $defPos;
				}
				else 
				{
					$atkObj->increLoseTimes();
					$winObj = &$defObj;
					$loseObj = &$atkObj;
					$winPos = $defPos;
					$losePos = $atkPos;
				}
				
				// 设置小轮状态为fightend
				$procedureObj->setSubRoundStatus($curSubRound, WorldCarnivalProcedureSubStatus::FIGHTEND);
				
				// 设置小轮的战斗结束时间
				$procedureObj->setFightTime($curSubRound, time());
				
				// 判断大轮是否也打完啦
				if ($loseObj->getLoseTimes() >= WorldCarnivalConf::$mapMaxLoseTimes[$curRound])
				{
					$procedureObj->setRoundStatus(WorldCarnivalProcedureStatus::FIGHTEND);
					$winObj->setRank(WorldCarnivalConf::$winRank[$curRound]);
				}
				
				// 增加战报
				$brid = RecordType::WCN_PREFIX . $atkRet['server']['brid'];
				$procedureObj->addBattleRecord($curSubRound, $fighterInfo1['pos'], $fighterInfo2['pos'], $fighterInfo1['pos'] == $winPos ? 1 : 0, $brid);
				
				// 同步到db，安全起见，要重试
				for ($i = 1; $i <= 5; ++$i)
				{
					try
					{
						$procedureObj->update();
						$fighterObj1->update();
						$fighterObj2->update();
						break;
					}
					catch (Exception $e)
					{
						if ($i == 5)
						{
							Logger::fatal('update failed, need fix data!!!!!!!!!!!!!!!!!!!!!!!!');
							exit();
						}
						else
						{
							usleep(1000);
							Logger::warning('update failed, exception[%s], retry', $e->getMessage());
						}
					}
				}
				
				// 推送数据
				$nextFightTime = $procedureObj->getNextFightTime($confObj->getBeginTime(), $confObj->getNormalPeriod(), $confObj->getFinalPeriod());
				self::push($procedureObj->getCurRound(), $procedureObj->getCurStatus(), $procedureObj->getCurSubRound(), $procedureObj->getCurSubStatus(), $winPos, $nextFightTime);
				
				// 释放obj，允许玩家更新战斗信息
				WorldCarnivalCrossUserObj::releaseInstance($fighterInfo1['server_id'], $fighterInfo1['pid']);
				WorldCarnivalCrossUserObj::releaseInstance($fighterInfo2['server_id'], $fighterInfo2['pid']);
				unset($atkObj);
				unset($defObj);
				unset($winObj);
				unset($loseObj);
			}
			catch (Exception $e)
			{
				Logger::warning('fight failed, exception[%s]', $e->getMessage());
			}
			
			sleep(1);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
