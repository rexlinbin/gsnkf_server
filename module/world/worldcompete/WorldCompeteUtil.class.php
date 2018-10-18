<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteUtil.class.php 244482 2016-05-27 08:33:50Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompeteUtil.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-27 08:33:50 +0000 (Fri, 27 May 2016) $
 * @version $Revision: 244482 $
 * @brief 
 *  
 **/
 
class WorldCompeteUtil
{	
	/**
	 * 根据配置获得挑战的免费次数
	 *
	 * @return int
	 */
	public static function getAtkDefault()
	{
		return btstore_get()->WORLD_COMPETE_RULE['atk_default'];
	}
	
	/**
	 * 根据配置获得刷新的免费次数
	 *
	 * @return int
	 */
	public static function getRefreshDefault()
	{
		return btstore_get()->WORLD_COMPETE_RULE['refresh_default'];
	}
	
	/**
	 * 根据配置获得挑战的购买上限
	 * 
	 * @return int
	 */
	public static function getBuyLimit()
	{
		return btstore_get()->WORLD_COMPETE_RULE['buy_limit'];
	}
	
	/**
	 * 根据配置和当前购买的次数，得到花费
	 * 
	 * @param int $buyNum
	 * @return int
	 */
	public static function getBuyCost($buyNum)
	{
		$buyCostConf = btstore_get()->WORLD_COMPETE_RULE['buy_cost']->toArray();
		
		$cost = 0;
		foreach ($buyCostConf as $buyCount => $buyCost)
		{
			if ($buyNum < $buyCount) 
			{
				break;
			}
			$cost = $buyCost;
		}
		
		return $cost;
	}
	
	/**
	 * 根据配置获得刷新的花费
	 * 
	 * @return int
	 */
	public static function getRefreshCost()
	{
		return btstore_get()->WORLD_COMPETE_RULE['refresh_cost'];
	}
	
	/**
	 * 根据配置获得狂怒的属性
	 *
	 * @return array
	 */
	public static function getCrazyAttr()
	{
		return btstore_get()->WORLD_COMPETE_RULE['crazy_attr'];
	}
	
	/**
	 * 根据配置获得狂怒的消耗次数
	 *
	 * @return int
	 */
	public static function getCrazyCost()
	{
		return btstore_get()->WORLD_COMPETE_RULE['crazy_cost'];
	}
	
	/**
	 * 根据配置获得膜拜的上限
	 *
	 * @return int
	 */
	public static function getWorshipLimit()
	{
		return WorldCompeteConf::WORSHIP_LIMIT;
	}
	
	/**
	 * 根据配置获得膜拜的条件
	 *
	 * @param int $num
	 * @return array 等级和vip
	 */
	public static function getWorshipCon($num)
	{
		if (!isset(btstore_get()->WORLD_COMPETE_RULE['worship_con'.$num])) 
		{
			return array(0, 0);
		}
		return btstore_get()->WORLD_COMPETE_RULE['worship_con'.$num]->toArray();
	}
	
	/**
	 * 根据配置获得膜拜的奖励
	 *
	 * @param int $num
	 * @return array
	 */
	public static function getWorshipReward($num)
	{
		return btstore_get()->WORLD_COMPETE_RULE['worship_reward'.$num];
	}
	
	/**
	 * 根据配置获得挑战的荣誉
	 *
	 * @param bool $isSuc 是否胜利
	 * @param int $defFF 守方战斗力 
	 * @return int
	 */
	public static function getAtkHonor($isSuc, $defFF)
	{
		// int(基础积分+min（（敌方战力/20000），150)) 2016年5月27日100改成150
		if ($isSuc) 
		{
			$base = btstore_get()->WORLD_COMPETE_RULE['suc_honor'];
			return intval($base + min($defFF / 20000, 150));
		}
		else 
		{
			return btstore_get()->WORLD_COMPETE_RULE['fail_honor'];
		}
	}
	
	/**
	 * 根据配置获得对手的范围
	 *
	 * @return int
	 */
	public static function getRivalRange()
	{
		return btstore_get()->WORLD_COMPETE_RULE['rival_range'];
	}
	
	/**
	 * 刷新用户的对手
	 * 
	 * @param int $teamId
	 * @param int $honor
	 * @param array
	 * {
	 * 		{
	 * 			server_id
	 * 			server_name
	 * 			pid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			title
	 * 			fight_force
	 * 			dress
	 * 			status							status为0是失败,1是成功
	 * 		}
	 * }
	 */
	public static function refreshRival($teamId, $honor)
	{
		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		$range = self::getRivalRange();
		$arrField = WorldCompeteCrossUserField::$RIVAL_FIELDS;
		$arrCond = array(array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', self::activityBeginTime()));
		$arrLowerCrossUser = WorldCompeteDao::getArrUserWithLowerHonor($teamId, $honor, $arrCond, $arrField, 0, $range);
		$arrHigherCrossUser = WorldCompeteDao::getArrUserWithHigherEqualHonor($teamId, $honor, $arrCond, $arrField, 0, $range);
		$arrCrossUser = array_merge($arrLowerCrossUser, $arrHigherCrossUser);
		//如果人数不够，就从本服玩家中取小于等于用户等级的N个玩家
		//这些玩家直接初始化到cross_user表里面
		if (count($arrCrossUser) <= WorldCompeteConf::RIVAL_COUNT) 
		{
			$num = WorldCompeteConf::RIVAL_COUNT + 1;
			$level = EnUser::getUserObj($uid)->getLevel();
			$arrField = array('uid', 'pid', 'uname', 'vip', 'level', 'htid', 'fight_force', 'dress', 'title');
			if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
			{
				$arrField[] = 'server_id';
			}
			$arrUser = EnUser::getArrUserBasicInfoWithLowerEqualLevel($num, $level, $arrField);
			foreach ($arrUser as $key => $value)
			{
				$skip = false;
				foreach ($arrCrossUser as $info)
				{
					if ($info['server_id'] == (defined('GameConf::MERGE_SERVER_OPEN_DATE') ? $value['server_id'] : $serverId) 
						&& $info['pid'] == $value['pid']) 
					{
						$skip = true;
						break;
					}
				}
				if ($skip) 
				{
					unset($arrUser[$key]);
					continue;
				}
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_SERVER_ID] = defined('GameConf::MERGE_SERVER_OPEN_DATE') ? $value['server_id'] : $serverId;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_TEAM_ID] = $teamId;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_MAX_HONOR] = 0;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_UPDATE_TIME] = Util::getTime();
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS] = $value['dress'];
				unset($arrUser[$key]['dress']);
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION] = array();
				WorldCompeteDao::insertIgnoreCrossUser($teamId, $arrUser[$key]);
			}
			$arrCrossUser = array_merge($arrCrossUser, $arrUser);
		}
		
		//排除自己
		foreach ($arrCrossUser as $key => $value)
		{
			if ($value['server_id'] == $serverId && $value['pid'] == $pid) 
			{
				unset($arrCrossUser[$key]);
			}
		}
		
		//随机N个对手
		$arrKey = array_rand($arrCrossUser, WorldCompeteConf::RIVAL_COUNT);
		$arrRivalInfo = array(); 
		foreach ($arrKey as $key)
		{
			$arrRivalInfo[] = $arrCrossUser[$key];
		}
		Logger::trace('arrRivalInfo:%s', $arrRivalInfo);
		//筛选信息,获取server_name
		$arrServerId = array_unique(Util::arrayExtract($arrRivalInfo, WorldCompeteCrossUserField::FIELD_SERVER_ID));
		$arrServerId2Name = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		foreach ($arrRivalInfo as $key => $value)
		{
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_TEAM_ID]);
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_UID]);
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_MAX_HONOR]);
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_UPDATE_TIME]);
			$arrRivalInfo[$key]['dress'] = $arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS];
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_VA_EXTRA]);
			unset($arrRivalInfo[$key][WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION]);
			$arrRivalInfo[$key]['server_name'] = $arrServerId2Name[$value['server_id']];
			$arrRivalInfo[$key]['status'] = 0;
		}

		return $arrRivalInfo;
	}
	
	/**
	 * 根据排名获取对应的排名奖励内容
	 *
	 * @param int $rank
	 * @return array
	 */
	public static function getRankReward($rank)
	{
		$allRewardConf = btstore_get()->WORLD_COMPETE_REWARD->toArray();
	
		foreach ($allRewardConf as $maxRank => $rewardContent)
		{
			if ($rank <= $maxRank)
			{
				return $rewardContent;
			}
		}
	
		throw new ConfigException('no rank reward of rank[%d], all reward conf[%s]', $rank, $allRewardConf);
	}
	
	/**
	 * 根据胜利次数获取对应的胜利奖励内容
	 *
	 * @param int $sucNum
	 * @return array
	 */
	public static function getSucPrize($sucNum)
	{
		$allPrizeConf = btstore_get()->WORLD_COMPETE_PRIZE->toArray();
		
		if (isset($allPrizeConf[$sucNum])) 
		{
			return $allPrizeConf[$sucNum];
		}
		
		throw new FakeException('no suc prize of sucNum[%d], all prize conf[%s]', $sucNum, $allPrizeConf);
	}
	
	public static function getMaxHp($heroInfo)
	{
		$hpBase = $heroInfo[PropertyKey::HP_BASE];
		$hpRatio = $heroInfo[PropertyKey::HP_RATIO];
		$hpFinal = $heroInfo[PropertyKey::HP_FINAL];
		$hp = intval(($hpBase*(1+$hpRatio/UNIT_BASE)+$hpFinal)*(1+($heroInfo[PropertyKey::REIGN]-5000)/UNIT_BASE));
		return $hp;
	}
	
	/**
	 * 获得服内用户的战斗数据
	 * 
	 * @param int $uid
	 * @param bool $crazy
	 * @return array
	 */
	public static function getUserBattleFormation($uid, $crazy = FALSE)
	{
		$battleFormation = EnUser::getUserObj($uid)->getBattleFormation();
		if ($crazy) 
		{
			$arrAttr = HeroUtil::adaptAttr(self::getCrazyAttr());
			foreach ($battleFormation['arrHero'] as $pos => $val)
			{
				foreach ($arrAttr as $propertyKey => $propertyVal)
				{
					if (!isset($battleFormation['arrHero'][$pos][$propertyKey]))
					{
						$battleFormation['arrHero'][$pos][$propertyKey] = 0;
						Logger::fatal('uid:%d, propertyKey:%s is not exist in battle info', $uid, $propertyKey);
					}
					$battleFormation['arrHero'][$pos][$propertyKey] += $propertyVal;
					$battleFormation['arrHero'][$pos][PropertyKey::MAX_HP] = self::getMaxHp($battleFormation['arrHero'][$pos]);
				}
			}
		}
	
		Logger::trace('getUserBattleFormation:ret[%s]', $battleFormation);
	
		return $battleFormation;
	}
	
	/**
	 * 获得服外用户的战斗数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param int $teamId
	 * @return array
	 */
	public static function getOtherUserBattleFormation($serverId, $pid, $teamId)
	{
		$battleFormation = array(); 
		if (!self::isMyServer($serverId)) 
		{
			try
			{
				$group = Util::getGroupByServerId($serverId);
				$proxy = new ServerProxy();
				$proxy->init($group, Util::genLogId());
				$battleFormation = $proxy->syncExecuteRequest('worldcompete.getBattleFormation', array($serverId, $pid, $teamId));
			}
			catch (Exception $e)
			{
				Logger::info('getBattleFormation error serverGroup:%s', $serverId);
				$battleFormation = WorldCompeteCrossUserObj::getInstance($serverId, $pid, 0, $teamId)->getBattleFormation();
			}
		}
		else 
		{
			$uid = self::getUidByPid($serverId, $pid);
			$battleFormation = self::getUserBattleFormation($uid);
		}
		
		return $battleFormation;
	}
	
	/**
	 * 获得服内用户的阵容信息
	 *
	 * @param int $uid
	 * @return array
	 */
	public static function getUserBattleData($uid)
	{
		$user = new User();
		return $user->getBattleDataOfUsers(array($uid));
	}
	
	/**
	 * 获得服外用户的阵容数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @return array
	 */
	public static function getOtherUserBattleData($serverId, $pid)
	{
		$battleData = array();
		if (!self::isMyServer($serverId))
		{
			try
			{
				$group = Util::getGroupByServerId($serverId);
				$proxy = new ServerProxy();
				$proxy->init($group, Util::genLogId());
				$battleData = $proxy->syncExecuteRequest('worldcompete.getBattleDataOfUsers', array($serverId, $pid));
			}
			catch (Exception $e)
			{
				Logger::fatal('getBattleDataOfUsers error serverGroup:%s', $serverId);
			}
		}
		else
		{
			$uid = self::getUidByPid($serverId, $pid);
			$battleData = self::getUserBattleData($uid);
		}
	
		return $battleData;
	}
	
	/**
	 * 获得key
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @return string
	 */
	public static function getKey($serverId, $pid)
	{
		return $serverId . '_' . $pid;
	}
	
	public static function getUidByPid($serverId, $pid)
	{
		$arrUserInfo = UserDao::getArrUserByPid($pid, array('uid'), $serverId);
		if (empty($arrUserInfo))
		{
			throw new InterException('not valid pid[%d], no user info', $pid);
		}
		return $arrUserInfo[0]['uid'];
	}
	
	/**
	 * 获得跨服库的db名称
	 * 
	 * @return string
	 */
	public static function getCrossDbName()
	{
		return WorldCompeteConf::WORLD_COMPETE_DB_PREFIX . PlatformConfig::PLAT_NAME;
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
	 * 获得玩家的pid
	 * 
	 * @param int $uid
	 * @param boolean $inMyConn
	 * @throws FakeException
	 * @return int
	 */
	public static function getPid($uid, $inMyConn = TRUE)
	{
		$sessionUid = RPCContext::getInstance()->getUid();
		if ($inMyConn && $sessionUid != $uid)
		{
			throw new FakeException('not in myconnection');
		}
		return EnUser::getUserObj($uid)->getPid();
	}
	
	/**
	 * 是否在活动时间范围内，例如周一到周六返回true，周日返回false，周日用于发奖
	 * 
	 * @param int $time
	 * @return boolean
	 */
	public static function inActivity($time = 0)
	{
		$checkTime = ($time == 0 ? Util::getTime() : $time);
		$beginTime = self::activityBeginTime($time);
		$endTime = self::activityEndTime($time);
		
		return $checkTime >= $beginTime && $checkTime <= $endTime;
	}
	
	/**
	 * 是否在发奖时间范围内，例如周一到周六返回false，周日返回true，周日用于发奖
	 *
	 * @param int $time
	 * @return boolean
	 */
	public static function inReward($time = 0)
	{
		$checkTime = ($time == 0 ? Util::getTime() : $time);
		$beginTime = self::activityEndTime($time);
		$endTime = self::rewardEndTime($time);
		
		return $checkTime >= $beginTime && $checkTime <= $endTime;
	}
	
	/**
	 * 判断是否在一个周期内，线上是一周，测试是2个小时
	 * 
	 * @param number $checkTime
	 * @return boolean
	 */
	public static function inSamePeriod($checkTime)
	{		
		if (WorldCompeteConf::$TEST_MODE > 0) 
		{
			$periodStartTime = self::activityBeginTime();
			$periodEndTime = self::activityEndTime() + 1800;
			return $checkTime >= $periodStartTime && $checkTime <= $periodEndTime;
		}
		
		return Util::isSameWeek($checkTime);
	}
	
	/**
	 * 获得周期类型，测试的话，就2个小时，线上一周
	 * 
	 * @return number
	 */
	public static function getPeriod()
	{
		if (WorldCompeteConf::$TEST_MODE > 0) 
		{
			return 2 * 3600;
		}
		
		return 7 * SECONDS_OF_DAY;
	}
		
	/**
	 * 返回每次活动的开始挑战时间
	 * 
	 * @param int $time
	 * @return int
	 */
	public static function activityBeginTime($time = 0)
	{	
		if (WorldCompeteConf::$TEST_MODE > 0) 
		{
			$hour = date('H', empty($time) ? Util::getTime() : $time);
			return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00') - ((WorldCompeteConf::$TEST_MODE + $hour % 2) % 2 * 3600);
		}
		
		return self::getCurrWeekStart($time) + intval(btstore_get()->WORLD_COMPETE_RULE['begin_time']);
	}
	
	/**
	 * 返回每次活动的结束挑战时间
	 * 
	 * @param int $time
	 * @return int
	 */
	public static function activityEndTime($time = 0)
	{
		if (WorldCompeteConf::$TEST_MODE > 0)
		{
			$hour = date('H', empty($time) ? Util::getTime() : $time);
			return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00') + ((WorldCompeteConf::$TEST_MODE + $hour % 2 + 1) % 2 * 3600) + 1800;
		}
		
		return self::getCurrWeekStart($time) + intval(btstore_get()->WORLD_COMPETE_RULE['end_time']);
	}
	
	/**
	 * 返回每次活动的结束时间，也就是下次活动的开始时间
	 *
	 * @param int $time
	 * @return int
	 */
	public static function periodEndTime($time = 0)
	{
		return self::activityBeginTime($time) + self::getPeriod();
	}
	
	/**
	 * 返回每次活动的奖励结束时间
	 *
	 * @param int $time
	 * @return int
	 */
	public static function rewardEndTime($time = 0)
	{
		$periodEndTime = self::periodEndTime($time);
		
		if (WorldCompeteConf::$TEST_MODE > 0) 
		{
			return $periodEndTime - 300;
		}
		
		return $periodEndTime - 2 * 3600;
	}
	
	/**
	 * 返回服务器可以开启活动的最近时间
	 *
	 * @return int
	 */
	public static function serverOpenActivityTime()
	{
		$validTime = strtotime(GameConf::SERVER_OPEN_YMD . '000000') + intval(btstore_get()->WORLD_COMPETE_RULE['need_open_days']) * SECONDS_OF_DAY;
	
		if (WorldCompeteConf::$TEST_MODE > 0)
		{
			return $validTime + WorldCompeteConf::$TEST_MODE % 2 * 3600;
		}
	
		$curWeek = date('N', $validTime);
		if ($curWeek == 1)
		{
			return $validTime;
		}
		else
		{
			return $validTime + (8 - $curWeek) * SECONDS_OF_DAY;
		}
	}
	
	/**
	 * 获得当前这个星期的第一天的时间戳
	 * 
	 * @param int $time
	 * @return int
	 */
	public static function getCurrWeekStart($time = 0)
	{
		$date = date('Y-m-d', (empty($time) ? Util::getTime() : $time));
		$w = date('w', strtotime($date));
		$currWeakStart = date('Y-m-d', strtotime("$date -" . ($w ? $w - 1 : 6) . ' days'));
		
		return strtotime($currWeakStart);
	}
	
	/**
	 * 根据teamId获取最新的这个team下面的serverId
	 * 
	 * @param int $teamId
	 * @return array
	 */
	public static function getArrServerIdByTeamId($teamId)
	{		
		$arrField = array
		(
				WorldCompeteCrossTeamField::FIELD_TEAM_ID,
				WorldCompeteCrossTeamField::FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldCompeteCrossTeamField::FIELD_TEAM_ID, '=', $teamId),
				array(WorldCompeteCrossTeamField::FIELD_UPDATE_TIME, '>=', self::activityBeginTime()),
		);
		$teamInfo = WorldCompeteDao::selectTeamInfo($arrCond, $arrField);
		
		return Util::arrayExtract($teamInfo, WorldCompeteCrossTeamField::FIELD_SERVER_ID);
	}
	
	/**
	 * 根据serverId获取teamId
	 * 
	 * @param int $serverId
	 * @return array
	 */
	public static function getTeamIdByServerId($serverId)
	{		
		$arrField = array
		(
				WorldCompeteCrossTeamField::FIELD_TEAM_ID,
				WorldCompeteCrossTeamField::FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldCompeteCrossTeamField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteCrossTeamField::FIELD_UPDATE_TIME, '>=', self::activityBeginTime()),
		);
		$teamInfo = WorldCompeteDao::selectTeamInfo($arrCond, $arrField);
		
		return empty($teamInfo) ? 0 : $teamInfo[0][WorldCompeteCrossTeamField::FIELD_TEAM_ID];
	}
	
	/**
	 * 获取所有分组的id
	 * 
	 * @param boolean $next
	 * @return array
	 */
	public static function getAllTeam($next = FALSE)
	{		
		$arrInfo = self::getAllTeamInfo($next);
		return array_keys($arrInfo);
	}
	
	/**
	 * 获得分组信息
	 * 
	 *  @param boolean $next
	 * @return array
	 */
	public static function getAllTeamInfo($next = FALSE)
	{
		$arrField = array
		(
				WorldCompeteCrossTeamField::FIELD_TEAM_ID,
				WorldCompeteCrossTeamField::FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldCompeteCrossTeamField::FIELD_UPDATE_TIME, '>=', $next ? self::periodEndTime() : self::activityBeginTime()),
		);
		$allTeamInfo = WorldCompeteDao::selectTeamInfo($arrCond, $arrField);
		
		$arrRet = array();
		foreach ($allTeamInfo as $aInfo)
		{
			$aTeamId = $aInfo[WorldCompeteCrossTeamField::FIELD_TEAM_ID];
			$aServerId = $aInfo[WorldCompeteCrossTeamField::FIELD_SERVER_ID];
			if (!isset($arrRet[$aTeamId]))
			{
				$arrRet[$aTeamId] = array();
			}
			$arrRet[$aTeamId][] = $aServerId;
		}
		
		return $arrRet;
	}
	
	public static function getTopActivityInfo()
	{
		$ret = array('status' => 'ok', 'extra' => array('num' => 0, 'box_reward' => 0, 'can_worship' => 0));
		
		// 获得玩家serverId和pid
		$uid = RPCContext::getInstance()->getUid();
		$pid = self::getPid($uid);
		$serverId = Util::getServerIdOfConnection();
		$teamId = self::getTeamIdByServerId($serverId);
		
		if (!EnSwitch::isSwitchOpen(SwitchDef::WORLDCOMPETE)
		|| empty($teamId))
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		// 获得玩家obj
		$user = EnUser::getUserObj();
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		
		//比武时间段内
		if (self::inActivity()) 
		{
			//剩余攻击次数
			$atkDefault = self::getAtkDefault();
			$atkNum = $worldCompeteInnerUserObj->getAtkNum();
			$buyAtkNum = $worldCompeteInnerUserObj->getBuyAtkNum();
			$ret['extra']['num'] = $atkDefault + $buyAtkNum - $atkNum;
			
			//是否有胜场奖励
			$prizeConf = btstore_get()->WORLD_COMPETE_PRIZE;
			$sucNum = $worldCompeteInnerUserObj->getSucNum();
			foreach ($prizeConf as $key => $value)
			{
				if ($worldCompeteInnerUserObj->hasPrize($key)) 
				{
					continue;
				}
				if ($sucNum < $key) 
				{
					continue;
				}
				$ret['extra']['box_reward'] ++;
			}
		}
		
		//发奖时间段内
		if (self::inReward()) 
		{
			//检查用户膜拜条件是否达到
			$worshipNum = $worldCompeteInnerUserObj->getWorshipNum();
			list($needLevel, $needVip) = self::getWorshipCon($worshipNum + 1);
			if ($user->getLevel() >= $needLevel || $user->getVip() >= $needVip)
			{
				$ret['extra']['can_worship'] = self::getWorshipLimit() - $worshipNum;
			}			
		}
		
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */