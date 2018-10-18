<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassUtil.class.php 229579 2016-02-25 10:05:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPassUtil.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-02-25 10:05:36 +0000 (Thu, 25 Feb 2016) $
 * @version $Revision: 229579 $
 * @brief 
 *  
 **/
 
class WorldPassUtil
{
	/**
	 * 根据排名获取对应的排名奖励内容
	 * 
	 * @param int $rank
	 * @return array
	 */
	public static function getRankReward($rank)
	{
		$allRewardConf = btstore_get()->WORLD_PASS_REWARD->toArray();
		
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
	 * 随机出所有关卡的怪物armyId
	 * 
	 * @return array
	 */
	public static function getRandMonster($teamId, $stage = 0)
	{
		$allCopyConf = btstore_get()->WORLD_PASS_COPY->toArray();
		
		if (count($allCopyConf) < WorldPassConf::STAGE_COUNT) 
		{
			throw new ConfigException('invalid copy conf, need count[%d] curr count[%s]', WorldPassConf::STAGE_COUNT, count($allCopyConf));
		}
		
		$activityBeginTime = WorldPassUtil::activityBeginTime();
		$seed = $activityBeginTime << 3 + $teamId;
		$arrRandNum = Util::pseudoRand($seed, WorldPassConf::STAGE_COUNT);
		
		$ret = array();
		for ($i = 1; $i <= WorldPassConf::STAGE_COUNT; ++$i)
		{
			if (empty($allCopyConf[$i])) 
			{
				throw new ConfigException('empty army id of copy[%d]', $i);
			}
			$ret[$i] = $allCopyConf[$i]['army'][$arrRandNum[$i - 1] % count($allCopyConf[$i]['army'])];
		}
		
		if (empty($stage)) 
		{
			return $ret;
		}
		
		return $ret[$stage];
	}
	
	/**
	 * 刷新武将候选列表，去除掉参数中的武将
	 * 
	 * @param array $arrExclude
	 * @return array
	 */
	public static function getNewChoice($arrExclude)
	{
		$allHeroConf = btstore_get()->WORLD_PASS_RULE['all_hero']->toArray();
		
		foreach ($allHeroConf as $htid => $aHeroConf)
		{
			if (in_array($htid, $arrExclude)) 
			{
				unset($allHeroConf[$htid]);
			}
		}
		
		if (count($allHeroConf) < WorldPassConf::CHOICE_COUNT) 
		{
			throw new InterException('no enough hero, all hero conf[%s], all exclude[%s]', $allHeroConf, $arrExclude);
		}
		
		return Util::noBackSample($allHeroConf, WorldPassConf::CHOICE_COUNT, 'weight');
	}
	
	/**
	 * 根据配置获得购买攻击次数的上限
	 * 
	 * @return array
	 */
	public static function getBuyLimit()
	{
		$buyCostConf = btstore_get()->WORLD_PASS_RULE['buy_cost']->toArray();
		
		$ret = 0;
		foreach ($buyCostConf as $buyCount => $buyCost)
		{
			if ($buyCount > $ret) 
			{
				$ret = $buyCount;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 根据配置和当前购买的次数，得到花费
	 * 
	 * @param int $buyNum
	 * @return int
	 */
	public static function getBuyCost($buyNum)
	{
		$buyCostConf = btstore_get()->WORLD_PASS_RULE['buy_cost']->toArray();
		
		foreach ($buyCostConf as $buyCount => $buyCost)
		{
			if ($buyNum <= $buyCount) 
			{
				return $buyCost;
			}
		}
		
		throw new InterException('invalid buy num[%d], buy cost conf[%s]', $buyNum, $buyCostConf);
	}
	
	/**
	 * 根据配置获得刷新武将列表次数的上限
	 * 
	 * @return array
	 */
	public static function getRefreshLimit()
	{
		$callCostConf = btstore_get()->WORLD_PASS_RULE['call_cost']->toArray();
		
		$ret = 0;
		foreach ($callCostConf as $callCount => $callCost)
		{
			if ($callCount > $ret)
			{
				$ret = $callCount;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 根据配置和当前刷新的次数，得到花费
	 * 
	 * @param int $refreshNum
	 * @return int
	 */
	public static function getRefreshCost($refreshNum)
	{
		$callCostConf = btstore_get()->WORLD_PASS_RULE['call_cost']->toArray();
		
		foreach ($callCostConf as $callCount => $callCost)
		{
			if ($refreshNum <= $callCount)
			{
				return $callCost;
			}
		}
		
		throw new InterException('invalid call num[%d], call cost conf[%s]', $refreshNum, $callCostConf);
	}
	
	/**
	 * 根据对方损血和我方剩血计算积分
	 * 
	 * @param int $damage
	 * @param int $hp
	 * @return int
	 */
	public static function getPoint($damage, $hp)
	{
		$pointCoef = btstore_get()->WORLD_PASS_RULE['point_coef']->toArray();
		$damageCoef = $pointCoef[0];
		$hpCoef = $pointCoef[1];
		
		if ($damage < 0) 
		{
			$damage = 0;
		}
		
		return intval($damage / $damageCoef) + intval($hp / $hpCoef);
	}
	
	/**
	 * 获得跨服库的db名称
	 * 
	 * @return string
	 */
	public static function getCrossDbName()
	{
		return WorldPassConf::WORLD_PASS_DB_PREFIX . PlatformConfig::PLAT_NAME;
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
		$beginTime = WorldPassUtil::activityBeginTime($time);
		$endTime = WorldPassUtil::activityEndTime($time);
		
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
		if (WorldPassConf::$TEST_MODE > 0) 
		{
			$periodStartTime = WorldPassUtil::activityBeginTime();
			$periodEndTime = WorldPassUtil::activityEndTime() + 3600;
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
		if (WorldPassConf::$TEST_MODE > 0) 
		{
			return 2 * 3600;
		}
		
		return 7 * SECONDS_OF_DAY;
	}
		
	/**
	 * 返回每次活动的开始闯关时间
	 * 
	 * @param int $time
	 * @return int
	 */
	public static function activityBeginTime($time = 0)
	{	
		if (WorldPassConf::$TEST_MODE > 0) 
		{
			$hour = date('H', empty($time) ? Util::getTime() : $time);
			return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00') - ((WorldPassConf::$TEST_MODE + $hour % 2) % 2 * 3600);
		}
		
		return WorldPassUtil::getCurrWeekStart($time) + intval(btstore_get()->WORLD_PASS_RULE['begin_time']);
	}
	
	/**
	 * 返回每次活动的结束闯关时间
	 * 
	 * @param int $time
	 * @return int
	 */
	public static function activityEndTime($time = 0)
	{
		if (WorldPassConf::$TEST_MODE > 0)
		{
			$hour = date('H', empty($time) ? Util::getTime() : $time);
			return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00') + ((WorldPassConf::$TEST_MODE + $hour % 2 + 1) % 2 * 3600);
		}
		
		return WorldPassUtil::getCurrWeekStart($time) + intval(btstore_get()->WORLD_PASS_RULE['end_time']);
	}
	
	/**
	 * 返回每次活动的结束时间，也就是下次活动的开始时间
	 *
	 * @param int $time
	 * @return int
	 */
	public static function periodEndTime($time = 0)
	{
		return WorldPassUtil::activityBeginTime($time) + WorldPassUtil::getPeriod();
	}
	
	/**
	 * 返回每次活动的奖励结束时间
	 *
	 * @param int $time
	 * @return int
	 */
	public static function rewardEndTime($time = 0)
	{
		$periodEndTime = WorldPassUtil::periodEndTime($time);
		
		if (WorldPassConf::$TEST_MODE > 0) 
		{
			return $periodEndTime - 1800;
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
		$validTime = strtotime(GameConf::SERVER_OPEN_YMD . '000000') + intval(btstore_get()->WORLD_PASS_RULE['need_open_days']) * SECONDS_OF_DAY;
		
		if (WorldPassConf::$TEST_MODE > 0) 
		{
			return $validTime + WorldPassConf::$TEST_MODE % 2 * 3600;
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
	
	public static function getUserBattleFormation($uid, $arrFmt)
	{
		//　生成默认的进阶等级，５星７阶，６星５阶，7星５阶
		$arrEvolve = array();
		foreach ($arrFmt as $index => $aHtid)
		{
			$aStar = Creature::getHeroConf($aHtid, CreatureAttr::STAR_LEVEL);
			if ($aStar == 5) 
			{
				$arrEvolve[$index] = 7;
			}
			else if ($aStar == 6) 
			{
				$arrEvolve[$index] = 5;
			}
			else if ($aStar == 7)
			{
				$arrEvolve[$index] = 5;
			}
			else 
			{
				$arrEvolve[$index] = 1;
			}
		}
		Logger::trace('getUserBattleFormation : arr evolve[%s]', $arrEvolve);
		
		// 一些基本的属性替换
		$arrAttr = array();
		$arrFiveStarAttr = btstore_get()->WORLD_PASS_RULE['five_star_attr']->toArray();
		$arrSixStarAttr = btstore_get()->WORLD_PASS_RULE['six_star_attr']->toArray();
		$arrSevenStarAttr = btstore_get()->WORLD_PASS_RULE['seven_star_attr']->toArray();
		foreach ($arrFmt as $index => $aHtid)
		{
			$aStar = Creature::getHeroConf($aHtid, CreatureAttr::STAR_LEVEL);
			if ($aStar == 5) // 5星武将属性替换
			{
				foreach ($arrFiveStarAttr as $aAttrId => $aAttrValue)
				{
					$arrAttr[$index][PropertyKey::$MAP_CONF[$aAttrId]] = $aAttrValue;
				}
			}
			else if ($aStar == 6) // 6星武将属性替换
			{
				foreach ($arrSixStarAttr as $aAttrId => $aAttrValue)
				{
					$arrAttr[$index][PropertyKey::$MAP_CONF[$aAttrId]] = $aAttrValue;
				}
			}
			else if ($aStar == 7) // 7星武将属性替换
			{
				foreach ($arrSevenStarAttr as $aAttrId => $aAttrValue)
				{
					$arrAttr[$index][PropertyKey::$MAP_CONF[$aAttrId]] = $aAttrValue;
				}
			}
		}
		Logger::trace('getUserBattleFormation : arr attr[%s]', $arrAttr);
		
		// 获得基本的战斗数据
		$userBattleFormation = EnFormation::getBattleFormationByArrHtid($uid, $arrFmt, 1, NULL, $arrEvolve, $arrAttr);
		Logger::trace('getUserBattleFormation : ret[%s]', $userBattleFormation);
		
		return $userBattleFormation;
	}
	
	/**
	 * 根据armyId返回战斗数据，并且返回战斗类型，结束条件
	 * 
	 * @param int $armyId
	 */
	public static function getMonsterBattleFormation($armyId)
	{
		$formation = EnFormation::getMonsterBattleFormation($armyId);
		$battleType = btstore_get()->ARMY[$armyId]['fight_type'];
		$endCondition = array();
		if(isset(btstore_get()->ARMY[$armyId]['end_condition']))
		{
			$endCondition = btstore_get()->ARMY[$armyId]['end_condition']->toArray();
		}
		
		return array($formation, $battleType, $endCondition);
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
				WorldPassCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldPassCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldPassCrossTeamField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
		);
		$teamInfo = WorldPassDao::selectTeamInfo($arrCond, $arrField);
		
		return Util::arrayExtract($teamInfo, WorldPassCrossTeamField::TBL_FIELD_SERVER_ID);
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
				WorldPassCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldPassCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldPassCrossTeamField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
		);
		$teamInfo = WorldPassDao::selectTeamInfo($arrCond, $arrField);
		
		return empty($teamInfo) ? 0 : $teamInfo[0][WorldPassCrossTeamField::TBL_FIELD_TEAM_ID];
	}
	
	/**
	 * 获取所有分组的id
	 * 
	 * @param boolean $next
	 * @return array
	 */
	public static function getAllTeam($next = FALSE)
	{		
		$arrInfo = WorldPassUtil::getAllTeamInfo($next);
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
				WorldPassCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldPassCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', $next ? WorldPassUtil::periodEndTime() : WorldPassUtil::activityBeginTime()),
		);
		$allTeamInfo = WorldPassDao::selectTeamInfo($arrCond, $arrField);
		
		$arrRet = array();
		foreach ($allTeamInfo as $aInfo)
		{
			$aTeamId = $aInfo[WorldPassCrossTeamField::TBL_FIELD_TEAM_ID];
			$aServerId = $aInfo[WorldPassCrossTeamField::TBL_FIELD_SERVER_ID];
			if (!isset($arrRet[$aTeamId]))
			{
				$arrRet[$aTeamId] = array();
			}
			$arrRet[$aTeamId][] = $aServerId;
		}
		
		return $arrRet;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */