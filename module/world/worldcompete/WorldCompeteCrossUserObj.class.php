<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteCrossUserObj.class.php 241116 2016-05-05 07:30:49Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompeteCrossUserObj.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 07:30:49 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241116 $
 * @brief 
 *  
 **/
 
class WorldCompeteCrossUserObj
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
	 * @return WorldCompeteCrossUserObj
	*/
	public static function getInstance($serverId, $pid, $uid, $teamId, $init = FALSE)
	{
		$key = WorldCompeteUtil::getKey($serverId, $pid);
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
		$key = WorldCompeteUtil::getKey($serverId, $pid);
		if (isset(self::$sArrInstance[$key]))
		{
			unset(self::$sArrInstance[$key]);
		}
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
		$info = $this->getInfo($serverId, $pid, $teamId);
		if (empty($info))
		{
			$info = $this->createInfo($serverId, $pid, $uid, $teamId, $init);
		}

		$this->mTeamId = $teamId;
		$this->mObj = $info;
		$this->mObjModify = $info;
		$this->refresh($uid);
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
				array(WorldCompeteCrossUserField::FIELD_TEAM_ID, '=', $teamId),
				array(WorldCompeteCrossUserField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteCrossUserField::FIELD_PID, '=', $pid),
		);
		$arrField = WorldCompeteCrossUserField::$ALL_FIELDS;

		return WorldCompeteDao::selectCrossUser($teamId, $arrCond, $arrField);
	}

	/**
	 * 如果第一次进入，需要插入初始化数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @param int $teamId
	 * @param boolean $init db没数据的话是否初始化
	 * @return array
	 */
	public function createInfo($serverId, $pid, $uid, $teamId, $init)
	{
		$user = EnUser::getUserObj($uid);
		$initInfo = array
		(
				WorldCompeteCrossUserField::FIELD_TEAM_ID => $teamId,
				WorldCompeteCrossUserField::FIELD_PID => $pid,
				WorldCompeteCrossUserField::FIELD_SERVER_ID => $serverId,
				WorldCompeteCrossUserField::FIELD_UID => $uid,
				WorldCompeteCrossUserField::FIELD_UNAME => $user->getUname(),
				WorldCompeteCrossUserField::FIELD_VIP => $user->getVip(),
				WorldCompeteCrossUserField::FIELD_LEVEL => $user->getLevel(),
				WorldCompeteCrossUserField::FIELD_HTID => $user->getHeroManager()->getMasterHeroObj()->getHtid(),
				WorldCompeteCrossUserField::FIELD_TITLE => $user->getTitle(),
				WorldCompeteCrossUserField::FIELD_FIGHT_FORCE => $user->getFightForce(),
				WorldCompeteCrossUserField::FIELD_MAX_HONOR => 0,
				WorldCompeteCrossUserField::FIELD_UPDATE_TIME => Util::getTime(),
				WorldCompeteCrossUserField::FIELD_VA_EXTRA => array(WorldCompeteCrossUserField::DRESS => $user->getDressInfo()),
				WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION => array(WorldCompeteCrossUserField::FORMATION => $user->getBattleFormation()),
		);
		
		if ($init) 
		{
			WorldCompeteDao::insertCrossUser($teamId, $initInfo);
		}

		return $initInfo;
	}

	/**
	 * 周期切换时候，需要刷新数据
	 */
	public function refresh($uid)
	{
		//每周刷新
		if (!WorldCompeteUtil::inSamePeriod($this->getUpdateTime())) 
		{
			$this->mObjModify[WorldCompeteCrossUserField::FIELD_MAX_HONOR] = 0;
			$this->mObjModify[WorldCompeteCrossUserField::FIELD_UPDATE_TIME] = Util::getTime();
			if (!empty($uid)) 
			{
				$user = EnUser::getUserObj($uid);
				$this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION] = 
					array(WorldCompeteCrossUserField::FORMATION => $user->getBattleFormation());
			}
			
		}
	}
	
	/**
	 * 更新用户信息
	 */
	public function refreshUserInfo($uid)
	{
		$user = EnUser::getUserObj($uid);
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_UID] = $uid;
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_UNAME] = $user->getUname();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_VIP] = $user->getVip();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_LEVEL] = $user->getLevel();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_HTID] = $user->getHeroManager()->getMasterHeroObj()->getHtid();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_TITLE] = $user->getTitle();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_FIGHT_FORCE] = $user->getFightForce();
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_EXTRA] = array(WorldCompeteCrossUserField::DRESS => $user->getDressInfo());
		
	}

	/**
	 * 获得serverId
	 *
	 * @return int
	 */
	public function getServerId()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_SERVER_ID];
	}

	/**
	 * 获得pid
	 *
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_PID];
	}

	/**
	 * 获得uid
	 *
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_UID];
	}
	
	/**
	 * 获得uname
	 * 
	 * @return string
	 */
	public function getUname()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_UNAME];
	}
	
	/**
	 * 获得vip
	 * 
	 * @return int
	 */
	public function getVip()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_VIP];
	}
	
	/**
	 * 获得level
	 * 
	 * @return int
	 */
	public function getLevel()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_LEVEL];
	}
	
	/**
	 * 获得htid
	 *
	 * @return int
	 */
	public function getHtid()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_HTID];
	}
	
	/**
	 * 获得title
	 *
	 * @return int
	 */
	public function getTitle()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_TITLE];
	}
	
	/**
	 * 获得战力
	 *
	 * @return int
	 */
	public function getFightForce()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_FIGHT_FORCE];
	}
	
	/**
	 * 获得dress
	 *
	 * @return array
	 */
	public function getDress()
	{
		if (!isset($this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS])) 
		{
			return array();
		}
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS];
	}
	
	/**
	 * 获得max_honor
	 * 
	 * @return int
	 */
	public function getMaxHonor()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_MAX_HONOR];
	}
	
	/**
	 * 设置max_honor
	 * 
	 * @param int $num
	 */
	public function setMaxHonor($num)
	{
		$this->mObjModify[WorldCompeteCrossUserField::FIELD_MAX_HONOR] = $num;
	}
	
	/**
	 * 获得update_time
	 *
	 * @return int
	 */
	public function getUpdateTime()
	{
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_UPDATE_TIME];
	}
	
	/**
	 * 获得formation
	 * 
	 * @return array
	 */
	public function getBattleFormation()
	{
		if (!isset($this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION][WorldCompeteCrossUserField::FORMATION]))
		{
			return array();
		}
		return $this->mObjModify[WorldCompeteCrossUserField::FIELD_VA_BATTLE_FORMATION][WorldCompeteCrossUserField::FORMATION];
	}
	
	/**
	 *  获得玩家实际的排名，荣誉值>荣誉时间>uid
	 *  
	 *  @return int
	 */
	public function getRank()
	{
		//大于用户荣誉值的人数
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '>', $this->getMaxHonor()),
				array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
		);
		$rank = WorldCompeteDao::getCrossCount($this->mTeamId, $arrCond);
		//等于用户荣誉值，小于用户时间的人数
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_MAX_HONOR, '=', $this->getMaxHonor()),
				array(WorldCompeteCrossUserField::FIELD_UPDATE_TIME, 'BETWEEN', array(WorldCompeteUtil::activityBeginTime(), $this->getUpdateTime() - 1)),
		);
		$rank += WorldCompeteDao::getCrossCount($this->mTeamId, $arrCond) + 1;
		//荣誉和时间都相同，就不必啦，没有太大必要，而且跨服库上尽量少一些sql
		return $rank;
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
		
		if (!isset($arrUpdate[WorldCompeteCrossUserField::FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldCompeteCrossUserField::FIELD_UPDATE_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(WorldCompeteCrossUserField::FIELD_TEAM_ID, '=', $this->mTeamId),
				array(WorldCompeteCrossUserField::FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldCompeteCrossUserField::FIELD_PID, '=', $this->getPid()),
		);
		
		WorldCompeteDao::updateCrossUser($this->mTeamId, $arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setMaxHonorForConsole($num)
	{
		$this->setMaxHonor($num);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */