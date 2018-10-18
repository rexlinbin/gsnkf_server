<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaCrossUserObj.class.php 244613 2016-05-30 06:49:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaCrossUserObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-30 06:49:52 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244613 $
 * @brief 
 *  
 **/
 
class WorldArenaCrossUserObj
{
	private static $sArrInstance = array();
	private $mTeamId = 0;
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @param int $uid 玩家uid
	 * @param int $teamId 玩家所在分组id
	 * @param boolean $init db没数据的话是否初始化
	 * @static
	 * @access public
	 * @return WorldArenaCrossUserObj
	*/
	public static function getInstance($serverId, $pid, $uid, $teamId, $init = TRUE)
	{
		$key = self::getKey($serverId, $pid);
		if (!isset(self::$sArrInstance[$key]))
		{
			self::$sArrInstance[$key] = new self($serverId, $pid, $uid, $teamId, $init);
		}

		return self::$sArrInstance[$key];
	}

	/**
	 * 释放实例
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 */
	public static function releaseInstance($serverId, $pid)
	{
		$key = self::getKey($serverId, $pid);
		if (isset(self::$sArrInstance[$key]))
		{
			unset(self::$sArrInstance[$key]);
		}
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

	/**
	 * 构造函数
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @param int $uid 玩家uid
	 * @param int $teamId 玩家所在分组id
	 * @param boolean $init db没数据的话是否初始化
	 */
	private function __construct($serverId, $pid, $uid, $teamId, $init)
	{
		$userInfo = $this->getInfo($serverId, $pid, $teamId);
		if (empty($userInfo))
		{
			if ($init) 
			{
				$userInfo = $this->createInfo($serverId, $pid, $uid, $teamId);
			}
			else 
			{
				throw new FakeException('no cross user info, serverId[%d], pid[%d], uid[%d], teamId[%d]', $serverId, $pid, $uid, $teamId);
			}
		}

		$this->mTeamId = $teamId;
		$this->mObj = $userInfo;
		$this->mObjModify = $userInfo;
		$this->refresh();
	}

	/**
	 * 从db中获取数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param int $teamId
	 * @return array
	 */
	public function getInfo($serverId, $pid, $teamId)
	{
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $pid),
		);
		$arrField = WorldArenaCrossUserField::$ALL_FIELDS;

		return WorldArenaDao::selectCrossUser($teamId, $arrCond, $arrField);
	}

	/**
	 * 如果第一次进入，需要插入初始化数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @param int $teamId
	 * @return array
	 */
	public function createInfo($serverId, $pid, $uid, $teamId)
	{
		$initInfo = $this->getInitInfo($serverId, $pid, $uid);
		WorldArenaDao::insertCrossUser($teamId, $initInfo);

		return $initInfo;
	}

	/**
	 * 周期切换时候，需要刷新数据
	 */
	public function refresh()
	{
		$confObj = WorldArenaConfObj::getInstance();
		if ($this->getUpdateTime() < $confObj->getPeriodBgnTime())
		{
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_ROOM_ID] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_POS] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME] = 0;
			$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
	}

	/**
	 * db没有数据时候的初始化数据
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @return array
	 */
	public function getInitInfo($serverId, $pid, $uid)
	{
		$initInfo = array
		(
				WorldArenaCrossUserField::TBL_FIELD_PID => $pid,
				WorldArenaCrossUserField::TBL_FIELD_SERVER_ID => $serverId,
				WorldArenaCrossUserField::TBL_FIELD_ROOM_ID => 0,
				WorldArenaCrossUserField::TBL_FIELD_UID => $uid,
				WorldArenaCrossUserField::TBL_FIELD_UNAME => '',
				WorldArenaCrossUserField::TBL_FIELD_VIP => 0,
				WorldArenaCrossUserField::TBL_FIELD_LEVEL => 0,
				WorldArenaCrossUserField::TBL_FIELD_HTID => 0,
				WorldArenaCrossUserField::TBL_FIELD_TITLE => 0,
				WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE => 0,
				WorldArenaCrossUserField::TBL_FIELD_POS => 0,
				WorldArenaCrossUserField::TBL_FIELD_KILL_NUM => 0,
				WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM => 0,
				WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM => 0,
				WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME => 0,
				WorldArenaCrossUserField::TBL_FIELD_POS_REWARD_TIME => 0,
				WorldArenaCrossUserField::TBL_FIELD_KILL_REWARD_TIME => 0,
				WorldArenaCrossUserField::TBL_FIELD_CONTI_REWARD_TIME => 0,
				WorldArenaCrossUserField::TBL_FIELD_KING_REWARD_TIME => 0,
				WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA => array(WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS => array()),
		);

		return $initInfo;
	}

	/**
	 * 获得serverId
	 *
	 * @return int
	 */
	public function getServerId()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
	}

	/**
	 * 获得pid
	 *
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PID];
	}

	/**
	 * 获得uid
	 *
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_UID];
	}
	
	/**
	 * 获得uname
	 * 
	 * @return string
	 */
	public function getUname()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_UNAME];
	}
	
	/**
	 * 返回vip
	 * 
	 * @return int
	 */
	public function getVip()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_VIP];
	}
	
	public function getTitle()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_TITLE];
	}
	
	/**
	 * 返回level
	 * 
	 * @return int
	 */
	public function getLevel()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_LEVEL];
	}
	
	/**
	 * 返回htid
	 *
	 * @return int
	 */
	public function getHtid()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_HTID];
	}
	
	/**
	 * 返回战力
	 *
	 * @return int
	 */
	public function getFightForce()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE];
	}
	
	/**
	 * 获得三榜奖励的时间：如三榜都是第一的奖励
	 * 
	 * @return number
	 */
	public function getKingRewardTime()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KING_REWARD_TIME];
	}
	
	/**
	 * 设置三榜奖励的时间：如三榜都是第一的奖励
	 * 
	 * @param number $time
	 */
	public function setKingRewardTime($time)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KING_REWARD_TIME] = $time;
	}
	
	/**
	 * 返回dress
	 *
	 * @return array
	 */
	public function getDress()
	{
		if (empty($this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS])) 
		{
			return array();
		}
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS];
	}
	
	/**
	 * 获得所在房间id
	 * 
	 * @return int
	 */
	public function getRoomId()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_ROOM_ID];
	}
	
	/**
	 * 获得玩家的总击杀数
	 * 
	 * @return int
	 */
	public function getKillNum()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM];
	}
	
	/**
	 * 获得玩家当前的连杀次数
	 * 
	 * @return int
	 */
	public function getCurContiNum()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM];
	}
	
	/**
	 * 如果玩家赢了，需要增加击杀数，增加当前连杀次数，设置保护时间
	 * 
	 * @param $isAttacker 是否是主动攻击胜利
	 */
	public function win($isAttacker = TRUE)
	{
		if ($isAttacker) 
		{
			// 判断如果当前的连杀次数超过了历史连杀次数，还需要更新历史连杀次数
			++$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM];
			if ($this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM] > $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM])
			{
				$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM] = $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM];
			}
			
			// 增加击杀数
			++$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM];
		}
		
		// 设置保护时间
		$confObj = WorldArenaConfObj::getInstance();
		$protectTime = $confObj->getProtectTime();
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME] = Util::getTime() + $protectTime;
	}
	
	/**
	 * 玩家失败以后需要将连杀归0，设置保护时间
	 * 
	 * @param $isAttacker 是否是主动攻击失败
	 * @return int
	 */
	public function lose($isAttacker = TRUE)
	{
		// 将玩家的当前连续击杀次数返回, 设置玩家的当前连杀次数为0
		$ret = $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM];
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM] = 0;
		
		// 设置保护时间
		$confObj = WorldArenaConfObj::getInstance();
		$protectTime = $confObj->getProtectTime();
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME] = Util::getTime() + $protectTime;
		
		return $ret;
	}
	
	/**
	 * 获得玩家最大的连杀次数
	 * 
	 * @return int
	 */
	public function getMaxContiNum()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM];
	}
	
	/**
	 * 更新uname,vip等，但不更新战斗力
	 * 
	 * @param string $uname
	 * @param int $vip
	 * @param int $level
	 * @param int $htid
	 * @param array $dress
	 */	
	public function updateBasicInfo($uname, $vip, $level, $htid, $dress, $title)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_UNAME] = $uname;
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_VIP] = $vip;
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_LEVEL] = $level;
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_HTID] = $htid;
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_TITLE] = $title;
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_VA_EXTRA][WorldArenaCrossUserField::TBL_VA_EXTRA_DRESS] = $dress;
	}
	
	/**
	 * 更新跨服数据的战斗力
	 * 
	 * @param int $fightForce
	 */
	public function updateFightForce($fightForce)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_FIGHT_FORCE] = $fightForce;
	}
	
	/**
	 * 返回玩家的保护时间
	 * 
	 * @return int
	 */
	public function getProtectTime()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME];
	}
	
	/**
	 * 获得玩家的排序键值
	 * 
	 * @return int
	 */
	public function getPos()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_POS];
	}
	
	/**
	 *  设置玩家的排序键值，一般是主动打高于自己排名的人赢啦
	 *  
	 * @param int $pos
	 */
	public function setPos($pos)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_POS] = $pos;
	}
	
	/**
	 *  获得玩家实际的排名，需要根据pos到数据库查询这个房间所有pos小于玩家当前pos的个数
	 *  这个接口没调用一次，都会产生一个db请求
	 */
	public function getRank()
	{
		$confObj = WorldArenaConfObj::getInstance();
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_ROOM_ID, '=', $this->getRoomId()),
				array(WorldArenaCrossUserField::TBL_FIELD_POS, 'BETWEEN', array(1, $this->getPos() - 1)),
				//array(WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME, '>=', $confObj->getSignupBgnTime()),
		);
		return WorldArenaDao::selectCrossUserCount($this->mTeamId, $arrCond) + 1;
	}
	
	/**
	 * 根据玩家的真实排名，获取目标对手的真实排名，然后获取目标对手的跨服信息
	 *
	 * @param boolean $includeSelf
	 * @return array
	 */
	public function getEnemyInfo($includeSelf = TRUE)
	{
		$myRank = $this->getRank();
		$arrTargetRank = WorldArenaUtil::getTargetRank($myRank);
		$arrTargetInfo = WorldArenaUtil::getPlayerInfoByArrRank($this->mTeamId, $this->getRoomId(), $arrTargetRank);
		if ($includeSelf) 
		{
			$arrTargetInfo[$myRank] = $this->mObjModify;
		}
		
		return $arrTargetInfo;
	}

	/**
	 * 获得最后更新时间
	 *
	 * @return int
	 */
	public function getUpdateTime()
	{
		return $this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME];
	}
	
	/**
	 * 更新之
	 */
	public function update()
	{
		if ($this->mObjModify == $this->mObj)
		{
			return;
		}
		
		$arrUpdate = array();
		foreach ($this->mObjModify as $key => $info)
		{
			if($info != $this->mObj[$key])
			{
				$arrUpdate[$key] = $info;
			}
		}
		
		if (empty($arrUpdate))
		{
			return;
		}
		
		if (!isset($arrUpdate[WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldArenaCrossUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(WorldArenaCrossUserField::TBL_FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldArenaCrossUserField::TBL_FIELD_PID, '=', $this->getPid()),
		);
		
		WorldArenaDao::updateCrossUser($this->mTeamId, $arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setProtectTimeForConsole($time)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_PROTECT_TIME] = $time; 
	}
	
	public function setKillNumForConsole($num)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_KILL_NUM] = $num;
	}
	
	public function setCurContiNumForConsole($num)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_CUR_CONTI_NUM] = $num;
	}
	
	public function setMaxContiNumForConsole($num)
	{
		$this->mObjModify[WorldArenaCrossUserField::TBL_FIELD_MAX_CONTI_NUM] = $num;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */