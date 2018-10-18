<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWarLogic.class.php 214051 2015-12-04 07:18:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/CityWarLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-04 07:18:41 +0000 (Fri, 04 Dec 2015) $
 * @version $Revision: 214051 $
 * @brief 
 *  
 **/
class CityWarLogic
{ 
	public static function getGuildSignupList($uid, $guildId)
	{
		Logger::trace('CityWarLogic::getGuildSignupList Start.');
		
		self::checkLevelConds($uid);
		
		//占领,所有阶段都有的
		$occupy = array();
		$cityList = CityWarDao::getCityList();
		Logger::trace('city list:%s', $cityList);
		$arrGuildId = Util::arrayExtract($cityList, CityWarDef::LAST_GID);
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		foreach ($cityList as $cityId => $cityInfo)
		{
			$occupy[$cityId] = $arrGuildInfo[$cityInfo[CityWarDef::LAST_GID]];
		}
		Logger::trace('occupy:%s', $occupy);
		
		//报名,从报名开始到准备开始
		$sign = array();
		$now = Util::getTime();
		Logger::trace('now:%s', $now);
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime(0);
		if ($now >= $signupStartTime && $now <= $prepareStartTime) 
		{
			$signList = CityWarDao::getGuildSignupList($guildId, $signupStartTime, $signupEndTime);
			$sign = array_keys($signList);
		}
		Logger::trace('sign:%s', $sign);
		
		//攻击,从准备开始到战斗结束
		$suc = array();
		$attack = array();
		$battleEndTime = self::getBattleEndTime();
		if ($now >= $prepareStartTime && $now <= $battleEndTime)
		{
			$attackList = CityWarDao::getAllAttackList($signupStartTime, $signupEndTime);
			$attack = Util::arrayExtract($attackList, CityWarDef::CITY_ID);
			$attack = array_unique($attack);
			$signList = CityWarDao::getGuildSignupList($guildId, $signupStartTime, $signupEndTime);
			$sign = array_keys($signList);
			$suc = array_intersect($sign, $attack);	
		}
		Logger::trace('sign:%s suc:%s attack:%s', $sign, $suc, $attack);
		
		//领奖,领奖时间到结束
		$reward = 0;
		$userInfo = self::getUserInfo($uid);
		list($rewardStartTime, $rewardEndTime) = self::getRewardTime();
		$cityGuild = Util::arrayIndexCol($cityList, CityWarDef::CITY_ID, CityWarDef::LAST_GID);
		$cityId = array_search($guildId, $cityGuild);
		if ($now >= $rewardStartTime && $now <= $rewardEndTime 
		&& $cityId && key_exists($uid, $cityList[$cityId][CityWarDef::VA_REWARD]['list']))
		{
			//用户是否已经领奖
			$rewardTime = $userInfo[CityWarDef::REWARD_TIME];
			Logger::trace('user get reward time:%s.', $rewardTime);
			if ($rewardTime < $rewardStartTime || $rewardTime > $rewardEndTime)
			{
				$reward = $cityId;
			}
		}
		Logger::trace('reward:%s', $reward);
		
		//用户离线入场信息
		$offline = array();
		if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] >= $signupEndTime
		&& !empty($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round']))
		{
			$round = $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'];
			foreach ($round as $key => $value)
			{
				$offline[$key + 1] = $value;
			}
		}
		Logger::trace('offline:%s', $offline);
		
		//军团的周贡献
		$arrContriWeek = EnGuild::getArrContriWeek(array($guildId));
		$contriWeek = $arrContriWeek[$guildId];
		Logger::trace('contri week:%s', $contriWeek);
		
		$ret = array(
				'timeConf' => self::getTimeConf(),
				'occupy' => $occupy,
				'sign' => $sign,
				'suc' => $suc,
				'attack' => $attack,
				'reward' => $reward,
				'offline' => $offline,
				'contri_week' => $contriWeek,
		);
		
		Logger::trace('CityWarLogic::getGuildSignupList End.');
		
		return $ret;
	}
	
	public static function getCitySignupList($uid, $cityId, $guildId)
	{
		Logger::trace('CityWarLogic::getCitySignupList Start.');
		
		$ret = array('list' => array(), 'self' => array());
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//获得报名此城池的所有军团
		$now = Util::getTime();
		$battleEndTime = self::getBattleEndTime();
		list($signStartTime, $signEndTime) = self::getSignupTime();
		$list = CityWarDao::getCitySignupList($cityId, $signStartTime, $signEndTime);
		$arrContriWeek = Util::arrayIndexCol($list, CityWarDef::ATTACK_GID, CityWarDef::ATTACK_CONTRI);
		$arrGuildId = array_keys($list);
		if ($now < $signEndTime || $now > $battleEndTime)
		{
			$arrContriWeek = EnGuild::getArrContriWeek($arrGuildId);
		}
		$arrField = array(GuildDef::GUILD_NAME, GuildDef::GUILD_LEVEL, GuildDef::FIGHT_FORCE);
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, $arrField);
		foreach ($arrGuildInfo as $key => $guildInfo)
		{
			$arrGuildInfo[$key][GuildDef::CONTRI_WEEK] = $arrContriWeek[$key];
			$arrGuildInfo[$key][CityWarDef::SIGNUP_ID] = $list[$key][CityWarDef::SIGNUP_ID];
		}
		
		//排序，按照周贡献降序，报名时间升序
		$sort = array(
				GuildDef::CONTRI_WEEK => SortByFieldFunc::DESC,
				CityWarDef::SIGNUP_ID => SortByFieldFunc::ASC,
		);
		$sortCmp = new SortByFieldFunc($sort);
		usort($arrGuildInfo, array($sortCmp, 'cmp'));
		$list = array_slice($arrGuildInfo, 0, 10);
		$ret['list'] = $list;
		
		//用户所在军团如果报名了，单独处理
		$arrGuildId = Util::arrayExtract($arrGuildInfo, GuildDef::GUILD_ID);
		if (in_array($guildId, $arrGuildId)) 
		{
			//前端没有加1
			$index = array_search($guildId, $arrGuildId);
			$ret['self'][$index + 1] = $arrGuildInfo[$index];
			$ret['self'][$index + 1]['useless'] = 0;
			unset($ret['self'][$index + 1]['useless']);
		}
		
		Logger::trace('CityWarLogic::getCitySignupList End.');
		
		return $ret;
	}
	
	public static function getCityAttackList($uid, $cityId)
	{
		Logger::trace('CityWarLogic::getCityAttackList Start.');
		
		$ret = array();
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//是否在本轮报名时间内，否则取上轮的报名时间
		$now = Util::getTime();
		list($signStartTime, $signEndTime) = self::getSignupTime();
		if ($now >= $signStartTime && $now <= $signEndTime) 
		{
			$signStartTime -= CityWarConf::ROUND_DURATION;
			$signEndTime -= CityWarConf::ROUND_DURATION;
		}
		
		//获得此城池的攻打列表
		$list = CityWarDao::getCityAttackList($cityId, $signStartTime, $signEndTime);
		
		//获得战报的详细信息, 跟场次有关
		foreach ($list as $key => $value)
		{
			$attackGid = $value[CityWarDef::ATTACK_GID];
			$defendGid = $value[CityWarDef::DEFEND_GID];
			Logger::trace('attackGid:%d defendGid:%d', $attackGid, $defendGid);

			$arrGuildInfo = self::getBasicGuildInfo($attackGid, $defendGid);
			$ret[$key] = array(
					'attack' => $arrGuildInfo[$attackGid],
					'defend' => $arrGuildInfo[$defendGid],
					'result' => $value[CityWarDef::ATTACK_RESULT],
					'replay' => $value[CityWarDef::ATTACK_REPLAY],
			);
			//如果非第一场的话，攻守方需要交换顺序
			if ($key > 0) 
			{
				$ret[$key]['attack'] = $arrGuildInfo[$defendGid];
				$ret[$key]['defend'] = $arrGuildInfo[$attackGid];
			}
		}
		
		Logger::trace('CityWarLogic::getCityAttackList End.');
		
		return $ret;
	}
	
	public static function getCityInfo($uid, $cityId)
	{
		Logger::trace('CityWarLogic::getCityInfo Start.');
		
		$ret = array(
				GuildDef::GUILD_ID => 0,
				GuildDef::GUILD_NAME => 0,
				GuildDef::GUILD_LEVEL => 0,
				GuildDef::FIGHT_FORCE => 0,
		);
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//获得刷新后的城池信息
		$cityInfo = self::getRefreshedCityInfo($cityId);
		if (!empty($cityInfo[CityWarDef::LAST_GID])) 
		{
			$guildId = $cityInfo[CityWarDef::LAST_GID];
			$arrField = array(GuildDef::GUILD_ID, GuildDef::GUILD_NAME, GuildDef::GUILD_LEVEL, GuildDef::FIGHT_FORCE);
			$arrGuildInfo = EnGuild::getArrGuildInfo(array($guildId), $arrField);
			$ret = $arrGuildInfo[$guildId];
		}
		//根据城防默认值和系数计算战斗力
		$defence = $cityInfo[CityWarDef::CITY_DEFENCE];
		$default = btstore_get()->CITY_WAR[$cityId][CityWarDef::DEFENCE_DEFAULT];
		$param = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_PARAM];
		$force = intval((1 + $param * ($defence - $default) / $default / UNIT_BASE) * 100);
		$ret['city_defence'] = $defence;
		$ret['city_force'] = $force;
		$userInfo = self::getUserInfo($uid);
		$ret[CityWarDef::MEND_TIME] = $userInfo[CityWarDef::MEND_TIME];
		$ret[CityWarDef::RUIN_TIME] = $userInfo[CityWarDef::RUIN_TIME];
		Logger::trace('CityWarLogic::getCityInfo End.');
		
		return $ret;
	}
	
	public static function getCityId($uid)
	{
		Logger::trace('CityWarLogic::getCityId Start.');
		
		self::checkLevelConds($uid);

		$cityId = 0;
		$id = self::getPreparePeriod();
		if ($id != -1) 
		{
			$userInfo = self::getUserInfo($uid);
			$enterTime = $userInfo[CityWarDef::ENTER_TIME];
			list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($id);
			if ($enterTime >= $prepareStartTime && $enterTime <= $prepareEndTime) 
			{
				$cityId = $userInfo[CityWarDef::CUR_CITY];
			}
			//或者用户报名了离线入场
			if (empty($cityId)) 
			{
				list($signupStartTime, $signupEndTime) = self::getSignupTime();
				if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
				&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] >= $signupEndTime
				&& isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id]))
				{
					$cityId = $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id];
				}
			}
		}
		
		Logger::trace('CityWarLogic::getCityId End.');
		
		return $cityId;
	}
	
	public static function getGuildCityId($uid)
	{
		Logger::trace('CityWarLogic::getGuildCityId Start.');
		
		$cityId = 0;
		//如果在当前用户线程，取session中的数据
		if ($uid == RPCContext::getInstance()->getUid())
		{
			//当前时间
			$now = Util::getTime();
			//战斗结算结束时间
			$battleEnd = self::getBattleEndTime() + CityWarConf::CHECK_OFFSET;
			$sessionInfo = RPCContext::getInstance()->getSession(CityWarDef::SESSION_INFO);
			//session中没有数据或者是当新一轮战斗结束而session中的数据还是旧的
			if (empty($sessionInfo)
			|| $now > $battleEnd && $sessionInfo['time'] < $battleEnd)
			{
				$guildId = EnGuild::getGuildId($uid);
				if (!empty($guildId)) 
				{
					$list = CityWarDao::getGuildCityList($guildId);
					$cityId = empty($list) ? 0 : key($list);
					//更新session中的数据
					$sessionInfo = array(
							'city' => $cityId,
							'time' => $now
					);
					RPCContext::getInstance()->setSession(CityWarDef::SESSION_INFO, $sessionInfo);
				}
			}
			else 
			{
				$cityId = $sessionInfo['city'];
			}
		}
		else 
		{
			$guildId = EnGuild::getGuildId($uid);
			if (!empty($guildId))
			{
				$list = CityWarDao::getGuildCityList($guildId);
				$cityId = empty($list) ? 0 : key($list);
			}
		}
		
		Logger::trace('CityWarLogic::getGuildCityId End.');
		
		return $cityId;
	}
	
	public static function offlineEnter($uid, $cityId, $roundId)
	{
		Logger::trace('CityWarLogic::offlineEnter Start.');
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//检查用户报名时间是否正确
		self::checkOETime($roundId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			return 'err';
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$roundId]))
		{
			Logger::warning('Battle:%d is not exist.', $roundId);
			return 'nobattle';
		}
		
		//检查用户是否属于第1场的攻击方或者防守方，以及第2场至第N场的攻击方
		$arrGuildId = array(
				$list[0][CityWarDef::ATTACK_GID], 
				$list[0][CityWarDef::DEFEND_GID],
		);
		for ($i = 1; $i <= $roundId; $i++)
		{
			$arrGuildId[] = $list[$i][CityWarDef::ATTACK_GID]; 
		}
		if (!in_array($guildId, $arrGuildId))
		{
			throw new FakeException('User choose the wrong battle.');
		}
		
		//用户选择当前城池离线入场时，不能同时选择其他城池的离线入场
		$userInfo = self::getUserInfo($uid);
		if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] < $signupEndTime) 
		{
			$userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'] = array();
		}
		if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId]))
		{
			if ($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId] != $cityId) 
			{
				$orginalCityId = $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId];
				throw new FakeException('uid:%d is offline enter city:%d for battle:%d, cancel it first.', $uid, $orginalCityId, $roundId);
			}
			else 
			{
				return 'ok';
			}
		}
		
		//获得城池的离线列表
		$cityInfo = CityWarDao::selectCity($cityId);
		if (empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId]))
		{
			$cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId] = array();
		}
		
		//离线入场人数是否超过最大参战人数
		$limit = btstore_get()->CITY_WAR_ATTACK[CityWarDef::JOIN_LIMIT];
		if (count($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId]) >= $limit)
		{
			Logger::warning('User num reach limit:%d.', $limit);
			return 'limit';
		}
		
		//将用户的离线入场信息加入到城池的离线列表中
		if (!in_array($uid, $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId])) 
		{
			$cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId][] = $uid;
			CityWarDao::updateCity($cityId, array(CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR]));
		}
		
		//更新用户的离线入场信息
		$userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] = Util::getTime();
		$userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId] = $cityId;
		CityWarDao::insertOrUpdateUser($userInfo);
		
		Logger::trace('CityWarLogic::offlineEnter End.');
		
		return 'ok';
	}
	
	public static function cancelOfflineEnter($uid, $cityId, $roundId)
	{
		Logger::trace('CityWarLogic::cancelOfflineEnter Start.');
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//检查用户取消报名时间是否正确
		self::checkOETime($roundId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			return 'err';
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$roundId]))
		{
			Logger::warning('Battle:%d is not exist.', $roundId);
			return 'nobattle';
		}
		
		//检查用户是否属于第1场的攻击方或者防守方，以及第2场至第N场的攻击方
		$arrGuildId = array(
				$list[0][CityWarDef::ATTACK_GID],
				$list[0][CityWarDef::DEFEND_GID],
		);
		for ($i = 1; $i <= $roundId; $i++)
		{
			$arrGuildId[] = $list[$i][CityWarDef::ATTACK_GID];
		}
		if (!in_array($guildId, $arrGuildId))
		{
			throw new FakeException('User choose the wrong battle.');
		}
		
		//用户选择当前城池离线入场时，不能同时选择其他城池的离线入场
		$userInfo = self::getUserInfo($uid);
		if (!isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
		|| $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] < $signupEndTime
		|| !isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId])
		|| $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId] != $cityId)
		{
			throw new FakeException('uid:%d cant cancel offline enter city:%d for battle:%d.', $uid, $cityId, $roundId);
		}
		
		//获得城池的离线列表
		$cityInfo = CityWarDao::selectCity($cityId);
		if (empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId]))
		{
			$cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId] = array();
		}
		
		//将用户的离线入场信息从城池的离线列表中去掉
		if (in_array($uid, $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId]))
		{
			$index = array_search($uid, $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId]);
			unset($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$roundId][$guildId][$index]);
			CityWarDao::updateCity($cityId, array(CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR]));
		}	
			
		//更新用户的离线入场信息
		$userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] = Util::getTime();
		unset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$roundId]);
		CityWarDao::insertOrUpdateUser($userInfo);
		
		Logger::trace('CityWarLogic::cancelOfflineEnter End.');

		return 'ok';
	}
	
	public static function enter($uid, $cityId) 
	{
		Logger::trace('CityWarLogic::enter Start.');
		
		$ret = array('ret' => 'ok', 'user' => array(), 'attacker' => array(), 'defender' => array());
		
		//TODO:arenaID
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//检查当前是否在某个战斗场次的准备时间内
		$id = self::getPreparePeriod();
		if ($id == -1)
		{
			Logger::warning('Not in any battle prepare time.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$id]))
		{
			Logger::warning('Battle:%d is not exist.', $id);
			$ret['ret'] = 'nobattle';
			return $ret;
		}
		
		//检查用户是否属于攻击方或者防守方
		$attackGid = $list[$id][CityWarDef::ATTACK_GID];
		$defendGid = $list[$id][CityWarDef::DEFEND_GID];
		if ($guildId != $attackGid && $guildId != $defendGid)
		{
			throw new FakeException('User enter the wrong battle.');
		}
		
		//用户参加当前城池战斗时，不能同时去参加其他城池的战斗
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($id);
		$userInfo = self::getUserInfo($uid);
		if( $userInfo[CityWarDef::ENTER_TIME] >= $prepareStartTime
		&& $userInfo[CityWarDef::ENTER_TIME] <= $prepareEndTime
		&& $userInfo[CityWarDef::CUR_CITY] != $cityId)
		{
			throw new FakeException('uid:%d in city:%d, cant enter city:%d.', $uid, $userInfo[CityWarDef::CUR_CITY], $cityId);
		}
		//如果用户报名了离线入场，规则同在线入场，用户从离线入场列表消失，加入到在线列表最后
		if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] >= $signupEndTime
		&& isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id] != $cityId)
		{
			throw new FakeException('uid:%d cant enter city:%d for battle:%d.', $uid, $cityId, $id);
		}
		
		//获得战场上的参战双方成员列表
		$cityInfo = CityWarDao::selectCity($cityId);
		$attackList = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid]))
		{
			$attackList = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid];
		}
		$defendList = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid]))
		{
			$defendList = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid];
		}
		//离线入场用户列表
		$attackOffline = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid]))
		{
			$attackOffline = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid];
		}
		$defendOffline = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid]))
		{
			$defendOffline = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid];
		}
		
		//刷新参战双方的人员信息
		$now = Util::getTime();
		$arrUid = array_merge($attackList, $defendList, $attackOffline, $defendOffline);
		$arrUid[] = $uid;
		$attackRefresh = array('enter' => array(), 'leave' => array());
		$defendRefresh = array('enter' => array(), 'leave' => array());
		$arrUser = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'utid', 'uname', 'vip', 'htid', 'dress', 'status', 'last_logoff_time'));
		//给参战双方推送离开成员信息
		foreach ($attackList as $key => $value)
		{
			if ($arrUser[$value]['status'] == 2 
			&& $now - $arrUser[$value]['last_logoff_time'] >= CityWarConf::OFFLINE_DURATION) 
			{
				//如果用户勾选了离线入场，就从在线状态变成离线状态，否则直接退出
				$attackRefresh['leave'][$value] = 0;
				if (in_array($value, $attackOffline)) 
				{
					$index = array_search($value, $attackOffline);
					$attackRefresh['leave'][$value] = $index + 1;
				}
				unset($attackList[$key]);
			}
		}
		foreach ($defendList as $key => $value)
		{
			if ($arrUser[$value]['status'] == 2
			&& $now - $arrUser[$value]['last_logoff_time'] >= CityWarConf::OFFLINE_DURATION)
			{
				$defendRefresh['leave'][$value] = 0;
				if (in_array($value, $defendOffline))
				{
					$index = array_search($value, $defendOffline);
					$defendRefresh['leave'][$value] = $index + 1;
				}
				unset($defendList[$key]);
			}
		}
		
		//是否超过最大参战人数
		$limit = btstore_get()->CITY_WAR_ATTACK[CityWarDef::JOIN_LIMIT];
		$guildList = $guildId == $attackGid ? $attackList : $defendList;
		$guildOffline = $guildId == $attackGid ? $attackOffline : $defendOffline;
		$guildAll = array_unique(array_merge($guildList, $guildOffline));
		if (!in_array($uid, $guildAll) && count($guildAll) >= $limit)
		{
			Logger::warning('User num reach limit:%d.', $limit);
			$ret['ret'] = 'limit';
			return $ret;
		}
		
		//给参战双方推送加入成员信息
		if ($guildId == $attackGid)
		{
			if (!in_array($uid, $attackList)) 
			{
				$attackList[] = $uid;
				$attackRefresh['enter'][] = $arrUser[$uid];
			}
			$guildList = $attackList;
		}
		else
		{
			if (!in_array($uid, $defendList)) 
			{
				$defendList[] = $uid;
				$defendRefresh['enter'][] = $arrUser[$uid];
			}
			$guildList = $defendList;
		}
		
		//更新用户信息
		$userInfo[CityWarDef::ENTER_TIME] = Util::getTime();
		$userInfo[CityWarDef::CUR_CITY] = $cityId;
		CityWarDao::insertOrUpdateUser($userInfo);
		
		//更新城池信息
		$cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId] = $guildList;
		CityWarDao::updateCity($cityId, array(CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR]));
		
		//获得当前用户的鼓舞和连胜信息
		$vaInfo = $userInfo[CityWarDef::VA_CITY_WAR_USER];
		$ret['user'] = array('max_win' => 0, 'buy_num' => 0, 'inspire_cd' => 0, 'attack_level' => 0, 'defend_level' => 0);
		if (!empty($vaInfo['info'][$cityId]['win']))
		{
			$winDefault = btstore_get()->CITY_WAR_ATTACK[CityWarDef::WIN_DEFAULT];
			$ret['user']['max_win'] = $winDefault + $vaInfo['info'][$cityId]['win']['add'];
			$ret['user']['buy_num'] = $vaInfo['info'][$cityId]['win']['buy'];
		}
		if (!empty($vaInfo['info'][$cityId]['inspire']))
		{
			$ret['user']['inspire_cd'] = $vaInfo['info'][$cityId]['inspire']['time'];
			$ret['user']['attack_level'] = $vaInfo['info'][$cityId]['inspire']['attack'];
			$ret['user']['defend_level'] = $vaInfo['info'][$cityId]['inspire']['defend'];
		}
		
		//获取城池参战双方信息
		$arrGuildInfo = self::getBasicGuildInfo($attackGid, $defendGid);
		$attacker = $arrGuildInfo[$attackGid];
		$attackRefresh = array_merge($attacker, $attackRefresh);
		foreach ($attackList as $key => $value)
		{
			$attacker['list'][] = array(
					'uid' => $arrUser[$value]['uid'],
					'utid' => $arrUser[$value]['utid'],
					'uname' => $arrUser[$value]['uname'],
					'vip' => $arrUser[$value]['vip'],
					'htid' => $arrUser[$value]['htid'],
					'dress' => $arrUser[$value]['dress'],
			);
		}
		$attackDiff = array_diff($attackOffline, $attackList);
		foreach ($attackDiff as $key => $value)
		{
			$attacker['offline'][] = array(
					'uid' => $arrUser[$value]['uid'],
					'utid' => $arrUser[$value]['utid'],
					'uname' => $arrUser[$value]['uname'],
					'vip' => $arrUser[$value]['vip'],
					'htid' => $arrUser[$value]['htid'],
					'dress' => $arrUser[$value]['dress'],
			);
		}
		$defender = $arrGuildInfo[$defendGid];
		$defendRefresh = array_merge($defender, $defendRefresh);
		foreach ($defendList as $key => $value)
		{
			$defender['list'][] = array(
					'uid' => $arrUser[$value]['uid'],
					'utid' => $arrUser[$value]['utid'],
					'uname' => $arrUser[$value]['uname'],
					'vip' => $arrUser[$value]['vip'],
					'htid' => $arrUser[$value]['htid'],
					'dress' => $arrUser[$value]['dress'],
			);
		}
		$defendDiff = array_diff($defendOffline, $defendList);
		foreach ($defendDiff as $key => $value)
		{
			$defender['offline'][] = array(
					'uid' => $arrUser[$value]['uid'],
					'utid' => $arrUser[$value]['utid'],
					'uname' => $arrUser[$value]['uname'],
					'vip' => $arrUser[$value]['vip'],
					'htid' => $arrUser[$value]['htid'],
					'dress' => $arrUser[$value]['dress'],
			);
		}
		
		$ret['attacker'] = $attacker;
		$ret['defender'] = $defender;
		$refresh = array('attacker' => $attackRefresh, 'defender' => $defendRefresh);
		//如果非第一场的话，攻守方需要交换顺序
		if ($id > 0) 
		{
			$ret['attacker'] = $defender;
			$ret['defender'] = $attacker;
			$refresh = array('attacker' => $defendRefresh, 'defender' => $attackRefresh);
		}
		//给前端推送信息
		$arrUid = array_merge($attackList, $defendList, $attackDiff, $defendDiff);
		RPCContext::getInstance()->sendMsg(array_diff($arrUid, array($uid)), PushInterfaceDef::CITYWAR_REFRESH, $refresh);
		
		Logger::trace('CityWarLogic::enter End.');
	
		return $ret;
	}

	public static function leave($uid, $cityId) 
	{
		Logger::trace('CityWarLogic::leave Start.');
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			return 'err';
		}
		
		//检查当前是否在某个战斗场次的准备时间内
		$id = self::getPreparePeriod();
		if ($id == -1)
		{
			Logger::warning('Not in any battle prepare time.');
			return 'err';
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$id]))
		{
			Logger::warning('Battle:%d is not exist.', $id);
			return 'err';
		}
		
		//检查用户是否属于攻击方或者防守方
		$attackGid = $list[$id][CityWarDef::ATTACK_GID];
		$defendGid = $list[$id][CityWarDef::DEFEND_GID];
		if ($guildId != $attackGid && $guildId != $defendGid)
		{
			throw new FakeException('User leave the wrong battle.');
		}
		
		//用户参加当前城池战斗时，不能同时去参加其他城池的战斗
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($id);
		$userInfo = self::getUserInfo($uid);
		if( $userInfo[CityWarDef::ENTER_TIME] >= $prepareStartTime
		&& $userInfo[CityWarDef::ENTER_TIME] <= $prepareEndTime
		&& $userInfo[CityWarDef::CUR_CITY] != $cityId)
		{
			throw new FakeException('uid:%d in city:%d, cant leave city:%d.', $uid, $userInfo[CityWarDef::CUR_CITY], $cityId);
		}
		//如果用户报名了离线入场，规则同在线入场
		if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['time'] >= $signupEndTime
		&& isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id])
		&& $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id] != $cityId)
		{
			throw new FakeException('uid:%d cant enter city:%d for battle:%d.', $uid, $cityId, $id);
		}
		
		//获得战场上的参战双方成员列表
		$cityInfo = CityWarDao::selectCity($cityId);
		$attackList = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid]))
		{
			$attackList = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid];
		}
		$defendList = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid]))
		{
			$defendList = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid];
		}
		//离线入场用户列表
		$attackOffline = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid]))
		{
			$attackOffline = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid];
		}
		$defendOffline = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid]))
		{
			$defendOffline = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid];
		}
		
		//刷新参战双方的人员信息
		$now = Util::getTime();
		$arrUid = array_merge($attackList, $defendList, $attackOffline, $defendOffline);
		$attackRefresh = array('enter' => array(), 'leave' => array());
		$defendRefresh = array('enter' => array(), 'leave' => array());
		$arrUser = EnUser::getArrUser($arrUid, array('uid', 'status', 'last_logoff_time'));
		//给参战双方推送离开成员信息
		foreach ($attackList as $key => $value)
		{
			if ($arrUser[$value]['status'] == 2
			&& $now - $arrUser[$value]['last_logoff_time'] >= CityWarConf::OFFLINE_DURATION
			|| $value == $uid)
			{
				$attackRefresh['leave'][$value] = 0;
				if (in_array($value, $attackOffline) && $value != $uid)
				{
					$index = array_search($value, $attackOffline);
					$attackRefresh['leave'][$value] = $index + 1;
				}
				unset($attackList[$key]);
			}
		}
		foreach ($defendList as $key => $value)
		{
			if ($arrUser[$value]['status'] == 2
			&& $now - $arrUser[$value]['last_logoff_time'] >= CityWarConf::OFFLINE_DURATION
			|| $value == $uid)
			{
				
				$defendRefresh['leave'][$value] = 0;
				if (in_array($value, $defendOffline) && $value != $uid)
				{
					$index = array_search($value, $defendOffline);
					$defendRefresh['leave'][$value] = $index + 1;
				}
				unset($defendList[$key]);
			}
		}
		//用户主动退场的话，离线入场也会失效
		foreach ($attackOffline as $key => $value)
		{
			if ($value == $uid) 
			{
				$attackRefresh['leave'][$value] = 0;
				unset($attackOffline[$key]);
			}
		}
		foreach ($defendOffline as $key => $value)
		{
			if ($value == $uid)
			{
				$defendRefresh['leave'][$value] = 0;
				unset($defendOffline[$key]);
			}
		}
		
		//更新用户信息
		$arrField = array(
				CityWarDef::CUR_CITY => 0,
				CityWarDef::ENTER_TIME => 0,
		);
		CityWarDao::updateUser($uid, $arrField);
		
		//更新城池信息
		$guildList = $guildId == $attackGid ? $attackList : $defendList;
		$cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId] = $guildList;
		$guildOffline = $guildId == $attackGid ? $attackOffline : $defendOffline;
		$cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$guildId] = $guildOffline;
		CityWarDao::updateCity($cityId, array(CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR]));
		
		//获取城池参战双方信息
		$arrGuildInfo = self::getBasicGuildInfo($attackGid, $defendGid);
		$attackRefresh = array_merge($arrGuildInfo[$attackGid], $attackRefresh);
		$defendRefresh = array_merge($arrGuildInfo[$defendGid], $defendRefresh);
		$refresh = array('attacker' => $attackRefresh, 'defender' => $defendRefresh);
		//如果非第一场的话，攻守方需要交换顺序
		if ($id > 0)
		{
			$refresh = array('attacker' => $defendRefresh, 'defender' => $attackRefresh);
		}
		//给前端在线人员推送信息
		$arrUid = array_merge($attackList, $defendList);
		RPCContext::getInstance()->sendMsg($arrUid, PushInterfaceDef::CITYWAR_REFRESH, $refresh);
		
		Logger::trace('CityWarLogic::leave End.');
		
		return 'ok';
	}
	
	public static function inspire($uid, $cityId, $type)
	{
		Logger::trace('CityWarLogic::inspire Start.');
		
		$ret = array('ret' => 'ok', 'suc' => false, 'attack_level' => 0, 'defend_level' => 0);
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//检查当前是否在某个战斗场次的准备时间内
		$id = self::getPreparePeriod();
		if ($id == -1)
		{
			Logger::warning('Not in any battle prepare time.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$id]))
		{
			Logger::warning('Battle:%d is not exist.', $id);
			$ret['ret'] = 'nobattle';
			return $ret;
		}
		
		//检查用户是否属于攻击方或者防守方
		$attackGid = $list[$id][CityWarDef::ATTACK_GID];
		$defendGid = $list[$id][CityWarDef::DEFEND_GID];
		if ($guildId != $attackGid && $guildId != $defendGid)
		{
			throw new FakeException('User enter the wrong battle.');
		}
		
		//获得用户信息
		$userInfo = self::getUserInfo($uid);
		$vaInfo = $userInfo[CityWarDef::VA_CITY_WAR_USER];
		
		//是否在鼓舞冷却时间
		$inspireTime = 0;
		$attackLevel = 0;
		$defendLevel = 0;
		if (!empty($vaInfo['info'][$cityId]['inspire']))
		{
			$inspireTime = $vaInfo['info'][$cityId]['inspire']['time'];
			$attackLevel = $vaInfo['info'][$cityId]['inspire']['attack'];
			$defendLevel = $vaInfo['info'][$cityId]['inspire']['defend'];
		}
		$now = Util::getTime();
		if ($now <= $inspireTime)
		{
			throw new FakeException('User is in inspire cd');
		}
		//鼓舞是否都满级了
		$conf = btstore_get()->CITY_WAR_ATTACK;
		$limit = $conf[CityWarDef::INSPIRE_LIMIT];
		if ($attackLevel >= $limit && $defendLevel >= $limit) 
		{
			throw new FakeException('Both attack and defend level reach limit.');
		}
		
		//扣钱
		$rate = 100;
		$inspireCd = 0;
		$user = EnUser::getUserObj($uid);
		if ($type == 0) 
		{
			if ($user->subSilver($conf[CityWarDef::INSPIRE_SILVER]) == false) 
			{
				throw new FakeException('no enough silver.');
			}
			$inspireCd = $conf[CityWarDef::INSPIRE_CD];
			//计算银币鼓舞的概率
			$rate = intval(($conf[CityWarDef::INSPIRE_BASERATE] - $conf[CityWarDef::INSPIRE_SUCPARAM] * ($attackLevel + $defendLevel)) / 100);
		}
		if ($type == 1) 
		{
			if ($user->subGold($conf[CityWarDef::INSPIRE_GOLD], StatisticsDef::ST_FUNCKEY_CITYWAR_INSPIRE) == false) 
			{
				throw new FakeException('no enough gold.');
			}
		}
		
		//是否成功鼓舞
		$rand = rand(1, 100);
		if ($rand <= $rate) 
		{
			$ret['suc'] = true;
			$rand = rand(0, 1);
			if ($rand == 0) 
			{
				if ($attackLevel >= $limit) 
				{
					$defendLevel ++;
				}
				else 
				{
					$attackLevel ++;
				}
			}
			if ($rand == 1) 
			{
				if ($defendLevel >= $limit) 
				{
					$attackLevel ++;
				}
				else 
				{
					$defendLevel ++;
				}
			}
		}
		$ret['attack_level'] = $attackLevel;
		$ret['defend_level'] = $defendLevel;
		
		$user->update();
		
		//更新到数据库
		$userInfo[CityWarDef::VA_CITY_WAR_USER]['info'][$cityId]['inspire'] = array(
				'time' => $now + $inspireCd,
				'attack' => $attackLevel,
				'defend' => $defendLevel,
		);
		CityWarDao::updateUser($uid, array(CityWarDef::VA_CITY_WAR_USER => $userInfo[CityWarDef::VA_CITY_WAR_USER]));
		
		Logger::trace('CityWarLogic::inspire End.');
		
		return $ret;
	}
	
	public static function buyWin($uid, $cityId)
	{
		Logger::trace('CityWarLogic::buyWin Start.');
		
		$ret = array('ret' => 'ok', 'max_win' => 0, 'buy_num' => 0);
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//检查当前是否在某个战斗场次的准备时间内
		$id = self::getPreparePeriod();
		if ($id == -1)
		{
			Logger::warning('Not in any battle prepare time.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//城池是否有军团报名参战，这里是不包括占领方的
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$list = CityWarDao::getCityAttackList($cityId, $signupStartTime, $signupEndTime);
		if (!isset($list[$id]))
		{
			Logger::warning('Battle:%d is not exist.', $id);
			$ret['ret'] = 'nobattle';
			return $ret;
		}
		
		//检查用户是否属于攻击方或者防守方
		$attackGid = $list[$id][CityWarDef::ATTACK_GID];
		$defendGid = $list[$id][CityWarDef::DEFEND_GID];
		if ($guildId != $attackGid && $guildId != $defendGid)
		{
			throw new FakeException('User enter the wrong battle.');
		}
		
		//获得用户信息
		$buyNum = 0;
		$addNum = 0;
		$userInfo = self::getUserInfo($uid);
		$vaInfo = $userInfo[CityWarDef::VA_CITY_WAR_USER];
		if (!empty($vaInfo['info'][$cityId]['win'])) 
		{
			$buyNum = $vaInfo['info'][$cityId]['win']['buy'];
			$addNum = $vaInfo['info'][$cityId]['win']['add'];
		}

		//检查是否超过购买上限
		$conf = btstore_get()->CITY_WAR_ATTACK;
		$winGold = $conf[CityWarDef::WIN_GOLD];
		if ($buyNum >= count($winGold)) 
		{
			throw new FakeException('No buy num');
		}
		
		list($cost, $add) = $winGold[$buyNum];
		//检查用户金币是否足够
		$user = EnUser::getUserObj($uid);
		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_CITYWAR_BUYWIN) == false) 
		{
			throw new FakeException('No enough gold');
		}
		$user->update();
		//加购买次数和连胜次数
		$userInfo[CityWarDef::VA_CITY_WAR_USER]['info'][$cityId]['win'] = array(
				'buy' => $buyNum + 1,
				'add' => $addNum + $add,
		);
		CityWarDao::updateUser($uid, array(CityWarDef::VA_CITY_WAR_USER => $userInfo[CityWarDef::VA_CITY_WAR_USER]));
		
		$ret['max_win'] = $conf[CityWarDef::WIN_DEFAULT] + $addNum + $add;
		$ret['buy_num'] = $buyNum + 1;
		
		Logger::trace('CityWarLogic::buyWin End.');
		
		return $ret;
	}
	
	public static function getReward($uid, $cityId)
	{
		Logger::trace('CityWarLogic::getReward Start.');
		
		$ret = array('ret' => 'ok', 'member_type' => 0);
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//用户是否加入军团
		$guildId = EnGuild::getGuildId($uid);
		if (empty($guildId))
		{
			Logger::warning('User is not in guild.');
			$ret['ret'] = 'err';
			return $ret;
		}
		
		//检查是否在领奖时间内
		$now = Util::getTime();
		list($rewardStartTime, $rewardEndTime) = self::getRewardTime();
		if ($now < $rewardStartTime && $now > $rewardEndTime)
		{
			Logger::warning('Reward time is not arrive.');
			$ret['ret'] = 'err';
			return $ret;
		}

		//获得城池信息
		$cityInfo = CityWarDao::selectCity($cityId);
		if ($guildId != $cityInfo[CityWarDef::LAST_GID]) 
		{
			throw new FakeException('User can not get reward of the city');
		}
		
		//用户是否在领奖名单上
		$rewardList = $cityInfo[CityWarDef::VA_REWARD]['list'];
		if (!key_exists($uid, $rewardList)) 
		{
			throw new FakeException('User is not in the reward list');
		}
		$ret['member_type'] = $rewardList[$uid];
		
		//用户是否领奖过了
		$userInfo = self::getUserInfo($uid);
		$rewardTime = $userInfo[CityWarDef::REWARD_TIME];
		if ($rewardTime >= self::getBattleEndTime() && $rewardTime <= $rewardEndTime) 
		{
			throw new FakeException('User get reward already');
		}
		
		//给用户发奖
		$reward = self::getCityRewardByType($cityId, $rewardList[$uid]);
		RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_CITYWAR_REWARD);
		$userInfo[CityWarDef::REWARD_TIME] = Util::getTime();
		CityWarDao::insertOrUpdateUser($userInfo);
		MailTemplate::sendCityWarReward($uid, $cityId, $rewardList[$uid]);
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		Logger::trace('CityWarLogic::getReward End.');
		
		return $ret;
	}
	
	public static function signup($uid, $cityId)
	{
		Logger::info('CityWarLogic::signup Start. cityId:%d', $cityId);
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//判断是否在报名时间内
		$now = Util::getTime();
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		if ($now < $signupStartTime || $now > $signupEndTime)
		{
			Logger::warning('Signup time is not arrive.');
			return 'err';
		}
		
		//用户是否有报名的权限
		if (self::isMemberRight($uid) == false) 
		{
			Logger::warning('User has no privilege.');
			return 'err';
		}
		
		//军团是否占领其他城池
		$guildInfo = EnGuild::getGuildInfo($uid);
		$guildId = $guildInfo[GuildDef::GUILD_ID];
		$cityList = CityWarDao::getGuildCityList($guildId);
		$occupy = count($cityList);
		//如果占领城池数大于1，需要修复数据
		if ($occupy > 1)
		{
			Logger::fatal('Fix me! guild:%d occupied %d cities:%s!', $guildId, $occupy, array_keys($cityList));
		}
		//占领城池数是否达到上限
		$limit = btstore_get()->CITY_WAR_ATTACK[CityWarDef::SIGNUP_LIMIT];
		if ($occupy >= $limit)
		{
			Logger::fatal('Guild occupy num is reach limit.');
		}
		//军团等级是否足够
		$needLevel = btstore_get()->CITY_WAR[$cityId][CityWarDef::GUILD_LEVEL];
		if ($guildInfo[GuildDef::GUILD_LEVEL] < $needLevel) 
		{
			throw new FakeException('Guild:%d level is not reach:%d.', $guildId, $needLevel);
		}
		
		//军团是否已经报名
		$signupList = CityWarDao::getGuildSignupList($guildId, $signupStartTime, $signupEndTime);
		if (key_exists($cityId, $signupList))
		{
			Logger::warning('Guild signup the city already.');
			return 'err';
		}
		//军团报名城池数是否达到上限
		if (count($signupList) + $occupy >= $limit)
		{
			Logger::warning('Guild signup is reach limit.');
			return 'err';
		}
		
		//军团是否占领此城池
		$cityInfo = CityWarDao::selectCity($cityId);
		if (empty($cityInfo))
		{
			$cityInfo = self::initCity($cityId);
		}
		if ($cityInfo[CityWarDef::LAST_GID] == $guildId) 
		{
			throw new FakeException('Guild:%d is occupy the city.', $guildId);
		}
		
		//初始化军团报名信息
		self::initAttack($cityId, $guildId);
		
		//添加报名结束的timer,如果timer已经失效就修复数据
		if (!empty($cityInfo[CityWarDef::SIGNUP_END_TIMER])) 
		{
			$task = TimerDAO::getTask($cityInfo[CityWarDef::SIGNUP_END_TIMER], array('status'));
			if (!in_array($task['status'], array(TimerStatus::UNDO, TimerStatus::RETRY))) 
			{
				$cityInfo[CityWarDef::SIGNUP_END_TIMER] = 0;
			}
		}
		if (empty($cityInfo[CityWarDef::SIGNUP_END_TIMER])) 
		{
			$cityInfo[CityWarDef::SIGNUP_END_TIMER] = TimerTask::addTask(0, $signupEndTime, 'citywar.signupEnd', array($cityId));
			CityWarDao::insertOrUpdateCity($cityInfo);
		}
		
		//给军团其他人推送报名城池
		$memberList = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID));
		$arrUid = array_keys($memberList);
		$arrCity = array_keys($signupList);
		RPCContext::getInstance()->sendMsg(array_diff($arrUid, array($uid)), PushInterfaceDef::CITYWAR_SIGN_REFRESH, array_merge($arrCity, array($cityId)));
		
		Logger::trace('CityWarLogic::signup End.');
		
		return 'ok';
	}
	
	public static function ruinCity($uid, $cityId)
	{
		Logger::trace('CityWarLogic::ruinCity Start. uid:%d, cityId:%d', $uid, $cityId);
		
		$ret = array('ret' => 'failed', 'atk' => array());
		
		$now = Util::getTime();
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime(0);
		$battleEndTime = self::getBattleEndTime();
		if ($now >= $prepareStartTime - 600 && $now <= $battleEndTime + 600)
		{
			return $ret;
		}
		
		self::checkLevelConds($uid);
		
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//判断任务是否可以执行
		$task = EnGuildTask::beforeTask($uid, GuildTaskType::RUIN_CITY);
		if ($task['can'] == false) 
		{
			throw new FakeException('user:%d can not do the guild task:ruin city:%d', $uid, $cityId);
		}
		//检查城池的等级是否正确
		if (btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_LEVEL] < $task['extra']['level']) 
		{
			throw new FakeException('city:%d level is not reach for:%d', $cityId, $task['extra']['level']);
		}
		$subDefence = $task['extra']['num'];
		//检查用户Cd时间
		$userInfo = self::getUserInfo($uid);
		$cd = btstore_get()->CITY_WAR_ATTACK[CityWarDef::CD_TIME];
		if ($userInfo[CityWarDef::RUIN_TIME] + $cd > $now) 
		{
			throw new FakeException('user:%d is in ruin city cd time', $uid);
		}
		//检查用户体力
		$subExe = $task['extra']['perExe'];
		$user = EnUser::getUserObj($uid);
		if ($user->subExecution($subExe) == false) 
		{
			throw new FakeException('user:%d has no enough execution', $uid);
		}
		$user->update();
		
		//获得刷新后的城池信息
		list($cityInfo, $init) = self::getRefreshedCityInfo($cityId, true);
		$oldInfo = $cityInfo;
		
		//检查用户所在军团是否是城池占领者
		$lastGuildId = $cityInfo[CityWarDef::LAST_GID];
		$guildId =  EnGuild::getGuildId($uid);
		if ($lastGuildId == $guildId) 
		{
			throw new FakeException('user can not ruin his own city:%d, guildId:%d', $cityId, $guildId);
		}
		
		//破坏城池的时候，如果城池无人占领，则100%选取守城部队（NPC）
		//如果有军团占领，则50%概率选取守城部队，50%概率选取守城军团中随机1人(排除本人)
		$guards = btstore_get()->CITY_WAR[$cityId][CityWarDef::RUIN_GUARD];
		$guarder = $guards[rand(0, count($guards) - 1)];
		$battleGuard = EnFormation::getMonsterBattleFormation($guarder);
		$guardFF = $battleGuard['fightForce'];
		$atkUid = 0;
		$atkName = $guarder;
		if (!empty($lastGuildId) && rand(1, 100) <= 50) 
		{
			$memberList = EnGuild::getMemberList($lastGuildId, array(GuildDef::USER_ID));
			unset($memberList[$uid]);
			if (!empty($memberList)) 
			{
				$guard = array_rand($memberList);
				$guarder = EnUser::getUserObj($guard);
				$battleGuard = $guarder->getBattleFormation();
				$guardFF = $guarder->getFightForce();
				$atkUid = $guard;
				$atkName = $guarder->getUname();
			}
		}
		
		//获得用户的战斗力和战斗阵型
		$battleUser = $user->getBattleFormation();
		$userFF = $user->getFightForce();
		
		//开始战斗
		$type = EnBattle::setFirstAtk(0, $userFF >= $guardFF);
		$atkRet = EnBattle::doHero($battleUser, $battleGuard, $type);
		$ret['atk'] = array(
				'uid' => $atkUid,
				'uname' => $atkName,
				'fightRet' => $atkRet['client'],
				'appraisal' => $atkRet['server']['appraisal'],
		);
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		if ($isSuc) 
		{
			$default = btstore_get()->CITY_WAR[$cityId][CityWarDef::DEFENCE_DEFAULT];
			$min = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_MIN];
			$min = intval($min * $default / 100);
			$cityInfo[CityWarDef::CITY_DEFENCE] = max($cityInfo[CityWarDef::CITY_DEFENCE] - $subDefence, $min);
			//计算城防和战斗力变化
			$defence = $cityInfo[CityWarDef::CITY_DEFENCE];
			$param = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_PARAM];
			$force = intval((1 + $param * ($defence - $default) / $default / UNIT_BASE) * 100);
			$ret['defence'] = $defence;
			$ret['force'] = $force;
			$ret['subdefence'] = $oldInfo[CityWarDef::CITY_DEFENCE] - $cityInfo[CityWarDef::CITY_DEFENCE];
			$ret['subforce'] = round($ret['subdefence'] * 100 / $default, 1);
		}
		
		//刷新城池的破坏者列表
		$date = intval(strftime("%Y%m%d", $now));
		$lastDate = intval(strftime("%Y%m%d", $now - SECONDS_OF_DAY));
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'])) 
		{
			foreach ($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'] as $key => $value)
			{
				if ($key != $date && $key != $lastDate) 
				{
					unset($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$key]);
				}
			}
		}
		
		//记录战斗力前20的破坏者
		$ruin = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$date])) 
		{
			$ruin = $cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$date];
		}
		//用户不在破坏者列表中
		if (!in_array($uid, $ruin)) 
		{
			if (count($ruin) < CityWarConf::RUIN_LIMIT) 
			{
				$ruin[] = $uid;
			}
			else 
			{
				$arrUser = EnUser::getArrUser($ruin, array('fight_force'));
				$arrUser = Util::arrayIndexCol($arrUser, 'uid', 'fight_force');
				$arrUser[$uid] = $userFF;
				arsort($arrUser);
				$ruin = array_keys(array_slice($arrUser, 0, CityWarConf::RUIN_LIMIT, true));
			}
		}
		$cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$date] = $ruin;
		
		//数据有更新的时候再存入数据库
		$arrField = array();
		foreach ($cityInfo as $key => $value)
		{
			if ($oldInfo[$key] != $value)
			{
				$arrField[$key] = $value;
				if ($key == CityWarDef::CITY_DEFENCE) 
				{
					$arrField[CityWarDef::DEFENCE_TIME] = $cityInfo[CityWarDef::DEFENCE_TIME];
				}
			}
		}
		if (!empty($arrField)) 
		{
			if ($init) 
			{
				CityWarDao::insertOrUpdateCity($cityInfo);
			}
			else 
			{
				CityWarDao::updateCity($cityId, $arrField);
			}
		}
		
		//更新用户破坏城防时间
		$userInfo[CityWarDef::RUIN_TIME] = $now;
		CityWarDao::insertOrUpdateUser($userInfo);
		
		//完成军团任务
		if ($isSuc) 
		{
			EnGuildTask::taskIt($uid, GuildTaskType::RUIN_CITY, 0, 1);
		}
		
		$ret['ret'] = 'ok';
		Logger::trace('CityWarLogic::ruinCity End.');
		
		return $ret;
	}
	
	public static function mendCity($uid, $cityId)
	{
		Logger::trace('CityWarLogic::mendCity Start. uid:%d, cityId:%d', $uid, $cityId);
		
		$ret = array('ret' => 'failed', 'atk' => array());
		
		$now = Util::getTime();
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime(0);
		$battleEndTime = self::getBattleEndTime();
		if ($now >= $prepareStartTime - 600 && $now <= $battleEndTime + 600)
		{
			return $ret;
		}
		
		self::checkLevelConds($uid);
	
		//检查城池是否存在
		self::checkCityExist($cityId);
		
		//判断任务是否可以执行
		$task = EnGuildTask::beforeTask($uid, GuildTaskType::MEND_CITY);
		if ($task['can'] == false)
		{
			throw new FakeException('user:%d can not do the guild task:mend city:%d', $uid, $cityId);
		}
		//检查城池的等级是否正确
		if (btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_LEVEL] < $task['extra']['level'])
		{
			throw new FakeException('city:%d level is not reach for:%d', $cityId, $task['extra']['level']);
		}
		$addDefence = $task['extra']['num'];
		//检查用户Cd时间
		$userInfo = self::getUserInfo($uid);
		$cd = btstore_get()->CITY_WAR_ATTACK[CityWarDef::CD_TIME];
		if ($userInfo[CityWarDef::MEND_TIME] + $cd > $now)
		{
			throw new FakeException('user:%d is in mend city cd time', $uid);
		}
		//检查用户体力
		$subExe = $task['extra']['perExe'];
		$user = EnUser::getUserObj($uid);
		if ($user->subExecution($subExe) == false)
		{
			throw new FakeException('user:%d has no enough execution', $uid);
		}
		$user->update();
	
		//获得刷新后的城池信息
		list($cityInfo, $init) = self::getRefreshedCityInfo($cityId, true);
		$oldInfo = $cityInfo;
		
		//检查用户所在军团是否是城池占领者
		$lastGuildId = $cityInfo[CityWarDef::LAST_GID];
		$guildId =  EnGuild::getGuildId($uid);
		if ($lastGuildId != $guildId)
		{
			throw new FakeException('user must mend his own city:%d, lastGuildId:%d, guildId:%d', $cityId, $lastGuildId, $guildId);
		}
		
		//刷新城池的破坏者列表
		$date = intval(strftime("%Y%m%d", $now));
		$lastDate = intval(strftime("%Y%m%d", $now - SECONDS_OF_DAY));
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['ruin']))
		{
			foreach ($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'] as $key => $value)
			{
				if ($key != $date && $key != $lastDate)
				{
					unset($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$key]);
				}
			}
		}
		//如果前一天没有破坏者，就100%与NPC打，
		//如果前一天有破坏者，就50%概率与NPC打，50%概率与破坏者打(排除本人)
		$guards = btstore_get()->CITY_WAR[$cityId][CityWarDef::MEND_GUARD];
		$guarder = $guards[rand(0, count($guards) - 1)];
		$battleGuard = EnFormation::getMonsterBattleFormation($guarder);
		$guardFF = $battleGuard['fightForce'];
		$atkUid = 0;
		$atkName = $guarder;
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$lastDate]) && rand(1, 100) <= 50)
		{
			$ruin = $cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$lastDate];
			$memberList = array_keys(EnGuild::getMemberList($lastGuildId, array(GuildDef::USER_ID)));
			$ruin = array_values(array_diff($ruin, $memberList));
			if (!empty($ruin)) 
			{
				$guard = $ruin[rand(0, count($ruin) - 1)];
				$guarder = EnUser::getUserObj($guard);
				$battleGuard = $guarder->getBattleFormation();
				$guardFF = $guarder->getFightForce();
				$atkUid = $guard;
				$atkName = $guarder->getUname();
			}
		}
	
		//获得用户的战斗力和战斗阵型
		$battleUser = $user->getBattleFormation();
		$userFF = $user->getFightForce();
	
		//开始战斗     
		$type = EnBattle::setFirstAtk(0, $userFF >= $guardFF);
		$atkRet = EnBattle::doHero($battleUser, $battleGuard, $type);
		$ret['atk'] = array(
				'uid' => $atkUid,
				'uname' => $atkName,
				'fightRet' => $atkRet['client'],
				'appraisal' => $atkRet['server']['appraisal'],
		);
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		if ($isSuc)
		{
			$default = btstore_get()->CITY_WAR[$cityId][CityWarDef::DEFENCE_DEFAULT];
			$max = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_MAX];
			$max = intval($max * $default / 100);
			$cityInfo[CityWarDef::CITY_DEFENCE] = min($cityInfo[CityWarDef::CITY_DEFENCE] + $addDefence, $max);
			//计算城防和战斗力变化
			$defence = $cityInfo[CityWarDef::CITY_DEFENCE];
			$param = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_PARAM];
			$force = intval((1 + $param * ($defence - $default) / $default / UNIT_BASE) * 100);
			$ret['defence'] = $defence;
			$ret['force'] = $force;
			$ret['adddefence'] = $cityInfo[CityWarDef::CITY_DEFENCE] - $oldInfo[CityWarDef::CITY_DEFENCE];
			$ret['addforce'] = round($ret['adddefence'] * 100 / $default, 1);
		}
		
		//数据有更新的时候再存入数据库
		$arrField = array();
		foreach ($cityInfo as $key => $value)
		{
			if ($oldInfo[$key] != $value)
			{
				$arrField[$key] = $value;
				if ($key == CityWarDef::CITY_DEFENCE)
				{
					$arrField[CityWarDef::DEFENCE_TIME] = $cityInfo[CityWarDef::DEFENCE_TIME];
				}
			}
		}
		if (!empty($arrField)) 
		{
			if ($init) 
			{
				CityWarDao::insertOrUpdateCity($cityInfo);
			}
			else 
			{
				CityWarDao::updateCity($cityId, $arrField);
			}
		}
		
		//更新用户修复城防时间
		$userInfo[CityWarDef::MEND_TIME] = $now;
		CityWarDao::insertOrUpdateUser($userInfo);
		
		//完成军团任务
		if ($isSuc) 
		{
			EnGuildTask::taskIt($uid, GuildTaskType::MEND_CITY, 0, 1);
		}
	
		$ret['ret'] = 'ok';
		Logger::trace('CityWarLogic::mendCity End.');
	
		return $ret;
	}
	
	public static function clearCd($uid, $type)
	{
		Logger::trace('CityWarLogic::clearCd Start.');
		
		$now = Util::getTime();
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime(0);
		$battleEndTime = self::getBattleEndTime();
		if ($now >= $prepareStartTime - 600 && $now <= $battleEndTime + 600)
		{
			return 'failed';
		}
		
		self::checkLevelConds($uid);
		
		$user = EnUser::getUserObj($uid);
		$num = btstore_get()->CITY_WAR_ATTACK[CityWarDef::CD_CLEAR];
		if ($user->subGold($num, StatisticsDef::ST_FUNCKEY_CITYWAR_CLEAR_CD) == false) 
		{
			throw new FakeException('user:%d has no enough gold:%d', $uid, $num);
		}
		$user->update();
		
		//更新用户cd时间
		$arrField = array(CityWarDef::$CLEARCD_VALID_TYPES[$type] => 0);
		CityWarDao::updateUser($uid, $arrField);
		
		Logger::trace('CityWarLogic::clearCd End.');
		
		return 'ok';
	}
	
	public static function signupEnd($cityId)
	{
		Logger::info('CityWarLogic::signupEnd Start. cityId:%d', $cityId);
		
		//获取城池的报名列表
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		
		//如果有人报名后，修改了配置，会出现错误。 现在没有处理，打个日志
		if(abs(Util::getTime() - $signupEndTime) > 60)
		{
			Logger::fatal('signupEnd time erro. endTime:%s', date('Y-m-d H:i:s', $signupEndTime));
		}
		
		$list = CityWarDao::getCitySignupList($cityId, $signupStartTime, $signupEndTime);
		$arrGuildId = array_keys($list);
		$arrContriWeek = EnGuild::getArrContriWeek($arrGuildId, $signupEndTime - CityWarConf::ROUND_DURATION);
		
		//获取军团的周贡献，用于排序
		foreach ($list as $key => $value)
		{
			$guildId = $value[CityWarDef::ATTACK_GID];
			$list[$key][GuildDef::CONTRI_WEEK] = $arrContriWeek[$guildId];
		}
		Logger::info('city:%d signup list:%s', $cityId, $list);
		
		//计算报名结果
		$cityInfo = CityWarDao::selectCity($cityId);
		$defendGid = $cityInfo[CityWarDef::LAST_GID];
		//如果没有防守方就取NPC
		if (empty($defendGid)) 
		{
			$defendGid = self::getNpcId($cityId);
			$defendGid = empty($defendGid) ? 0 : 1;
		}
		
		Logger::debug('list:%s', $list);
		
		$join = array();
		$attackNum = self::getAttackNum();
		$guildNum = !empty($defendGid) ? $attackNum : $attackNum + 1;
		for ($i = 0; $i < $guildNum; $i++)
		{
			$contriMax = -1;
			$index = -1;
			//每次都取出当前List中周贡献最大的一个
			foreach ($list as $key => $value)
			{
				if ($value[GuildDef::CONTRI_WEEK] > $contriMax)
				{
					$contriMax = $value[GuildDef::CONTRI_WEEK];
					$index = $key;
				}
			}
			if ($index != -1)
			{
				$join[] = $list[$index];
				unset($list[$index]);
			}
		}
		Logger::debug('join:%s', $join);
		
		Logger::info('city:%d owner:%d', $cityId, $defendGid);
		
		//如果防守方为空就取第二个军团为防守方
		if (empty($defendGid) && count($join) > 1) 
		{
			//没有防守者时，第一场，第一名打第二名
			$defendGid = $join[1][CityWarDef::ATTACK_GID];
			$list[] = $join[1];
			unset($join[1]);
			$join = array_merge($join);
			Logger::info('city:%d has no defender, the second guild:%d as defender', $cityId, $defendGid);
		}
		
		//给参战方加战斗开始的timer
		foreach ($join as $key => $value)
		{
			if (empty($value[CityWarDef::ATTACK_TIMER]))
			{
				list($attackStartTime, $attackEndTime) = self::getAttackTime($key);
				$timerId = TimerTask::addTask(0, $attackStartTime, 'citywar.attackStart',
						array($key, $cityId, $value[CityWarDef::SIGNUP_ID], $value[CityWarDef::ATTACK_GID]));
				Logger::info('set attack timer. city:%d, attack:%d, defend:%d', $cityId, $value[CityWarDef::ATTACK_GID], $defendGid);
				$arrField = array(CityWarDef::ATTACK_TIMER => $timerId, CityWarDef::ATTACK_CONTRI => $value[GuildDef::CONTRI_WEEK]);
				if(!empty($defendGid))
				{	
					$arrField[CityWarDef::DEFEND_GID] = $defendGid;
					$defendGid = 0;
				}
				CityWarDao::updateAttack($value[CityWarDef::SIGNUP_ID], $arrField);
			}
			else 
			{
				Logger::fatal('cityId:%d, attacker timer:%d when signup', $cityId, $value[CityWarDef::ATTACK_TIMER]);
			}
		}
		
		//增加整个城池战结束timer，重置报名的timer
		$timerId = TimerTask::addTask(2, self::getBattleEndTime(), 'citywar.battleEnd', array($cityId));
		$arrField = array(
				CityWarDef::SIGNUP_END_TIMER => 0,
				CityWarDef::BATTLE_END_TIMER => $timerId,
		);
		CityWarDao::updateCity($cityId, $arrField);
		
		//记录所有报名军团的周贡献值
		foreach ($list as $key => $value)
		{
			$arrField = array(CityWarDef::ATTACK_CONTRI => $value[GuildDef::CONTRI_WEEK]);
			CityWarDao::updateAttack($value[CityWarDef::SIGNUP_ID], $arrField);
		}
		
		Logger::info('CityWarLogic::signupEnd End. city:%d', $cityId);
	}
	
	public static function checkAttack()  
	{
		Logger::trace('CityWarLogic::checkAttack Start.');
		
		$allCity = btstore_get()->CITY_WAR->toArray();
		$allCity = array_keys($allCity);
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$attackList = CityWarDao::getAllAttackList($signupStartTime, $signupEndTime);
		$attackCity = Util::arrayExtract($attackList, CityWarDef::CITY_ID);
		$attackCity = array_unique($attackCity);
		$noAttackCity = array_diff($allCity, $attackCity);
		$battleEndTime = self::getBattleEndTime();
		$arrTask = TimerDAO::getArrTaskByName('citywar.battleEnd', array(TimerStatus::UNDO), $battleEndTime, array('va_args'));
		$taskCity = array();
		foreach ($arrTask as $task)
		{
			$taskCity[] = $task['va_args'][0];
		}
		$noAttackTaskCity = array_diff($noAttackCity, $taskCity);
		foreach ($noAttackTaskCity as $cityId)
		{
			//增加整个城池战结束timer
			TimerTask::addTask(2, $battleEndTime, 'citywar.battleEnd', array($cityId));
			Logger::info('add battle end timer of city:%d', $cityId);
		}
		
		Logger::trace('CityWarLogic::checkAttack End.');
	}
	
	public static function attackStart($id, $cityId, $signupId, $attackGid)
	{
		Logger::trace('CityWarLogic::attackStart Start.');
	
		$arrArg = array($id, $cityId, $signupId, $attackGid);
		Logger::info('attack args: battle:%d city:%d signup:%d attackGid:%d', $id, $cityId, $signupId, $attackGid);
		RPCContext::getInstance()->asyncExecuteTask('citywar.doAttack', $arrArg);
		
		Logger::trace('CityWarLogic::attackStart End.');
	}
	
	public static function doAttack($id, $cityId, $signupId, $attackGid)
	{
		Logger::trace('CityWarLogic::doAttack Start.');
		
		//获得城池的实际占领者
		$cityInfo = CityWarDao::selectCity($cityId);
		$lastGid = $cityInfo[CityWarDef::LAST_GID];
		
		//获得攻击的防守方
		$attackInfo = CityWarDao::selectAttack($signupId);
		$defendGid = $attackInfo[CityWarDef::DEFEND_GID];
		
		//获得参战双方的用户
		$attackUid = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid]))
		{
			$attackUid = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$attackGid];
			Logger::trace("attack uid:%s", $attackUid);
		}
		$attackOfflineUid = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid]))
		{
			$attackOfflineUid = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$attackGid];
			Logger::trace("attack offline uid:%s", $attackOfflineUid);
		}
		$defendUid = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid]))
		{
			$defendUid = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$defendGid];
			Logger::trace("defend uid:%s", $defendUid);
		}
		$defendOfflineUid = array();
		if (!empty($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid]))
		{
			$defendOfflineUid = $cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id][$defendGid];
			Logger::trace("defend offline uid:%s", $defendOfflineUid);
		}
		$arrUid = array_unique(array_merge($attackUid, $defendUid, $attackOfflineUid, $defendOfflineUid));
		$arrGuildId = EnGuild::getMultiMember($arrUid, array(GuildDef::GUILD_ID));
		
		//获得攻击方和防守方的战斗数据
		$arrUser = array();
		if (!empty($arrUid)) 
		{
			$arrCond = array(array(CityWarDef::USER_ID, 'in', $arrUid));
			$arrField = array(
					CityWarDef::USER_ID, 
					CityWarDef::CUR_CITY,
					CityWarDef::ENTER_TIME,
					CityWarDef::VA_CITY_WAR_USER,
			);
			$arrUser = CityWarDao::getArrUser($arrCond, $arrField);
		}
		//过滤attackuid和defenduid
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($id);
		foreach ($attackUid as $key => $uid)
		{
			if ($attackGid != $arrGuildId[$uid][GuildDef::GUILD_ID]
			|| $arrUser[$uid][CityWarDef::ENTER_TIME] < $prepareStartTime
			|| $arrUser[$uid][CityWarDef::ENTER_TIME] > $prepareEndTime
			|| $arrUser[$uid][CityWarDef::CUR_CITY] != $cityId)
			{
				unset($attackUid[$key]);
				unset($arrUser[$uid]);
				Logger::warning("invalid uid:%d guild:%d", $uid, $attackGid);
			}
		}
		foreach ($defendUid as $key => $uid)
		{
			if ($defendGid != $arrGuildId[$uid][GuildDef::GUILD_ID]
			|| $arrUser[$uid][CityWarDef::ENTER_TIME] < $prepareStartTime
			|| $arrUser[$uid][CityWarDef::ENTER_TIME] > $prepareEndTime
			|| $arrUser[$uid][CityWarDef::CUR_CITY] != $cityId)
			{
				unset($defendUid[$key]);
				unset($arrUser[$uid]);
				Logger::warning("invalid uid:%d guild:%d", $uid, $defendGid);
			}
		}
		//过滤attackofflineuid和defendofflineuid
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		foreach ($attackOfflineUid as $key => $uid)
		{
			if ($attackGid != $arrGuildId[$uid][GuildDef::GUILD_ID]
			|| !isset($arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
			|| $arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['time'] < $signupEndTime
			|| !isset($arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id])
			|| $arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id] != $cityId)
			{
				unset($attackOfflineUid[$key]);
				unset($arrUser[$uid]);
				Logger::warning("invalid uid:%d guild:%d", $uid, $attackGid);
			}
		}
		foreach ($defendOfflineUid as $key => $uid)
		{
			if ($defendGid != $arrGuildId[$uid][GuildDef::GUILD_ID]
			|| !isset($arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['time'])
			|| $arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['time'] < $signupEndTime
			|| !isset($arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id])
			|| $arrUser[$uid][CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id] != $cityId)
			{
				unset($defendOfflineUid[$key]);
				unset($arrUser[$uid]);
				Logger::warning("invalid uid:%d guild:%d", $uid, $defendGid);
			}
		}
		$attackUid = array_unique(array_merge($attackUid, $attackOfflineUid));
		$defendUid = array_unique(array_merge($defendUid, $defendOfflineUid));
		$arrUid = array_unique(array_merge($attackUid, $defendUid));
		$attackBattle = self::getBattleInfo($attackGid, $id, $cityId, $attackUid, $arrUser);
		$defendBattle = self::getBattleInfo($defendGid, $id, $cityId, $defendUid, $arrUser);
		
		$arenaCount = 3;
		$maxWin = btstore_get()->CITY_WAR_ATTACK[CityWarDef::WIN_DEFAULT];
		$arrExtra = array(
				'arrNeedResult'=> array(
						'simpleRecord' => 2, 
						'saveSimpleRecord' => 0,
						'defaultAttackWin' => true,//默认攻方赢
				),
				'mainType' => BattleType::CITY_WAR,
		);
		//第一场攻击方和防守方都是正确的，之后的攻击方和防守方都是反过来的，这个是策划要求的
		if ($id == 0) 
		{
			$atkRet = EnBattle::doMultiHero($attackBattle, $defendBattle, $arenaCount, $maxWin, $arrExtra);
			$result = $atkRet['server']['result'];
			$guildId = $result == 1 ? $attackGid : $defendGid;
		}
		else 
		{
			$atkRet = EnBattle::doMultiHero($defendBattle, $attackBattle, $arenaCount, $maxWin, $arrExtra);
			$result = $atkRet['server']['result'];
			$guildId = $result == 1 ? $defendGid : $attackGid;
		}
		
		//保存此次攻击的战斗结果
		$replayId = $atkRet['server']['brid'];
		$arrField = array(CityWarDef::ATTACK_REPLAY => $replayId, CityWarDef::ATTACK_RESULT => $result);
		CityWarDao::updateAttack($signupId, $arrField);
		
		//如果接下来还有攻击，就更新下次攻击的防守方
		$attackTimer = $attackInfo[CityWarDef::ATTACK_TIMER];
		$arrCond = array(
				array(CityWarDef::CITY_ID, '=', $cityId),
				array(CityWarDef::ATTACK_TIMER, '>', $attackTimer),
		);
		$arrRet = CityWarDao::getAttack($arrCond);
		$nextGuildIds = array();
		if (!empty($arrRet))
		{
			$nextSignupId = $arrRet[0][CityWarDef::SIGNUP_ID];
			CityWarDao::updateAttack($nextSignupId, array(CityWarDef::DEFEND_GID => $guildId));
			//获得下一场参战双方军团
			$nextAttackInfo = CityWarDao::selectAttack($nextSignupId);
			$nextGuildIds = array($nextAttackInfo[CityWarDef::ATTACK_GID], $nextAttackInfo[CityWarDef::DEFEND_GID]);
		}
		
		//获得参战所有人员发奖
		//最后胜利军团个人贡献 =（胜利贡献基础值 + 参加战斗总次数 * 战斗贡献加成值）
		//最后失败军团个人贡献 =（失败贡献基础值 + 参加战斗总次数 * 战斗贡献加成值）
		$uidMap = array();
		$conf = btstore_get()->CITY_WAR_ATTACK;
		$addPoint = $conf[CityWarDef::CONTRI_ADD];
		$winPoint = $conf[CityWarDef::CONTRI_WIN];
		$failPoint = $conf[CityWarDef::CONTRI_FAIL];
		foreach ($atkRet['server']['arrProcess'] as $value)
		{
			$attacker = array();
			$defender = array();
			foreach ($value as $arrRow)
			{
				if (empty($arrRow)) 
				{
					continue;
				}
				$attacker[] = $arrRow['attacker'];
				$attacker[] = $arrRow['defender'];
			}
			$arrRet = array_merge($attacker, $defender);
			$arrRet = array_count_values($arrRet);
			$uidMap = Util::arrayAdd2V(array($uidMap, $arrRet));
		}
		foreach ($attackUid as $uid)
		{
			$point = $attackGid == $guildId ? $winPoint : $failPoint;
			$count = !isset($uidMap[$uid]) ? 0 : $uidMap[$uid];
			$point += $count * $addPoint;
			RPCContext::getInstance()->executeTask($uid, 'guild.addUserPoint', array($uid, $point), false);
		}
		foreach ($defendUid as $uid)
		{
			$point = $defendGid == $guildId ? $winPoint : $failPoint;
			$count = !isset($uidMap[$uid]) ? 0 : $uidMap[$uid];
			$point += $count * $addPoint;
			RPCContext::getInstance()->executeTask($uid, 'guild.addUserPoint', array($uid, $point), false);
		}
		
		//清空在当前城池的所有用户鼓舞信息,保留离线入场信息
		foreach ($arrUser as $uid => $userInfo)
		{
			//如果用户勾选了下一场离线，但是所在军团没有参战，就自动取消勾选
			if (isset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id + 1])
			&& $cityId == $userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id + 1]
			&& !in_array($arrGuildId[$uid][GuildDef::GUILD_ID], $nextGuildIds))
			{
				unset($userInfo[CityWarDef::VA_CITY_WAR_USER]['offline']['round'][$id + 1]);
			}
			unset($userInfo[CityWarDef::VA_CITY_WAR_USER]['info']);
			$arrField = array(CityWarDef::VA_CITY_WAR_USER => $userInfo[CityWarDef::VA_CITY_WAR_USER]);
			CityWarDao::updateUser($uid, $arrField);
		}
		
		//如果有下一场战斗，自动取消没有参战的军团的离线信息
		if (isset($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id + 1])) 
		{
			foreach ($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id + 1] as $key => $value)
			{
				if (!in_array($key, $nextGuildIds)) 
				{
					unset($cityInfo[CityWarDef::VA_CITY_WAR]['offline'][$id + 1][$key]);
				}
			}
		}
		//更新城池的占领方,清空参战人员,保留离线入场信息,保留破坏城防信息
		unset($cityInfo[CityWarDef::VA_CITY_WAR]['list']);
		$arrField = array(
				CityWarDef::CURR_GID => $guildId,
				CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR],
		);
		CityWarDao::updateCity($cityId, $arrField);
		
		//推送战报信息和战斗结束信息
		RPCContext::getInstance()->sendMsg($arrUid, PushInterfaceDef::CITYWAR_ATK_RESULT, $atkRet);
		RPCContext::getInstance()->sendMsg(array(0), PushInterfaceDef::CITYWAR_ATK_END, array('cityId' => $cityId));
		
		foreach ($arrUid as $uid)
		{
			EnAchieve::updateCityWarBattle($uid, $signupStartTime);
		}
		Logger::trace('CityWarLogic::doAttack End.');
	}
	
	public static function battleEnd($cityId)
	{
		Logger::trace('CityWarLogic::battleEnd Start.');
		
		//如果有人报名后，修改了配置，会出现错误。 现在没有处理，打个日志
		$battleEndTime = self::getBattleEndTime();
		if(abs(Util::getTime() - $battleEndTime) > 60)
		{
			Logger::fatal('battleEnd time erro. endTime:%s', date('Y-m-d H:i:s', $battleEndTime));
		}
		
		//如果军团报名前占领着城池a,而报名城池b和c
		//战斗结束后军团成功占领城池b和c,且守住了城池a,则自动放弃城池a
		//最终根据城池等级和报名时间占领b和c中的一个
		
		//获得城池的信息
		$cityInfo = CityWarDao::selectCity($cityId);
		//城池信息为空直接跳过
		if (empty($cityInfo)) 
		{
			return;
		}
		$currGid = $cityInfo[CityWarDef::CURR_GID];
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		
		$uidType = array();
		//如果当前占领者不是NPC和空
		if ($currGid > 1) 
		{
			//获得军团报名的城池列表
			$signList = CityWarDao::getGuildSignupList($currGid, $signupStartTime, $signupEndTime);
			//获得军团占领的所有城池
			$cityList = CityWarDao::getGuildCityList($currGid, CityWarDef::CURR_GID);
			$limit = btstore_get()->CITY_WAR_ATTACK[CityWarDef::SIGNUP_LIMIT];
			$occupy = count($cityList);
			if ($occupy > $limit) 
			{
				Logger::warning('Fix me! guild:%d occupied %d cities:%s!', $currGid, $occupy, array_keys($cityList));
			}
			//默认占领等级较高的城池，如果城池等级相同，则占领非原占领城池，如果没有原占领城池，则占领报名时间较早的城池。如果时间相同，则随机占领一个。
			$sort = array(
					'level' => SortByFieldFunc::DESC,
					'diff' => SortByFieldFunc::DESC,
					'time' => SortByFieldFunc::ASC,
			);
			foreach ($cityList as $key => $value)
			{
				$level = btstore_get()->CITY_WAR[$key][CityWarDef::CITY_LEVEL];
				$diff = $value[CityWarDef::LAST_GID] == $value[CityWarDef::CURR_GID] ? 0 : 1;
				$time = empty($signList[$key]) ? 0 : $signList[$key][CityWarDef::SIGNUP_TIME];
				$sortArray[] = array(
						'level' => $level,
						'diff' => $diff,
						'time' => $time,
						'id' => $key,
				);
			}
			$sortCmp = new SortByFieldFunc($sort);
			usort($sortArray, array($sortCmp, 'cmp'));
			$index = $sortArray[0]['id'];
			
			//放弃其他城池
			foreach ($cityList as $key => $value)
			{
				if ($key != $index)
				{
					unset($value[CityWarDef::VA_CITY_WAR]['list']);
					unset($value[CityWarDef::VA_CITY_WAR]['offline']);
					$arrField = array(
							CityWarDef::CITY_DEFENCE => 0,
							CityWarDef::DEFENCE_TIME => 0,
							CityWarDef::LAST_GID => 0,
							CityWarDef::CURR_GID => 0,
							CityWarDef::OCCUPY_TIME => 0,
							CityWarDef::VA_REWARD => array(),
							CityWarDef::VA_CITY_WAR => $value[CityWarDef::VA_CITY_WAR],
					);
					CityWarDao::updateCity($key, $arrField);
				}
			}
			
			//占领这个城池
			$memberList = EnGuild::getMemberList($currGid, array(GuildDef::MEMBER_TYPE));
			$uidType = Util::arrayIndexCol($memberList, GuildDef::USER_ID, GuildDef::MEMBER_TYPE);
			//获取军团所有成员的职位信息，存入city表
			unset($cityList[$index][CityWarDef::VA_CITY_WAR]['list']);
			unset($cityList[$index][CityWarDef::VA_CITY_WAR]['offline']);
			$arrField = array(
					CityWarDef::CITY_DEFENCE => btstore_get()->CITY_WAR[$index][CityWarDef::DEFENCE_DEFAULT],
					CityWarDef::DEFENCE_TIME => $battleEndTime,
					CityWarDef::LAST_GID => $currGid,
					CityWarDef::CURR_GID => $currGid,
					CityWarDef::OCCUPY_TIME => $battleEndTime,
					CityWarDef::VA_REWARD => array('list' => $uidType),
					CityWarDef::VA_CITY_WAR => $cityList[$index][CityWarDef::VA_CITY_WAR],
			);
			//军团占领的城池没有变化时
			if ($cityList[$index][CityWarDef::LAST_GID] == $cityList[$index][CityWarDef::CURR_GID]) 
			{
				unset($arrField[CityWarDef::CITY_DEFENCE]);
				unset($arrField[CityWarDef::DEFENCE_TIME]);
				unset($arrField[CityWarDef::OCCUPY_TIME]);
			}
			CityWarDao::updateCity($index, $arrField);
		}
		else 
		{
			//占领这个城池
			unset($cityInfo[CityWarDef::VA_CITY_WAR]['list']);
			unset($cityInfo[CityWarDef::VA_CITY_WAR]['offline']);
			$arrField = array(
					CityWarDef::CITY_DEFENCE => 0,
					CityWarDef::DEFENCE_TIME => 0,
					CityWarDef::LAST_GID => 0,
					CityWarDef::CURR_GID => 0,
					CityWarDef::OCCUPY_TIME => 0,
					CityWarDef::VA_REWARD => array(),
					CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR],
			);
			CityWarDao::updateCity($cityId, $arrField);
		}
		
		//在下一轮报名结束后加上timer，检查所有有军团占领但是没有报名战斗的城池
		TimerTask::addTask(2, $signupEndTime + CityWarConf::ROUND_DURATION + CityWarConf::CHECK_OFFSET, 'citywar.checkAttack', array());
		
		foreach ($uidType as $uid => $type)
		{
			EnAchieve::updateCityCapture($uid, 1);
		}
		
		Logger::trace('CityWarLogic::battleEnd End.');
	}
	
	public static function getCityPrepareInfoByUid($uid)
	{
		$cityInfo = array();
		
		$id = self::getPreparePeriod();
		if ($id == -1)
		{
			Logger::trace('Not in any battle prepare time');
			return $cityInfo;
		}
		
		$userInfo = self::getUserInfo($uid);
		$cityId = $userInfo[CityWarDef::CUR_CITY];
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($id);
		
		if(  $userInfo[CityWarDef::ENTER_TIME] >= $prepareStartTime
			&& $userInfo[CityWarDef::ENTER_TIME] <= $prepareEndTime
			&&  $cityId > 0 )
		{
			$cityInfo = CityWarDao::selectCity($cityId);
		}
		
		return $cityInfo;
	}
	
	/**
	 * 正在战场上的玩家，登陆时需要通知前端
	 */
	public static function loginNotify($uid)
	{
		
		$cityInfo = self::getCityPrepareInfoByUid($uid);
		if( empty($cityInfo) )
		{
			return;
		}
		
		$guildId =  EnGuild::getGuildId($uid);
		if (empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId]))
		{
			$arrUid = array();
		}
		else
		{
			$arrUid = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId];
		}
		if( in_array($uid, $arrUid) )
		{
			$list = array_diff($arrUid, array($uid));
			$msg = array(
				'uid' => $uid,
			);
			RPCContext::getInstance()->sendMsg( $list, PushInterfaceDef::CITYWAR_USER_LOGIN, $msg);
		}
		
	}
	
	/**
	 * 正在战场上的玩家，离线需要通知前端
	 */
	public static function logoffNotify($uid)
	{
	
		$cityInfo = self::getCityPrepareInfoByUid($uid);
		if( empty($cityInfo) )
		{
			return;
		}
		
		$guildId =  EnGuild::getGuildId($uid);
		if (empty($cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId]))
		{
			$arrUid = array();
		}
		else
		{
			$arrUid = $cityInfo[CityWarDef::VA_CITY_WAR]['list'][$guildId];
		}
		if( in_array($uid, $arrUid) )
		{
			$list = array_diff($arrUid, array($uid));
			$msg = array(
						'uid' => $uid,
						'time' => Util::getTime()
				);
				RPCContext::getInstance()->sendMsg( $list, PushInterfaceDef::CITYWAR_USER_LOGOFF, $msg);
		}
	}
	
	/**
	 * 取战斗相关的时间配置，返回给前端使用
	 */
	public static function getTimeConf()
	{
		$conf = btstore_get()->CITY_WAR_ATTACK;
		
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$rewardStartTime = self::getBattleEndTime() + CityWarConf::GAP_BATTLE_REWARD;
		$rewardEndTime = $rewardStartTime + CityWarConf::REWARD_DURATION;
		
		$arrData = array(
			'signupStart' => $signupStartTime,
			'signupEnd' => $signupEndTime,
			'prepare' => CityWarConf::PREPARE_DURATION,
			'arrAttack' => array(),
			'reward' => array($rewardStartTime, $rewardEndTime),
		);
		
		$attackNum = self::getAttackNum();
		for($i = 0; $i < $attackNum; $i++ )
		{
			$arrData['arrAttack'][$i] = self::getAttackTime($i);
		}
		return $arrData;
	}
	
	public static function getRoundTime()
	{
		Logger::trace('CityWarLogic::getRoundTime Start.');
		
		$now = Util::getTime();
		$serverStartTime = strtotime(GameConf::SERVER_OPEN_YMD . '000000');
		$battleCount = floor(($now - $serverStartTime) / CityWarConf::ROUND_DURATION);
		$roundStartTime = $serverStartTime + $battleCount * CityWarConf::ROUND_DURATION;
		$roundEndTime = $roundStartTime + CityWarConf::ROUND_DURATION;
		Logger::trace('this round start:%s end:%s', $roundStartTime, $roundEndTime);
		
		Logger::trace('CityWarLogic::getRoundTime End.');
		
		return array($roundStartTime, $roundEndTime);
	}
	
	public static function getSignupTime()
	{
		Logger::trace('CityWarLogic::getSignupTime Start.');
		
		list($roundStartTime, $roundEndTime) = self::getRoundTime();
		$signupStartTime = $roundStartTime + CityWarConf::GAP_START_SIGNUP;
		$signupEndTime = $signupStartTime + CityWarConf::SIGNUP_DURATION;
		Logger::trace('signup start:%s end:%s', $signupStartTime, $signupEndTime);
		
		Logger::trace('CityWarLogic::getSignupTime End.');
		
		return array($signupStartTime, $signupEndTime);
	}
	
	/**
	 * 获取上一次报名结束时间
	 * 
	 * 如果当前处在当前轮城池战报名结束之后的阶段，返回此轮报名结束时间
	 * 如果当前处在当前轮城池战报名结束之前的阶段，返回上一轮报名结束时间
	 * @return int
	 */
	public static function getLastSignupEndTime()
	{
		list($signupStartTime, $signupEndTime) = self::getSignupTime();

		if(Util::getTime() >= $signupEndTime)
		{
			Logger::trace('now after signup endtime:%d', $signupEndTime);
			return $signupEndTime;
		}
		
		$signupEndTime -= CityWarConf::ROUND_DURATION;
		Logger::trace('now before signup endtime:%d', $signupEndTime);
		
		return $signupEndTime;
	}
	
	public static function getPrepareTime($id)
	{
		Logger::trace('CityWarLogic::getPrepareTime Start.');
	
		list($attackStartTime, $attackEndTime) = self::getAttackTime($id);
		$prepareEndTime = $attackStartTime;
		$prepareStartTime = $prepareEndTime - CityWarConf::PREPARE_DURATION;
		Logger::trace('prepare start:%s end:%s', $prepareStartTime, $prepareEndTime);
		
		Logger::trace('CityWarLogic::getPrepareTime End.');
	
		return array($prepareStartTime, $prepareEndTime);
	}
	
	public static function getAttackTime($id)
	{
		Logger::trace('CityWarLogic::getAttackTime Start.');
		
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		$attackStartTime = $signupEndTime + CityWarConf::GAP_SIGNUP_BATTLE + $id * CityWarConf::GAP_ATTACK_ATTACK;
		$attackEndTime = $attackStartTime + CityWarConf::ATTACK_DURATION;
		Logger::trace('attack start:%s end:%s', $attackStartTime, $attackEndTime);
		
		Logger::trace('CityWarLogic::getAttackTime End.');
		
		return array($attackStartTime, $attackEndTime);
	}
	
	public static function getBattleEndTime()
	{
		Logger::trace('CityWarLogic::getBattleEndTime Start.');
		
		list($attackStartTime, $attackEndTime) = self::getAttackTime(0);
		$battleEndTime = $attackStartTime + CityWarConf::BATTLE_DURATION;
		Logger::trace('battle end:%s', $battleEndTime);
		
		Logger::trace('CityWarLogic::getBattleEndTime End.');
		
		return $battleEndTime;
	}
	
	private static function getRewardTime()
	{
		Logger::trace('CityWarLogic::getRewardTime Start.');
		
		$battleEndTime = self::getBattleEndTime();
		$rewardStartTime = $battleEndTime + CityWarConf::GAP_BATTLE_REWARD;
		//如果当前时间小于当前轮的发奖开始时间就返回上一轮轮的发奖开始和结束时间
		//否则就返回当前轮的发奖开始和结束时间
		if (Util::getTime() < $rewardStartTime) 
		{
			$rewardStartTime -= CityWarConf::ROUND_DURATION;
		}
		$rewardEndTime = $rewardStartTime + CityWarConf::REWARD_DURATION;
		Logger::trace('reward start:%s end:%s', $rewardStartTime, $rewardEndTime);
		
		Logger::trace('CityWarLogic::getRewardTime End.');
		
		return array($rewardStartTime, $rewardEndTime);
	}
	
	private static function getPreparePeriod()
	{
		Logger::trace('CityWarLogic::getPreparePeriod Start.');
	
		$ret = -1;
		$now = Util::getTime();
		$attackNum = self::getAttackNum();
		for ($i = 0; $i < $attackNum; $i++)
		{
			list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($i);
			if ($now >= $prepareStartTime && $now <= $prepareEndTime)
			{
				$ret = $i;
				break;
			}
		}
	
		Logger::trace('CityWarLogic::getPreparePeriod End.');
	
		return $ret;
	}
	
	private static function getAttackNum()
	{
		Logger::trace('CityWarLogic::getAttackNum Start.');
		
		$attackNum = CityWarConf::ATTACK_OF_BATTLE;
		Logger::trace('attack num:%d', $attackNum);
		
		Logger::trace('CityWarLogic::getAttackNum End.');
		
		return $attackNum;
	}
	
	private static function getCityRewardByType($cityId, $type)
	{
		Logger::trace('CityWarLogic::getCityRewardByType Start.');
		
		$ret = array();
		$param = btstore_get()->CITY_WAR_ATTACK[CityWarDef::REWARD_PARAM][$type];
		$reward = btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_REWARD];
		
		foreach ($reward as $key => $value)
		{
			$ret[$key] = array($value[0], $value[1], intval($value[2] * $param / 100));
		}
		
		Logger::trace('CityWarLogic::getCityRewardByType End.');
		
		return $ret;
	}
	
	private static function checkCityExist($cityId)
	{
		Logger::trace('CityWarLogic::checkCityExist Start.');
		
		if (empty(btstore_get()->CITY_WAR[$cityId]))
		{
			throw new FakeException('City:%d is not exist.', $cityId);
		}
		
		Logger::trace('CityWarLogic::checkCityExist End.');
	}
	
	private static function checkLevelConds($uid)
	{
		Logger::trace('CityWarLogic::checkLevelConds Start.');
		
		//检查用户等级
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$needLevel = btstore_get()->CITY_WAR_ATTACK[CityWarDef::USER_LEVEL];
		if ($userLevel < $needLevel)
		{
			throw new FakeException('user level:%d is not reach:%d.', $userLevel, $needLevel);
		}
		
		//检查军团等级
		$guildLevel = EnGuild::getBuildLevel($uid, GuildDef::GUILD);
		$needLevel = btstore_get()->CITY_WAR_ATTACK[CityWarDef::GUILD_LEVEL];
		if ($guildLevel < $needLevel)
		{
			throw new FakeException('guild level:%d is not reach:%d.', $guildLevel, $needLevel);
		}
		
		Logger::trace('CityWarLogic::checkLevelConds End.');
	}
	
	private static function checkOETime($roundId)
	{
		Logger::trace('CityWarLogic::checkOETime Start.');
		
		//第N场的报名时间为整轮战斗报名结束到第N场战斗准备前
		$now = Util::getTime();
		list($signupStartTime, $signupEndTime) = self::getSignupTime();
		list($prepareStartTime, $prepareEndTime) = self::getPrepareTime($roundId);
		if ($now < $signupEndTime || $now > $prepareStartTime) 
		{
			throw new FakeException('offline enter time is invalid! now:%s', $now);
		}
		
		Logger::trace('CityWarLogic::checkOETime End.');
	}
	
	private static function isMemberRight($uid)
	{
		Logger::trace('CityWarLogic::isMemberRight Start.');
		
		$ret = true;
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			$ret = false;
		}
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE] ))
		{
			$ret = false;
		}
		
		Logger::trace('CityWarLogic::isMemberRight End.');
	
		return $ret;
	}
	
	private static function initCity($cityId)
	{
		Logger::trace('CityWarLogic::initCity Start.');

		$arrField = array(
				CityWarDef::CITY_ID => $cityId,
				CityWarDef::CITY_DEFENCE => 0,
				CityWarDef::DEFENCE_TIME => 0,
				CityWarDef::LAST_GID => 0,
				CityWarDef::CURR_GID => 0,
				CityWarDef::OCCUPY_TIME => 0,
				CityWarDef::SIGNUP_END_TIMER => 0,
				CityWarDef::BATTLE_END_TIMER => 0,
				CityWarDef::VA_CITY_WAR => array(),
				CityWarDef::VA_REWARD => array(),
		);

		Logger::trace('CityWarLogic::initCity End.');
	
		return $arrField;
	}
	
	private static function initAttack($cityId, $guildId)
	{
		Logger::trace('CityWarLogic::initAttack Start.');
	
		$arrField = array(
				CityWarDef::SIGNUP_TIME => Util::getTime(),
				CityWarDef::CITY_ID => $cityId,
				CityWarDef::ATTACK_GID => $guildId,
				CityWarDef::DEFEND_GID => 0,
				CityWarDef::ATTACK_TIMER => 0,
				CityWarDef::ATTACK_REPLAY => 0,
				CityWarDef::ATTACK_RESULT => 0,
				CityWarDef::ATTACK_CONTRI => 0,
		);
	
		CityWarDao::insertAttack($arrField);
	
		Logger::trace('CityWarLogic::initAttack End.');
	}
	
	private static function getUserInfo($uid)
	{
		Logger::trace('CityWarLogic::getUserInfo Start.');
		
		$info = CityWarDao::selectUser($uid);
	
		if(empty($info))
		{
			$info = array(
					CityWarDef::USER_ID => $uid,
					CityWarDef::CUR_CITY => 0,
					CityWarDef::ENTER_TIME => 0,
					CityWarDef::REWARD_TIME => 0,
					CityWarDef::MEND_TIME => 0,
					CityWarDef::RUIN_TIME => 0,
					CityWarDef::VA_CITY_WAR_USER => array(),
			);
		}
		
		Logger::trace('CityWarLogic::getUserInfo End.');
		
		return $info;
	}
	
	private static function getNpcId($cityId)
	{
		return btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_GUARD];
	}
	
	private static function getNpcInfo($npcId)
	{
		if (!isset(btstore_get()->COPYTEAM[$npcId]))
		{
			throw new ConfigException('npc:%d is not exist', $npcId);
		}
		return btstore_get()->COPYTEAM[$npcId];
	}
	
	private static function getNpcLevel($npcId)
	{
		$npcInfo = self::getNpcInfo($npcId);
		if (!isset($npcInfo['level']))
		{
			throw new ConfigException('npc level is not exist');
		}
		return $npcInfo['level'];
	}
	
	private static function getNpcArmy($npcId)
	{
		$npcInfo = self::getNpcInfo($npcId);
		if (!isset($npcInfo['base_id']))
		{
			throw new ConfigException('npc base id is not exist');
		}
		return CopyUtil::getArmyInBase($npcInfo['base_id']);
	}
	
	private static function getBasicGuildInfo($attackGid, $defendGid)
	{
		Logger::trace('CityWarLogic::getBasicGuildInfo Start.');
		
		$arrGuildInfo = array();
		$arrGuildId = array($attackGid);
		if($defendGid > 1)
		{
			$arrGuildId[] = $defendGid;
		}
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		if ($defendGid <= 1) 
		{
			$arrGuildInfo[$defendGid] = array(
					GuildDef::GUILD_ID => $defendGid,
					GuildDef::GUILD_NAME => '',
			);
		}
		
		Logger::trace('CityWarLogic::getBasicGuildInfo Start.');
		
		return $arrGuildInfo;
	}
	
	private static function getBattleInfo($guildId, $id, $cityId, $arrUid, $arrUser)
	{
		Logger::trace('CityWarLogic::getBattleInfo Start.');
		
		switch ($guildId)
		{
			case 0://空
				$battleInfo = array(
						'name' => '',
						'level' => 0,
						'teamId' => 0,
						'members' => array(),
				);
				break;
			case 1://NPC
				$npcId = self::getNpcId($cityId);
				$battleInfo = array(
						'name' => $npcId,
						'level' => self::getNpcLevel($npcId),
						'teamId' => $guildId,
						'members' => array(),
				);
				$npcArmy = self::getNpcArmy($npcId);
				foreach($npcArmy as $index => $armyId)
				{
					$battleInfo['members'][$index] = EnFormation::getMonsterBattleFormation($armyId, BaseLevel::NORMAL);
					$battleInfo['members'][$index]['uid'] = $index + 1;
				}
				break;
			default://军团
				$arrGuildInfo = EnGuild::getArrGuildInfo(array($guildId), array(GuildDef::GUILD_NAME, GuildDef::GUILD_LEVEL));
				$battleInfo = array(
						'name' => $arrGuildInfo[$guildId][GuildDef::GUILD_NAME],
						'level' => $arrGuildInfo[$guildId][GuildDef::GUILD_LEVEL],
						'teamId' => $guildId,
						'members' => array(),
				);
				$cityInfo = self::getRefreshedCityInfo($cityId);
				foreach ($arrUid as $uid)
				{
					$battleInfo['members'][] = EnUser::getUserObj($uid)->getBattleFormation();
				}
				$conf = btstore_get()->CITY_WAR_ATTACK;
				$cityConf = btstore_get()->CITY_WAR[$cityId];
				foreach ($battleInfo['members'] as $key => $value)
				{
					if (!isset($arrUser[$value['uid']][CityWarDef::VA_CITY_WAR_USER])) 
					{
						$arrUser[$value['uid']][CityWarDef::VA_CITY_WAR_USER] = array();
					}
					$info = $arrUser[$value['uid']][CityWarDef::VA_CITY_WAR_USER];
					//加连胜次数
					$add = 0;
					if (!empty($info['info'][$cityId]['win']))
					{
						$add = $info['info'][$cityId]['win']['add'];
					}
					$value['maxWin'] = $conf[CityWarDef::WIN_DEFAULT] + $add;
					//加攻防等级
					$attackLevel = 0;
					$defendLevel = 0;
					if (!empty($info['info'][$cityId]['inspire']))
					{
						$attackLevel = $info['info'][$cityId]['inspire']['attack'];
						$defendLevel = $info['info'][$cityId]['inspire']['defend'];
					}
					$attackAdd = array();
					foreach ($conf[CityWarDef::INSPIRE_ATTACK] as $attrId => $attrValue)
					{
						$attackAdd[$attrId] = $attrValue * $attackLevel;
					}
					$defendAdd = array();
					foreach ($conf[CityWarDef::INSPIRE_DEFEND] as $attrId => $attrValue)
					{
						$defendAdd[$attrId] = $attrValue * $defendLevel;
					}
					$allAdd = Util::arrayAdd2V(array($attackAdd, $defendAdd));
					foreach ($value['arrHero'] as $pos => $heroInfo)
					{
						foreach ($allAdd as $attrId => $attrValue)
						{
							$attrName = PropertyKey::$MAP_CONF[$attrId];
							if (!isset($heroInfo[$attrName]))
							{
								$heroInfo[$attrName] = 0;
							}
							$heroInfo[$attrName] += $attrValue;
							$value['arrHero'][$pos] = $heroInfo;
						}
						//如果为城池的占领者，就设置守城部队的系统调整攻击倍率和系统调整防御倍率
						if ($guildId == $cityInfo[CityWarDef::LAST_GID]) 
						{
							$param = $conf[CityWarDef::DEFENCE_PARAM];
							$default = $cityConf[CityWarDef::DEFENCE_DEFAULT];
							$attrValue = intval(($cityInfo[CityWarDef::CITY_DEFENCE] - $default) * $param / $default);
							$allSub = array(78 => $attrValue, 79 => $attrValue);
							foreach ($allSub as $attrId => $attrValue)
							{
								$attrName = PropertyKey::$MAP_CONF[$attrId];
								if (!isset($heroInfo[$attrName]))
								{
									$heroInfo[$attrName] = 0;
								}
								$heroInfo[$attrName] += $attrValue;
								$value['arrHero'][$pos] = $heroInfo;
							}
						}
					}
					$battleInfo['members'][$key] = $value;
				}
		}
		
		Logger::trace('CityWarLogic::getBattleInfo Start.');
		
		return $battleInfo;
	}
	
	private static function getRefreshedCityInfo($cityId, $checkInit = FALSE)
	{
		$default = btstore_get()->CITY_WAR[$cityId][CityWarDef::DEFENCE_DEFAULT];
		$decrease = btstore_get()->CITY_WAR[$cityId][CityWarDef::DEFENCE_DECREASE];
		$min = btstore_get()->CITY_WAR_ATTACK[CityWarDef::DEFENCE_MIN];
		$min = intval($min * $default / 100);
		list($roundStartTime, $roundEndTime) = self::getRoundTime();
		$battleEndTime = self::getBattleEndTime();
		
		$init = false;
		$cityInfo = CityWarDao::selectCity($cityId);
		if (empty($cityInfo))
		{
			$init = true;
			$cityInfo = self::initCity($cityId);
		}
		if (empty($cityInfo[CityWarDef::LAST_GID])) 
		{
			//没有军团占领时，城防不需要每轮递减，只需根据城防刷新时间是否小于本轮开始时间，判断是否取默认值
			$cityInfo[CityWarDef::CITY_DEFENCE] = $cityInfo[CityWarDef::DEFENCE_TIME] < $roundStartTime ? $default : $cityInfo[CityWarDef::CITY_DEFENCE];
		}
		else 
		{
			//有军团占领时，有以下三种情况：
			//1.defence为0,这时城防刷新时间为0,根据占领时间更新城防;
			//2.defence没有刷新,这时城防刷新时间小于本轮开始时间,根据刷新时间更新城防;
			//3.defence刷新过,这时城防刷新时间大于本轮开始时间,不需要更新城防;
			//更新城防刷新时间时可以用本轮战斗结束时间
			if (empty($cityInfo[CityWarDef::DEFENCE_TIME])) 
			{
				$cityInfo[CityWarDef::CITY_DEFENCE] = $default - $decrease * intval(($battleEndTime - $cityInfo[CityWarDef::OCCUPY_TIME]) / CityWarConf::ROUND_DURATION);
			}
			else if ($cityInfo[CityWarDef::DEFENCE_TIME] < $roundStartTime) 
			{
				$cityInfo[CityWarDef::CITY_DEFENCE] -= $decrease * intval(($battleEndTime - $cityInfo[CityWarDef::DEFENCE_TIME]) / CityWarConf::ROUND_DURATION);
			}		
		}
		$cityInfo[CityWarDef::CITY_DEFENCE] = max($cityInfo[CityWarDef::CITY_DEFENCE], $min);
		$cityInfo[CityWarDef::DEFENCE_TIME] = $battleEndTime;
		
		Logger::trace('defence:%d', $cityInfo[CityWarDef::CITY_DEFENCE]);
		return $checkInit ? array($cityInfo, $init) : $cityInfo;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */