<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteLogic.class.php 241117 2016-05-05 07:31:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompeteLogic.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 07:31:46 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241117 $
 * @brief 
 *  
 **/
 
class WorldCompeteLogic
{
	/**
	 * 获得跨服比武的的基本信息
	 * 
	 * @param int $uid
	 * @throws Exception
	 * @return array
	 */
	public static function getWorldCompeteInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		if (!WorldCompeteConf::$OPEN) 
		{
			$ret = array('ret' => 'off');
			Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
			return $ret;
		}
		
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			$ret = array('ret' => 'no', 'open_time' => WorldCompeteUtil::serverOpenActivityTime());
			Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
			return $ret;
		}
		
		// 获得玩家obj
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['atk_num'] = $worldCompeteInnerUserObj->getAtkNum();
		$ret['suc_num'] = $worldCompeteInnerUserObj->getSucNum();
		$ret['buy_atk_num'] = $worldCompeteInnerUserObj->getBuyAtkNum();
		$ret['refresh_num'] = $worldCompeteInnerUserObj->getRefreshNum();
		$ret['worship_num'] = $worldCompeteInnerUserObj->getWorshipNum();
		$ret['max_honor'] = $worldCompeteInnerUserObj->getMaxHonor();
		$ret['cross_honor'] = $worldCompeteInnerUserObj->getCrossHonor();
		$ret['begin_time'] = WorldCompeteUtil::activityBeginTime();
		$ret['end_time'] = WorldCompeteUtil::activityEndTime();
		$ret['reward_end_time'] = WorldCompeteUtil::rewardEndTime();
		$ret['period_end_time'] = WorldCompeteUtil::periodEndTime();
		$ret['prize'] = $worldCompeteInnerUserObj->getPrize();
		$ret['rival'] = $worldCompeteInnerUserObj->getRival();
		if (empty($ret['rival'])) 
		{
			$ret['rival'] = $worldCompeteInnerUserObj->refreshRival($teamId, $worldCompeteInnerUserObj->getMaxHonor(), true);
			$worldCompeteInnerUserObj->update();
		}
		else 
		{
			$arrServerId = array_unique(Util::arrayExtract($ret['rival'], 'server_id'));
			$arrServerId2Name = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
			foreach ($ret['rival'] as $key => $value)
			{
				$value['server_name'] = $arrServerId2Name[$value['server_id']];
				$worldCompeteCrossRivalObj = WorldCompeteCrossUserObj::getInstance($value['server_id'], $value['pid'], 0, $teamId);
				$value['uname'] = $worldCompeteCrossRivalObj->getUname();
				$value['htid'] = $worldCompeteCrossRivalObj->getHtid();
				$value['level'] = $worldCompeteCrossRivalObj->getLevel();
				$value['vip'] = $worldCompeteCrossRivalObj->getVip();
				$value['fight_force'] = $worldCompeteCrossRivalObj->getFightForce();
				$value['dress'] = $worldCompeteCrossRivalObj->getDress();
				$value['title'] = $worldCompeteCrossRivalObj->getTitle();
				$ret['rival'][$key] = $value;
			}
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 挑战
	 * 
	 * @param int $uid
	 * @param int $rivalServerId
	 * @param int $rivalPid
	 * @param int $crazy
	 * @param int $skip
	 */
	public static function attack($uid, $rivalServerId, $rivalPid, $crazy, $skip)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldCompeteUtil::inActivity()) 
		{
			throw new FakeException('not in activity, curr[%s], begin[%s], end[%s]', strftime('%Y%m%d %H:%M:%S', Util::getTime()), strftime('%Y%m%d %H:%M:%S', WorldCompeteUtil::activityBeginTime()), strftime('%Y%m%d %H:%M:%S', WorldCompeteUtil::activityEndTime()));
		}
		
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查玩家攻打的目标不是自己
		if ($serverId == $rivalServerId && $pid == $rivalPid)
		{
			throw new FakeException('can not attack self');
		}
		
		// 检查玩家和攻打的目标是否都在同一个分组，只有处在不同的服才需要判断
		if ($serverId != $rivalServerId)
		{
			$rivalTeamId = WorldCompeteUtil::getTeamIdByServerId($rivalServerId);
			if ($rivalTeamId != $teamId)
			{
				throw new FakeException('not in same team, my team[%d], rival team[%d]', $teamId, $rivalTeamId);
			}
		}
		
		// 获得玩家obj
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		
		// 判断挑战次数是否足够
		$atkDefault = WorldCompeteUtil::getAtkDefault();
		$atkNum = $worldCompeteInnerUserObj->getAtkNum();
		$buyAtkNum = $worldCompeteInnerUserObj->getBuyAtkNum();
		if ($atkNum >= $atkDefault + $buyAtkNum)
		{
			throw new FakeException('no enough atk num');
		}
		
		// 检查对手是否在用户的对手列表里
		if (!$worldCompeteInnerUserObj->hasRival($rivalServerId, $rivalPid)) 
		{
			throw new FakeException('not has rival serverId[%d], pid[%d]', $rivalServerId, $rivalPid);
		}
				
		// 获得玩家的战斗数据和对手的战斗数据，战斗
		$userBattleFormation = WorldCompeteUtil::getUserBattleFormation($uid, $crazy);
		$rivalBattleFormation = WorldCompeteUtil::getOtherUserBattleFormation($rivalServerId, $rivalPid, $teamId);
		$atkRet = EnBattle::doHero($userBattleFormation, $rivalBattleFormation, EnBattle::setFirstAtk(0, true), NULL, NULL, array('isMotifyId' => TRUE));
		Logger::trace('ATTACK : user fmt[%s], rival fmt[%s], atk result[%s]', $userBattleFormation, $rivalBattleFormation, $atkRet);
		
		// 获得跨服荣誉
		$num = $crazy ? WorldCompeteUtil::getCrazyCost() : 1;
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		$worldCompeteCrossUserObj = WorldCompeteCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, true);
		$worldCompeteCrossRivalObj = WorldCompeteCrossUserObj::getInstance($rivalServerId, $rivalPid, 0, $teamId);
		$worldCompeteCrossUserObj->refreshUserInfo($uid);
		$honor = WorldCompeteUtil::getAtkHonor($isSuc, $worldCompeteCrossRivalObj->getFightForce());
		$worldCompeteInnerUserObj->afterAttack($rivalServerId, $rivalPid, $num, $honor, $isSuc);
		$ret = array();
		if ($worldCompeteInnerUserObj->isDefeatAll()) 
		{
			$ret['rival'] = $worldCompeteInnerUserObj->refreshRival($teamId, $worldCompeteInnerUserObj->getMaxHonor(), true);
		}
		$worldCompeteInnerUserObj->update();
		// 初始化并更新cross_user用户数据
		$worldCompeteCrossUserObj->setMaxHonor($worldCompeteInnerUserObj->getMaxHonor());
		$worldCompeteCrossUserObj->update();
						
		// 返回值
		$ret['ret'] = 'ok';
		$ret['appraisal'] = $atkRet['server']['appraisal'];
		$ret['fight_force'] = $worldCompeteCrossRivalObj->getFightForce();
		if (!$skip) 
		{
			$ret['fightRet'] = $atkRet['client'];
		}
		//加入每日任务
		EnActive::addTask(ActiveDef::WORLD_COMPETE);
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 购买挑战次数
	 * 
	 * @param int $uid
	 * @param int $num
	 * @throws FakeException
	 * @return string
	 */
	public static function buyAtkNum($uid, $num)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		if ($num <= 0)
		{
			throw new FakeException('invalid num:%d', $num);
		}
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldCompeteUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查购买次数是否达到上限
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldCompeteInnerUserObj->getBuyAtkNum() + $num > WorldCompeteUtil::getBuyLimit()) 
		{
			throw new FakeException('buy num exceed, curr buy num[%d], buy limit[%d]', $worldCompeteInnerUserObj->getBuyAtkNum(), WorldCompeteUtil::getBuyLimit());
		}
		
		// 扣金币,update
		$cost = 0;
		for ($i = 1; $i <= $num; $i++)
		{
			$cost += WorldCompeteUtil::getBuyCost($worldCompeteInnerUserObj->getBuyAtkNum() + $i);
		}
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_BUY_NUM_COST)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
		
		// 加次数
		$worldCompeteInnerUserObj->addBuyAtkNum($num);
		$worldCompeteInnerUserObj->update();
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 刷新对手信息
	 *
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function refreshRival($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
	
		// 检查是否在活动有效期内
		if (!WorldCompeteUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
	
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
	
		// 先看看有没有免费次数，有免费次数则先使用免费次数刷新，没免费次数则使用金币刷新
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldCompeteInnerUserObj->getRefreshNum() >= WorldCompeteUtil::getRefreshDefault())
		{
			$cost = WorldCompeteUtil::getRefreshCost();
			$userObj = EnUser::getUserObj($uid);
			if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_REFRESH_RIVAL_COST))
			{
				throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
			}
			$userObj->update();
		}
		
		//刷新对手
		$ret = $worldCompeteInnerUserObj->refreshRival($teamId, $worldCompeteInnerUserObj->getMaxHonor());
		$worldCompeteInnerUserObj->update();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 领取每日胜场奖励
	 * 
	 * @param int $uid
	 * @param int $num
	 * @throws FakeException
	 * @return string
	 */
	public static function getPrize($uid, $num)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldCompeteUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查是否存在胜场次数奖励
		$sucPrize = WorldCompeteUtil::getSucPrize($num);
		
		//检查用户胜场次数是否足够
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldCompeteInnerUserObj->getSucNum() < $num)
		{
			throw new FakeException('not enough suc num, curr suc num[%d], need num[%d]', $worldCompeteInnerUserObj->getSucNum(), $num);
		}
		
		//检查用户是否已经领取胜场奖励
		if ($worldCompeteInnerUserObj->hasPrize($num)) 
		{
			throw new FakeException('user has get suc num[%d] prize', $num);
		}
		
		// 加次数
		$worldCompeteInnerUserObj->addPrize($num);
		
		// max_honor自己加上，cross_honor走奖励中心
		foreach ($sucPrize as $prize)
		{
			if (RewardConfType::CROSS_HONOR == $prize[0]) 
			{
				$worldCompeteInnerUserObj->addMaxHonorOnly($prize[2]);
			}
		}
		
		//给用户发胜场奖励
		$rewardInfo = RewardUtil::reward3DArr($uid, $sucPrize, StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_DAY_SUC_PRIZE);
		unset($rewardInfo['rewardInfo']);
		$worldCompeteInnerUserObj->update();
		RewardUtil::updateReward($uid, $rewardInfo);
		
		//更新跨服数据，用户排名
		$worldCompeteCrossUserObj = WorldCompeteCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, true);
		$worldCompeteCrossUserObj->setMaxHonor($worldCompeteInnerUserObj->getMaxHonor());
		$worldCompeteCrossUserObj->update();
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 膜拜
	 *
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function worship($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
	
		// 检查是否在活动有效期内
		if (!WorldCompeteUtil::inReward())
		{
			throw new FakeException('not in reward');
		}
	
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
	
		//检查用户膜拜次数是否达到上限
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldCompeteInnerUserObj->getWorshipNum() >= WorldCompeteUtil::getWorshipLimit())
		{
			throw new FakeException('worship num exceed, curr worship num[%d], limit num[%d]', $worldCompeteInnerUserObj->getWorshipNum(), WorldCompeteUtil::getWorshipLimit());
		}
		
		//检查用户膜拜条件是否达到
		list($needLevel, $needVip) = WorldCompeteUtil::getWorshipCon($worldCompeteInnerUserObj->getWorshipNum() + 1);
		$user = EnUser::getUserObj($uid);
		if ($user->getLevel() < $needLevel && $user->getVip() < $needVip) 
		{
			throw new FakeException('not reach worship condition, user level[%d] vip[%d], need level[%d] vip[%d]', $user->getLevel(), $user->getVip(), $needLevel, $needVip);
		}
	
		// 加次数
		$worldCompeteInnerUserObj->addWorshipNum(1);
	
		//给用户发胜场奖励
		$worshipReward = WorldCompeteUtil::getWorshipReward($worldCompeteInnerUserObj->getWorshipNum());
		$rewardInfo = RewardUtil::reward3DArr($uid, $worshipReward, StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_WORSHIP_REWARD);
		unset($rewardInfo['rewardInfo']);
		$worldCompeteInnerUserObj->update();
		RewardUtil::updateReward($uid, $rewardInfo);
	
		$ret = 'ok';
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
		
		$ret = WorldCompeteUtil::getOtherUserBattleData($aServerId, $aPid);
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 排行榜
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getRankList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 自己的排名初始化
		$myInnerRank = 0;
		$myCrossRank = 0;
		
		// 获得本服的排行
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteInnerUserField::FIELD_MAX_HONOR, '>', 0),
		);
		$arrField = array
		(
				WorldCompeteInnerUserField::FIELD_PID,
				WorldCompeteInnerUserField::FIELD_SERVER_ID,
				WorldCompeteInnerUserField::FIELD_UID,
				WorldCompeteInnerUserField::FIELD_MAX_HONOR,
		);
		$innerRankList = WorldCompeteDao::getInnerRankList($arrCond, $arrField, WorldCompeteConf::INNER_RANK_LIST_COUNT);
		$arrUid = Util::arrayExtract($innerRankList, WorldCompeteInnerUserField::FIELD_UID);
		$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'uname', 'htid', 'level', 'vip', 'fight_force', 'dress'));
		$innerInfo = array();
		$rank = 0;
		foreach ($innerRankList as $index => $aInfo)
		{
			$aUid = $aInfo[WorldCompeteInnerUserField::FIELD_UID];
			$aMaxHonor = $aInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR];
			
			if (!isset($arrUserInfo[$aUid])) 
			{
				Logger::warning('inner rank list, no basic info or user[%d]', $aUid);
				continue;
			}
			
			$innerInfo[] = array
			(
					'uid' => $aUid,
					'uname' => $arrUserInfo[$aUid]['uname'],
					'htid' => $arrUserInfo[$aUid]['htid'],
					'level' => $arrUserInfo[$aUid]['level'],
					'vip' => $arrUserInfo[$aUid]['vip'],
					'fight_force' => $arrUserInfo[$aUid]['fight_force'],
					'dress' => $arrUserInfo[$aUid]['dress'],
					'max_honor' => $aMaxHonor,
					'rank' => ++$rank,
			);
			
			if ($uid == $aUid) 
			{
				$myInnerRank = $rank;
			}
		}
		Logger::trace('GET_RANK_LIST : INNER : rank list[%s]', $innerInfo);
		
		// 获取自己的服内排名
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($myInnerRank == 0 && $worldCompeteInnerUserObj->getMaxHonor() > 0) 
		{
			$myInnerRank = $worldCompeteInnerUserObj->getRank();
		}
		
		// 获得跨服的排行
		$arrServerId = WorldCompeteUtil::getArrServerIdByTeamId($teamId);
		$isReward = Util::getTime() > WorldCompeteUtil::activityEndTime() ? true : false;
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '>', 0),
		);
		$arrField = WorldCompeteCrossUserField::$RANK_FIELDS;
		$crossRankList = WorldCompeteDao::getCrossRankList($teamId, $arrCond, $arrField, WorldCompeteConf::CROSS_RANK_LIST_COUNT, $isReward);
		$arrServerName = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		Logger::trace('GET_RANK_LIST : CROSS : server name info[%s]', $arrServerName);
		
		$crossInfo = array();
		$rank = 0;
		foreach ($crossRankList as $index => $aInfo)
		{
			$aPid = $aInfo[WorldCompeteCrossUserField::FIELD_PID];
			unset($aInfo[WorldCompeteCrossUserField::FIELD_TEAM_ID]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_PID]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_UPDATE_TIME]);
			$aInfo['dress'] = $aInfo[WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS];
			unset($aInfo[WorldCompeteCrossUserField::FIELD_VA_EXTRA]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION]);
			$aInfo['server_name'] = $arrServerName[$aInfo[WorldCompeteCrossUserField::FIELD_SERVER_ID]];
			$aInfo['rank'] = ++$rank;
			$crossInfo[] = $aInfo;
			
			if ($serverId == $aInfo['server_id'] && $pid == $aPid) 
			{
				$myCrossRank = $rank;
			}
		}
		Logger::trace('GET_RANK_LIST : CROSS : rank list[%s]', $crossInfo);
		
		//获得自己的跨服排名
		$worldCompeteCrossUserObj = WorldCompeteCrossUserObj::getInstance($serverId, $pid, $uid, $teamId);
		if ($myCrossRank == 0 && $worldCompeteCrossUserObj->getMaxHonor() > 0) 
		{				
			$myCrossRank = $worldCompeteCrossUserObj->getRank();
		}
		
		$ret = array
		(
				'inner' => $innerInfo,
				'cross'	=> $crossInfo,
				'my_inner_rank' => $myInnerRank,
				'my_cross_rank' => $myCrossRank,
		);
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	public static function getChampion($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
	
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
	
		// 检查是否在一个分组内
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
	
		// 获得跨服的排行
		$arrServerId = WorldCompeteUtil::getArrServerIdByTeamId($teamId);
		$isReward = Util::getTime() > WorldCompeteUtil::activityEndTime() ? true : false;
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '>', 0),
		);
		$arrField = WorldCompeteCrossUserField::$RANK_FIELDS;
		$crossRankList = WorldCompeteDao::getCrossRankList($teamId, $arrCond, $arrField, 1, $isReward);
		$arrServerName = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		Logger::trace('GET_CHAMPION : CROSS : server name info[%s]', $arrServerName);
	
		$crossInfo = array();
		$rank = 0;
		foreach ($crossRankList as $index => $aInfo)
		{
			$aPid = $aInfo[WorldCompeteCrossUserField::FIELD_PID];
			unset($aInfo[WorldCompeteCrossUserField::FIELD_TEAM_ID]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_PID]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_UPDATE_TIME]);
			$aInfo['dress'] = $aInfo[WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS];
			unset($aInfo[WorldCompeteCrossUserField::FIELD_VA_EXTRA]);
			unset($aInfo[WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION]);
			$aInfo['server_name'] = $arrServerName[$aInfo[WorldCompeteCrossUserField::FIELD_SERVER_ID]];
			$aInfo['rank'] = ++$rank;
			$crossInfo[] = $aInfo;
		}
		Logger::trace('GET_CHAMPION : CROSS : rank list[%s]', $crossInfo);
	
		$ret = array
		(
				'cross'	=> $crossInfo,
		);
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 获得商店信息
	 *
	 * @param int $uid
	 * @return array
	 */
	public static function getShopInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		$shop = new WorldCompeteShop($uid);	
		$ret = $shop->getInfo();

		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 购买某个商品
	 *
	 * @param int $uid
	 * @param int $goodsId
	 * @param int $num
	 * @throws FakeException
	 * @return array
	 */
	public static function buyGoods($uid, $goodsId, $num)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId[%d] num[%d]', $goodsId, $num);
		}
	
		$shop = new WorldCompeteShop($uid);
	
		$ret = $shop->exchange($goodsId, $num);
		$shop->update();
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
}

class WorldCompeteScriptLogic
{
	/**
	 * 同属配置的分组数据，将没有在配置中的服务器自动分组
	 * 
	 * @param boolean $commit
	 * @throws InterException
	 */
	public static function syncAllTeamFromPlat2Cross($commit = TRUE, $next = TRUE)
	{
		// 是否在活动期间
		if ($next && WorldCompeteUtil::inActivity()) 
		{
			Logger::warning('SYNC_ALL_TEAM : in activity, can not sync.');
			return;
		}
		
		// 得到配置的分组数据和所有服务器信息
		$beginTime = Util::getTime();
		if ($next) 
		{
			$beginTime = WorldCompeteUtil::periodEndTime();//也就是下个周期的开始时间
		}
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo = WorldCompeteUtil::getAllTeamInfo($next);
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
		
		// 将跨服库的所有分组数据都置无效（update_time置为0，防止多次同步残余的数据）
		// TODO 先注释掉，加上缓存以后不能这样update
		/*$arrField = array
		(
				WorldCompeteCrossTeamField::FIELD_UPDATE_TIME => 0,
		);
		$arrCond = array
		(
				array(WorldCompeteCrossTeamField::FIELD_UPDATE_TIME, '>', 0),
				array(WorldCompeteCrossTeamField::FIELD_SERVER_ID, '>', 0),
		);
		if ($commit) 
		{
			WorldCompeteDao::updateTeamInfo($arrCond, $arrField);
		}*/
		
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
		$needOpenDuration = intval(btstore_get()->WORLD_COMPETE_RULE['need_open_days']);
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $next ? WorldCompeteUtil::periodEndTime() : WorldCompeteUtil::activityBeginTime();
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
				$minCount = defined('PlatformConfig::WORLD_COMPETE_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_COMPETE_TEAM_MIN_COUNT : 5;
				$maxCount = defined('PlatformConfig::WORLD_COMPETE_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_COMPETE_TEAM_MAX_COUNT : 7;
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
					if ($commit) 
					{
						$arrField = array
						(
								WorldCompeteCrossTeamField::FIELD_TEAM_ID => $aTeamId,
								WorldCompeteCrossTeamField::FIELD_SERVER_ID => $aServerId,
								WorldCompeteCrossTeamField::FIELD_UPDATE_TIME => $beginTime + 1,
						);
						WorldCompeteDao::insertTeamInfo($arrField);
					}
					Logger::info('SYNC_ALL_TEAM : sync teamdId[%d] server[%d] success.', $aTeamId, $aServerId);
				}
			}
		}
		Logger::info('SYNC_ALL_TEAM : sync team info from plat to cross done');
	}
	
	/**
	 * 检测分组是否正确，如果没有执行完整，可以接着继续分组
	 * 已经产生的分组不会被改变
	 * 
	 * @param boolean $commit	是否将最终的分组提交到db
	 * @param boolean $next		检测的是下个活动周期，还是当前活动周期的分组数据，默认是下个周期的数据
	 */
	public static function checkTeamChange($commit = TRUE, $next = TRUE)
	{
		// 得到配置的分组数据和所有服务器信息
		$beginTime = WorldCompeteUtil::activityBeginTime();
		if ($next)
		{
			$beginTime = WorldCompeteUtil::periodEndTime();
		}
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo = WorldCompeteUtil::getAllTeamInfo($next);
		ksort($arrMyTeamInfo);
		$allServerInfo = ServerInfoManager::getInstance()->getAllServerInfo();
		ksort($allServerInfo);
		Logger::info('CHECK_TEAM_CHANGE : all config team info[%s]', $arrCfgTeamInfo);
		Logger::info('CHECK_TEAM_CHANGE : all my team info[%s]', $arrMyTeamInfo);
		Logger::info('CHECK_TEAM_CHANGE : all server info[%s]', $allServerInfo);
		
		// 找到配置的最大分组teamId
		$orginMaxTeamId = 0;
		foreach ($arrCfgTeamInfo as $aTeamId => $aInfo)
		{
			if ($aTeamId > $orginMaxTeamId)
			{
				$orginMaxTeamId = $aTeamId;
			}
		}
		Logger::info('CHECK_TEAM_CHANGE : orgin max teamId[%d]', $orginMaxTeamId);
		
		// 合并已经生成的分组信息
		if (!empty($arrMyTeamInfo)) 
		{
			foreach ($arrMyTeamInfo as $aMyTeamId => $arrMyServerId)
			{
				if (isset($arrCfgTeamInfo[$aMyTeamId])) 
				{
					if ($arrCfgTeamInfo[$aMyTeamId] == $arrMyServerId) 
					{
						Logger::info('CHECK_TEAM_CHANGE : teamId[%d], servers[%s] both in my info and cfg info, same, ignore', $aMyTeamId, $arrMyServerId);
					}
					else 
					{
						$orginServer = $arrCfgTeamInfo[$aMyTeamId];
						foreach ($arrMyServerId as $aServerId)
						{
							if (!in_array($aServerId, $arrCfgTeamInfo[$aMyTeamId])) 
							{
								$arrCfgTeamInfo[$aMyTeamId][] = $aServerId;
							}
						}
						Logger::info('CHECK_TEAM_CHANGE : teamId[%d], servers[%s] in my info, servers[%s] in cfg info, diff, merge, result[%s]', $aMyTeamId, $arrMyServerId, $orginServer, $arrCfgTeamInfo[$aMyTeamId]);
					}
				}
				else 
				{
					$arrCfgTeamInfo[$aMyTeamId] = $arrMyServerId;
					Logger::info('CHECK_TEAM_CHANGE : teamId[%d] servers[%s] in my info, but not in cfg info, add', $aMyTeamId, $arrMyServerId);
				}
			}
		}
		
		// 找到当前的最大分组teamId
		$curMaxTeamId = 0;
		foreach ($arrCfgTeamInfo as $aTeamId => $aInfo)
		{
			if ($aTeamId > $curMaxTeamId)
			{
				$curMaxTeamId = $aTeamId;
			}
		}
		Logger::info('CHECK_TEAM_CHANGE : cur max teamId[%d]', $curMaxTeamId);
		
		// 处理合服的情况，得到所有同一个db的服务器信息，db -> array(serverId...)
		$arrDb2Info = array();
		foreach ($allServerInfo as $aServerId => $aInfo)
		{
			if (!isset($arrDb2Info[$aInfo['db_name']]))
			{
				$arrDb2Info[$aInfo['db_name']] = array();
			}
			$arrDb2Info[$aInfo['db_name']][] = $aServerId;
		}
		Logger::info('CHECK_TEAM_CHANGE : db 2 info of all server[%s]', $arrDb2Info);
		
		// 得到需要重新分组的服务器
		$tmpAllServerInfo = $allServerInfo;
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				unset($tmpAllServerInfo[$aServerId]);
			}
		}
		Logger::info('CHECK_TEAM_CHANGE : all new server info[%s]', $tmpAllServerInfo);
		
		// 去掉开服日期不符合要求的
		$needOpenDuration = intval(btstore_get()->WORLD_COMPETE_RULE['need_open_days']);
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $next ? WorldCompeteUtil::periodEndTime() : WorldCompeteUtil::activityBeginTime();
			$betweenDays = intval((strtotime(date("Y-m-d", $referTime)) - strtotime(date("Y-m-d", $aOpenTime))) / SECONDS_OF_DAY);
			if ($betweenDays < $needOpenDuration)
			{
				unset($tmpAllServerInfo[$aServerId]);
				Logger::info('CHECK_TEAM_CHANGE : server id[%d] skip, open time[%s], refer time[%s], need open days[%d].', $aServerId, date("Y-m-d", $aOpenTime), date("Y-m-d", $referTime), $needOpenDuration);
			}
		}
		Logger::info('CHECK_TEAM_CHANGE : all new server info after open days filter[%s]', $tmpAllServerInfo);
		
		// 将剩余的服务器自动分组，合服的要在同一个组里
		if (!empty($tmpAllServerInfo))
		{
			// 随机的上界和下界
			$minCount = defined('PlatformConfig::WORLD_COMPETE_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_COMPETE_TEAM_MIN_COUNT : 5;
			$maxCount = defined('PlatformConfig::WORLD_COMPETE_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_COMPETE_TEAM_MAX_COUNT : 7;
			Logger::info('CHECK_TEAM_CHANGE : min server count[%d], max server count[%d]', $minCount, $maxCount);
			
			// 如果最后一个分组不是原始的配置的最后一个分组，检查一下这个分组的大小是否正确，所有合服的服算一个
			$fromCurMax = FALSE;
			$curMaxTeamServerCount = 0;
			if ($curMaxTeamId > $orginMaxTeamId) // 如果当前最大的分组id不是配置的最大分组id才需要处理，否侧直接从下一个开始吧
			{
				$arrExclude = array();
				foreach ($arrCfgTeamInfo[$curMaxTeamId] as $aServerId)
				{
					if (in_array($aServerId, $arrExclude)) 
					{
						continue;
					}
					
					$aDb = $allServerInfo[$aServerId]['db_name'];
					$allMergeServer = $arrDb2Info[$aDb];
					foreach ($allMergeServer as $aMergeServerId)
					{
						if ($aServerId == $aMergeServerId) 
						{
							continue;
						}
						
						$arrExclude[] = $aMergeServerId;
						
						if (!isset($tmpAllServerInfo[$aMergeServerId])) 
						{
							continue;
						}
						
						$arrCfgTeamInfo[$curMaxTeamId][] = $aMergeServerId;
						unset($tmpAllServerInfo[$aMergeServerId]);
					}
					++$curMaxTeamServerCount;
				}
				
				if ($curMaxTeamServerCount < $minCount) 
				{
					$fromCurMax = TRUE;
				}
			}
			
			// 是否从最后一个分组开始
			if ($fromCurMax) 
			{
				$curServerCount = $curMaxTeamServerCount;
				$curTeamNeedCount = mt_rand($minCount, $maxCount);
				$curTeamId = $curMaxTeamId;
				Logger::info('CHECK_TEAM_CHANGE : use cur max team[%d], new team server need count[%d], cur server count[%d]', $curTeamId, $curTeamNeedCount, $curServerCount);
			}
			else 
			{
				$curServerCount = 0;
				$curTeamNeedCount = mt_rand($minCount, $maxCount);
				$curTeamId = ++$curMaxTeamId;
				Logger::info('CHECK_TEAM_CHANGE : generate new team[%d], new team server need count[%d], cur server count[%d]', $curTeamId, $curTeamNeedCount, $curServerCount);
			}
			
			
			// 开始给当前组添加server
			$arrExclude = array();
			foreach ($tmpAllServerInfo as $aServerId => $aInfo)
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
					Logger::info('CHECK_TEAM_CHANGE : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
				}
		
				$arrCfgTeamInfo[$curTeamId][] = $aServerId;
				Logger::info('CHECK_TEAM_CHANGE : generate new team[%d], add a normal server[%d]', $curTeamId, $aServerId);
				foreach ($arrDb2Info[$aInfo['db_name']] as $aMergeServerId)
				{
					if ($aMergeServerId == $aServerId)
					{
						continue;
					}
					if (!isset($tmpAllServerInfo[$aMergeServerId])) 
					{
						if (in_array($aMergeServerId, $arrCfgTeamInfo[$curTeamId])) 
						{
							continue;
						}
						else 
						{
							Logger::warning('CHECK_TEAM_CHANGE : merge server[%d], not in tmp[%s] and not in curr info[%s]', $aMergeServerId, $tmpAllServerInfo, $arrCfgTeamInfo[$curTeamId]);
							return;
						}
					}
					$arrCfgTeamInfo[$curTeamId][] = $aMergeServerId;
					$arrExclude[] = $aMergeServerId;
					Logger::info('CHECK_TEAM_CHANGE : generate new team[%d], add a merge server[%d]', $curTeamId, $aMergeServerId);
				}
				++$curServerCount;
			}
		
			// 处理当最后一个分组个数没有达到最低个数的情况，就直接塞到最后一组吧
			if ($curServerCount < $minCount)
			{
				if (isset($arrCfgTeamInfo[$curTeamId - 1]))
				{
					$arrCfgTeamInfo[$curTeamId - 1] = array_merge($arrCfgTeamInfo[$curTeamId - 1], $arrCfgTeamInfo[$curTeamId]);
					unset($arrCfgTeamInfo[$curTeamId]);
					Logger::info('CHECK_TEAM_CHANGE : cur team[%d] count[%d] less than min[%d], add to last', $curTeamId, $curServerCount, $minCount);
				}
			}
		}
		
		ksort($arrCfgTeamInfo);
		Logger::info('CHECK_TEAM_CHANGE : final team info[%s]', $arrCfgTeamInfo);
		
		// 更新跨服库分组信息
		foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
		{
			foreach ($arrServerId as $aServerId)
			{
				if (!isset($allServerInfo[$aServerId]))
				{
					Logger::fatal('CHECK_TEAM_CHANGE : no server info of teamId[%d], serverId[%d], skip.', $aTeamId, $aServerId);
				}
				else
				{
					if ($commit)
					{
						$arrField = array
						(
								WorldCompeteCrossTeamField::FIELD_TEAM_ID => $aTeamId,
								WorldCompeteCrossTeamField::FIELD_SERVER_ID => $aServerId,
								WorldCompeteCrossTeamField::FIELD_UPDATE_TIME => $beginTime + 1,
						);
						WorldCompeteDao::insertTeamInfo($arrField);
					}
					Logger::info('CHECK_TEAM_CHANGE : sync teamdId[%d] server[%d] success.', $aTeamId, $aServerId);
				}
			}
		}
		Logger::info('CHECK_TEAM_CHANGE : sync team info from plat to cross done');
	}
	
	/**
	 * 把某个服上的最高积分数据同步到跨服库上，用于发排名奖
	 * ！！坑：假设一个服的活跃玩家在100000以内！！
	 * 
	 * @param int $serverId
	 * @param int $belongTeamId
	 * @param int $commit
	 */
	public static function syncInner2Cross($serverId, $belongTeamId, $commit = TRUE)
	{
		// 获得所属组id
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		if ($teamId != $belongTeamId) 
		{
			throw new InterException('SYNC_INNER_2_CROSS : invalid sync from inner 2 cross, belong team[%d], plat team[%d]', $belongTeamId, $teamId);
		}
		
		// 先从跨服库取这个服的最高积分数据
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_TEAM_ID, '=', $teamId),
				array(WorldCompeteCrossUserField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '>', 0),
				array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
		);
		$arrField = array
		(
				WorldCompeteCrossUserField::FIELD_PID,
				WorldCompeteCrossUserField::FIELD_MAX_HONOR,
		);
		$arrCrossInfo = WorldCompeteDao::getCrossRankList($teamId, $arrCond, $arrField, 100000);
		$arrCrossInfo = Util::arrayIndex($arrCrossInfo, WorldCompeteCrossUserField::FIELD_PID);
		
		// 从这个服上拉取最高积分数据
		$serverDb = ServerInfoManager::getInstance()->getDbNameByServerId($serverId);		
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteInnerUserField::FIELD_MAX_HONOR, '>', 0),
				array(WorldCompeteInnerUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
		);
		$arrField = array
		(
				WorldCompeteInnerUserField::FIELD_PID,
				WorldCompeteInnerUserField::FIELD_MAX_HONOR,
		);
		$arrInnerInfo = WorldCompeteDao::getInnerRankList($arrCond, $arrField, 100000, $serverDb);
		$arrInnerInfo = Util::arrayIndex($arrInnerInfo, WorldCompeteInnerUserField::FIELD_PID);
		
		// 同步数据
		foreach ($arrInnerInfo as $aPid => $aInnerInfo)
		{
			// 本服有，跨服库没有
			if (!isset($arrCrossInfo[$aPid])) 
			{
				Logger::warning('SYNC_INNER_2_CROSS : no max honor info of teamId[%d] serverId[%d] pid[%d] in cross', $teamId, $serverId, $aPid);
				if ($commit) 
				{
					$arrField = array
					(
							WorldCompeteCrossUserField::FIELD_TEAM_ID => $teamId,
							WorldCompeteCrossUserField::FIELD_PID => $aPid,
							WorldCompeteCrossUserField::FIELD_SERVER_ID => $serverId,
							WorldCompeteCrossUserField::FIELD_MAX_HONOR => $aInnerInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR],
							WorldCompeteCrossUserField::FIELD_UPDATE_TIME => Util::getTime(),
					);
					WorldCompeteDao::insertCrossUser($teamId, $arrField);
				}
				unset($arrCrossInfo[$aPid]);
			}
			else if ($aInnerInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR] != $arrCrossInfo[$aPid][WorldCompeteCrossUserField::FIELD_MAX_HONOR]) 
			{
				Logger::warning('SYNC_INNER_2_CROSS : max honor not equal of teamId[%d] serverId[%d] pid[%d], inner[%d] cross[%d], update to inner', $teamId, $serverId, $aPid, $aInnerInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR], $arrCrossInfo[$aPid][WorldCompeteCrossUserField::FIELD_MAX_HONOR]);
				if ($commit) 
				{
					$arrCond = array
					(
							array(WorldCompeteCrossUserField::FIELD_TEAM_ID, '=', $teamId),
							array(WorldCompeteCrossUserField::FIELD_PID, '=', $aPid),
							array(WorldCompeteCrossUserField::FIELD_SERVER_ID, '=', $serverId),
					);
					$arrField = array
					(
							WorldCompeteCrossUserField::FIELD_MAX_HONOR => $aInnerInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR],
							WorldCompeteCrossUserField::FIELD_UPDATE_TIME => Util::getTime(),
					);
					WorldCompeteDao::updateCrossUser($teamId, $arrCond, $arrField);
				}
				unset($arrCrossInfo[$aPid]);
			}
			else 
			{
				Logger::trace('SYNC_INNER_2_CROSS : max honor equal of teamId[%d] serverId[%d] pid[%d], inner[%d] cross[%d], no need sync', $teamId, $serverId, $aPid, $aInnerInfo[WorldCompeteInnerUserField::FIELD_MAX_HONOR], $arrCrossInfo[$aPid][WorldCompeteCrossUserField::FIELD_MAX_HONOR]);
				unset($arrCrossInfo[$aPid]);
			}
		}
		
		// 判断是否有在跨服库，却不在服内的数据
		if (!empty($arrCrossInfo)) 
		{
			Logger::warning('SYNC_INNER_2_CROSS : !!!!!!!!!have cross info, but no inner info, teamId[%d], cross info[%s]', $teamId, $arrCrossInfo);
		}
	}
	
	/**
	 * 发奖
	 * 
	 * @param array $arrSpec	指定需要发奖的玩家，格式：array(teamId => array(serverId => array(pid,...)))
	 * @param boolean $commit	是否提交
	 * @param boolean $sync 	是否将最大积分数据从服内同步到跨服库
	 */
	public static function reward($arrSpec = array(), $commit = TRUE, $sync = TRUE)
	{
		Logger::info('WORLD_COMPETE_RANK_REWARD : ****** [Begin] run reward at time[%s] commit[%s] spec[%s] sync[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE", $arrSpec, $sync ? "TRUE" : "FALSE");
		
		if (WorldCompeteUtil::inActivity()) 
		{
			Logger::warning('WORLD_COMPETE_RANK_REWARD : in activity, can not reward.');
			return;
		}
		
		$allTeamId = WorldCompeteUtil::getAllTeam();
		foreach ($allTeamId as $aTeamId)
		{
			try
			{
				Logger::info('WORLD_COMPETE_RANK_REWARD : team[%d] reward begin.', $aTeamId);
				
				// 过滤掉不需要处理的组
				if (!empty($arrSpec) && !isset($arrSpec[$aTeamId])) 
				{
					Logger::trace('WORLD_COMPETE_RANK_REWARD : no need run reward for teamId[%d]', $aTeamId);
					continue;
				}
				
				// 当前分组下的所有服
				$arrServerId = WorldCompeteUtil::getArrServerIdByTeamId($aTeamId);
				
				// 从每个服上同步最大积分数据到跨服库
				if ($sync) 
				{
					foreach ($arrServerId as $aServerId)
					{
						self::syncInner2Cross($aServerId, $aTeamId);
						Logger::info('WORLD_COMPETE_RANK_REWARD : sync max honor info for teamId[%d] serverId[%d] done.', $aTeamId, $aServerId);
					}
				}
				
				// 从跨服库上获取这个组所有需要发奖的记录
				$arrCond = array
				(
						array(WorldCompeteCrossUserField::FIELD_TEAM_ID, '=', $aTeamId),
						array(WorldCompeteCrossUserField::FIELD_SERVER_ID, 'IN', $arrServerId),
						array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '>', 0),
						array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				);
				$arrField = array
				(
						WorldCompeteCrossUserField::FIELD_PID,
						WorldCompeteCrossUserField::FIELD_SERVER_ID,
						WorldCompeteCrossUserField::FIELD_MAX_HONOR,
				);
				$crossRewardList = WorldCompeteDao::getCrossRankList($aTeamId, $arrCond, $arrField, WorldCompeteConf::CROSS_REWARD_COUNT);
				
				// 批量拉取server的db信息
				$crossServer2Db = array();
				$crossServer2Db = ServerInfoManager::getInstance()->getArrDbName($arrServerId);
				
				// 发奖
				$rank = 0;
				RewardCfg::$NO_CALLBACK = TRUE;
				$arrRewardUser = array();
				foreach ($crossRewardList as $index => $aInfo)
				{
					try 
					{
						++$rank;
						
						// 基本信息
						$aPid = $aInfo[WorldCompeteCrossUserField::FIELD_PID];
						$aServerId = $aInfo[WorldCompeteCrossUserField::FIELD_SERVER_ID];
						$aMaxHonor = $aInfo[WorldCompeteCrossUserField::FIELD_MAX_HONOR];
						$aServerDb = $crossServer2Db[$aServerId];
						
						// 过滤不需要处理的玩家
						if (empty($arrSpec) 
							|| (isset($arrSpec[$aTeamId]) && empty($arrSpec[$aTeamId])) 
							|| (isset($arrSpec[$aTeamId][$aServerId]) && empty($arrSpec[$aTeamId][$aServerId])) 
							|| isset($arrSpec[$aTeamId][$aServerId][$aPid]))
						{
							Logger::trace('WORLD_COMPETE_RANK_REWARD : need run reward for teamId[%d] serverId[%d] pid[%d]', $aTeamId, $aServerId, $aPid);
						}
						else 
						{
							Logger::trace('WORLD_COMPETE_RANK_REWARD : no need run reward for teamId[%d] serverId[%d] pid[%d]', $aTeamId, $aServerId, $aPid);
							continue;
						}
						
						// 从服内上取发奖时间和uid
						$arrCond = array
						(
								array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $aServerId),
								array(WorldCompeteInnerUserField::FIELD_PID, '=', $aPid),
						);
						$arrField = array
						(
								WorldCompeteInnerUserField::FIELD_PID,
								WorldCompeteInnerUserField::FIELD_UID,
								WorldCompeteInnerUserField::FIELD_REWARD_TIME,
						);
						$aUserInfo = WorldCompeteDao::selectInnerUser($arrCond, $arrField, $aServerDb);
						if (empty($aUserInfo))
						{
							throw new InterException('WORLD_COMPETE_REWARD : no inner user info of serverId[%d] pid[%d] db[%s]', $aServerId, $aPid, $aServerDb);
						}
						$aUid = $aUserInfo[WorldCompeteInnerUserField::FIELD_UID];
						$aRewardTime = $aUserInfo[WorldCompeteInnerUserField::FIELD_REWARD_TIME];
						
						if (WorldCompeteUtil::inSamePeriod($aRewardTime)) // 如果已经发过奖啦，打印一下
						{
							Logger::warning('WORLD_COMPETE_RANK_REWARD : already send reward for teamId[%d] serverId[%d] pid[%d] when[%s] rank[%d] maxHonor[%d]', $aTeamId, $aServerId, $aPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime), $rank, $aMaxHonor);
						}
						else
						{
							$arrReward = WorldCompeteUtil::getRankReward($rank);
							Logger::info('WORLD_COMPETE_RANK_REWARD : send reward for teamId[%d] serverId[%d] pid[%d] rank[%d] reward[%s]', $aTeamId, $aServerId, $aPid, $rank, $arrReward);
							if ($commit) 
							{
								$arrRewardUser[$aServerId][] = $aUid;
								RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::WORLD_COMPETE_RANK_REWARD, array('rank' => $rank), $aServerDb);
								$arrField = array
								(
										WorldCompeteInnerUserField::FIELD_REWARD_TIME => Util::getTime(),
								);
								$arrCond = array
								(
										array(WorldCompeteInnerUserField::FIELD_PID, '=', $aPid),
										array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $aServerId),
								);
								WorldCompeteDao::updateInnerUser($arrCond, $arrField, $aServerDb);
							}
						}
						
						// 每发100个，sleep 1毫秒
						if ($rank % 100 == 0) 
						{
							usleep(1000);
						}
					}
					catch (Exception $e) 
					{
						Logger::fatal('WORLD_COMPETE_RANK_REWARD : occur exception when reward teamId[%d], serverId[%d], pid[%d], rank[%d], exception[%s], trace[%s]', $aTeamId, $aServerId, $aPid, $rank, $e->getMessage(), $e->getTraceAsString());
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
						$group = Util::getGroupByServerId($aServerId);
						$proxy = new ServerProxy();
						$proxy->init($group, Util::genLogId());
						$proxy->sendMessage($arrUid, PushInterfaceDef::REWARD_NEW, array());
					}
				}
				
				Logger::info('WORLD_COMPETE_RANK_REWARD : team[%d] reward end.', $aTeamId);
				usleep(10000);//每组发完奖励，sleep 10毫秒
			}
			catch (Exception $e)
			{
				Logger::fatal('WORLD_COMPETE_RANK_REWARD : occur exception when reward teamId[%d], exception[%s], trace[%s]', $aTeamId, $e->getMessage(), $e->getTraceAsString());
			}
		}

		Logger::info('WORLD_COMPETE_RANK_REWARD : ****** [End] run reward at time[%s] commit[%s] spec[%s] sync[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE", $arrSpec, $sync ? "TRUE" : "FALSE");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */