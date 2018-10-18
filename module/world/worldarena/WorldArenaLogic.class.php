<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaLogic.class.php 245795 2016-06-07 08:53:22Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaLogic.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-07 08:53:22 +0000 (Tue, 07 Jun 2016) $
 * @version $Revision: 245795 $
 * @brief 
 *  
 **/
 
class WorldArenaLogic
{
	/**
	 * 获得各个阶段的主界面信息
	 * 
	 * @param int $uid
	 * @return array
	 */
	public static function getWorldArenaInfo($uid)
	{	
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 获得玩家服内obj和配置obj
		$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid);
		$confObj = WorldArenaConfObj::getInstance();
		
		// 获得玩家所在的分组id,获得房间id
		$roomId = 0;
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (!empty($teamId) && $myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime()) 
		{
			$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
			$roomId = $myCrossObj->getRoomId();
		}
		
		// 检测没有更新room_id和pos的玩家是否超过一定范围，超过的话抛异常
		if (!empty($teamId) && !empty($roomId) && $confObj->getStage() == WorldArenaDef::STAGE_TYPE_ATTACK) 
		{
			$arrCond = array
			(
					array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
					array(WorldArenaCrossUserField::TBL_FIELD_POS, '<', 1),
					array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
			);
			$missUserCount = WorldArenaDao::selectCrossUserCount($teamId, $arrCond);
			if (!empty($missUserCount))
			{
				if ($missUserCount > WorldArenaConf::MAX_MISS_USER_PER_TEAM) 
				{
					throw new InterException('teamId[%d], miss user count[%d], max available count[%d], failed', $teamId, $missUserCount, WorldArenaConf::MAX_MISS_USER_PER_TEAM);
				}
				else 
				{
					Logger::warning('teamId[%d], miss user count[%d], continue', $teamId, $missUserCount);	
				}
			}
		}
		
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['stage'] = $confObj->getStage();
		$ret['team_id'] = $teamId;
		$ret['room_id'] = $roomId;
		$ret['pid'] = $pid;
		$ret['signup_time'] = $myInnerObj->getSignupTime();
		$ret['period_bgn_time'] = $confObj->getPeriodBgnTime();
		$ret['period_end_time'] = $confObj->getPeriodEndTime();
		$ret['signup_bgn_time'] = $confObj->getSignupBgnTime();
		$ret['signup_end_time'] = $confObj->getSignupEndTime();
		$ret['attack_bgn_time'] = $confObj->getAttackBgnTime();
		$ret['attack_end_time'] = $confObj->getAttackEndTime();
		$ret['cd_duration_before_end'] = intval(WorldArenaConf::CD_DURATION);
		$ret['extra'] = array();
		
		if ($confObj->getStage() == WorldArenaDef::STAGE_TYPE_SIGNUP) 
		{
			$ret['extra']['update_fmt_time'] = $myInnerObj->getUpdateFmtTime();
		}
		else if ($confObj->getStage() == WorldArenaDef::STAGE_TYPE_ATTACK && !empty($teamId) && !empty($roomId)) // 已经报名的情况下，才有这个extra信息
		{
		    try
		    {
			    $ret['extra'] = self::getBasicFrontInfo($myInnerObj, $myCrossObj);
		    }
		    catch (Exception $e)
		    {
		        Logger::warning('getBasicFrontInfo failed ');
		        $ret['team_id'] = 0;
		        $ret['room_id'] = 0;
		    }
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	
	/**
	 * 玩家报名跨服竞技场
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return int
	 */
	public static function signUp($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在报名期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_SIGNUP) 
		{
			throw new FakeException('not in sign up stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查玩家等级是否够
		$userObj = EnUser::getUserObj($uid);
		$myLevel = $userObj->getLevel();
		$needLevel = $confObj->getNeedLevel();
		if ($myLevel < $needLevel) 
		{
			throw new FakeException('my level[%d], need level[%d]', $myLevel, $needLevel);
		}
		
		// 检查玩家是否已经报名
		$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime()) 
		{
			throw new FakeException('already sign up');
		}
		
		// 报名，更新服内信息
		$myInnerObj->signUp();
		$myInnerObj->updateFmt($userObj->getBattleFormation(), FALSE);
		
		// 报名，更新跨服信息
		$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId);
		$myCrossObj->updateBasicInfo($userObj->getUname(), $userObj->getVip(), $userObj->getLevel(), $userObj->getHeroManager()->getMasterHeroObj()->getHtid(), $userObj->getDressInfo(), $userObj->getTitle());
		$myCrossObj->updateFightForce($userObj->getFightForce());
		
		// 同步到数据库
		$myInnerObj->update();
		$myCrossObj->update();
		
		$ret = $myInnerObj->getSignupTime();
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 报名阶段内，可以更新战斗信息，但是有冷却时间
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return int
	 */
	public static function updateFmt($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在报名期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_SIGNUP)
		{
			throw new FakeException('not in sign up stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查玩家是否已经报名
		$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
		if (!$myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime())
		{
			throw new FakeException('not sign up');
		}
		
		// 检查玩家是否处于冷却时间
		$updateFmtTime = $myInnerObj->getUpdateFmtTime();
		$coldTime = $confObj->getColdTime();
		if ($updateFmtTime + $coldTime >= Util::getTime()) 
		{
			throw new FakeException('in cold, last update time[%s], cold time[%d], curr time[%s]', strftime('%Y%m%d %H:%M:%S', $updateFmtTime), $coldTime, strftime('%Y%m%d %H:%M:%S', Util::getTime()));
		}
		
		// 更新战斗信息，更新服内信息
		$userObj = EnUser::getUserObj($uid);
		$myInnerObj->updateFmt($userObj->getBattleFormation());
		
		// 更新战斗信息，更新跨服信息
		$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
		$myCrossObj->updateBasicInfo($userObj->getUname(), $userObj->getVip(), $userObj->getLevel(), $userObj->getHeroManager()->getMasterHeroObj()->getHtid(), $userObj->getDressInfo(), $userObj->getTitle());
		$myCrossObj->updateFightForce($userObj->getFightForce());
		
		// 同步到数据库,更新
		$myInnerObj->update();
		$myCrossObj->update();
		
		$ret = $myInnerObj->getUpdateFmtTime();
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 玩家挑战某个server的某个pid
	 * 
	 * @param int $uid
	 * @param int $targetServerId
	 * @param int $targetPid
	 * @param int $skip
	 * @throws FakeException
	 * @throws Exception
	 * @return array
	 */
	public static function attack($uid, $targetServerId, $targetPid, $skip)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在攻打期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_ATTACK)
		{
			throw new FakeException('not in attack stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查玩家攻打的目标不是自己
		if ($serverId == $targetServerId && $pid == $targetPid) 
		{
			throw new FakeException('can not attack self');
		}
		
		// 检查玩家和攻打的目标是否都在同一个分组，只有处在不同的服才需要判断
		if ($serverId != $targetServerId) 
		{
			$targetTeamId = WorldArenaUtil::getTeamIdByServerId($targetServerId);
			if ($targetTeamId != $teamId)
			{
				throw new FakeException('not in same team, my team[%d], target team[%d]', $teamId, $targetTeamId);
			}
		}
		
		try
		{
			// 这里开始加跨服锁，按顺序锁住这两个玩家
			$key1 = 'worldarena_' . $serverId . '_' . $pid;
			$key2 = 'worldarena_' . $targetServerId . '_' . $targetPid;
			$arrKey = array($key1, $key2);
			sort($arrKey);
			$crossLocker = new CrossLocker(WorldArenaUtil::getCrossDbName());
			foreach ($arrKey as $aKey)
			{
				$crossLocker->lock($aKey);
			}
			
			// 检查玩家和攻打目标都已经报名
			$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
			$targetInnerObj = WorldArenaInnerUserObj::getInstance($targetServerId, $targetPid, 0, FALSE);
			if (!$myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime() || !$targetInnerObj->getSignupTime() >= $confObj->getSignupBgnTime()) 
			{
				throw new FakeException('not all sign up, my sign up time[%s], target sign up time[%s]', strftime('%Y%m%d %H:%M:%S', $myInnerObj->getSignupTime()), strftime('%Y%m%d %H:%M:%S', $targetInnerObj->getSignupTime()));
			}
			
			// 检查是否在冷却时间范围内，且玩家是否没有冷却
			if ($confObj->inAttackCdPeriod(Util::getTime())) 
			{
				$lastAttackTime = $myInnerObj->getLastAttackTime();
				$cd = $confObj->getAttackCd();
				if (Util::getTime() <= ($lastAttackTime + $cd)) 
				{
					throw new FakeException('in cd, last attack time[%s], cd[%d], curr time[%s]', strftime('%Y%m%d %H:%M:%S', $lastAttackTime), $cd, strftime('%Y%m%d %H:%M:%S', Util::getTime()));
				}
			}
			
			// 检查玩家的攻击次数是否足够
			if ($myInnerObj->getAtkNum() <= 0) 
			{
				throw new FakeException('no enough atk num');
			}
			
			// 检查玩家是否在一个房间
			$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
			$roomId = $myCrossObj->getRoomId();
			if (empty($roomId))
			{
				throw new FakeException('not in any room');
			}
			
			// 检查玩家和攻打的目标是否都在同一个房间
			$targetCrossObj = WorldArenaCrossUserObj::getInstance($targetServerId, $targetPid, 0, $teamId, FALSE);
			$targetRoomId = $targetCrossObj->getRoomId();
			if ($roomId != $targetRoomId) 
			{
				throw new FakeException('not in same room, my room[%d], target room[%d]', $roomId, $targetRoomId);
			}
			
			// 检查攻打的目标是否处于保护时间内
			if (Util::getTime() <= $targetCrossObj->getProtectTime()) 
			{
				// 返回值
				$ret = array();
				$ret = self::getBasicFrontInfo($myInnerObj, $myCrossObj);
				$ret['ret'] = 'protect';
				
				// 释放跨服锁
				array_reverse($arrKey);
				foreach ($arrKey as $aKey)
				{
					$crossLocker->unlock($aKey);
				}
				
				Logger::trace('target in protect, protect time[%s], cur time[%s]', strftime('%Y%m%d %H:%M:%S', $targetCrossObj->getProtectTime()), strftime('%Y%m%d %H:%M:%S', Util::getTime()));
				return $ret;
			}
			
			// 检查玩家的排名和攻打目标的排名是否在有效的攻击范围内
			$myPos = $myCrossObj->getPos();
			$myRank = $myCrossObj->getRank();
			$targetPos = $targetCrossObj->getPos();
			$targetRank = $targetCrossObj->getRank();
			if (!WorldArenaUtil::inRange($myRank, $targetRank)) 
			{	
				// 返回值
				$ret = array();
				$ret = self::getBasicFrontInfo($myInnerObj, $myCrossObj);
				$ret['ret'] = 'out_range';
				
				// 释放跨服锁
				array_reverse($arrKey);
				foreach ($arrKey as $aKey)
				{
					$crossLocker->unlock($aKey);
				}
				
				Logger::warning('target not in range, my rank[%d], target rank[%d]', $myRank, $targetRank);
				return $ret;
			}
			
			// 正常攻打, 获取双方战斗数据，hid,uid做偏移，血量继承
			$myFmt = $myInnerObj->getFmt(WorldArenaDef::OFFSET_ONE);
			$targetFmt = $targetInnerObj->getFmt(WorldArenaDef::OFFSET_TWO);
			$arrDamageIncreConf = array
			(
					array(BattleDamageIncreType::Fix, 10, 14, 5000),
					array(BattleDamageIncreType::Fix, 15, 19, 10000),
					array(BattleDamageIncreType::Fix, 20, 24, 15000),
					array(BattleDamageIncreType::Fix, 25, 29, 20000),
					array(BattleDamageIncreType::Fix, 30, 30, 25000),
			);
			$atkRet = EnBattle::doHero($myFmt, $targetFmt, 0, NULL, NULL, array('isWAN' => true, 'type' => BattleType::WORLD_ARENA, 'damageIncreConf' => $arrDamageIncreConf), WorldArenaUtil::getCrossDbName());
			$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
			
			// 根据战斗结果，先更新这两个玩家跨服的数据，而且需要在一个事务里执行，维护$myCrossObj和$targetCrossObj的正确性，后面要用到
			$userObj = EnUser::getUserObj($uid);
			$myCrossObj->updateBasicInfo($userObj->getUname(), $userObj->getVip(), $userObj->getLevel(), $userObj->getHeroManager()->getMasterHeroObj()->getHtid(), $userObj->getDressInfo(), $userObj->getTitle());
			if ($kill) 
			{
				$myCrossObj->win(TRUE); 
				$myTerminalContiNum = $targetCrossObj->lose(FALSE);
				if ($targetPos < $myPos)
				{
					$myCrossObj->setPos($targetPos);
				}
			}
			else 
			{
				$targetTerminalContiNum = $myCrossObj->lose(TRUE);
				$targetCrossObj->win(FALSE);
			}
			$lastPos = WorldArenaDao::updateCrossUserByBatch($teamId, $roomId, $myCrossObj, $targetCrossObj, $kill);
			
			// 如果玩家赢了，需要发放玩家胜利奖励，玩家连胜奖励，玩家终结对方连胜奖励
			$arrReward = array();
			if ($kill) 
			{
				// 更新服内信息, 玩家自己的攻击次数，两个人的血量和怒气
				$myInnerObj->win($atkRet['server']['team1'], WorldArenaDef::OFFSET_ONE);
				$targetInnerObj->lose(WorldArenaDef::OFFSET_TWO);
								
				// 赢了要给自己发奖
				$winReward = $confObj->getWinReward();
				$contiReward = $confObj->getContiReward($myCrossObj->getCurContiNum());
				$terminalContiReward = $confObj->getTerminalContiReward($myTerminalContiNum);
				$rewardRet = RewardUtil::reward3DArr($uid, array_merge($winReward, $contiReward, $terminalContiReward), StatisticsDef::ST_FUNCKEY_WORLD_ARENA_ATTACK_WIN_REWARD);
				if ($rewardRet[UpdateKeys::USER])
				{
					EnUser::getUserObj($uid)->update();
				}
				if ($rewardRet[UpdateKeys::BAG])
				{
					BagManager::getInstance()->getBag($uid)->update();
				}
				
				// 记录下奖励
				$arrReward['win_reward'] = $winReward;
				$arrReward['conti_reward'] = $contiReward;
				$arrReward['terminal_conti_reward'] = $terminalContiReward;
			}
			else 
			{
				// 更新服内信息, 玩家自己的攻击次数，两个人的血量和怒气
				$myInnerObj->lose(WorldArenaDef::OFFSET_ONE);
				$targetInnerObj->win($atkRet['server']['team2'], WorldArenaDef::OFFSET_TWO);
				
				// 输了也有奖励
				$loseReward = $confObj->getLoseReward();
				$rewardRet = RewardUtil::reward3DArr($uid, array_merge($loseReward), StatisticsDef::ST_FUNCKEY_WORLD_ARENA_ATTACK_LOSE_REWARD);
				if ($rewardRet[UpdateKeys::USER])
				{
					EnUser::getUserObj($uid)->update();
				}
				if ($rewardRet[UpdateKeys::BAG])
				{
					BagManager::getInstance()->getBag($uid)->update();
				}
				
				// 记录下奖励
				$arrReward['lose_reward'] = $loseReward;
			}
			
			// 设置玩家上次攻打的时间，用于cd
			$myInnerObj->setLastAttackTime(Util::getTime());
			
			// 同步到db，跨服的数据已经用事务同步啦，这只同步服内的
			$myInnerObj->update();
			$targetInnerObj->update();
			
			// 生成返回数据，重新刷玩家的对手列表
			if (!$kill) // 如果输了玩家被放在最后一名，pos是最大的，但是使用子查询更新的数据，所以不知道具体的pos值，需要重新拉一遍
			{
				//WorldArenaCrossUserObj::releaseInstance($serverId, $pid);
				//$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
				$myCrossObj->setPos($lastPos);
			}
			$ret = self::getBasicFrontInfo($myInnerObj, $myCrossObj);
			$ret['ret'] = 'ok';
			$ret['appraisal'] = $atkRet['server']['appraisal'];
			if (!$skip) 
			{
				$ret['fightRet'] = $atkRet['client'];
			}
			if ($kill) 
			{
				$ret['terminal_conti_num'] = $myTerminalContiNum;
			}
			$ret['reward'] = $arrReward;
			
			// 释放跨服锁
			array_reverse($arrKey);
			foreach ($arrKey as $aKey)
			{
				$crossLocker->unlock($aKey);
			}
		}
		catch (Exception $e)
		{
			array_reverse($arrKey);
			foreach ($arrKey as $aKey)
			{
				$crossLocker->unlock($aKey);
			}
			throw $e;
		}
		
		// 更新战报表的数据，插入一条战报
		$arrRecordField = array
		(
				WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID => $teamId,
				WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID => $roomId,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_SERVER_ID => $serverId,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_PID => $pid,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_UNAME => $userObj->getUname(),
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_HTID => $myCrossObj->getHtid(),
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_RANK => $myRank,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_CONTI => $kill ? $myCrossObj->getCurContiNum() : 0,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_TERMINAL_CONTI => $kill ? $myTerminalContiNum : 0,
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_SERVER_ID => $targetServerId,
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_PID => $targetPid,
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_UNAME => $targetCrossObj->getUname(),
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_HTID => $targetCrossObj->getHtid(),
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_RANK => $targetRank,
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_CONTI => $kill ? 0 : $targetCrossObj->getCurContiNum(),
				WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_TERMINAL_CONTI => $kill ? 0 : $targetTerminalContiNum,
				WorldArenaCrossRecordField::TBL_FIELD_ATTACK_TIME => Util::getTime(),
				WorldArenaCrossRecordField::TBL_FIELD_RESULT => $kill ? 1 : 0,
				WorldArenaCrossRecordField::TBL_FIELD_BRID => RecordType::WAN_PREFIX . $atkRet['server']['brid'],
		);
		WorldArenaDao::insertCrossRecord($arrRecordField);
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 根据玩家的服内信息和跨服信息，获取返给前端的基础信息
	 * 
	 * @param WorldArenaInnerUserObj $myInnerObj
	 * @param WorldArenaCrossUserObj $myCrossObj
	 */
	private static function getBasicFrontInfo($myInnerObj, $myCrossObj)
	{
		$ret = array();
	
		// 自己的跨服竞技场相关信息
		$ret['atk_num'] = $myInnerObj->getAtkNum();
		$ret['buy_atk_num'] = $myInnerObj->getBuyAtkNum();
		$ret['silver_reset_num'] = $myInnerObj->getSilverResetNum();
		$ret['gold_reset_num'] = $myInnerObj->getGoldResetNum();
		$ret['kill_num'] = $myCrossObj->getKillNum();
		$ret['cur_conti_num'] = $myCrossObj->getCurContiNum();
		$ret['max_conti_num'] = $myCrossObj->getMaxContiNum();
		$ret['last_attack_time'] = $myInnerObj->getLastAttackTime();
		
		// 对手的基础信息，包括自己
		$arrPlayerInfo = array(); 
		$arrTmpInfo = $myCrossObj->getEnemyInfo(TRUE);
		$arrServerId = Util::arrayExtract($arrTmpInfo, WorldArenaCrossUserField::TBL_FIELD_SERVER_ID);
		$arrServerId = array_unique($arrServerId);
		$arrServerId2Name = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		foreach ($arrTmpInfo as $rank => $aTmpInfo)
		{
			$arrPlayerInfo[$rank]['server_id'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
			$arrPlayerInfo[$rank]['server_name'] = $arrServerId2Name[$aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID]];
			$arrPlayerInfo[$rank]['pid'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_PID];
			$arrPlayerInfo[$rank]['uname'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_UNAME];
			$arrPlayerInfo[$rank]['htid'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_HTID];
			$arrPlayerInfo[$rank]['level'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_LEVEL];
			$arrPlayerInfo[$rank]['vip'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_VIP];
			$arrPlayerInfo[$rank]['title'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_TITLE];
			$arrPlayerInfo[$rank]['fight_force'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE];
			$arrPlayerInfo[$rank]['dress'] = empty($aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS]) ? array() : $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS];
			
			$aInnerObj = WorldArenaInnerUserObj::getInstance($aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID], $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_PID], 0, FALSE);
			$arrPlayerInfo[$rank]['hp_percent'] = $aInnerObj->getHpPercent();
			$arrPlayerInfo[$rank]['protect_time'] = $aTmpInfo[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME];
			
			// 标识下自己
			if ($arrPlayerInfo[$rank]['server_id'] == $myCrossObj->getServerId()
				&& $arrPlayerInfo[$rank]['pid'] == $myCrossObj->getPid()) 
			{
				$arrPlayerInfo[$rank]['self'] = 1;
			}
			else 
			{
				$arrPlayerInfo[$rank]['self'] = 0;
			}
		}
		$ret['player'] = $arrPlayerInfo;
		
		return $ret;
	}
	
	/**
	 * 购买多次挑战次数
	 * 
	 * @param int $uid
	 * @param int $num
	 * @throws FakeException
	 * @throws Exception
	 * @return int
	 */
	public static function buyAtkNum($uid, $num)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在攻打期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_ATTACK)
		{
			throw new FakeException('not in attack stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
			
		// 检查玩家是否已经报名
		$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
		if (!$myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime())
		{
			throw new FakeException('not sign up');
		}
			
		// 检查玩家购买次数是否达到上限
		$confObj = WorldArenaConfObj::getInstance();
		$maxBuyAtkNum = $confObj->getMaxBuyAtkNum();
		$curBuyAtkNum = $myInnerObj->getBuyAtkNum();
		if ($curBuyAtkNum + $num > $maxBuyAtkNum) 
		{
			throw new FakeException('buy atk num exceed, cur num[%d], buy num[%d], max num[%d]', $curBuyAtkNum, $num, $maxBuyAtkNum);
		}
			
		// 扣金币，加次数
		$cost = 0;
		for ($i = 1; $i <= $num; ++$i)
		{
			$cost += $confObj->getBuyAtkCost($curBuyAtkNum + $i);
		}			
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_ARENA_BUY_ATK_NUM_COST)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
			
		// 增加次数
		$myInnerObj->addBuyAtkNum($num);
		$myInnerObj->update();
		
		$ret = $myInnerObj->getAtkNum();
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 重置玩家的战斗信息，会更新战斗信息，设置满血满怒，分为银币重置和金币重置
	 * 
	 * @param int $uid
	 * @param int $type
	 * @throws FakeException
	 * @throws Exception
	 * @return string
	 */
	public static function reset($uid, $type)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查type是否有效
		if (!in_array($type, WorldArenaDef::$VALID_RESET_TYPE)) 
		{
			throw new FakeException('not valid reset type[%s], valid type[%s]', $type, WorldArenaDef::$VALID_RESET_TYPE);
		}
		
		// 检查是否在攻打期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_ATTACK)
		{
			throw new FakeException('not in attack stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		try 
		{
			// 这里开始加跨服锁
			$key = 'worldarena_' . $serverId . '_' . $pid;
			$crossLocker = new CrossLocker(WorldArenaUtil::getCrossDbName());
			$crossLocker->lock($key);
			
			// 检查玩家是否已经报名
			$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
			if (!$myInnerObj->getSignupTime() >= $confObj->getSignupBgnTime())
			{
				throw new FakeException('not sign up');
			}
			
			// 检查玩家重置次数是否达到上限
			$confObj = WorldArenaConfObj::getInstance();
			$maxResetNum = $confObj->getMaxResetNum($type);
			$curResetNum = ($type == WorldArenaDef::RESET_TYPE_GOLD ? $myInnerObj->getGoldResetNum() : $myInnerObj->getSilverResetNum());
			if ($curResetNum >= $maxResetNum)
			{
				throw new FakeException('reset num exceed, type[%s], cur num[%d], max num[%d]', $type, $curResetNum, $maxResetNum);
			}
			
			// 根据重置类型扣金币或者银币
			$cost = $confObj->getResetCost($type, $curResetNum + 1);
			$userObj = EnUser::getUserObj($uid);
			if ($type == WorldArenaDef::RESET_TYPE_GOLD) 
			{
				if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_ARENA_RESET_COST)) 
				{
					throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
				}
			}
			else if ($type == WorldArenaDef::RESET_TYPE_SILVER) 
			{
				if (!$userObj->subSilver($cost)) 
				{
					throw new FakeException('not enough silver, need[%d], curr[%d]', $cost, $userObj->getSilver());
				}
			}
			else 
			{
				throw new InterException('impossible');
			}
			$userObj->update();
			
			// 重置服内战斗信息，设置满血满怒
			$myInnerObj->updateFmt($userObj->getBattleFormation());
			if ($type == WorldArenaDef::RESET_TYPE_GOLD) 
			{
				$myInnerObj->increGoldResetNum();
			}
			else if ($type == WorldArenaDef::RESET_TYPE_SILVER) 
			{
				$myInnerObj->increSilverResetNum();
			}
			else 
			{
				throw new InterException('impossible');
			}
			
			// 更新战斗信息，更新跨服信息
			$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
			$myCrossObj->updateBasicInfo($userObj->getUname(), $userObj->getVip(), $userObj->getLevel(), $userObj->getHeroManager()->getMasterHeroObj()->getHtid(), $userObj->getDressInfo(), $userObj->getTitle());
			$myCrossObj->updateFightForce($userObj->getFightForce());
			
			// 同步到数据库,更新
			$myInnerObj->update();
			$myCrossObj->update();
			
			// 释放跨服锁
			$crossLocker->unlock($key);
		}
		catch (Exception $e)
		{
			$crossLocker->unlock($key);
			throw $e;
		}
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 拉取玩家的战报列表
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return unknown
	 */
	public static function getRecordList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在攻打期间或者发奖期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_ATTACK
			&& $confObj->getStage() != WorldArenaDef::STAGE_TYPE_REWARD)
		{
			throw new FakeException('not in attack or reward stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 获取玩家的房间id 
		$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
		$roomId = $myCrossObj->getRoomId();
		if (empty($roomId)) 
		{
			throw new FakeException('not in any room');
		}
		
		// 拉取自己的战报列表和本房间的连杀战报
		$myRecordList = WorldArenaDao::getUserRecordList($teamId, $roomId, $serverId, $pid, $confObj->getAttackBgnTime(), WorldArenaConf::USER_RECORD_COUNT);
		foreach ($myRecordList as $index => $info)
		{
			unset($myRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_ID]);
			unset($myRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID]);
			unset($myRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID]);
		}
		$contiRecordList = WorldArenaDao::getContiRecordList($teamId, $roomId, $confObj->getAttackBgnTime(), WorldArenaConf::CONTI_RECORD_COUNT);
		foreach ($contiRecordList as $index => $info)
		{
			unset($contiRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_ID]);
			unset($contiRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_TEAM_ID]);
			unset($contiRecordList[$index][WorldArenaCrossRecordField::TBL_FIELD_ROOM_ID]);
		}
		
		// 获取所有serverName
		$arrAllRecord = array_merge($myRecordList, $contiRecordList);
		$arrAllServerIdPart1 = Util::arrayExtract($arrAllRecord, WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_SERVER_ID);
		$arrAllServerIdPart2 = Util::arrayExtract($arrAllRecord, WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_SERVER_ID);
		$arrAllServerId = array_merge($arrAllServerIdPart1, $arrAllServerIdPart2);
		$arrAllServerId = array_unique($arrAllServerId);
		$arrServerId2Name = ServerInfoManager::getInstance()->getArrServerName($arrAllServerId);
		
		// 返回值中加上serverName
		foreach ($myRecordList as $index => $info)
		{
			$myRecordList[$index]['attacker_server_name'] = $arrServerId2Name[$info[WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_SERVER_ID]];
			$myRecordList[$index]['defender_server_name'] = $arrServerId2Name[$info[WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_SERVER_ID]];
		}
		foreach ($contiRecordList as $index => $info)
		{
			$contiRecordList[$index]['attacker_server_name'] = $arrServerId2Name[$info[WorldArenaCrossRecordField::TBL_FIELD_ATTACKER_SERVER_ID]];
			$contiRecordList[$index]['defender_server_name'] = $arrServerId2Name[$info[WorldArenaCrossRecordField::TBL_FIELD_DEFENDER_SERVER_ID]];
		}
		
		$ret = array();
		$ret['my'] = $myRecordList;
		$ret['conti'] = $contiRecordList;
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 拉取排行榜
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getRankList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldArenaUtil::getPid($uid);
		
		// 检查是否在攻打期间或者发奖期间
		$confObj = WorldArenaConfObj::getInstance();
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_ATTACK
			&& $confObj->getStage() != WorldArenaDef::STAGE_TYPE_REWARD)
		{
			throw new FakeException('not in attack or reward stage');
		}
		
		// 检查玩家是否在一个分组内
		$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 获得玩家所在的房间号
		$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
		$roomId = $myCrossObj->getRoomId();
		if (empty($roomId)) 
		{
			throw new FakeException('not in any room');
		}
		
		// 拉取三种排行榜
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
		);
		$posTmpRank = WorldArenaDao::getPosRankList($teamId, $arrCond, array(), WorldArenaConf::POS_RANK_MAX_COUNT);
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossUserField::TBL_FIELD_KILL_NUM, '>', 0),
				array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
		);
		$killTmpRank = WorldArenaDao::getKillRankList($teamId, $arrCond, array(), WorldArenaConf::KILL_RANK_MAX_COUNT);
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM, '>', 0),
				array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
		);
		$contiTmpRank = WorldArenaDao::getContiRankList($teamId, $arrCond, array(), WorldArenaConf::CONTI_RANK_MAX_COUNT);
		
		// 获取所有serverName
		$arrAllRank = array_merge($posTmpRank, $killTmpRank, $contiTmpRank);
		$arrAllServerId = Util::arrayExtract($arrAllRank, WorldArenaCrossUserField::TBL_FIELD_SERVER_ID);
		$arrServerId2Name = ServerInfoManager::getInstance()->getArrServerName($arrAllServerId);
		
		// 位置排名，生成前端的格式
		$rank = 0;
		$posRank = array();
		foreach ($posTmpRank as $aInfo)
		{
			$tmp = array();
			$tmp['rank'] = ++$rank;
			$tmp['server_id'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
			$tmp['server_name'] = $arrServerId2Name[$aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID]];
			$tmp['pid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_PID];
			$tmp['uname'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_UNAME];
			$tmp['htid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_HTID];
			$tmp['level'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_LEVEL];
			$tmp['vip'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VIP];
			$tmp['title'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_TITLE];
			$tmp['fight_force'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE];
			$tmp['dress'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS];
			$posRank[] = $tmp;
		}
		
		// 击杀排名，生成前端的格式
		$rank = 0;
		$killRank = array();
		foreach ($killTmpRank as $aInfo)
		{
			$tmp = array();
			$tmp['rank'] = ++$rank;
			$tmp['kill_num'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM];
			$tmp['server_id'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
			$tmp['server_name'] = $arrServerId2Name[$aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID]];
			$tmp['pid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_PID];
			$tmp['uname'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_UNAME];
			$tmp['htid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_HTID];
			$tmp['level'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_LEVEL];
			$tmp['vip'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VIP];
			$tmp['title'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_TITLE];
			$tmp['fight_force'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE];
			$tmp['dress'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS];
			$killRank[] = $tmp;
		}
		
		// 最大连杀排名，生成前端的格式
		$rank = 0;
		$contiRank = array();
		foreach ($contiTmpRank as $aInfo)
		{
			$tmp = array();
			$tmp['rank'] = ++$rank;
			$tmp['max_conti_num'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM];
			$tmp['server_id'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
			$tmp['server_name'] = $arrServerId2Name[$aInfo[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID]];
			$tmp['pid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_PID];
			$tmp['uname'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_UNAME];
			$tmp['htid'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_HTID];
			$tmp['level'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_LEVEL];
			$tmp['vip'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VIP];
			$tmp['title'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_TITLE];
			$tmp['fight_force'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE];
			$tmp['dress'] = $aInfo[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS];
			$contiRank[] = $tmp;
		}
		
		$ret = array();
		$ret['pos_rank'] = $posRank;
		$ret['kill_rank'] = $killRank;
		$ret['conti_rank'] = $contiRank;
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
}

class WorldArenaScriptLogic
{
	/**
	 * 同属配置的分组数据，将没有在配置中的服务器自动分组
	 *
	 * @param boolean $commit
	 * @throws InterException
	 */
	public static function syncAllTeamFromPlat2Cross($commit = TRUE, $force = FALSE)
	{
		// 是否处在报名前的阶段
		$confObj = WorldArenaConfObj::getInstance(WorldArenaField::CROSS);
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_BEFORE_SIGNUP && !$force)
		{
			Logger::warning('SYNC_ALL_TEAM : not in before signup stage, can not sync, cur stage[%s]', $confObj->getStage());
			return;
		}
	
		// 得到配置的分组数据和所有服务器信息
		$beginTime = $confObj->getPeriodBgnTime();
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::WORLDARENA, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo = WorldArenaUtil::getAllTeamInfo();
		ksort($arrMyTeamInfo);
		$allServerInfo = ServerInfoManager::getInstance()->getAllServerInfo();
		ksort($allServerInfo);
		Logger::info('SYNC_ALL_TEAM : all config team info[%s]', $arrCfgTeamInfo);
		Logger::info('SYNC_ALL_TEAM : all my team info[%s]', $arrMyTeamInfo);
		Logger::info('SYNC_ALL_TEAM : all server info[%s]', $allServerInfo);
	
		if (!empty($arrMyTeamInfo))
		{
			Logger::warning('SYNC_ALL_TEAM : already have valid team[%s], return', $arrMyTeamInfo);
			return ;
		}
	
		// 找到配置的当前最大分组teamId
		$curMaxTeamId = 0;
		foreach ($arrCfgTeamInfo as $aTeamId => $aInfo)
		{
			if ($aTeamId > $curMaxTeamId)
			{
				$curMaxTeamId = $aTeamId;
			}
		}
		$orginMaxTeamId = $curMaxTeamId;
	
		// 得到需要自动分组的服务器
		$tmpAllServerInfo = $allServerInfo;
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				unset($tmpAllServerInfo[$aServerId]);
			}
		}
		Logger::info('SYNC_ALL_TEAM : all new server info[%s]', $tmpAllServerInfo);
	
		// 去掉开服日期不符合要求的
		$needOpenDuration = $confObj->getNeedOpenDays();
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $beginTime;
			$betweenDays = intval((strtotime(date("Y-m-d", $referTime)) - strtotime(date("Y-m-d", $aOpenTime))) / SECONDS_OF_DAY);
			if ($betweenDays < $needOpenDuration)
			{
				unset($tmpAllServerInfo[$aServerId]);
				Logger::info('SYNC_ALL_TEAM : server id[%d] skip, open time[%s], refer time[%s], need open days[%d].', $aServerId, date("Y-m-d", $aOpenTime), date("Y-m-d", $referTime), $needOpenDuration);
			}
		}
		Logger::info('SYNC_ALL_TEAM : all new server info after open days filter[%s]', $tmpAllServerInfo);
		
		$arrDetailServerInfo = array();
		foreach ($tmpAllServerInfo as $aServerId => $aServerInfo)
		{
			$groupBase = intval($aServerId / 10000);
			$arrDetailServerInfo[$groupBase][$aServerId] = $aServerInfo;
		}
		Logger::info('SYNC_ALL_TEAM : all new server info after group base filter[%s]', $arrDetailServerInfo);
		
		foreach ($arrDetailServerInfo as $groupBase => $prefixServerInfo)
		{
			// 将剩余的服务器自动分组，合服的要在同一个组里
			if (!empty($prefixServerInfo))
			{
				// 处理合服的情况，db -> array(serverId...)
				$arrDb2Info = array();
				foreach ($prefixServerInfo as $aServerId => $aInfo)
				{
					if (!isset($arrDb2Info[$aInfo['db_name']]))
					{
						$arrDb2Info[$aInfo['db_name']] = array();
					}
					$arrDb2Info[$aInfo['db_name']][] = $aServerId;
				}
				Logger::info('SYNC_ALL_TEAM : group base[%d], db 2 info of new server[%s]', $groupBase, $arrDb2Info);
			
				// 处理正常的分组
				$minCount = defined('PlatformConfig::WORLD_ARENA_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_ARENA_TEAM_MIN_COUNT : 18;
				$maxCount = defined('PlatformConfig::WORLD_ARENA_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_ARENA_TEAM_MAX_COUNT : 22;
				Logger::info('SYNC_ALL_TEAM : group base[%d], min server count[%d], max server count[%d]', $groupBase, $minCount, $maxCount);
			
				$curServerCount = 0;
				$curTeamNeedCount = mt_rand($minCount, $maxCount);
				$curTeamId = ++$curMaxTeamId;
				$curPrefixFirstTeamId = $curTeamId;
				Logger::info('SYNC_ALL_TEAM : group base[%d], generate new team[%d], new team server count[%d]', $groupBase, $curTeamId, $curTeamNeedCount);
				$arrExclude = array();
				foreach ($prefixServerInfo as $aServerId => $aInfo)
				{
					if (in_array($aServerId, $arrExclude))
					{
						continue;
					}
			
					if ($curServerCount >= $curTeamNeedCount)
					{
						$curServerCount = 0;
						$curTeamNeedCount = mt_rand($minCount, $maxCount);
						$curTeamId = ++$curMaxTeamId;
						Logger::info('SYNC_ALL_TEAM : group base[%d], generate new team[%d], new team server count[%d]', $groupBase, $curTeamId, $curTeamNeedCount);
					}
			
					$arrCfgTeamInfo[$curTeamId][] = $aServerId;
					Logger::info('SYNC_ALL_TEAM : group base[%d], generate new team[%d], add a normal server[%d]', $groupBase, $curTeamId, $aServerId);
					foreach ($arrDb2Info[$aInfo['db_name']] as $aMergeServerId)
					{
						if ($aMergeServerId == $aServerId)
						{
							continue;
						}
						$arrCfgTeamInfo[$curTeamId][] = $aMergeServerId;
						$arrExclude[] = $aMergeServerId;
						Logger::info('SYNC_ALL_TEAM : group base[%d], generate new team[%d], add a merge server[%d]', $groupBase, $curTeamId, $aMergeServerId);
					}
					++$curServerCount;
				}
			
				// 处理当最后一个分组个数没有达到最低个数的情况，就直接塞到最后一组吧
				if ($curServerCount < $minCount)
				{
					if (isset($arrCfgTeamInfo[$curTeamId - 1]) && $curTeamId > $curPrefixFirstTeamId)
					{
						$arrCfgTeamInfo[$curTeamId - 1] = array_merge($arrCfgTeamInfo[$curTeamId - 1], $arrCfgTeamInfo[$curTeamId]);
						unset($arrCfgTeamInfo[$curTeamId]);
						Logger::info('SYNC_ALL_TEAM : group base[%d], cur team[%d] count[%d] less than min[%d], add to last', $groupBase, $curTeamId, $curServerCount, $minCount);
					}
				}
			}
			
		}
	
		ksort($arrCfgTeamInfo);
		Logger::info('SYNC_ALL_TEAM : final team info[%s]', $arrCfgTeamInfo);
	
		// 更新跨服库分组信息
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				if (!isset($allServerInfo[$aServerId]))
				{
					Logger::fatal('SYNC_ALL_TEAM : no server info of teamId[%d], serverId[%d], skip.', $aTeamId, $aServerId);
				}
				else
				{
					for ($i = 1; $i <= 3; ++$i)
					{
						try
						{
							if ($commit)
							{
								$arrField = array
								(
										WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID => $aTeamId,
										WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID => $aServerId,
										WorldArenaCrossTeamField::TBL_FIELD_UPDATE_TIME => $beginTime + 1,
								);
								WorldArenaDao::insertTeamInfo($arrField);
							}
							Logger::info('SYNC_ALL_TEAM : sync teamdId[%d] server[%d] success.', $aTeamId, $aServerId);
							
							break;
						}
						catch (Exception $e)
						{
							usleep(1000);
							Logger::fatal('SYNC_ALL_TEAM : occur exception when sync teamdId[%d] server[%d], exception[%s], trace[%s], retry...', $aTeamId, $aServerId, $e->getMessage(), $e->getTraceAsString());
						}
							
						if ($i == 3)
						{
							Logger::fatal('SYNC_ALL_TEAM : occur exception when sync teamdId[%d] server[%d], failed', $aTeamId, $aServerId);
						}
					}
				}
			}
		}
		Logger::info('SYNC_ALL_TEAM : sync team info from plat to cross done');
	}
	
	/**
	 * 分房间
	 * 
	 * @param string $commit
	 */
	public static function rangeRoom($commit = TRUE, $force = FALSE)
	{
		Logger::info('WORLD_ARENA_RANGE_ROOM : ****** [Bgn] run range room at time[%s] commit[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE");
		
		// 是否处在分房阶段
		$confObj = WorldArenaConfObj::getInstance(WorldArenaField::CROSS);
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_RANGE_ROOM && !$force)
		{
			Logger::warning('WORLD_ARENA_RANGE_ROOM : not in range room stage, can not run, cur stage[%s]', $confObj->getStage());
			return;
		}
		
		// 为所有的分组的报名选手进行打乱分组
		$pos = 0;
		$newPos = 0;
		$allTeamId = WorldArenaUtil::getAllTeam();
		foreach ($allTeamId as $aTeamId)
		{
			try
			{
				// 批量将这个team的跨服数据的pos和room_id都置为0
				$arrField = array
				(
						WorldArenaCrossUserField::TBL_FIELD_ROOM_ID => 0,
						WorldArenaCrossUserField::TBL_FIELD_POS => 0,
				);
				$arrCond = array
				(
						array(WorldArenaCrossUserField::TBL_FIELD_POS, '>=', 0),
				);
				if ($commit) 
				{
					WorldArenaDao::updateCrossUser($aTeamId, $arrCond, $arrField);
				}
				Logger::info('WORLD_ARENA_RANGE_ROOM : update all pos and room as 0 for teamId[%d]', $aTeamId);
				
				// 拉取这个组内所有报名的玩家信息
				$arrField = array
				(
						WorldArenaCrossUserField::TBL_FIELD_SERVER_ID,
						WorldArenaCrossUserField::TBL_FIELD_PID,
				);
				$arrCond = array
				(
						array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
				);
				$arrAllUser = WorldArenaDao::getUserList($aTeamId, $arrCond, $arrField, 100000);
				Logger::info('WORLD_ARENA_RANGE_ROOM : teamId[%d] all user[%s]', $aTeamId, $arrAllUser);
				
				/*
				 * 扰乱，分房
				 * 分房规则：先按房间的标准大小进行分配，剩下的人数如果大于每个房间的最少人数，则自己开个房
				 * 如果不够最少人数，则平均分配到其他房间去
				 */
				$arrRoomInfo = array();
				shuffle($arrAllUser);
				$roomCapacity = $confObj->getRoomCapacity();
				$roomMinCount = $confObj->getRoomMinCount();
				Logger::info('WORLD_ARENA_RANGE_ROOM : all user count[%d], room capacity[%d], room min count[%d]', count($arrAllUser), $roomCapacity, $roomMinCount);
				
				// 先按标准的人数分
				while (!empty($arrAllUser) && count($arrAllUser) >= $roomCapacity)
				{
					$arrRoomInfo[] = array_slice($arrAllUser, 0, $roomCapacity);
					$arrAllUser = array_slice($arrAllUser, $roomCapacity);
				}
				
				// 处理剩余的
				if (!empty($arrAllUser)) 
				{
					if (count($arrAllUser) >= $roomMinCount) 
					{
						$arrRoomInfo[] = $arrAllUser;
					}
					else 
					{
						$roomCount = count($arrRoomInfo);
						if ($roomCount == 0)
						{
							Logger::warning('teamId[%d], total user count[%d], room capacity[%d], room min count[%d]', $aTeamId, count($arrAllUser), $roomCapacity, $roomMinCount);
							$arrRoomInfo[] = $arrAllUser;
						}
						else 
						{
							$remainCount = count($arrAllUser);
							$tmp1 = $remainCount / $roomCount;
							$tmp2 = $remainCount % $roomCount;
							foreach ($arrRoomInfo as $index => $info)
							{
								$count = $index < $tmp2 ? $tmp1 + 1 : $tmp1;
								$arrRoomInfo[$index] = array_merge($arrRoomInfo[$index], array_slice($arrAllUser, 0, $count));
								$arrAllUser = array_slice($arrAllUser, $count);
							}
						}
					}
				}
				
				// 将分组信息同步到db，每一个分组的所有房间的pos都是使用的同一个序列
				foreach ($arrRoomInfo as $index => $arrUser)
				{
					Logger::info('WORLD_ARENA_RANGE_ROOM : room id[%d], room user count[%d]', $index + 1, count($arrUser));
					foreach ($arrUser as $index2 => $aUser)
					{
						$newPos = ++$pos;
						for ($i = 1; $i <= 3; ++$i)
						{
							try 
							{
								$arrField = array
								(
										WorldArenaCrossUserField::TBL_FIELD_ROOM_ID => $index + 1,
										WorldArenaCrossUserField::TBL_FIELD_POS => $newPos,
										WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
								);
								$arrCond = array
								(
										array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID]),
										array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $aUser[WorldArenaCrossUserField::TBL_FIELD_PID]),
								);
								if ($commit)
								{
										WorldArenaDao::updateCrossUser($aTeamId, $arrCond, $arrField);
								}
								Logger::info('WORLD_ARENA_RANGE_ROOM : team id[%d] server id[%d], pid[%d], in room[%d] with pos[%d]', $aTeamId, $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID], $aUser[WorldArenaCrossUserField::TBL_FIELD_PID], $index + 1, $newPos);
								
								break;
							} 
							catch (Exception $e) 
							{
								usleep(1000);
								Logger::fatal('WORLD_ARENA_RANGE_ROOM : occur exception when range room for team id[%d] server id[%d], pid[%d], in room[%d] with pos[%d], exception[%s], trace[%s], retry...', $aTeamId, $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID], $aUser[WorldArenaCrossUserField::TBL_FIELD_PID], $index + 1, $newPos, $e->getMessage(), $e->getTraceAsString());
							}
							
							if ($i == 3) 
							{
								Logger::fatal('WORLD_ARENA_RANGE_ROOM : occur exception when range room for team id[%d] server id[%d], pid[%d], in room[%d] with pos[%d], failed', $aTeamId, $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID], $aUser[WorldArenaCrossUserField::TBL_FIELD_PID], $index + 1, $newPos);
							}
						}
					}
					
					usleep(1000);
				}
				
				usleep(10000);
			}
			catch (Exception $e)
			{
				Logger::fatal('WORLD_ARENA_RANGE_ROOM : occur exception when range room for teamId[%d], exception[%s], trace[%s]', $aTeamId, $e->getMessage(), $e->getTraceAsString());
			}
		}
		
		// 设置id
		for ($i = 1; $i <= 3; ++$i)
		{
			try 
			{
				IdGenerator::setId('worldarena_pos_id', ++$newPos, WorldArenaUtil::getCrossDbName());
				Logger::info('WORLD_ARENA_RANGE_ROOM : set pos id[%d] success', $newPos);
				break;
			}
			catch (Exception $e)
			{
				if ($i == 3) 
				{
					Logger::fatal('WORLD_ARENA_RANGE_ROOM : occur exception when set pos id[%d], failed...', $newPos);
				}
				else 
				{
					usleep(1000);
					Logger::fatal('WORLD_ARENA_RANGE_ROOM : occur exception when set pos id[%d], retry...', $newPos);
				}
			}
		}
						
		Logger::info('WORLD_ARENA_RANGE_ROOM : ****** [End] run range room at time[%s] commit[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE");
	}
	
	/**
	 * 发排名奖励
	 * 
	 * @param string $commit
	 */
	public static function reward($commit = TRUE, $arrSpec = array())
	{
		Logger::info('WORLD_ARENA_REWARD : ****** [Bgn] run reward at time[%s] commit[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE");
		
		// 是否处在发奖阶段
		$confObj = WorldArenaConfObj::getInstance(WorldArenaField::CROSS);
		if ($confObj->getStage() != WorldArenaDef::STAGE_TYPE_REWARD)
		{
			Logger::warning('WORLD_ARENA_REWARD : not in reward stage, can not run, cur stage[%s]', $confObj->getStage());
			return;
		}
		
		// 循环对所有分组发奖
		$allTeamId = WorldArenaUtil::getAllTeam();
		foreach ($allTeamId as $aTeamId)
		{
			try
			{
				Logger::info('WORLD_ARENA_REWARD : team[%d] reward begin.', $aTeamId);
				
				// 过滤掉不需要处理的组
				if (!empty($arrSpec) && !isset($arrSpec[$aTeamId]))
				{
					Logger::trace('WORLD_ARENA_REWARD : no need run reward for teamId[%d]', $aTeamId);
					continue;
				}
				
				// 当前分组下的所有服
				$arrServerId = WorldArenaUtil::getArrServerIdByTeamId($aTeamId);
				
				// 批量拉取server的db信息
				$crossServer2Db = array();
				$crossServer2Db = ServerInfoManager::getInstance()->getArrDbName($arrServerId);
				
				RewardCfg::$NO_CALLBACK = TRUE;
				$arrRewardUser = array();
				
				// 循环对这个组的所有房间依次进行发奖
				for ($roomId = 1; $roomId <= 10000; ++$roomId)
				{
					$arrTopUser = array();
					
					// 取位置排名数据
					Logger::info('WORLD_ARENA_REWARD : team[%d] room[%d] type[pos] reward begin.', $aTeamId, $roomId);
					for ($i = 1; $i <= 3; ++$i)
					{
						try
						{
							$arrCond = array
							(
									array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									array(WorldArenaCrossUserField::TBL_FIELD_POS, '>', 0),
									array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
							);
							$arrField = array
							(
									WorldArenaCrossUserField::TBL_FIELD_SERVER_ID,
									WorldArenaCrossUserField::TBL_FIELD_PID,
									WorldArenaCrossUserField::TBL_FIELD_UID,
									WorldArenaCrossUserField::TBL_FIELD_POS_REWARD_TIME,
							);
							$arrPosRankUser = WorldArenaDao::getPosRankList($aTeamId, $arrCond, $arrField, WorldArenaConf::POS_REWARD_MAX_COUNT);
							break;
						}
						catch (Exception $e)
						{
							usleep(1000);
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get pos reward user for team id[%d] room id[%d], exception[%s], trace[%s], retry...', $aTeamId, $roomId, $e->getMessage(), $e->getTraceAsString());
						}
						
						if ($i == 3)
						{
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get pos reward user for team id[%d] room id[%d], failed', $aTeamId, $roomId);
						}
					}
					
					// 如果连位置排名的玩家都没有，说明这个组压根就没有这个房间，代表这个组的所有奖励都发完啦
					if (empty($arrPosRankUser)) 
					{
						break;
					}
					
					// 开始发位置排名奖
					$rank = 0;
					foreach ($arrPosRankUser as $aUser)
					{
						++$rank;
						$aServerId = $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
						$aPid = $aUser[WorldArenaCrossUserField::TBL_FIELD_PID];
						$aRewardTime = $aUser[WorldArenaCrossUserField::TBL_FIELD_POS_REWARD_TIME];
						$aServerDb = $crossServer2Db[$aServerId];
						
						$arrInnerCond = array
						(
								array(WorldArenaInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
								array(WorldArenaInnerUserField::TBL_FIELD_PID, '=', $aPid),
						);
						$arrInnerField = array(WorldArenaInnerUserField::TBL_FIELD_UID);
						$innerInfo = WorldArenaDao::selectInnerUser($arrInnerCond, $arrInnerField, $aServerDb);
						if (empty($innerInfo)) 
						{
							throw new InterException('WORLD_ARENA_REWARD : can not get uid from inner for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						$aUid = $innerInfo[WorldArenaInnerUserField::TBL_FIELD_UID];
						
						// 取排名第一的人的信息
						if ($rank == 1) 
						{
							$arrTopUser['pos']['serverId'] = $aServerId;
							$arrTopUser['pos']['pid'] = $aPid;
							$arrTopUser['pos']['uid'] = $aUid;
							$arrTopUser['pos']['serverDb'] = $aServerDb;
						}
						
						// 过滤不需要处理的玩家
						if (empty($arrSpec)
							|| (isset($arrSpec[$aTeamId]) && empty($arrSpec[$aTeamId]))
							|| (isset($arrSpec[$aTeamId][$aServerId]) && empty($arrSpec[$aTeamId][$aServerId]))
							|| isset($arrSpec[$aTeamId][$aServerId][$aPid]))
						{
							Logger::trace('WORLD_ARENA_REWARD : need run pos reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						else
						{
							Logger::trace('WORLD_ARENA_REWARD : no need run pos reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
							continue;
						}
						
						if ($aRewardTime >= $confObj->getAttackEndTime()) 
						{
							Logger::warning('WORLD_ARENA_REWARD : already send pos reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] when[%s] rank[%d]', $aTeamId, $roomId, $aServerId, $aPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime), $rank);
						}
						else 
						{
							$arrReward = $confObj->getPosRankReward($rank);
							Logger::info('WORLD_ARENA_REWARD : send pos reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward);
							if ($commit)
							{
								try 
								{
									$arrField = array
									(
											WorldArenaCrossUserField::TBL_FIELD_POS_REWARD_TIME => Util::getTime(),
											WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
									);
									$arrCond = array
									(
											array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $aPid),
											array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
											array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									);
									WorldArenaDao::updateCrossUser($aTeamId, $arrCond, $arrField);
									
									$arrRewardUser[$aServerId][] = $aUid;
									RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::WORLD_ARENA_POS_RANK_REWARD, array('rank' => $rank), $aServerDb);	
								}
								catch (Exception $e)
								{
									Logger::fatal('WORLD_ARENA_REWARD : occur exception when send pos reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s], exception[%s], trace[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward, $e->getMessage(), $e->getTraceAsString());
								}
							}
						}
					}
					
					// 取击杀总数排名数据
					Logger::info('WORLD_ARENA_REWARD : team[%d] room[%d] type[kill] reward begin.', $aTeamId, $roomId);
					for ($i = 1; $i <= 3; ++$i)
					{
						try
						{
							$arrCond = array
							(
									array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									array(WorldArenaCrossUserField::TBL_FIELD_POS, '>', 0),
									array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
							);
							$arrField = array
							(
									WorldArenaCrossUserField::TBL_FIELD_SERVER_ID,
									WorldArenaCrossUserField::TBL_FIELD_PID,
									WorldArenaCrossUserField::TBL_FIELD_UID,
									WorldArenaCrossUserField::TBL_FIELD_KILL_REWARD_TIME,
									WorldArenaCrossUserField::TBL_FIELD_KILL_NUM,
							);
							$arrKillRankUser = WorldArenaDao::getKillRankList($aTeamId, $arrCond, $arrField, WorldArenaConf::KILL_REWARD_MAX_COUNT);
							break;
						}
						catch (Exception $e)
						{
							usleep(1000);
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get kill reward user for team id[%d] room id[%d], exception[%s], trace[%s], retry...', $aTeamId, $roomId, $e->getMessage(), $e->getTraceAsString());
						}
						
						if ($i == 3)
						{
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get kill reward user for team id[%d] room id[%d], failed', $aTeamId, $roomId);
						}
					}
						
					// 开始发击杀总数排名奖励
					$rank = 0;
					foreach ($arrKillRankUser as $aUser)
					{
						++$rank;
						$aServerId = $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
						$aPid = $aUser[WorldArenaCrossUserField::TBL_FIELD_PID];
						$aRewardTime = $aUser[WorldArenaCrossUserField::TBL_FIELD_KILL_REWARD_TIME];
						$aServerDb = $crossServer2Db[$aServerId];
						
						// 策划新需求，如果没有击杀过任何人，则给最低档奖励
						if ($aUser[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM] == 0) 
						{
							$rank = WorldArenaConf::KILL_REWARD_MAX_COUNT;
						}
						
						$arrInnerCond = array
						(
								array(WorldArenaInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
								array(WorldArenaInnerUserField::TBL_FIELD_PID, '=', $aPid),
						);
						$arrInnerField = array(WorldArenaInnerUserField::TBL_FIELD_UID);
						$innerInfo = WorldArenaDao::selectInnerUser($arrInnerCond, $arrInnerField, $aServerDb);
						if (empty($innerInfo))
						{
							throw new InterException('WORLD_ARENA_REWARD : can not get uid from inner for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						$aUid = $innerInfo[WorldArenaInnerUserField::TBL_FIELD_UID];
						
						// 取排名第一的人的信息
						if ($rank == 1)
						{
							$arrTopUser['kill']['serverId'] = $aServerId;
							$arrTopUser['kill']['pid'] = $aPid;
							$arrTopUser['kill']['uid'] = $aUid;
							$arrTopUser['kill']['serverDb'] = $aServerDb;
						}
						
						// 过滤不需要处理的玩家
						if (empty($arrSpec)
							|| (isset($arrSpec[$aTeamId]) && empty($arrSpec[$aTeamId]))
							|| (isset($arrSpec[$aTeamId][$aServerId]) && empty($arrSpec[$aTeamId][$aServerId]))
							|| isset($arrSpec[$aTeamId][$aServerId][$aPid]))
						{
							Logger::trace('WORLD_ARENA_REWARD : need run kill reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						else
						{
							Logger::trace('WORLD_ARENA_REWARD : no need run kill reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
							continue;
						}
					
						if ($aRewardTime >= $confObj->getAttackEndTime())
						{
							Logger::warning('WORLD_ARENA_REWARD : already send kill reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] when[%s] rank[%d]', $aTeamId, $roomId, $aServerId, $aPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime), $rank);
						}
						else
						{
							$arrReward = $confObj->getKillRankReward($rank);
							Logger::info('WORLD_ARENA_REWARD : send kill reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward);
							if ($commit)
							{
								try
								{
									$arrField = array
									(
											WorldArenaCrossUserField::TBL_FIELD_KILL_REWARD_TIME => Util::getTime(),
											WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
									);
									$arrCond = array
									(
											array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $aPid),
											array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
											array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									);
									WorldArenaDao::updateCrossUser($aTeamId, $arrCond, $arrField);
									
									$arrRewardUser[$aServerId][] = $aUid;
									RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::WORLD_ARENA_KILL_RANK_REWARD, array('rank' => $rank), $aServerDb);
								}
								catch (Exception $e)
								{
									Logger::fatal('WORLD_ARENA_REWARD : occur exception when send kill reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s], exception[%s], trace[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward, $e->getMessage(), $e->getTraceAsString());
								}
							}
						}
					}
					
					// 取最大连杀排名数据
					Logger::info('WORLD_ARENA_REWARD : team[%d] room[%d] type[conti] reward begin.', $aTeamId, $roomId);
					for ($i = 1; $i <= 3; ++$i)
					{
						try
						{
							$arrCond = array
							(
									array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									array(WorldArenaCrossUserField::TBL_FIELD_POS, '>', 0),
									array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
							);
							$arrField = array
							(
									WorldArenaCrossUserField::TBL_FIELD_SERVER_ID,
									WorldArenaCrossUserField::TBL_FIELD_PID,
									WorldArenaCrossUserField::TBL_FIELD_UID,
									WorldArenaCrossUserField::TBL_FIELD_CONTI_REWARD_TIME,
									WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM,
							);
							$arrContiRankUser = WorldArenaDao::getContiRankList($aTeamId, $arrCond, $arrField, WorldArenaConf::CONTI_REWARD_MAX_COUNT);
							break;
						}
						catch (Exception $e)
						{
							usleep(1000);
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get conti reward user for team id[%d] room id[%d], exception[%s], trace[%s], retry...', $aTeamId, $roomId, $e->getMessage(), $e->getTraceAsString());
						}
						
						if ($i == 3)
						{
							Logger::fatal('WORLD_ARENA_REWARD : occur exception when get conti reward user for team id[%d] room id[%d], failed', $aTeamId, $roomId);
						}
					}

					// 开始发最大连杀排名奖励
					$rank = 0;
					foreach ($arrContiRankUser as $aUser)
					{
						++$rank;
						$aServerId = $aUser[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
						$aPid = $aUser[WorldArenaCrossUserField::TBL_FIELD_PID];
						$aRewardTime = $aUser[WorldArenaCrossUserField::TBL_FIELD_CONTI_REWARD_TIME];
						$aServerDb = $crossServer2Db[$aServerId];
						
						// 策划新需求，如果没有击杀过任何人，则给最低档奖励
						if ($aUser[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM] == 0)
						{
							$rank = WorldArenaConf::CONTI_REWARD_MAX_COUNT;
						}
						
						$arrInnerCond = array
						(
								array(WorldArenaInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
								array(WorldArenaInnerUserField::TBL_FIELD_PID, '=', $aPid),
						);
						$arrInnerField = array(WorldArenaInnerUserField::TBL_FIELD_UID);
						$innerInfo = WorldArenaDao::selectInnerUser($arrInnerCond, $arrInnerField, $aServerDb);
						if (empty($innerInfo))
						{
							throw new InterException('WORLD_ARENA_REWARD : can not get uid from inner for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						$aUid = $innerInfo[WorldArenaInnerUserField::TBL_FIELD_UID];
						
						// 取排名第一的人的信息
						if ($rank == 1)
						{
							$arrTopUser['conti']['serverId'] = $aServerId;
							$arrTopUser['conti']['pid'] = $aPid;
							$arrTopUser['conti']['uid'] = $aUid;
							$arrTopUser['conti']['serverDb'] = $aServerDb;
						}
						
						// 过滤不需要处理的玩家
						if (empty($arrSpec)
							|| (isset($arrSpec[$aTeamId]) && empty($arrSpec[$aTeamId]))
							|| (isset($arrSpec[$aTeamId][$aServerId]) && empty($arrSpec[$aTeamId][$aServerId]))
							|| isset($arrSpec[$aTeamId][$aServerId][$aPid]))
						{
							Logger::trace('WORLD_ARENA_REWARD : need run conti reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
						}
						else
						{
							Logger::trace('WORLD_ARENA_REWARD : no need run conti reward for teamId[%d] roomId[%d] serverId[%d] pid[%d]', $aTeamId, $roomId, $aServerId, $aPid);
							continue;
						}
					
						if ($aRewardTime >= $confObj->getAttackEndTime())
						{
							Logger::warning('WORLD_ARENA_REWARD : already send conti reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] when[%s] rank[%d]', $aTeamId, $roomId, $aServerId, $aPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime), $rank);
						}
						else
						{
							$arrReward = $confObj->getContiRankReward($rank);
							Logger::info('WORLD_ARENA_REWARD : send conti reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward);
							if ($commit)
							{
								try
								{
									$arrField = array
									(
											WorldArenaCrossUserField::TBL_FIELD_CONTI_REWARD_TIME => Util::getTime(),
											WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
									);
									$arrCond = array
									(
											array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $aPid),
											array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
											array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
									);
									WorldArenaDao::updateCrossUser($aTeamId, $arrCond, $arrField);
									
									$arrRewardUser[$aServerId][] = $aUid;
									RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::WORLD_ARENA_CONTI_RANK_REWARD, array('rank' => $rank), $aServerDb);
								}
								catch (Exception $e)
								{
									Logger::fatal('WORLD_ARENA_REWARD : occur exception when send conti reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] rank[%d] reward[%s], exception[%s], trace[%s]', $aTeamId, $roomId, $aServerId, $aPid, $rank, $arrReward, $e->getMessage(), $e->getTraceAsString());
								}
							}
						}
					}
					
					// 发每个房间三榜第一的奖励
					if (isset($arrTopUser['pos'])
						&& isset($arrTopUser['kill'])
						&& isset($arrTopUser['conti'])
						&& $arrTopUser['pos'] === $arrTopUser['kill']
						&& $arrTopUser['pos'] === $arrTopUser['conti']) 
					{
						$kingServerId = $arrTopUser['pos']['serverId'];
						$kingPid = $arrTopUser['pos']['pid'];
						$kingUid = $arrTopUser['pos']['uid'];
						$kingServerDb = $arrTopUser['pos']['serverDb'];
						$kingCrossObj = WorldArenaCrossUserObj::getInstance($kingServerId, $kingPid, 0, $aTeamId, FALSE);
						$aRewardTime = $kingCrossObj->getKingRewardTime();
						
						if ($aRewardTime >= $confObj->getAttackEndTime())
						{
							Logger::warning('WORLD_ARENA_REWARD : already send king reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] when[%s]', $aTeamId, $roomId, $kingServerId, $kingPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime));
						}
						else
						{
							$arrReward = $confObj->getKingReward();
							Logger::info('WORLD_ARENA_REWARD : send king reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] reward[%s]', $aTeamId, $roomId, $kingServerId, $kingPid, $arrReward);
							if ($commit)
							{
								try
								{
									$kingCrossObj->setKingRewardTime(Util::getTime());
									$kingCrossObj->update();
										
									$arrRewardUser[$kingServerId][] = $kingUid;
									RewardUtil::reward3DtoCenter($kingUid, array($arrReward), RewardSource::WORLD_ARENA_KING_RANK_REWARD, array(), $kingServerDb);
								}
								catch (Exception $e)
								{
									Logger::fatal('WORLD_ARENA_REWARD : occur exception when send king reward for teamId[%d] roomId[%d] serverId[%d] pid[%d] reward[%s], exception[%s], trace[%s]', $aTeamId, $roomId, $aServerId, $aPid, $arrReward, $e->getMessage(), $e->getTraceAsString());
								}
							}
						}
					}
				}

				// 推送
				if ($commit)
				{
					foreach ($arrRewardUser as $aServerId => $arrUid)
					{
						if (empty($arrUid))
						{
							continue;
						}
						$arrUid = array_unique($arrUid);//可能有重复
						$group = Util::getGroupByServerId($aServerId);
						$proxy = new ServerProxy();
						$proxy->init($group, Util::genLogId());
						$proxy->sendMessage($arrUid, PushInterfaceDef::REWARD_NEW, array());
					}
				}
				
				Logger::info('WORLD_ARENA_REWARD : team[%d] reward end.', $aTeamId);
				usleep(10000);//每组发完奖励，sleep 10毫秒
			}
			catch (Exception $e)
			{
				Logger::fatal('WORLD_ARENA_REWARD : occur exception when reward for teamId[%d], exception[%s], trace[%s]', $aTeamId, $e->getMessage(), $e->getTraceAsString());
			}
		}
		
		Logger::info('WORLD_ARENA_REWARD : ****** [End] run reward at time[%s] commit[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */