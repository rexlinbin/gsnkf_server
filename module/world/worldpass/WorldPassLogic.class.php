<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassLogic.class.php 180320 2015-06-24 04:09:10Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPassLogic.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-24 04:09:10 +0000 (Wed, 24 Jun 2015) $
 * @version $Revision: 180320 $
 * @brief 
 *  
 **/
 
class WorldPassLogic
{
	/**
	 * 获得跨服闯关大赛的的基本信息
	 * 
	 * @param int $uid
	 * @throws Exception
	 * @return array
	 */
	public static function getWorldPassInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			$ret = array('ret' => 'no', 'open_time' => WorldPassUtil::serverOpenActivityTime());
			Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
			return $ret;
		}
		
		// 获得玩家obj
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['passed_stage'] = $worldPassInnerUserObj->getPassedStage();
		$ret['curr_point'] = $worldPassInnerUserObj->getCurrPoint();
		$ret['hell_point'] = $worldPassInnerUserObj->getHellPoint();
		$ret['atk_num'] = $worldPassInnerUserObj->getAtkNum();
		$ret['buy_atk_num'] = $worldPassInnerUserObj->getBuyAtkNum();
		$ret['refresh_num'] = $worldPassInnerUserObj->getRefreshNum();
		$ret['monster'] = WorldPassUtil::getRandMonster($teamId);
		$ret['begin_time'] = WorldPassUtil::activityBeginTime();
		$ret['end_time'] = WorldPassUtil::activityEndTime();
		$ret['reward_end_time'] = WorldPassUtil::rewardEndTime();
		$ret['period_end_time'] = WorldPassUtil::periodEndTime();
		
		// 武将召唤列表如果为空，则特殊处理一下，需要刷新一下
		$arrChoice = $worldPassInnerUserObj->getChoice();
		if (empty($arrChoice))
		{
			$worldPassInnerUserObj->refreshHero('sys');
			$worldPassInnerUserObj->update();
		}
		
		$ret['choice'] = $worldPassInnerUserObj->getChoice();
		
		$arrFormation = $worldPassInnerUserObj->getFormation();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			if (!isset($arrFormation[$i])) 
			{
				$arrFormation[$i] = 0;
			}
		}
		$ret['formation'] = $arrFormation;
		
		$ret['point'] = $worldPassInnerUserObj->getPointInfo();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 攻打关卡
	 * 
	 * @param int $uid
	 * @param int $stage
	 * @param int $arrFormation
	 * @throws FakeException
	 * @throws InterException
	 * @return array
	 */
	public static function attack($uid, $stage, $arrFormation)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldPassUtil::inActivity()) 
		{
			throw new FakeException('not in activity, curr[%s], begin[%s], end[%s]', strftime('%Y%m%d %H:%M:%S', Util::getTime()), strftime('%Y%m%d %H:%M:%S', WorldPassUtil::activityBeginTime()), strftime('%Y%m%d %H:%M:%S', WorldPassUtil::activityEndTime()));
		}
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查stage是否是对的
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($stage != $worldPassInnerUserObj->getPassedStage() + 1) 
		{
			throw new FakeException('invalid attack stage[%d], passed stage[%d]', $stage, $worldPassInnerUserObj->getPassedStage());
		}
		
		// 检查formation的有效性
		if (!$worldPassInnerUserObj->isFormationValid($arrFormation))
		{
			throw new FakeException('invalid formation[%s]', $arrFormation);
		}
		
		// 如果是第一关还要判断攻打次数是否足够
		if ($stage == 1 && $worldPassInnerUserObj->getAtkNum() <= 0)
		{
			throw new FakeException('no enough atk num');
		}
				
		// 获得玩家组织的阵型战斗数据和怪的战斗数据，战斗
		$userBattleFormation = WorldPassUtil::getUserBattleFormation($uid, $arrFormation);
		$monster = WorldPassUtil::getRandMonster($teamId, $stage);
		list($monsterBattleFormation, $battleType, $endCondition) = WorldPassUtil::getMonsterBattleFormation($monster);
		$atkRet = EnBattle::doHero($userBattleFormation, $monsterBattleFormation, $battleType, NULL, $endCondition);
		Logger::trace('ATTACK : user fmt[%s], monster fmt[%s], atk result[%s]', $userBattleFormation, $monsterBattleFormation, $atkRet);
			
		// 获取这次战斗的伤害，以每个武将的costHp为准
		$damage = 0;
		foreach ($atkRet['server']['team2'] as $aHeroInfo)
		{
			$damage += $aHeroInfo['costHp'];
		}
		if ($damage < 0)
		{
			$damage = 0;
		}
		
		// 获取自己的剩余血量
		$hp = 0;
		foreach ($atkRet['server']['team1'] as $aHeroInfo)
		{
			$hp += $aHeroInfo['hp'];
		}
		
		// 获得积分和炼狱积分
		$point = WorldPassUtil::getPoint($damage, $hp);
		$hellPoint = $worldPassInnerUserObj->afterAttack($teamId, $arrFormation, $point);
		$worldPassInnerUserObj->update();
						
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['fightRet'] = $atkRet['client'];
		$ret['appraise'] = $atkRet['server']['appraisal'];
		$ret['damage'] = $damage;
		$ret['hp'] = $hp;
		$ret['point'] = $point;
		$ret['hell_point'] = $hellPoint;
		$ret['choice'] = $worldPassInnerUserObj->getChoice();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 重新闯关
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function reset($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldPassUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查这轮是不是已经打通关啦
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldPassInnerUserObj->getPassedStage() != WorldPassConf::STAGE_COUNT) 
		{
			throw new FakeException('not finish curr round, curr passed stage[%d], stage count[%d]', $worldPassInnerUserObj->getPassedStage(), WorldPassConf::STAGE_COUNT);
		}
		
		// 重置本轮信息
		$worldPassInnerUserObj->resetRound();
		
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['passed_stage'] = $worldPassInnerUserObj->getPassedStage();
		$ret['curr_point'] = $worldPassInnerUserObj->getCurrPoint();
		$ret['hell_point'] = $worldPassInnerUserObj->getHellPoint();
		$ret['atk_num'] = $worldPassInnerUserObj->getAtkNum();
		$ret['buy_atk_num'] = $worldPassInnerUserObj->getBuyAtkNum();
		$ret['refresh_num'] = $worldPassInnerUserObj->getRefreshNum();
		$ret['monster'] = WorldPassUtil::getRandMonster($teamId);
		$ret['begin_time'] = WorldPassUtil::activityBeginTime();
		$ret['end_time'] = WorldPassUtil::activityEndTime();
		$ret['reward_end_time'] = WorldPassUtil::rewardEndTime();
		$ret['period_end_time'] = WorldPassUtil::periodEndTime();
		$arrChoice = $worldPassInnerUserObj->getChoice();
		if (empty($arrChoice))
		{
			$worldPassInnerUserObj->refreshHero('sys');
		}
		$ret['choice'] = $worldPassInnerUserObj->getChoice();
		
		$arrFormation = $worldPassInnerUserObj->getFormation();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			if (!isset($arrFormation[$i]))
			{
				$arrFormation[$i] = 0;
			}
		}
		$ret['formation'] = $arrFormation;
		
		$ret['point'] = $worldPassInnerUserObj->getPointInfo();
		$worldPassInnerUserObj->update();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 购买攻击次数
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function addAtkNum($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldPassUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查购买次数是否达到上限
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldPassInnerUserObj->getBuyAtkNum() >= WorldPassUtil::getBuyLimit()) 
		{
			throw new FakeException('buy num exceed, curr buy num[%d], buy limit[%d]', $worldPassInnerUserObj->getBuyAtkNum(), WorldPassUtil::getBuyLimit());
		}
		
		// 扣金币,update
		$cost = WorldPassUtil::getBuyCost($worldPassInnerUserObj->getBuyAtkNum() + 1);
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_PASS_BUY_NUM_COST)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
		
		// 加次数
		$worldPassInnerUserObj->buyAtkNum();
		$worldPassInnerUserObj->update();
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 获得服务器分组信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getMyTeamInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在活动有效期内
		if (!WorldPassUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 获得服务器名称
		$ret = array();
		$arrServerId = WorldPassUtil::getArrServerIdByTeamId($teamId);
		$ret = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		
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
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 自己的排名初始化
		$myInnerRank = 0;
		$myCrossRank = 0;
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		$myMaxPoint = $worldPassInnerUserObj->getMaxPoint();
		$myMaxPointTime = $worldPassInnerUserObj->getMaxPointTime();
		
		// 获得本服的排行
		$arrCond = array
		(
				array(WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
				array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, '>', 0),
		);
		$arrField = array
		(
				WorldPassInnerUserField::TBL_FIELD_PID,
				WorldPassInnerUserField::TBL_FIELD_SERVER_ID,
				WorldPassInnerUserField::TBL_FIELD_UID,
				WorldPassInnerUserField::TBL_FIELD_MAX_POINT,
		);
		$innerRankList = WorldPassDao::getInnerRankList($arrCond, $arrField, WorldPassConf::INNER_RANK_LIST_COUNT);
		$arrUid = Util::arrayExtract($innerRankList, WorldPassInnerUserField::TBL_FIELD_UID);
		$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'uname', 'htid', 'level', 'vip', 'fight_force', 'dress'));
		$innerInfo = array();
		$rank = 0;
		foreach ($innerRankList as $index => $aInfo)
		{
			$aUid = $aInfo[WorldPassInnerUserField::TBL_FIELD_UID];
			$aMaxPoint = $aInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT];
			
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
					'max_point' => $aMaxPoint,
					'rank' => ++$rank,
			);
			
			if ($uid == $aUid) 
			{
				$myInnerRank = $rank;
			}
		}
		
		// 获取自己的服内排名
		if ($myInnerRank == 0 && $myMaxPoint > 0) 
		{
			// 积分比自己高的玩家个数
			$arrCond = array
			(
					array(WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
					array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, '>', $myMaxPoint),
			);
			$count = WorldPassDao::getInnerCount($arrCond);
			
			// 积分相同，时间比自己早的
			$arrCond = array
			(
					array(WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
					array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, '=', $myMaxPoint),
					array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME, '<', $myMaxPointTime),
			);
			$count += WorldPassDao::getInnerCount($arrCond);
			
			// 积分时间都相同，uid比自己小的
			$arrCond = array
			(
					array(WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
					array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, '=', $myMaxPoint),
					array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME, '=', $myMaxPointTime),
					array(WorldPassInnerUserField::TBL_FIELD_UID, '<', $uid),
			);
			$count += WorldPassDao::getInnerCount($arrCond);
			
			$myInnerRank = $count + 1;
		}
		
		// 获得跨服的排行
		$arrServerId = WorldPassUtil::getArrServerIdByTeamId($teamId);
		$arrCond = array
		(
				array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, 'IN', $arrServerId),
				array(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
				array(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, '>', 0),
		);
		$arrField = array
		(
				WorldPassCrossUserField::TBL_FIELD_PID,
				WorldPassCrossUserField::TBL_FIELD_SERVER_ID,
				WorldPassCrossUserField::TBL_FIELD_MAX_POINT,
		);
		$crossRankList = WorldPassDao::getCrossRankList($teamId, $arrCond, $arrField, WorldPassConf::CROSS_RANK_LIST_COUNT);
		Logger::trace('GET_RANK_LIST : CROSS : raw rank list[%s]', $crossRankList);
		
		$crossServerInfo = array();
		foreach ($crossRankList as $aInfo)
		{
			$aPid = $aInfo[WorldPassCrossUserField::TBL_FIELD_PID];
			$aServerId = $aInfo[WorldPassCrossUserField::TBL_FIELD_SERVER_ID];
			$aMaxPoint = $aInfo[WorldPassCrossUserField::TBL_FIELD_MAX_POINT];
			$crossServerInfo[$aServerId][$aPid] = $aMaxPoint;
		}
		Logger::trace('GET_RANK_LIST : CROSS : after array index[%s]', $crossServerInfo);
		
		// 批量拉取server的db信息
		$crossServer2Db = ServerInfoManager::getInstance()->getArrDbName(array_keys($crossServerInfo));
		
		$arrUserInfo = array();
		foreach ($crossServerInfo as $aServerId => $arrInfo)
		{
			$aServerDb = $crossServer2Db[$aServerId];
			$arrPid = array_keys($arrInfo);
			$arrCond = array
			(
					array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
					array(WorldPassInnerUserField::TBL_FIELD_PID, 'IN', $arrPid),
			);
			$arrField = array
			(
					WorldPassInnerUserField::TBL_FIELD_PID,
					WorldPassInnerUserField::TBL_FIELD_UID,
			);
			$arrInnerInfo = WorldPassDao::getInnerRankList($arrCond, $arrField, 100000, $aServerDb);
			$arrPid2Uid = Util::arrayIndex($arrInnerInfo, WorldPassInnerUserField::TBL_FIELD_PID);
			$aArrUid = Util::arrayExtract($arrPid2Uid, WorldPassInnerUserField::TBL_FIELD_UID);
			$arrPartUserInfo = EnUser::getArrUserBasicInfo($aArrUid, array('uid', 'uname', 'htid', 'level', 'vip', 'fight_force', 'dress'), $aServerDb);
			foreach ($arrInfo as $aPid => $aMaxPoint)
			{
				if (!isset($arrPartUserInfo[$arrPid2Uid[$aPid][WorldPassInnerUserField::TBL_FIELD_UID]]))
				{
					Logger::warning('cross rank list, no basic info or user[%d]', $aUid);
					continue;
				}
				$arrUserInfo[$aServerId][$aPid] = $arrPartUserInfo[$arrPid2Uid[$aPid][WorldPassInnerUserField::TBL_FIELD_UID]];
			}
		}
		Logger::trace('GET_RANK_LIST : CROSS : user basic info[%s]', $arrUserInfo);
		
		$arrServerName = ServerInfoManager::getInstance()->getArrServerName($arrServerId);
		Logger::trace('GET_RANK_LIST : CROSS : server name info[%s]', $arrServerName);
		
		$crossInfo = array();
		$rank = 0;
		foreach ($crossRankList as $index => $aInfo)
		{
			$aPid = $aInfo[WorldPassCrossUserField::TBL_FIELD_PID];
			$aServerId = $aInfo[WorldPassCrossUserField::TBL_FIELD_SERVER_ID];
			$aMaxPoint = $aInfo[WorldPassCrossUserField::TBL_FIELD_MAX_POINT];
			
			if (!isset($arrUserInfo[$aServerId][$aPid])) 
			{
				Logger::warning('no basic info or user pid[%d] serverId[%d]', $aPid, $aServerId);
				continue;
			}
			
			$crossInfo[] = array
			(
					'server_id' => $aServerId,
					'server_name' => $arrServerName[$aServerId],
					'uid' => $arrUserInfo[$aServerId][$aPid]['uid'],
					'uname' => $arrUserInfo[$aServerId][$aPid]['uname'],
					'htid' => $arrUserInfo[$aServerId][$aPid]['htid'],
					'level' => $arrUserInfo[$aServerId][$aPid]['level'],
					'vip' => $arrUserInfo[$aServerId][$aPid]['vip'],
					'fight_force' => $arrUserInfo[$aServerId][$aPid]['fight_force'],
					'dress' => $arrUserInfo[$aServerId][$aPid]['dress'],
					'max_point' => $aMaxPoint,
					'rank' => ++$rank,
			);
			
			if ($serverId == $aServerId && $pid == $aPid) 
			{
				$myCrossRank = $rank;
			}
		}
		Logger::trace('GET_RANK_LIST : CROSS : final cross info[%s]', $crossInfo);
		
		if ($myCrossRank == 0 && $myMaxPoint > 0) 
		{				
			// 积分比自己高的玩家个数
			$arrCond = array
			(
					array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $teamId),
					array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, 'IN', $arrServerId),
					array(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
					array(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, '>', $myMaxPoint),
			);
			$count = WorldPassDao::getCrossCount($teamId, $arrCond);
				
			// 积分相同，时间比自己早的
			$arrCond = array
			(
					array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $teamId),
					array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, 'IN', $arrServerId),
					array(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, 'BETWEEN', array(WorldPassUtil::activityBeginTime(), $myMaxPointTime - 1)),
					array(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, '=', $myMaxPoint),
			);
			$count += WorldPassDao::getCrossCount($teamId, $arrCond);
				
			// 积分时间都相同，就不必啦，没有太大必要，而且跨服库上尽量少一些sql
			//
				
			$myCrossRank = $count + 1;
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
	
	/**
	 * 刷新武将信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function refreshHeros($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		// 获得玩家serverId和pid
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
				
		// 检查是否在活动有效期内
		if (!WorldPassUtil::inActivity())
		{
			throw new FakeException('not in activity');
		}
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			throw new FakeException('not in any team');
		}
		
		// 检查这轮是不是已经打通关啦
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		if ($worldPassInnerUserObj->getPassedStage() == WorldPassConf::STAGE_COUNT) 
		{
			throw new FakeException('already finish curr round, can not refresh hero');
		}
		
		// 先看看有没有刷新道具，有道具则先使用道具刷新，没道具则使用金币刷新
		$bag = BagManager::getInstance()->getBag($uid);
		$refreshItemId = intval(btstore_get()->WORLD_PASS_RULE['refresh_item_id']);
		if ($bag->deleteItembyTemplateID($refreshItemId, 1)) // 扣道具 
		{
			$bag->update();
			
			$worldPassInnerUserObj->refreshHero('item');
			$worldPassInnerUserObj->update();
		}
		else // 扣金币
		{
			if ($worldPassInnerUserObj->getRefreshNum() >= WorldPassUtil::getRefreshLimit())
			{
				throw new FakeException('refresh num exceed, curr refresh num[%d], refresh limit[%d]', $worldPassInnerUserObj->getRefreshNum(), WorldPassUtil::getRefreshLimit());
			}
			
			$cost = WorldPassUtil::getRefreshCost($worldPassInnerUserObj->getRefreshNum() + 1);
			$userObj = EnUser::getUserObj($uid);
			if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_WORLD_PASS_REFRESH_HERO_COST))
			{
				throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
			}
			$userObj->update();

			$worldPassInnerUserObj->refreshHero('gold');
			$worldPassInnerUserObj->update();
		}
		
		$ret = $worldPassInnerUserObj->getChoice();
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
	
		$shop = new WorldPassShop($uid);	
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
	
		$shop = new WorldPassShop($uid);
	
		$ret = $shop->exchange($goodsId, $num);
		$shop->update();
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
}

class WorldPassScriptLogic
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
		if ($next && WorldPassUtil::inActivity()) 
		{
			Logger::warning('SYNC_ALL_TEAM : in activity, can not sync.');
			return;
		}
		
		// 得到配置的分组数据和所有服务器信息
		$beginTime = Util::getTime();
		if ($next) 
		{
			$beginTime = WorldPassUtil::periodEndTime();//也就是下个周期的开始时间
		}
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo = WorldPassUtil::getAllTeamInfo($next);
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
				WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME => 0,
		);
		$arrCond = array
		(
				array(WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME, '>', 0),
				array(WorldPassCrossTeamField::TBL_FIELD_SERVER_ID, '>', 0),
		);
		if ($commit) 
		{
			WorldPassDao::updateTeamInfo($arrCond, $arrField);
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
		$needOpenDuration = intval(btstore_get()->WORLD_PASS_RULE['need_open_days']);
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $next ? WorldPassUtil::periodEndTime() : WorldPassUtil::activityBeginTime();
			$betweenDays = intval((strtotime(date("Y-m-d", $referTime)) - strtotime(date("Y-m-d", $aOpenTime))) / SECONDS_OF_DAY);
			if ($betweenDays < $needOpenDuration) 
			{
				unset($tmpAllServerInfo[$aServerId]);
				Logger::info('SYNC_ALL_TEAM : server id[%d] skip, open time[%s], refer time[%s], need open days[%d].', $aServerId, date("Y-m-d", $aOpenTime), date("Y-m-d", $referTime), $needOpenDuration);
			}
		}
		Logger::info('SYNC_ALL_TEAM : all new server info after open days filter[%s]', $tmpAllServerInfo);
		
		// 将剩余的服务器自动分组，合服的要在同一个组里
		if (!empty($tmpAllServerInfo))
		{
			// 处理合服的情况，db -> array(serverId...)
			$arrDb2Info = array();
			foreach ($tmpAllServerInfo as $aServerId => $aInfo)
			{
				if (!isset($arrDb2Info[$aInfo['db_name']]))
				{
					$arrDb2Info[$aInfo['db_name']] = array();
				}
				$arrDb2Info[$aInfo['db_name']][] = $aServerId;
			}
			Logger::info('SYNC_ALL_TEAM : db 2 info of new server[%s]', $arrDb2Info);
				
			// 处理正常的分组
			$minCount = defined('PlatformConfig::WORLD_PASS_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_PASS_TEAM_MIN_COUNT : 5;
			$maxCount = defined('PlatformConfig::WORLD_PASS_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_PASS_TEAM_MAX_COUNT : 7;
			Logger::info('SYNC_ALL_TEAM : min server count[%d], max server count[%d]', $minCount, $maxCount);
			
			$curServerCount = 0;
			$curTeamNeedCount = mt_rand($minCount, $maxCount);
			$curTeamId = ++$curMaxTeamId;
			Logger::info('SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
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
					Logger::info('SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
				}
		
				$arrCfgTeamInfo[$curTeamId][] = $aServerId;
				Logger::info('SYNC_ALL_TEAM : generate new team[%d], add a normal server[%d]', $curTeamId, $aServerId);
				foreach ($arrDb2Info[$aInfo['db_name']] as $aMergeServerId)
				{
					if ($aMergeServerId == $aServerId)
					{
						continue;
					}
					$arrCfgTeamInfo[$curTeamId][] = $aMergeServerId;
					$arrExclude[] = $aMergeServerId;
					Logger::info('SYNC_ALL_TEAM : generate new team[%d], add a merge server[%d]', $curTeamId, $aMergeServerId);
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
					Logger::info('SYNC_ALL_TEAM : cur team[%d] count[%d] less than min[%d], add to last', $curTeamId, $curServerCount, $minCount);
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
								WorldPassCrossTeamField::TBL_FIELD_TEAM_ID => $aTeamId,
								WorldPassCrossTeamField::TBL_FIELD_SERVER_ID => $aServerId,
								WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME => $beginTime + 1,
						);
						WorldPassDao::insertTeamInfo($arrField);
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
		$beginTime = WorldPassUtil::activityBeginTime();
		if ($next)
		{
			$beginTime = WorldPassUtil::periodEndTime();
		}
		$arrCfgTeamInfo = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getAllTeam();
		ksort($arrCfgTeamInfo);
		$arrMyTeamInfo = WorldPassUtil::getAllTeamInfo($next);
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
		$needOpenDuration = intval(btstore_get()->WORLD_PASS_RULE['need_open_days']);
		foreach ($tmpAllServerInfo as $aServerId => $aInfo)
		{
			$aOpenTime = $aInfo['open_time'];
			$referTime = $next ? WorldPassUtil::periodEndTime() : WorldPassUtil::activityBeginTime();
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
			$minCount = defined('PlatformConfig::WORLD_PASS_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_PASS_TEAM_MIN_COUNT : 5;
			$maxCount = defined('PlatformConfig::WORLD_PASS_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_PASS_TEAM_MAX_COUNT : 7;
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
								WorldPassCrossTeamField::TBL_FIELD_TEAM_ID => $aTeamId,
								WorldPassCrossTeamField::TBL_FIELD_SERVER_ID => $aServerId,
								WorldPassCrossTeamField::TBL_FIELD_UPDATE_TIME => $beginTime + 1,
						);
						WorldPassDao::insertTeamInfo($arrField);
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
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if ($teamId != $belongTeamId) 
		{
			throw new InterException('SYNC_INNER_2_CROSS : invalid sync from inner 2 cross, belong team[%d], plat team[%d]', $belongTeamId, $teamId);
		}
		
		// 先从跨服库取这个服的最高积分数据
		$arrCond = array
		(
				array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, '>', 0),
				array(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
		);
		$arrField = array
		(
				WorldPassCrossUserField::TBL_FIELD_PID,
				WorldPassCrossUserField::TBL_FIELD_MAX_POINT,
		);
		$arrCrossInfo = WorldPassDao::getCrossRankList($teamId, $arrCond, $arrField, 100000);
		$arrCrossInfo = Util::arrayIndex($arrCrossInfo, WorldPassCrossUserField::TBL_FIELD_PID);
		
		// 从这个服上拉取最高积分数据
		$serverDb = ServerInfoManager::getInstance()->getDbNameByServerId($serverId);		
		$arrCond = array
		(
				array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldPassInnerUserField::TBL_FIELD_MAX_POINT, '>', 0),
				array(WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
		);
		$arrField = array
		(
				WorldPassInnerUserField::TBL_FIELD_PID,
				WorldPassInnerUserField::TBL_FIELD_MAX_POINT,
		);
		$arrInnerInfo = WorldPassDao::getInnerRankList($arrCond, $arrField, 100000, $serverDb);
		$arrInnerInfo = Util::arrayIndex($arrInnerInfo, WorldPassInnerUserField::TBL_FIELD_PID);
		
		// 同步数据
		foreach ($arrInnerInfo as $aPid => $aInnerInfo)
		{
			// 本服有，跨服库没有
			if (!isset($arrCrossInfo[$aPid])) 
			{
				Logger::warning('SYNC_INNER_2_CROSS : no max point info of teamId[%d] serverId[%d] pid[%d] in cross', $teamId, $serverId, $aPid);
				if ($commit) 
				{
					$arrField = array
					(
							WorldPassCrossUserField::TBL_FIELD_TEAM_ID => $teamId,
							WorldPassCrossUserField::TBL_FIELD_PID => $aPid,
							WorldPassCrossUserField::TBL_FIELD_SERVER_ID => $serverId,
							WorldPassCrossUserField::TBL_FIELD_MAX_POINT => $aInnerInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT],
							WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
					);
					WorldPassDao::insertCrossUser($teamId, $arrField);
				}
				unset($arrCrossInfo[$aPid]);
			}
			else if ($aInnerInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT] != $arrCrossInfo[$aPid][WorldPassCrossUserField::TBL_FIELD_MAX_POINT]) 
			{
				Logger::warning('SYNC_INNER_2_CROSS : max point not equal of teamId[%d] serverId[%d] pid[%d], inner[%d] cross[%d], update to inner', $teamId, $serverId, $aPid, $aInnerInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT], $arrCrossInfo[$aPid][WorldPassCrossUserField::TBL_FIELD_MAX_POINT]);
				if ($commit) 
				{
					$arrCond = array
					(
							array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $teamId),
							array(WorldPassCrossUserField::TBL_FIELD_PID, '=', $aPid),
							array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
					);
					$arrField = array
					(
							WorldPassCrossUserField::TBL_FIELD_MAX_POINT => $aInnerInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT],
							WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
					);
					WorldPassDao::updateCrossUser($teamId, $arrCond, $arrField);
				}
				unset($arrCrossInfo[$aPid]);
			}
			else 
			{
				Logger::trace('SYNC_INNER_2_CROSS : max point equal of teamId[%d] serverId[%d] pid[%d], inner[%d] cross[%d], no need sync', $teamId, $serverId, $aPid, $aInnerInfo[WorldPassInnerUserField::TBL_FIELD_MAX_POINT], $arrCrossInfo[$aPid][WorldPassCrossUserField::TBL_FIELD_MAX_POINT]);
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
		Logger::info('WORLD_PASS_RANK_REWARD : ****** [Begin] run reward at time[%s] commit[%s] spec[%s] sync[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE", $arrSpec, $sync ? "TRUE" : "FALSE");
		
		if (WorldPassUtil::inActivity()) 
		{
			Logger::warning('WORLD_PASS_RANK_REWARD : in activity, can not reward.');
			return;
		}
		
		$allTeamId = WorldPassUtil::getAllTeam();
		foreach ($allTeamId as $aTeamId)
		{
			try
			{
				Logger::info('WORLD_PASS_RANK_REWARD : team[%d] reward begin.', $aTeamId);
				
				// 过滤掉不需要处理的组
				if (!empty($arrSpec) && !isset($arrSpec[$aTeamId])) 
				{
					Logger::trace('WORLD_PASS_RANK_REWARD : no need run reward for teamId[%d]', $aTeamId);
					continue;
				}
				
				// 当前分组下的所有服
				$arrServerId = WorldPassUtil::getArrServerIdByTeamId($aTeamId);
				
				// 从每个服上同步最大积分数据到跨服库
				if ($sync) 
				{
					foreach ($arrServerId as $aServerId)
					{
						self::syncInner2Cross($aServerId, $aTeamId);
						Logger::info('WORLD_PASS_RANK_REWARD : sync max point info for teamId[%d] serverId[%d] done.', $aTeamId, $aServerId);
					}
				}
				
				// 从跨服库上获取这个组所有需要发奖的记录
				$arrCond = array
				(
						array(WorldPassCrossUserField::TBL_FIELD_TEAM_ID, '=', $aTeamId),
						array(WorldPassCrossUserField::TBL_FIELD_SERVER_ID, 'IN', $arrServerId),
						array(WorldPassCrossUserField::TBL_FIELD_MAX_POINT, '>', 0),
						array(WorldPassCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', WorldPassUtil::activityBeginTime()),
				);
				$arrField = array
				(
						WorldPassCrossUserField::TBL_FIELD_PID,
						WorldPassCrossUserField::TBL_FIELD_SERVER_ID,
						WorldPassCrossUserField::TBL_FIELD_MAX_POINT,
				);
				$crossRewardList = WorldPassDao::getCrossRankList($aTeamId, $arrCond, $arrField, WorldPassConf::CROSS_REWARD_COUNT);
				
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
						$aPid = $aInfo[WorldPassCrossUserField::TBL_FIELD_PID];
						$aServerId = $aInfo[WorldPassCrossUserField::TBL_FIELD_SERVER_ID];
						$aMaxPoint = $aInfo[WorldPassCrossUserField::TBL_FIELD_MAX_POINT];
						$aServerDb = $crossServer2Db[$aServerId];
						
						// 过滤不需要处理的玩家
						if (empty($arrSpec) 
							|| (isset($arrSpec[$aTeamId]) && empty($arrSpec[$aTeamId])) 
							|| (isset($arrSpec[$aTeamId][$aServerId]) && empty($arrSpec[$aTeamId][$aServerId])) 
							|| isset($arrSpec[$aTeamId][$aServerId][$aPid]))
						{
							Logger::trace('WORLD_PASS_RANK_REWARD : need run reward for teamId[%d] serverId[%d] pid[%d]', $aTeamId, $aServerId, $aPid);
						}
						else 
						{
							Logger::trace('WORLD_PASS_RANK_REWARD : no need run reward for teamId[%d] serverId[%d] pid[%d]', $aTeamId, $aServerId, $aPid);
							continue;
						}
						
						// 从服内上取发奖时间和uid
						$arrCond = array
						(
								array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
								array(WorldPassInnerUserField::TBL_FIELD_PID, '=', $aPid),
						);
						$arrField = array
						(
								WorldPassInnerUserField::TBL_FIELD_PID,
								WorldPassInnerUserField::TBL_FIELD_UID,
								WorldPassInnerUserField::TBL_FIELD_REWARD_TIME,
						);
						$aUserInfo = WorldPassDao::selectInnerUser($arrCond, $arrField, $aServerDb);
						if (empty($aUserInfo))
						{
							throw new InterException('WORLD_PASS_REWARD : no inner user info of serverId[%d] pid[%d] db[%s]', $aServerId, $aPid, $aServerDb);
						}
						$aUid = $aUserInfo[WorldPassInnerUserField::TBL_FIELD_UID];
						$aRewardTime = $aUserInfo[WorldPassInnerUserField::TBL_FIELD_REWARD_TIME];
						
						if (WorldPassUtil::inSamePeriod($aRewardTime)) // 如果已经发过奖啦，打印一下
						{
							Logger::warning('WORLD_PASS_RANK_REWARD : already send reward for teamId[%d] serverId[%d] pid[%d] when[%s] rank[%d] maxPoint[%d]', $aTeamId, $aServerId, $aPid, strftime('%Y%m%d %H:%M:%S', $aRewardTime), $rank, $aMaxPoint);
						}
						else
						{
							$arrReward = WorldPassUtil::getRankReward($rank);
							Logger::info('WORLD_PASS_RANK_REWARD : send reward for teamId[%d] serverId[%d] pid[%d] rank[%d] reward[%s]', $aTeamId, $aServerId, $aPid, $rank, $arrReward);
							if ($commit) 
							{
								$arrRewardUser[$aServerId][] = $aUid;
								RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::WORLD_PASS_RANK_REWARD, array('rank' => $rank), $aServerDb);
								$arrField = array
								(
										WorldPassInnerUserField::TBL_FIELD_REWARD_TIME => Util::getTime(),
								);
								$arrCond = array
								(
										array(WorldPassInnerUserField::TBL_FIELD_PID, '=', $aPid),
										array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $aServerId),
								);
								WorldPassDao::updateInnerUser($arrCond, $arrField, $aServerDb);
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
						Logger::fatal('WORLD_PASS_RANK_REWARD : occur exception when reward teamId[%d], serverId[%d], pid[%d], rank[%d], exception[%s], trace[%s]', $aTeamId, $aServerId, $aPid, $rank, $e->getMessage(), $e->getTraceAsString());
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
				
				Logger::info('WORLD_PASS_RANK_REWARD : team[%d] reward end.', $aTeamId);
				usleep(10000);//每组发完奖励，sleep 10毫秒
			}
			catch (Exception $e)
			{
				Logger::fatal('WORLD_PASS_RANK_REWARD : occur exception when reward teamId[%d], exception[%s], trace[%s]', $aTeamId, $e->getMessage(), $e->getTraceAsString());
			}
		}

		Logger::info('WORLD_PASS_RANK_REWARD : ****** [End] run reward at time[%s] commit[%s] spec[%s] sync[%s] ******', strftime('%Y%m%d %H:%M:%S', Util::getTime()), $commit ? "TRUE" : "FALSE", $arrSpec, $sync ? "TRUE" : "FALSE");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */