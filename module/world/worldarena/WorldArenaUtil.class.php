<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaUtil.class.php 184936 2015-07-17 03:08:58Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaUtil.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-17 03:08:58 +0000 (Fri, 17 Jul 2015) $
 * @version $Revision: 184936 $
 * @brief 
 *  
 **/
 
class WorldArenaUtil
{
	/**
	 * 判断serverId是不是在本服上
	 *
	 * @param int $serverId
	 * @return boolean
	 */
	public static function isMyServer($serverId)
	{
		$group = RPCContext::getInstance()->getFramework()->getGroup();
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
	 * 根据teamId获取最新的这个team下面的serverId
	 *
	 * @param int $teamId
	 * @return array
	 */
	public static function getArrServerIdByTeamId($teamId)
	{
		$confObj = WorldArenaConfObj::getInstance();
		$periodBgnTime = $confObj->getPeriodBgnTime();
		
		$arrField = array
		(
				WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID, '=', $teamId),
				array(WorldArenaCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
		);
		$teamInfo = WorldArenaDao::selectTeamInfo($arrCond, $arrField);
	
		return Util::arrayExtract($teamInfo, WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID);
	}
	
	/**
	 * 根据serverId获取teamId
	 *
	 * @param int $serverId
	 * @return array
	 */
	public static function getTeamIdByServerId($serverId)
	{
		$confObj = WorldArenaConfObj::getInstance();
		$periodBgnTime = $confObj->getPeriodBgnTime();
		
		$arrField = array
		(
				WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldArenaCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
		);
		$teamInfo = WorldArenaDao::selectTeamInfo($arrCond, $arrField);
	
		return empty($teamInfo) ? 0 : $teamInfo[0][WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID];
	}
	
	/**
	 * 获取所有分组的id
	 *
	 * @return array
	 */
	public static function getAllTeam()
	{
		$arrInfo = WorldArenaUtil::getAllTeamInfo();
		return array_keys($arrInfo);
	}
	
	/**
	 * 获得分组信息
	 *
	 * @return array
	 */
	public static function getAllTeamInfo()
	{
		$confObj = WorldArenaConfObj::getInstance(WorldArenaField::CROSS);
		$periodBgnTime = $confObj->getPeriodBgnTime();
		$arrField = array
		(
				WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID,
				WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID,
		);
		$arrCond = array
		(
				array(WorldArenaCrossTeamField::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
		);
		$allTeamInfo = WorldArenaDao::selectTeamInfo($arrCond, $arrField);
	
		$arrRet = array();
		foreach ($allTeamInfo as $aInfo)
		{
			$aTeamId = $aInfo[WorldArenaCrossTeamField::TBL_FIELD_TEAM_ID];
			$aServerId = $aInfo[WorldArenaCrossTeamField::TBL_FIELD_SERVER_ID];
			if (!isset($arrRet[$aTeamId]))
			{
				$arrRet[$aTeamId] = array();
			}
			$arrRet[$aTeamId][] = $aServerId;
		}
	
		return $arrRet;
	}
	
	/**
	 * 获得跨服库的db名称
	 *
	 * @return string
	 */
	public static function getCrossDbName()
	{
		return WorldArenaConf::WORLD_ARENA_DB_PREFIX . PlatformConfig::PLAT_NAME;
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
	 * 根据参数所在的排名，获取这个排名可以攻打的排名列表
	 * 
	 * @param int $rank
	 * @param boolean $isAttack
	 * @return array
	 */
	public static function getTargetRank($rank, $isAttack = FALSE)
	{
		$ret = array();
		
		if ($rank <= 0) 
		{
			throw new InterException('invalid rank[%d]', $rank);
		}
		
		if ($rank <= WorldArenaConf::PLAYER_NUM_EVERY_PAGE)  // 当前排名如果在一页范围内，则取的对手排名是固定的 
		{
			for ($i = 1; $i <= WorldArenaConf::PLAYER_NUM_EVERY_PAGE; ++$i)
			{
				if ($i != $rank) 
				{
					$ret[] = $i;
				}
			}
		}
		else // 当前排名不在一页范围内，需要按一定规则找到排名，并且保证目标排名在100以内
		{
			$confObj = WorldArenaConfObj::getInstance();
			$targetCoef = $confObj->getTargetCoef();
			if ($isAttack) // 如果是攻击时候，目标的范围放大1个百分点
			{
				$targetCoef += 100;
			}
			for ($i = 1; $i < WorldArenaConf::PLAYER_NUM_EVERY_PAGE; ++$i)
			{
				// 玩家的目标排名范围控制在100以内
				$distant = intval(ceil($rank * $targetCoef * $i / UNIT_BASE));		
				if ($distant > intval(ceil($i / (WorldArenaConf::PLAYER_NUM_EVERY_PAGE - 1) * DataDef::MAX_FETCH))) 
				{
					$distant = intval(ceil($i / (WorldArenaConf::PLAYER_NUM_EVERY_PAGE - 1) * DataDef::MAX_FETCH));
				}
				$curRank = $rank - $distant;
				
				// 如果和已经找到的排名重复，则一直往前面的排名找
				for ($j = 1; $j < $i; ++$j)
				{
					if ($curRank >= $ret[$j - 1]) 
					{
						--$curRank;
					}
				}
				
				$ret[] = $curRank;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 根据攻方的排名和被攻方的排名，返回被攻方是否在攻方的可攻击范围内
	 * 
	 * @param int $attackRank
	 * @param int $defendRank
	 * @return boolean
	 */
	public static function inRange($attackRank, $defendRank)
	{
		// 如果排名相同，也不能
		if ($attackRank == $defendRank) 
		{
			Logger::warning('same rank[%d], why?', $attackRank);
			return FALSE;
		}
		
		//  名次在每一页最大玩家数以内,则只能攻击排名同样也在每一页最大玩家数以内的玩家
		if ($attackRank <= WorldArenaConf::PLAYER_NUM_EVERY_PAGE) 
		{
			return $defendRank <= WorldArenaConf::PLAYER_NUM_EVERY_PAGE;
		}

		// 目标排名处于自己之前，且在所有目标中最高目标的排名之后，则可以攻打
		$arrTargetRank = WorldArenaUtil::getTargetRank($attackRank, TRUE);
		return  $defendRank < $attackRank && $defendRank >= min($arrTargetRank);//TODO 放宽限制
	}
	
	/**
	 * 去某一个分组，某一个房间的一组排名的跨服 信息，排名范围不能超出100
	 * 
	 * @param int $teamId
	 * @param int $roomId
	 * @param int $arrRank
	 * @throws InterException
	 * @return array
	 */
	public static function getPlayerInfoByArrRank($teamId, $roomId, $arrRank)
	{
		if (empty($teamId) || empty($roomId)) 
		{
			throw new InterException('invalid teamId[%d] or invalid roomId[%d]', $teamId, $roomId);
		}
		
		$minRank = min($arrRank);
		$maxRank = max($arrRank);
		if (($maxRank - $minRank) >= DataDef::MAX_FETCH || $minRank <= 0 || $maxRank <= 0) 
		{
			throw new InterException('rank range is too big or invalid, all rank[%s]', $arrRank);
		}
		
		$offset = $minRank - 1;
		$limit = $maxRank - $minRank + 1;
		
		$ret = array();
		
		$confObj = WorldArenaConfObj::getInstance();
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $roomId),
				array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
		);
		$arrCrossUserInfo = WorldArenaDao::_getPosRankList($teamId, $arrCond, array(), $offset, $limit);
		foreach ($arrRank as $aRank)
		{
			if (isset($arrCrossUserInfo[$aRank - $minRank])) 
			{
				$ret[$aRank] = $arrCrossUserInfo[$aRank - $minRank];
			}
		}
		
		return $ret;
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
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */