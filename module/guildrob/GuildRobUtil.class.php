<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobUtil.class.php 259369 2016-08-30 07:04:50Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRobUtil.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-30 07:04:50 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259369 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobUtil
* Description : 军团抢粮战内部辅助类
* Inherit     :
**********************************************************************************************************************/
class GuildRobUtil
{	
	public static function checkAll($uid, $robId, $operationType)
	{
		// 获取当前玩家所在军团ID
		$currGuildId = GuildRobUtil::checkBasic($uid, $operationType);
		if (FALSE === $currGuildId) 
		{
			throw new FakeException("uid[%d] exec operation[%s] in rob battle[%d] error, checkBasic error.", $uid, $operationType, $robId);
		}
		Logger::trace("uid[%d] guild[%d] exec operation[%s] in rob battle[%d]:checkBasic is ok", $uid, $currGuildId, $operationType, $robId);
	
		// 抢夺军团ID就是抢夺战ID
		$attackGuildId = $robId;
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
	
		// 获取用户所在军团是否在这场抢粮战中
		$robObj = GuildRobObj::getInstance($robId);
		if (($currGuildId != $robObj->getGuildId() && $currGuildId != $robObj->getDefendGid())
		|| $robObj->getDefendGid() == 0)
		{
			throw new FakeException("uid[%d] guild[%d] exec operation[%s] in rob battle[%d] error, nethor attack guild[%d] nor defend guild[%d]", $uid, $currGuildId, $operationType, $robId, $robObj->getGuildId(), $robObj->getDefendGid());
		}
		Logger::trace("uid[%d] guild[%d] exec operation[%s] in rob battle[%d] as %s:rob obj is ok", $uid, $currGuildId, $operationType, $robId, $role);
		
		$startTime = $robObj->getStartTime();
	
		// 检测玩家数据表中的状态是否一致
		$robUserObj = GuildRobUserObj::getInstance($uid);
		if ($robUserObj->getRewardTime() > 0 // 本场战斗已经结束
		|| $robUserObj->getJoinTime() < $startTime // 加入战场时间小于战斗开始时间
		|| $robUserObj->getRobId() != $robId) // robId不一致
		{
			throw new FakeException("uid[%d] guild[%d] can not exec operation[%s] in rob battle[%d], because rob is over or different robId, rewardTime[%s], joinTime[%s], robStartTime[%s], currRobId[%d], userRobId[%d]",
					$uid, $currGuildId, $operationType, $robId, strftime("%Y%m%d-%H%M%S", $robUserObj->getRewardTime()), strftime("%Y%m%d-%H%M%S", $robUserObj->getJoinTime()), strftime("%Y%m%d-%H%M%S", $startTime), $robId, $robUserObj->getRobId());
		}
		Logger::trace("uid[%d] guild[%d] exec operation[%s] in rob battle[%d] as %s:rob user obj is ok", $uid, $currGuildId, $operationType, $robId, $role);
	
		// 判断是否在抢粮时间内
		if (FALSE === GuildRobUtil::checkRobTime($robId))
		{
			throw new FakeException("uid[%d] guild[%d] exec operation[%s] in rob battle[%d] error, because not in rob time", $uid, $currGuildId, $operationType, $robId);
		}
		Logger::trace("uid[%d] guild[%d] exec operation[%s] in rob battle[%d] as %s:rob time is ok", $uid, $currGuildId, $operationType, $robId, $role);
	
		return $currGuildId;
	}
	
	public static function checkBasic($uid, $operationType)
	{	
		// 判断是否在抢粮有效时间内
		if (FALSE === self::checkEffectTime())
		{
			Logger::trace("checkBasic : uid[%d] can not exec operation[%s], because checkEffectTime is error", $uid, $operationType);
			return FALSE;
		}
		
		// 如果是创建抢粮战的请求，整个抢粮战最晚结束时间应该早于活动结束时间，否则会存在活动结束，但是抢粮战还没有结束的问题
		if (GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_CREATE == $operationType)
		{
			$todayEndTimeStamp = self::getTodayEndEffectTime();
			$now = Util::getTime();
			$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
			if ($now + $totalBattleTime >= $todayEndTimeStamp)
			{
				Logger::trace("checkBasic : uid[%d] can not create rob battle, because rob activity will end within [%d] seconds, now[%s], end[%s]", $uid, $totalBattleTime, strftime("%Y%m%d-%H%M%S", $now), strftime("%Y%m%d-%H%M%S", $todayEndTimeStamp));
				return FALSE;
			}
		}
	
		// 判断用户是否属于一个军团，是的话获取军团ID和成员类型
		$ret = self::checkGuildMember($uid);
		if (FALSE === $ret)
		{
			Logger::trace("checkBasic : uid[%d] can not exec operation[%s], because checkGuildMember is error", $uid, $operationType);
			return FALSE;
		}
		list($guildId, $memberType) = $ret;
	
		// 判断权限
		if (FALSE == self::checkPrivilege($memberType, $operationType))
		{
			Logger::trace("checkBasic : uid[%d] can not exec operation[%s], because checkPrivilege is error", $uid, $operationType);
			return FALSE;
		}
	
		return $guildId;
	}
	
	public static function checkEffectTime($gap = 0)
	{
		$curWeekDay = Util::getTodayWeek();
		$curWeekDay = $curWeekDay ? $curWeekDay : 7;
		$now = Util::getTime();
	
		$config = GuildRobUtil::getEffectTime();
		if (!isset($config[$curWeekDay]))
		{
			Logger::trace("checkEffectTime : error because weekday[%d] not config in btstore", $curWeekDay);
			return FALSE;
		}
	
		$start = self::getTodayBeginEffectTime();
		$end = self::getTodayEndEffectTime();
		
		if (FALSE === $start || FALSE === $end) 
		{
			Logger::trace("checkEffectTime : error because today begin time or end time config wrong:start[%s],end[%s]", $start, $end);
			return FALSE;
		}
	
		if ($now < ($start - $gap) || $now > ($end + $gap))
		{
			Logger::trace("checkEffectTime : error because curr time[%d] not in the range start[%d] end[%d]", $now, $start, $end);
			return FALSE;
		}
	
		return TRUE;
	}
	
	public static function getTodayBeginEffectTime()
	{
		$curWeekDay = Util::getTodayWeek();
		$curWeekDay = $curWeekDay ? $curWeekDay : 7;
		
		$config = GuildRobUtil::getEffectTime();
		if (!isset($config[$curWeekDay]))
		{
			return FALSE;
		}
		
		$start = $config[$curWeekDay][0];
		if (strlen($start) < 6)
        {
            $start = sprintf('%06d', intval($start));
        }
		
		return  strtotime(Util::todayDate() . $start);
	}
	
	public static function getTodayEndEffectTime()
	{
		$curWeekDay = Util::getTodayWeek();
		$curWeekDay = $curWeekDay ? $curWeekDay : 7;
	
		$config = GuildRobUtil::getEffectTime();
		if (!isset($config[$curWeekDay]))
		{
			return FALSE;
		}
	
		$end = $config[$curWeekDay][1];
		if (strlen($end) < 6)
        {
            $end = sprintf('%06d', intval($end));
        }
	
		return  strtotime(Util::todayDate() . $end);
	}
	
	public static function checkRobTime($robId)
	{
		$robObj = GuildRobObj::getInstance($robId);
		$startTime = $robObj->getStartTime();
		$stage = $robObj->getStage();
		$readyTime = intval(btstore_get()->GUILD_ROB['ready_time']);
		$duration = intval(btstore_get()->GUILD_ROB['battle_time']);
		$curr = Util::getTime();
	
		if ($curr >= $startTime 
			&& $curr <= ($startTime + $readyTime + $duration)
			&& $stage == GuildRobField::GUILD_ROB_STAGE_START)
		{
			return TRUE;
		}
	
		Logger::warning("not in rob time or stage not right,curr[%s] start[%s] ready[%d] duration[%d] stage[%d]", strftime("%Y%m%d-%H%M%S", $curr), strftime("%Y%m%d-%H%M%S", $startTime), $readyTime, $duration, $stage);
		return FALSE;
	}
	
	public static function checkGuildMember($uid)
	{
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		$memberType = $guildMemberObj->getMemberType();
		
		if (0 == $guildId) 
		{
			return FALSE;
		}
	
		return array($guildId, $memberType);
	}
	
	public static function checkPrivilege($memberType, $operationType)
	{
		if (GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_CREATE == $operationType
			&& GuildMemberType::PRESIDENT != $memberType
			&& GuildMemberType::VICE_PRESIDENT != $memberType)
		{
			Logger::trace("checkPrivilege : error memberType[%d] do not have privilege to exec operation[%s]", $memberType, $operationType);
			return FALSE;
		}
	
		return TRUE;
	}
	
	public static function setLastDefendTime($guildId, $lastDefendTime)
	{
		$key = "guild.rob.lastDefendTime.$guildId";
		if(McClient::set($key, $lastDefendTime) != 'STORED')
		{
			Logger::warning("setLastDefendTime for guild[%d] lastDefendTime[%d] in memcache error", $guildId, $lastDefendTime);
			return FALSE;
		}
		
		return TRUE;
	}
	
	public static function getLastDefendTime($guildId)
	{
		$key = "guild.rob.lastDefendTime.$guildId";
		$lastDefendTime = McClient::get($key);
		
		return empty($lastDefendTime) ? 0 : intval($lastDefendTime);
	}
	
	public static function setLastAttackTime($guildId, $lastAttackTime)
	{
		$key = "guild.rob.lastAttackTime.$guildId";
		if(McClient::set($key, $lastAttackTime) != 'STORED')
		{
			Logger::warning("setLastAttackTime for guild[%d] lastAttackTime[%d] in memcache error", $guildId, $lastAttackTime);
			return FALSE;
		}
	
		return TRUE;
	}
	
	public static function getLastAttackTime($guildId)
	{
		$key = "guild.rob.lastAttackTime.$guildId";
		$lastAttackTime = McClient::get($key);
	
		return empty($lastAttackTime) ? 0 : intval($lastAttackTime);
	}
	
	public static function canRob($attackGuildId, $defendGuildId, &$rCanRobGrain)
	{
		$attackGuildObj = GuildObj::getInstance($attackGuildId);
		$defendGuildObj = GuildObj::getInstance($defendGuildId);
		
		// 检查抢粮军团是否开启了粮仓
		if (FALSE === $attackGuildObj->isGuildBarnOpen()) 
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because attacker guild barn is not open", $attackGuildId, $defendGuildId);
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACK_BARN_NOT_OPEN;
		}
		
		// 检查被抢军团是否开启了粮仓
		if (FALSE === $defendGuildObj->isGuildBarnOpen())
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because defender guild barn is not open", $attackGuildId, $defendGuildId);
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFEND_BARN_NOT_OPEN;
		}
		
		// 检查抢夺军团是否在冷却时间内，这段时间内，不能抢别的军团
		$lastAttackTime = self::getLastAttackTime($attackGuildId);
		if(Util::getTime() <= $lastAttackTime + intval(btstore_get()->GUILD_ROB['after_attack_cd_time']))
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because attacker guild is in cd time, currTime[%d] lastAttackTime[%d] afterAttackCdTime[%d]", $attackGuildId, $defendGuildId, Util::getTime(), $lastAttackTime, intval(btstore_get()->GUILD_ROB['after_attack_cd_time']));
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACK_IN_CD;
		}
		
		// 检查被抢军团是否在冷却时间内，这段时间内，别的军团不能抢他
		$lastDefendTime = self::getLastDefendTime($defendGuildId);
		if(Util::getTime() <= $lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time']))
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because defender guild is in shelter time, currTime[%d] lastDefendTime[%d] afterDefendCdTime[%d]", $attackGuildId, $defendGuildId, Util::getTime(), $lastDefendTime, intval(btstore_get()->GUILD_ROB['after_defend_cd_time']));
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFEND_IN_SHELTER;
		}
		
		// 检查被抢军团粮草数量是否满足条件
		$currGrainNum = $defendGuildObj->getGrainNum();
		$grainUpperLimit = $defendGuildObj->getGrainLimit();
		$canRobGrain = self::getCanRobGrain($currGrainNum, $grainUpperLimit);
		if ($canRobGrain <= 0)
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because defender guild grain too less, curr[%d] limit[%d] minPercent[%d]", $attackGuildId, $defendGuildId, $currGrainNum, $grainUpperLimit, intval(btstore_get()->GUILD_ROB['can_rob_min_percent']));
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFEND_LOW_GRAIN;
		}
		
		// 检查被抢军团今天被抢的次数是否超过上限
		$defendNum = $defendGuildObj->getDefendNum();
		$defendLimit = intval(btstore_get()->GUILD_ROB['defend_limit']);
		if ($defendNum >= $defendLimit)
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because defender guild defend too much, curr[%d] limit[%d]", $attackGuildId, $defendGuildId, $defendNum, $defendLimit);
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFEND_TOO_MUCH;
		}
		
		// 检查抢粮军团今天抢粮次数是否超过上限
		$attackNum = $attackGuildObj->getAttackNum();
		$attackLimit = intval(btstore_get()->GUILD_ROB['attack_limit']);
		if ($attackNum >= $attackLimit)
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because attacker guild attack too much, curr[%d] limit[%d]", $attackGuildId, $defendGuildId, $attackNum, $attackLimit);
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACK_TOO_MUCH;
		}
		
		// 检查战书是否足够
		$fightBookNum = $attackGuildObj->getFightBook();
		if ($fightBookNum <= 0)
		{
			Logger::trace("canRob : guild[%d] can not rob guild[%d], because attacker guild has no fight book", $attackGuildId, $defendGuildId);
			return GuildRobCreateRet::GUILD_ROB_CREATE_RET_LACK_FIGHT_BOOK;
		}
		
		$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
		$todayBeginTimeStamp = self::getTodayBeginEffectTime();
		if (FALSE === $todayBeginTimeStamp) 
		{
			throw new FakeException("impossiible, call checkBasic before call canRob!!!");
		}
		Logger::trace("canRob : today begin effect time : %s, total battle duration : %d", strftime("%Y%m%d-%H%M%S", $todayBeginTimeStamp), $totalBattleTime);
		
		// 检查两个军团是不是在抢夺其他军团, 检查两个军团是不是在被其他军团抢粮
		$arrField = array
			(
				GuildRobField::TBL_FIELD_GUILD_ID,
				GuildRobField::TBL_FIELD_DEFEND_GUILD_ID,
				GuildRobField::TBL_FIELD_START_TIME,
				GuildRobField::TBL_FIELD_STAGE,
			);
		
		$arrCondInAttack = array
			(
				array(GuildRobField::TBL_FIELD_GUILD_ID, 'in', array($attackGuildId, $defendGuildId)),
				array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
				array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
			);
		$infoInAttack = GuildRobDao::selectMultiRob($arrCondInAttack, $arrField);
		Logger::trace("canRob : guild[%d] rob guild[%d], wether in attack, info from db:%s", $attackGuildId, $defendGuildId, $infoInAttack);
		
		$arrCondInDefend = array
		(
				array(GuildRobField::TBL_FIELD_DEFEND_GUILD_ID, 'in', array($attackGuildId, $defendGuildId)),
				array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
				array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
		);
		$infoInDefend = GuildRobDao::selectMultiRob($arrCondInDefend, $arrField);
		Logger::trace("canRob : guild[%d] rob guild[%d], wether in defend, info from db:%s", $attackGuildId, $defendGuildId, $infoInDefend);
				
		if (!empty($infoInAttack) && isset($infoInAttack[$attackGuildId]))
		{
			if ((Util::getTime() - $infoInAttack[$attackGuildId][GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET) 
			{
				Logger::warning("canRob : curr attacker guild[%d] attack guild[%d] before, but not end successfully, stage:%d", $attackGuildId, $infoInAttack[$attackGuildId][GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $infoInAttack[$attackGuildId][GuildRobField::TBL_FIELD_STAGE]);
			}
			else 
			{
				Logger::trace("canRob : guild[%d] can not rob guild[%d], attacker is attacking", $attackGuildId, $defendGuildId);
				return GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACKER_ATTACKING;
			}
		}
		
		if (!empty($infoInAttack) && isset($infoInAttack[$defendGuildId]))
		{
			if ((Util::getTime() - $infoInAttack[$defendGuildId][GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
			{
				Logger::warning("canRob : curr defeneder guild[%d] attack guild[%d] before, but not end successfully, stage:%d", $defendGuildId, $infoInAttack[$defendGuildId][GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $infoInAttack[$defendGuildId][GuildRobField::TBL_FIELD_STAGE]);
			}
			else
			{
				Logger::trace("canRob : guild[%d] can not rob guild[%d], defender is attacking", $attackGuildId, $defendGuildId);
				return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFENDER_ATTACKING;
			}
		}
		
		if (!empty($infoInDefend)) 
		{
			foreach ($infoInDefend as $currAttackGuildId => $defendInfo)
			{
				$isAttacker = ($defendInfo[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID] == $attackGuildId ? TRUE : FALSE);
				if ((Util::getTime() - $defendInfo[GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
				{
					Logger::warning("canRob : curr %s guild[%d] is attacked by guild[%d] before, but not end successfully, stage:%d", ($isAttacker ? "attacker" : "defender"), $defendInfo[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $currAttackGuildId, $defendInfo[GuildRobField::TBL_FIELD_STAGE]);
				}
				else 
				{
					if ($isAttacker)
					{
						Logger::trace("canRob : guild[%d] can not rob guild[%d], attacker is defending", $attackGuildId, $defendGuildId);
						return GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACKER_DEFENDING;
					}
					else
					{
						Logger::trace("canRob : guild[%d] can not rob guild[%d], defender is defending", $attackGuildId, $defendGuildId);
						return GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFENDER_DEFENDING;
					}
				}
			}
		}
	
		// 如果能够抢夺该军团，将可抢夺数量返回
		$rCanRobGrain = $canRobGrain;
		return GuildRobCreateRet::GUILD_ROB_CREATE_RET_OK;
	}
	
	public static function getBattleData($uid, $isAttacker = true)
	{
		$userObj = EnUser::getUserObj($uid);
		$battleData = $userObj->getBattleFormation();
		
		$arrHero = $battleData ['arrHero'];
		$arrHero = BattleUtil::unsetEmpty($arrHero);
		foreach($arrHero as &$hero)
		{
			$hero[PropertyKey::CURR_HP] = $hero[PropertyKey::MAX_HP];
		}
		
		$formation = array
			(
				'name' => $userObj->getUname(),
				'level' => $userObj->getLevel(),
				'isPlayer' => true,
				'uid' => $uid,
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
		
		return array('formation' => $arrClientformation, 'arrHero' => $arrHero);
	}
	
	public static function broadcastKillTopN($robId, $winnerId)
	{
		Logger::trace('GuildRobUtil::broadcastKillTopN for rob battle[%d] winnerId[%d] begin...', $robId, $winnerId);
		
		$arrField = array
		(
				GuildRobUserField::TBL_FIELD_UID,
				GuildRobUserField::TBL_FIELD_UNAME,
				GuildRobUserField::TBL_FIELD_KILL_NUM,
		);
		$allKillInfo = GuildRobDao::getKillTopN($robId, -1, $arrField);
	
		$topTen = array();
		$rankInfos = array();
		$rank = 0;
		foreach ($allKillInfo as $value)
		{
			$tmp = array();
			$tmp['rank'] = ++$rank;
			$tmp['uname'] = $value[GuildRobUserField::TBL_FIELD_UNAME];
			$tmp['killNum'] = $value[GuildRobUserField::TBL_FIELD_KILL_NUM];
			
			$rankInfos[$value[GuildRobUserField::TBL_FIELD_UID]] = $tmp;
			if ($rank <= GuildRobConf::KILL_NUM_TOP_N) 
			{
				$topTen[$value[GuildRobUserField::TBL_FIELD_UID]] = $tmp;
			}
		}
		
		$broadcastAll = FALSE;
		$chance = rand(0, UNIT_BASE);
		if($chance <= GuildRobConf::BROADCAST_CHANCE)
		{	
			$broadcastAll = TRUE;
		}
		Logger::trace('GuildRobUtil::broadcastKillTopN, currChance:%d, broadcastChance:%d, broadcastAll:%s', $chance, GuildRobConf::BROADCAST_CHANCE, ($broadcastAll ? 'yes' : 'no'));
		
		foreach ($rankInfos as $aUid => $rankInfo)
		{
			if (!$broadcastAll && $aUid != $winnerId) 
			{
				continue;
			}
			
			$msg = array();
			foreach ($topTen as $key => $value)
			{
				$msg[$key] = $value;
			}
			if (!isset($msg[$aUid])) 
			{
				$msg[$aUid] = $rankInfo;
			}
			
			RPCContext::getInstance()->sendMsg(array($aUid), PushInterfaceDef::GUILD_ROB_TOP_N, $msg);
			Logger::trace('GuildRobUtil::broadcastKillTopN in rob battle[%d] for user[%d], rankInfo:%s', $robId, $aUid, $msg);
		}
		
		Logger::trace('GuildRobUtil::broadcastKillTopN for rob battle[%d] winnerId[%d] end...', $robId, $winnerId);
	}
	
	public static function getRobConfig($attackGuildId, $defendGuildId, $canRobGrain)
	{
		$prepareTime = intval(btstore_get()->GUILD_ROB['ready_time']);
		$battleDuration = intval(btstore_get()->GUILD_ROB['battle_time']) + $prepareTime;
		$joinCd = intval(btstore_get()->GUILD_ROB['join_cd']);
		$addRoadThr = intval(btstore_get()->GUILD_ROB['open_3rd_limit']);
		$routeConfig = btstore_get()->GUILD_ROB['route']->toArray();
		$routeNum = count($routeConfig);
		$moveSpeed = 1;
		foreach ($routeConfig as &$route)
		{
			$route = intval($route) * 1000 * $moveSpeed;
		}
		
		$config = btstore_get()->GUILD_ROB['attack_touch_down_user_reward']->toArray();
		$robSpeed = intval(ceil($canRobGrain * intval($config[0]) / UNIT_BASE));
		if ($robSpeed < intval(btstore_get()->GUILD_ROB['rob_grain_least'])) 
		{
			Logger::trace('GuildRobUtil::getRobConfig robSpeed[%d] is less than least[%d], make it equal', $robSpeed, intval(btstore_get()->GUILD_ROB['rob_grain_least']));
			$robSpeed = intval(btstore_get()->GUILD_ROB['rob_grain_least']);
		}
		
		$beatSpeed = intval(btstore_get()->GUILD_ROB['beat_speed']);
		$subTimeSpeed = intval(btstore_get()->GUILD_ROB['sub_time_speed']);
		 
		$fieldConf = array
		(
				'refreshTimeMs' => 1000,			// 场景刷新时间(ms)
				'refreshOutMs' => 1000,				// 将场景数据刷新到前端的周期(ms)。目前需要refreshOutMs=refreshTimeMs，不配置也默认refreshOutMs=refreshTimeMs
				'roadNum' => $routeNum,				// 有几个通道
				'maxGroupSize' => 100,			    // 每个阵营上的最大人数
				'maxGroupOnlineSize' => 100,		// 每个阵营中的最大在线人数
				'battleDuration' => $battleDuration,// 战斗持续时间(s)，传给lcserver时间，包含准备时间和真正的战斗时间
				'prepareTime' => $prepareTime,		// 战斗准备时间(s)
				'presenceIntervalMs' => 1000,       // 传送阵到通道的时间间隔(ms)，这个值必须是refreshTimeMs的整数倍
				'joinCdTime' => $joinCd,			// 战败或者占领粮仓后，重新出战冷却时间(s)
				'joinReadyTime' => $joinCd,			// 退出战场后，重新进入战场冷却时间(s)
				'maxWaitQueue' => 100,             	// 传送阵中最大等待人数
				'speed' => $moveSpeed,              // 战斗单位移动速度
				'roadLength' => $routeConfig,		// 通道长度
				'collisionRange' => 1000,           // 检测碰撞的范围
				'addRoadThr' => $addRoadThr,		// 场内达到这个人数后，就通知前端增加通道,后续需要从配置文件GUILD_ROB读取
				'robLimit' => $canRobGrain,         // 能够抢多的粮草数量上限
				'robSpeed' => $robSpeed,            // 抢粮的速度，既每次达阵能够获得的粮草数
				'beatSpeed' => $beatSpeed,          // 打击对方士气的速度，既每次达阵，对方减少的士气值
				'subTimeSpeed' => $subTimeSpeed,    // 守方达阵后，能够减少战场持续时间，这个是每次减少的时间值
		        'delay' => 3,                       // 离线入场cd数完后延迟几秒上场
					
				'battleEndCondition' => array		// 战斗结束条件
				(
						'dummy' => true
				),
				
				'battleExtra' => array				// 战斗额外配置
				(
						'dummy' => true,
						'isPvp' => 1,
				),
					
				'replayConf' => array				// 战报相关配置
				(
						'bgId' => 28,
						'type' => 14,//TODO 去def
						'musicId' => 0,
				),
		);
		
		$arrAttackCond = array(array(GuildDef::GUILD_ID, '=', $attackGuildId));
		$attackGuildMemberCount = GuildDao::getMemberCount($arrAttackCond);
		$attackGuildObj = GuildObj::getInstance($attackGuildId);
		
		$arrDefendCond = array(array(GuildDef::GUILD_ID, '=', $defendGuildId));
		$defendGuildMemberCount = GuildDao::getMemberCount($arrDefendCond);
		$defendGuildObj = GuildObj::getInstance($defendGuildId);
		
		$arrAttackGuildMemberList = EnGuild::getMemberList($attackGuildId, array('uid'));
		$arrDefendGuildMemberList = EnGuild::getMemberList($defendGuildId, array('uid'));
		
		$arrUserId = array_merge(array_keys($arrAttackGuildMemberList), array_keys($arrDefendGuildMemberList));
		
		$arrCond = array(
		    array(GuildRobUserField::TBL_FIELD_UID, 'IN', $arrUserId),
		    array(GuildRobUserField::TBL_FIELD_OFFLINE_TIME, '!=', 0)
		);
		
		$arrUserInfo = GuildRobDao::selectArrUser($arrCond, GuildRobUserField::$GUILD_ROB_USER_ALL_FIELDS);
		
		$arrAllOfflineUser = array();
		$arrAttackOfflineUser = array();
		$arrDefendOfflineUser = array();
		
		foreach ( $arrUserInfo as $userInfo )
		{
		    $uid = $userInfo[GuildRobUserField::TBL_FIELD_UID];
		    if ( isset( $arrAttackGuildMemberList[$uid] ) )
		    {
		        $arrAllOfflineUser[] = $uid;
		        $arrAttackOfflineUser[] = $uid;
		    }
		    if ( isset( $arrDefendGuildMemberList[$uid] ) )
		    {
		        $arrAllOfflineUser[] = $uid;
		        $arrDefendOfflineUser[] = $uid;
		    }
		}
		
		$arrAllOfflineUserInfo = array();
		if ( !empty( $arrAllOfflineUser ) )
		{
		    $arrAllOfflineUserInfo = EnUser::getArrUserBasicInfo($arrAllOfflineUser, array('uid', 'uname', 'master_hid', 'guild_id'));
		    
		    $arrHid = array();
		    foreach ( $arrAllOfflineUserInfo as $key => $info )
		    {
		        $arrHid[] = $info['master_hid'];
		        
		        $arrAllOfflineUserInfo[$key]['groupId'] = $info['guild_id'];
		        unset( $arrAllOfflineUserInfo[$key]['guild_id'] );
		    }
		    
		    $arrHeroInfo = HeroUtil::getArrHero($arrHid, array('htid'));
		    
		    foreach ( $arrAllOfflineUserInfo as $key => $info )
		    {
		        $arrAllOfflineUserInfo[$key]['master_htid'] = $arrHeroInfo[$info['master_hid']]['htid'];
		        unset( $arrAllOfflineUserInfo[$key]['master_hid'] );
		    }
		}
		
		$arrAttackOfflineUserInfo = array();
		$arrDefendOfflineUserInfo = array();
		
		foreach ( $arrAllOfflineUserInfo as $uid => $userInfo )
		{
		    if ( in_array($uid, $arrAttackOfflineUser) )
		    {
		        $arrAttackOfflineUserInfo[] = $userInfo;
		    }
		    else
		    {
		        $arrDefendOfflineUserInfo[] = $userInfo;
		    }
		}
	
		$battleInfo = array
		(
				'fieldConf' => $fieldConf,			// 战场配置
				'attacker' => array					// 攻击者
				(
						'id' => $attackGuildId,
						'name' => $attackGuildObj->getGuildName(),
						'totalMemberCount' => intval($attackGuildMemberCount),
						'morale' => intval(btstore_get()->GUILD_ROB['attacker_morale']),
				        'offlineUserInfo' => $arrAttackOfflineUserInfo,
				),
				'defender' => array					// 防守者
				(
						'id' => $defendGuildId,
						'name' => $defendGuildObj->getGuildName(),
						'totalMemberCount' => intval($defendGuildMemberCount),
						'morale' => 0,
				        'offlineUserInfo' => $arrDefendOfflineUserInfo,
				),
				'callMethods' => array				// 后端回调
				(
						'fightWin' => 'guildrob.onFightWin',
						'fightLose' => 'guildrob.onFightLose',
						'touchDown' => 'guildrob.onTouchDown',
						'battleEnd' => 'guildrob.onBattleEnd',
    				    'npcJoin' => 'guildrob.npcJoin',
    				    'getBattleData' => 'guildrob.getBattleData',
				),
				'frontCallbacks' => array			// 前端回调
				(
						'refresh' => PushInterfaceDef::GUILD_ROB_REFRESH,
						'fightWin' => PushInterfaceDef::GUILD_ROB_FIGHT_WIN,
						'fightLose' => PushInterfaceDef::GUILD_ROB_FIGHT_LOSE,
						'touchDown' => PushInterfaceDef::GUILD_ROB_TOUCH_DOWN,
						'fightResult' => PushInterfaceDef::GUILD_ROB_FIGHT_RESULT,
						'battleEnd' => PushInterfaceDef::GUILD_ROB_BATTLE_END,
						'scoreTopN' => PushInterfaceDef::GUILD_ROB_TOP_N,
						'reckon' => PushInterfaceDef::GUILD_ROB_RECKON,
				),
		);
	
		Logger::trace("getRobConfig:guild[%d] rob battle to guild[%d] battle config:%s", $attackGuildId, $defendGuildId, $battleInfo);
		return $battleInfo;
	}
	
	public static function pushSpecBarnFightInfo($robId, $winnerId, $loserId, $winStreak, $terminalStreak, $brid, $winnerReward, $loserReward)
	{
		$winnerName = EnUser::getUserObj($winnerId)->getUname();
		$loserName = EnUser::getUserObj($loserId)->getUname();
		
		// 广播fightResult
		$broadcastInfo = array();
		$broadcastInfo['winnerId'] = $winnerId;
		$broadcastInfo['loserId'] = $loserId;
		$broadcastInfo['winnerName'] = $winnerName;
		$broadcastInfo['loserName'] = $loserName;
		$broadcastInfo['winStreak'] = $winStreak;
		$broadcastInfo['terminalStreak'] = $terminalStreak;
		$broadcastInfo['brid'] = $brid;
		RPCContext::getInstance()->broadcastGroupBattle($robId, $broadcastInfo, PushInterfaceDef::GUILD_ROB_FIGHT_RESULT);
		
		// 向胜者发送奖励消息
		$winnerInfo = array();
		$winnerInfo['reward'] = $winnerReward;
		$winnerInfo['extra'] = array('adversaryName' => $loserName);
		RPCContext::getInstance()->sendMsg(array($winnerId), PushInterfaceDef::GUILD_ROB_FIGHT_WIN, $winnerInfo);
		
		// 向败者发送奖励信息
		$loserInfo = array();
		$loserInfo['reward'] = $loserReward;
		$loserInfo['extra'] = array
							(
								'adversaryName' => $winnerName,
								'joinCd' => Util::getTime() + intval(btstore_get()->GUILD_ROB['join_cd']),
							);
		
		RPCContext::getInstance()->sendMsg(array($loserId), PushInterfaceDef::GUILD_ROB_FIGHT_LOSE, $loserInfo);
	}
	
	public static function getTotalHp($uid)
	{
		$total = 0;
		$arrHp = self::getArrHeroHp($uid);
		foreach ($arrHp as $hid => $hp)
		{
			$total += $hp;
		}
		
		return $total;
	}
	
	public static function getArrHeroHp($uid)
	{
		$arrHeroHp = array();
		$battleFormat = EnUser::getUserObj($uid)->getBattleFormation();
		foreach($battleFormat['arrHero'] as $pos => $heroInfo)
		{
			$hid = $heroInfo[PropertyKey::HID];
			$hp = $heroInfo[PropertyKey::MAX_HP];
			$arrHeroHp[$hid] = $hp;
		}
	
		return $arrHeroHp;
	}
	
	public static function updateBattleFormat($battleFormat, $arrHeroHp, $delDeadBody = false)
	{
		foreach($battleFormat['arrHero'] as $pos => $heroInfo)
		{
			$hid = $heroInfo[PropertyKey::HID];
			if( $delDeadBody && $arrHeroHp[$hid] < 1 )
			{
				unset($battleFormat['arrHero'][$pos]);
				continue;
			}
				
			$battleFormat['arrHero'][$pos][PropertyKey::CURR_HP] = $arrHeroHp[$hid];
		}
	
		return $battleFormat;
	}
	
	public static function guildRobInfoChanged($guildId, $notifyRobId = FALSE, $robId = 0)
	{
		Logger::trace('GuildRobUtil::guildRobInfoChanged begin...');
		
		$guildRobInfo = self::getGuildRobInfo($guildId, $notifyRobId, $robId);
		
		// 向所有在抢粮区域页面的玩家推送
		RPCContext::getInstance()->sendFilterMessage('arena', SPECIAL_ARENA_ID::GUILDROB, PushInterfaceDef::GUILD_ROB_INFO, $guildRobInfo);
		Logger::trace('GuildRobUtil::guildRobInfoChanged push for all in rob page, guild[%d], info[%s]', $guildId, $guildRobInfo);
		
		// 向该军团所有成员推送
		$arrUid = EnGuild::getMemberList($guildId, array(GuildRobUserField::TBL_FIELD_UID));
		$arrNotifyUid = Util::arrayExtract($arrUid, GuildRobUserField::TBL_FIELD_UID);
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::GUILD_ROB_INFO, $guildRobInfo);
		Logger::trace('GuildRobUtil::guildRobInfoChanged push for all member in guild[%d], info[%s]', $guildId, $guildRobInfo);
		
		Logger::trace('GuildRobUtil::guildRobInfoChanged end...');
	}
	
	public static function getGuildRobInfo($guildId, $notifyRobId = FALSE, $robId = 0)
	{
		$guildObj = GuildObj::getInstance($guildId);
		
		// 获取可抢粮草
		$canRobGrain = 0;
		$currGrainNum = $guildObj->getGrainNum();
		$grainUpperLimit = $guildObj->getGrainLimit();
		$canRobGrain = self::getCanRobGrain($currGrainNum, $grainUpperLimit);
		if ($canRobGrain < 0)
		{
			$canRobGrain = 0;
		}
		
		// 生成数据
		$guildRobInfo = array();
		$guildRobInfo['guildId'] = $guildId;
		$guildRobInfo['name'] = $guildObj->getGuildName();
		$guildRobInfo['fight_book'] = $guildObj->getFightBook();
		$guildRobInfo['grain'] = $canRobGrain;
		$guildRobInfo['barn_level'] = $guildObj->getBuildLevel(GuildDef::BARN);
		if ($notifyRobId)
		{
			$guildRobInfo['robId'] = $robId;
		}
		else
		{
			$guildRobInfo['robId'] = self::getGuildRobId($guildId);
		}
		
		$lastDefendTime = GuildRobUtil::getLastDefendTime($guildId);
		if ($lastDefendTime == 0 || ($lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time'])) <= Util::getTime()) 
		{
			$guildRobInfo['shelterTime'] = 0;
		}
		else 
		{
			$guildRobInfo['shelterTime'] = $lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time']);
		}
		
		$lastAttackTime = GuildRobUtil::getLastAttackTime($guildId);
		if ($lastAttackTime == 0 || ($lastAttackTime + intval(btstore_get()->GUILD_ROB['after_attack_cd_time'])) <= Util::getTime())
		{
			$guildRobInfo['cdTime'] = 0;
		}
		else
		{
			$guildRobInfo['cdTime'] = $lastAttackTime + intval(btstore_get()->GUILD_ROB['after_attack_cd_time']);
		}
		
		return $guildRobInfo;
	}
	
	public static function getGuildRobId($guildId)
	{
		// 先检查在不在抢粮活动期间内，如果不在抢粮活动期间直接返回0
		if (FALSE == self::checkEffectTime())
		{
			return 0;
		}
		
		$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
		$todayBeginTimeStamp = self::getTodayBeginEffectTime();
		if (FALSE === $todayBeginTimeStamp)
		{
			throw new FakeException("impossiible, already call checkEffectTime at the beginning!!!");
		}
		Logger::trace("GuildRobUtil::getGuildRobId : today begin effect time : %s, total battle duration : %d", strftime("%Y%m%d-%H%M%S", $todayBeginTimeStamp), $totalBattleTime);
		
		$arrField = array
		(
				GuildRobField::TBL_FIELD_GUILD_ID,
				GuildRobField::TBL_FIELD_DEFEND_GUILD_ID,
				GuildRobField::TBL_FIELD_START_TIME,
				GuildRobField::TBL_FIELD_STAGE,
		);
		
		$arrCondInAttack = array
		(
				array(GuildRobField::TBL_FIELD_GUILD_ID, '=', $guildId),
				array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
				array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
		);
		$infoInAttack = GuildRobDao::selectRob($arrCondInAttack, $arrField);
		
		$arrCondInDefend = array
		(
				array(GuildRobField::TBL_FIELD_DEFEND_GUILD_ID, '=', $guildId),
				array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
				array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
		);
		$infoInDefend = GuildRobDao::selectMultiRob($arrCondInDefend, $arrField);
		
		if (!empty($infoInAttack)) 
		{
			if ((Util::getTime() - $infoInAttack[GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
			{
				Logger::warning("GuildRobUtil::getGuildRobId : curr guild[%d] attack guild[%d] before, but not end successfully, stage:%d", $guildId, $infoInAttack[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $infoInAttack[GuildRobField::TBL_FIELD_STAGE]);
			}
			else
			{
				return $infoInAttack[GuildRobField::TBL_FIELD_GUILD_ID];
			}
		}
		
		if (!empty($infoInDefend))
		{
			foreach ($infoInDefend as $currAttackGuildId => $defendInfo)
			{
				if ((Util::getTime() - $defendInfo[GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
				{
					Logger::warning("GuildRobUtil::getGuildRobId : curr guild[%d] is attacked by guild[%d] before, but not end successfully, stage:%d", $guildId, $currAttackGuildId, $defendInfo[GuildRobField::TBL_FIELD_STAGE]);
				}
				else
				{
					return $defendInfo[GuildRobField::TBL_FIELD_GUILD_ID];
				}
			}
		}
		
		return 0;
	}
	
	public static function directEndRob($robId)
	{
		Logger::warning("GuildRobUtil::directEndRob : direct end rob obj[%d]", $robId);
		
		$robObj = GuildRobObj::getInstance($robId);
		$robObj->directEnd();
		$robObj->update();
	}
	
	public static function isRobBattleAlive($arrBattleId)
	{
		Logger::trace("GuildRobUtil::isRobBattleAlive begin ... battle ids : %s", $arrBattleId);
		
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->groupBattleAlive($arrBattleId);
		if(!is_array($ret))
		{
			Logger::warning('GuildRobUtil::isRobBattleAlive: lcserver return error: %s', $ret);
			return array();
		}
		
		Logger::trace("GuildRobUtil::isRobBattleAlive end ... ret : %s", $ret);
		return $ret;
	}
	
	public static function getPids($attackGuildId, $defendGuildId)
	{
		$data = new CData();
		$arrRet = $data->select(array(GuildDef::USER_ID, GuildDef::GUILD_ID))
						->from(GuildDef::TABLE_GUILD_MEMBER)
						->where(array(GuildDef::GUILD_ID, 'IN', array($attackGuildId, $defendGuildId)))
						->limit(0, CData::MAX_FETCH_SIZE)
						->query();
		
		$arrUid2GuildId = Util::arrayIndex($arrRet, GuildDef::USER_ID);
		
		$arrUids = array_keys($arrUid2GuildId);
		$arrUid2Pid = array();
		$count = CData::MAX_FETCH_SIZE;
		$i = 0;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$uids = array_slice($arrUids, $i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			if (empty($uids))
			{
				break;
			}
			
			$ret = UserDao::getArrUserByArrUid($uids, array('pid', 'uid'));
			$arrUid2Pid = array_merge($arrUid2Pid, $ret);
			$count = count($ret);
			$i++;
		}
		$arrUid2Pid = Util::arrayIndex($arrUid2Pid, 'uid');
		
		$arrAttackPid = array();
		$arrDefendPid = array();
		foreach ($arrUid2GuildId as $aUid => $aInfo)
		{
			if ($aInfo[GuildDef::GUILD_ID] == $attackGuildId)
			{
				$arrAttackPid[] = $arrUid2Pid[$aUid]['pid'];
			}
			else
			{
				$arrDefendPid[] = $arrUid2Pid[$aUid]['pid'];
			}
		}
		
		return array($attackGuildId => $arrAttackPid, $defendGuildId => $arrDefendPid);
	}
	
	public static function getCanRobGrain($currGrainNum, $grainUpperLimit)
	{
		if ($currGrainNum <= intval($grainUpperLimit * intval(btstore_get()->GUILD_ROB['can_rob_min_percent']) / UNIT_BASE)) 
		{
			return 0;
		}
		return intval($currGrainNum * intval(btstore_get()->GUILD_ROB['can_rob_percent']) / UNIT_BASE); 
	}
	
	public static function getRemoveCdCost($removeTimes)
	{
		$clearCostConfig = btstore_get()->GUILD_ROB['clear_join_cd_cost']->toArray();
		
		$count = count($clearCostConfig);
		$index = 0;
		$cost = 0;
		foreach ($clearCostConfig as $key => $value)
		{
			++$index;
			if ($removeTimes <= $key || $index == $count)
			{
				$cost = intval($value);
				break;
			}
		}
		
		return $cost;
	}
	
	public static function getEffectTime()
	{
		$conf = btstore_get()->GUILD_ROB['effect_time']->toArray();
		if (GuildRobConf::TEST_MODE > 0) 
		{
			$conf[date('N')] = array(0, 235959);
		}
		
		return $conf;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */