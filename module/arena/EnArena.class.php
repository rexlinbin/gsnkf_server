<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: EnArena.class.php 151545 2015-01-10 07:22:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/EnArena.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-01-10 07:22:55 +0000 (Sat, 10 Jan 2015) $
 * @version $Revision: 151545 $
 * @brief 
 *  
 **/



class EnArena
{
	public static function sendRankReward()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (empty($uid))
		{
			Logger::debug("user is not login, ignore.");
			return;
		}
		
		ArenaLogic::sendRankReward($uid);
	}

	/**
	 * 增加竞技次数
	 * 
	 * @param int $uid
	 * @param int $num
	 * @return true or false
	 */
	public static function addChallengeNum($uid, $num)
	{
		$res = ArenaLogic::getInfo($uid);
		if (empty($res))
		{
			//初始化用户的竞技场信息
			$ret = ArenaLogic::initArena($uid);
            if ('ok' != $ret)
            {
            	return false;
            }
            $res = ArenaLogic::getInfo($uid);
		}
		
		ArenaDao::update($uid, array('challenge_num' => $res['challenge_num'] + $num));
		return true;
	}
	
	/**
	 * 查询用户的竞技场信息	
	 * 
	 * @param int $arrUid					
	 * @param array $arrField
	 */
	public static function getArrArena($arrUid, $arrField)
	{
		if (empty($arrUid) || empty($arrField))
		{
			return array();
		}
		return ArenaDao::getArrInfo($arrUid, $arrField);
	}
	
	public static function getPosition($uid)
	{
		$position = 0;
		$info = ArenaLogic::getInfo($uid);
		if (!empty($info))
		{
			$position = $info['position'];
		}
		return $position;
	}
	
	/**
	 * 排除当前用户，随机取出min到max之间pos的用户uid
	 * 
	 * @param int $uid 排除的uid
	 * @param int $min 最小排名
	 * @param int $max 最大排名
	 * @return int $taget 目标用户uid
	 */
	public static function getUid($uid, $min, $max)
	{
		if($uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $uid);
		}
		if ($min <= 0 || $max <= 0 || $min > $max) 
		{
			throw new FakeException('invalid para min:%d, max:%d', $min, $max);
		}
		//跨段了就截断
		if ($min <= ArenaConf::NPC_NUM && $max >= ArenaConf::NPC_NUM) 
		{
			$max = ArenaConf::NPC_NUM;
		}

		$info = ArenaDao::getByPosInRange($uid, $min, $max, array('uid'));
		return $info['uid'];
	}

	public static function getTop($offset, $limit, $arrField)
	{
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE) 
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		if (empty($arrField))
		{
			return array();
		}
		$arrPos = range($offset + 1, $offset + $limit);
		return ArenaDao::getArrByPos($arrPos, $arrField);
	}
	
	public static function getRankList($offset, $limit, $arrField)
	{
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		if (empty($arrField))
		{
			return array();
		}
		$rankList = array();
		$arrPos = range($offset + 1, $offset + $limit);
		foreach ($arrPos as $pos)
		{
			$rankList[$pos] = ArenaDao::getByPos($pos, $arrField);
		}
		return $rankList;
	}
	
	public static function readRewardActivityCSV()
	{
		return 'dummy';
	}
}

/**
 * 根据玩家竞技场排名区间，随机出真实玩家作为对手
 */
class EnArenaOpponent
{	
	/**
	 * 类型
	 * @var int
	 */
	protected $mType = NULL;
	
	/**
	 * 刷新偏移量
	 * @var int
	 */
	protected $mRefreshOffset = NULL;
	
	/**
	 * 构造函数
	 *
	 * @param int $type 	
	 * 
	 * @throws FakeException
	 *
	 */
	public function __construct($type)
	{
		if (isset(ArenaOpponentType::$offset[$type])) 
		{
			$this->mType = $type;
			$this->mRefreshOffset = ArenaOpponentType::$offset[$type];
		}
		else 
		{
			throw new FakeException('invalid EnArenaOpponent type:%d', $type);
		}
	}
	
	/**
	 * 根据区间随机获得区间中一个玩家的战斗数据
	 *
	 * @param array $arrRange 			需要选取对手的区间，区间应排序，且不重叠
	 * @param array $arrExceptedUid 	需要排除的uid数组，默认为空
	 *
	 * @throws FakeException
	 * 
	 * @return array
	 * [
	 * 		pos=>战斗数据
	 * ]
	 *
	 */
	public function getFmtByArrRange($arrRange, $arrExceptedUid = array())
	{
		Logger::trace('EnArenaOpponent.getFmtByArrRange param[arrRange:%s, arrExceptedUid:%s] begin...', $arrRange, $arrExceptedUid);
		
		$arrRange = self::checkArrRange($arrRange);
		$arrExceptedUid = self::checkArrUid($arrExceptedUid);
		
		// 为空，直接返回
		if (empty($arrRange)) 
		{
			Logger::trace('EnArenaOpponent.getFmtByArrRange empty range, return array()');
			return array();
		}
		
		// 得到排除的uid所在的排名位置
		$arrExceptedPos = self::getArrPosByArrUid($arrExceptedUid);
		Logger::trace('EnArenaOpponent.getFmtByArrRange arrExceptedPos:%s', $arrExceptedPos);
		
		// 从每个区间中随机出一个排名
		$arrPos = array();
		foreach ($arrRange as $range)
		{
			$range = range(intval($range[0]), intval($range[1]));
			$realRange = array_values(array_diff($range, $arrExceptedPos));
			if (empty($realRange))
			{
				Logger::warning('EnArenaOpponent.getFmtByArrRange real range is empty, range:%s arrExceptedUid:%s arrExcepedPos:%s', $range, $arrExceptedUid, $arrExceptedPos);
				continue;
			}
			
			$arrPos[] = $realRange[rand(0, count($realRange) - 1)];
		}
		$arrPos2Uid = self::getArrUidByArrPos($arrPos);
		Logger::trace('EnArenaOpponent.getFmtByArrRange rand pos:%s, pos2uid:%s', $arrPos, $arrPos2Uid);
		
		foreach ($arrPos as $aPos)
		{
			if (!isset($arrPos2Uid[$aPos])) 
			{
				throw new FakeException('EnArenaOpponent.getFmtByArrRange do not have uid in pos[%d]', $aPos);
			}
		}
		
		$arrRet = array();
		$arrUid2Fmt = self::getFmtByArrUid(array_values($arrPos2Uid));
		foreach ($arrPos2Uid as $aPos => $aUid)
		{
			if (isset($arrUid2Fmt[$aUid])) 
			{
				$arrRet[$aPos] = $arrUid2Fmt[$aUid];
			}
			else 
			{
				$arrRet[$aPos] = array();
				Logger::warning('EnArenaOpponent.getFmtByArrRange not found fmt of uid:%d, but in pos:%d', $aUid, $aPos);
			}
		}
		
		Logger::trace('EnArenaOpponent.getFmtByArrRange param[arrRange:%s, arrExceptedUid:%s] ret[arrRet:%s] end...', $arrRange, $arrExceptedUid, $arrRet);
		return $arrRet;
	}
	
	/**
	 * 根据uid获取玩家的战斗数据
	 *
	 * @param array $arrUid 			玩家uid数组
	 *
	 * @return array
	 * [
	 * 		uid=>战斗数据
	 * ]
	 *
	 */
	public function getFmtByArrUid($arrUid)
	{
		Logger::trace('EnArenaOpponent.getFmtByArrUid param[arrUid:%s] begin...', $arrUid);
		
		// 空，直接返回
		if (empty($arrUid))
		{
			Logger::trace('EnArenaOpponent.getFmtByArrUid empty arrUid, return array()', $arrUid);
			return array();
		}
		
		// 先从数据库中取这几个uid的战斗数据
		$arrCond = array
		(
				array('uid', 'IN', $arrUid),
				array('type', '=', $this->mType),
		);
		$arrField = array('uid', 'type', 'update_time', 'fight_force', 'va_fmt');
		$arrFmtInfo = ArenaDao::getArrFmtInfo($arrCond, $arrField);
		$arrFmtInfo = Util::arrayIndex($arrFmtInfo, 'uid');
		Logger::trace('EnArenaOpponent.getFmtByArrUid before refresh, info from db:%s', $arrFmtInfo);
		
		// 抽取出其中需要重新拉取战斗数据的uid
		$arrRefreshUid = array();
		$arrUpdateUid = array();
		foreach ($arrUid as $aUid)
		{
			//Npc不需要存在表里，需要重新拉取
			if (ArenaLogic::isNpc($aUid))
			{
				$arrRefreshUid[] = $aUid;
				Logger::trace('EnArenaOpponent.getFmtByArrUid uid:%d is Npc, need refresh', $aUid);
				continue;
			}
			
			// 表中没有这个玩家的战斗数据，需要重新拉取
			if (!isset($arrFmtInfo[$aUid]))
			{
				$arrRefreshUid[] = $aUid;
				Logger::trace('EnArenaOpponent.getFmtByArrUid uid:%d not in arrFmtInfo:%s, need refresh', $aUid, $arrFmtInfo);
				continue;
			}
				
			// 同一天内的战斗数据，不需要重新拉取
			$updateTime = $arrFmtInfo[$aUid]['update_time'];
			if (Util::isSameDay($updateTime, $this->mRefreshOffset))
			{
				Logger::trace('EnArenaOpponent.getFmtByArrUid uid:%d fmt info is same day, updateTime:%s, now:%s, offset:%d, no need refresh', $aUid, strftime('%Y%m%d-%H%M%S', $updateTime), strftime('%Y%m%d-%H%M%S', Util::getTime()), $this->mRefreshOffset);
				continue;
			}
				
			// 不在同一天的话，需要判断战斗力，如果玩家的战斗力变小，则不更新
			$fightForce = $arrFmtInfo[$aUid]['fight_force'];
			$currFightForce = EnUser::getUserObj($aUid)->getFightForce();
			if ($currFightForce < $fightForce)
			{
				$arrUpdateUid[] = $aUid;
				Logger::trace('EnArenaOpponent.getFmtByArrUid, not in same day, uid:%d fight force become smaller, curr fight force:%d, cache fight force:%d, no need refresh', $aUid, $currFightForce, $fightForce);
				continue;
			}
			
			$arrRefreshUid[] = $aUid;
			Logger::trace('EnArenaOpponent.getFmtByArrUid, not in same day, uid:%d fight force do not become smaller, curr fight force:%d, cache fight force:%d, need refresh', $aUid, $currFightForce, $fightForce);
		}
		Logger::trace('EnArenaOpponent.getFmtByArrUid before refresh, need refresh uid:%s', $arrRefreshUid);
		
		// 更新需要update的uid
		if (!empty($arrUpdateUid)) 
		{
			foreach ($arrUpdateUid as $aUid)
			{
				$arrUpdateCond = array
				(
						array('uid', '=', $aUid),
						array('type', '=', $this->mType),
				);
				$arrUpdateField = array
				(
						'update_time' => Util::getTime(),
				);
				ArenaDao::updateFmt($arrUpdateCond, $arrUpdateField);
			}
		}
		
		// 返回值
		$arrRet = Util::arrayIndexCol($arrFmtInfo, 'uid', 'va_fmt');
		
		// 重新获取战斗数据，并且更新表
		foreach ($arrRefreshUid as $aUid)
		{
			if (ArenaLogic::isNpc($aUid))
			{
				$battleFmt = EnFormation::getMonsterBattleFormation(ArenaLogic::getNpcArmyId($aUid));
				$battleFmt['uid'] = $aUid;
			}
			else
			{
				$aUserObj = EnUser::getUserObj($aUid);
				$battleFmt = $aUserObj->getBattleFormation();
				$fightForce = $aUserObj->getFightForce();
				
				$values = array
				(
						'uid' => $aUid,
						'type' => $this->mType,
						'update_time' => Util::getTime(),
						'fight_force' => $fightForce,
						'va_fmt' => $battleFmt,
				);
				ArenaDao::insertOrUpdateFmt($values);
			}
				
			$arrRet[$aUid] = $battleFmt;
		}
		
		Logger::trace('EnArenaOpponent.getFmtByArrUid param[arrUid:%s] ret[arrRet:%s]end...', $arrUid, $arrRet);
		return $arrRet;
	}
	
	private function checkArrRange($arrRange)
	{
		// 区间数组不能为空
		if (empty($arrRange))
		{
			throw new FakeException('EnArenaOpponent.checkArrRange empty arrRange');
		}
		
		// 检查区间有效性，区间从小到大，且不能重叠
		$i = 0;
		foreach ($arrRange as $range)
		{
			if (count($range) != 2
			|| intval($range[0]) <= $i
			|| intval($range[0]) > intval($range[1]))
			{
				throw new FakeException('EnArenaOpponent.checkArrRange invalid arrRange:%s', $arrRange);
			}
			else
			{
				$i = intval($range[1]);
			}
		}
		
		return $arrRange;
	}
	
	private function checkArrUid($arrUid)
	{
		$ret = array();
		foreach ($arrUid as $aUid)
		{
			if (is_numeric($aUid) && $aUid == intval($aUid) && intval($aUid) > 0)
			{
				$ret[] = intval($aUid);
			}
			else
			{
				throw new FakeException('EnArenaOpponent.checkArrUid invalid arrUid:%s', $arrUid);
			}
		}
		
		return $ret;
	}
	
	private function getArrUidByArrPos($arrPos)
	{
		$arrCond = array
		(
				array('position', 'IN', $arrPos),
				array('update_time', '!=', 0),
		);
		$arrField = array
		(
				'position', 
				'uid',
		);
		$arrPosInfo = ArenaDao::getArrHis($arrCond, $arrField);
		
		return Util::arrayIndexCol($arrPosInfo, 'position', 'uid');
	}
	
	private function getArrPosByArrUid($arrUid)
	{
		$arrCond = array
		(
				array('uid', 'IN', $arrUid),
				array('update_time', '!=', 0),
		);
		$arrField = array
		(
				'position',
				'uid',
		);
		$arrPosInfo = ArenaDao::getArrHis($arrCond, $arrField);
		
		return Util::arrayExtract($arrPosInfo, 'position');
	}
	
	private function getUidByPos($pos)
	{
		$arrPos2Uid = self::getArrUidByArrPos(array($pos));
		if (!empty($arrPos2Uid) && isset($arrPos2Uid[$pos])) 
		{
			return intval($arrPos2Uid[$pos]);
		}
		
		return 0;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */