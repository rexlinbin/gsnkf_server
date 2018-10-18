<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalUserObj.class.php 198237 2015-09-11 14:30:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalUserObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 14:30:41 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198237 $
 * @brief 
 *  
 **/
 
class WorldCarnivalCrossUserObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @param int $startTime 活动开始时间，用于控制刷新旧数据
	 * @param boolean $init
	 * @static
	 * @access public
	 * @return WorldCarnivalCrossUserObj
	*/
	public static function getInstance($serverId, $pid, $startTime, $init = TRUE)
	{
		$key = self::getKey($serverId, $pid);
		if (!isset(self::$sArrInstance[$key]))
		{
			self::$sArrInstance[$key] = new self($serverId, $pid, $startTime, $init);
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
	 * @param int $startTime 活动开始时间，用于控制刷新旧数据
	 * @param boolean $init
	 */
	private function __construct($serverId, $pid, $startTime, $init = TRUE)
	{
		$userInfo = $this->getInfo($serverId, $pid);
		if (empty($userInfo))
		{
			if ($init)
			{
				$userInfo = $this->createInfo($serverId, $pid);
			}
			else
			{
				throw new FakeException("can not init, serverId[%d], pid[%d].", $serverId, $pid);
			}
		}

		$this->mObj = $userInfo;
		$this->mObjModify = $userInfo;
		$this->refresh($startTime);
	}

	/**
	 * 从db中获取数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @return array
	 */
	public function getInfo($serverId, $pid)
	{
		$arrCond = array
		(
				array(WorldCarnivalCrossUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldCarnivalCrossUserField::TBL_FIELD_PID, '=', $pid),
		);
		$arrField = WorldCarnivalCrossUserField::$ALL_FIELDS;

		return WorldCarnivalDao::selectCrossUser($arrCond, $arrField);
	}

	/**
	 * 如果第一次进入，需要插入初始化数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @return array
	 */
	public function createInfo($serverId, $pid)
	{
		$initInfo = $this->getInitInfo($serverId, $pid);
		WorldCarnivalDao::insertCrossUser($initInfo);

		return $initInfo;
	}

	/**
	 * 周期切换时候，需要刷新数据
	 * 
	 * @param int $startTime 活动开始时间，用于控制刷新旧数据
	 */
	public function refresh($startTime)
	{
		if ($this->getUpdateTime() < $startTime)
		{
			$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_RANK] = WorldCarnivalConf::FIGHTER_COUNT;
			$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES] = 0;
			$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME] = time();
			$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA] = array(WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE => array());
		}
	}

	/**
	 * db里没数据时候，生成一份初始化数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @return array
	 */
	public function getInitInfo($serverId, $pid)
	{
		$initInfo = array
		(
				WorldCarnivalCrossUserField::TBL_FIELD_SERVER_ID => $serverId,
				WorldCarnivalCrossUserField::TBL_FIELD_PID => $pid,
				WorldCarnivalCrossUserField::TBL_FIELD_RANK => WorldCarnivalConf::FIGHTER_COUNT,
				WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES => 0,
				WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME => time(),
				WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA => array(WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE => array()),
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
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_SERVER_ID];
	}

	/**
	 * 获得pid
	 *
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_PID];
	}	
	
	/**
	 * 获得玩家的排名
	 * 
	 * @return int
	 */
	public function getRank()
	{
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_RANK];
	}
	
	/**
	 * 设置玩家的排名
	 * 
	 * @param int $rank
	 */
	public function setRank($rank)
	{
		$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_RANK] = $rank;
	}
	
	/**
	 * 获得玩家失败的次数
	 * 
	 * @return int
	 */
	public function getLoseTimes()
	{
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES];
	}
	
	/**
	 * 新一大轮开始，将失败次数设置为0
	 */
	public function resetLoseTimes()
	{
		$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES] = 0;
	}
	
	/**
	 * 增加一次玩家失败的次数
	 */
	public function increLoseTimes()
	{
		++$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES];
	}
	
	/**
	 * 强制设置失败次数，只用于脚本强制修复数据用
	 * 
	 * @param number $loseTimes
	 */
	public function setLoseTimesForTest($loseTimes)
	{
		$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_LOSE_TIMES] = $loseTimes;
	}
	
	/**
	 * 获取玩家保存的战斗力
	 * 
	 * @return int
	 */
	public function getFightForce()
	{ 
		$battleFmt = $this->getFmt();
		if (!isset($battleFmt['fightForce'])) 
		{
			Logger::warning('cat not get battle fmt');
			return 0;
		}
		return $battleFmt['fightForce'];
	}
	
	/**
	 * 获得玩家保存的战斗数据
	 * 
	 * @return array
	 */
	public function getFmt()
	{
		if (empty($this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA][WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE])) 
		{
			$battleFmt = array();
			try
			{
				$group = Util::getGroupByServerId($this->getServerId());
				$proxy = new ServerProxy();
				$proxy->init($group, Util::genLogId());
				$battleFmt = $proxy->syncExecuteRequest('worldcarnival.getBattleFmt', array($this->getServerId(), $this->getPid()));
			}
			catch (Exception $e)
			{
				Logger::fatal('getBattleFmt error serverGroup:%s', $this->getServerId());
				$battleFmt = array();
			}
			$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA][WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE] = $battleFmt;
		}
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA][WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE];
	}
	
	/**
	 * 更新玩家的战斗信息
	 * 
	 * @param array $formation
	 */
	public function updateFmt($formation)
	{
		$this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_VA_EXTRA][WorldCarnivalCrossUserField::TBL_VA_EXTRA_BATTLE] = $formation;
	}
	
	/**
	 * 获得玩家最后的操作时间
	 * 
	 * @return int
	 */
	public function getUpdateTime()
	{ 
		return $this->mObjModify[WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME];
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
		
		if (!isset($arrUpdate[WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldCarnivalCrossUserField::TBL_FIELD_UPDATE_TIME] = time();
		}
		
		$arrCond = array
		(
				array(WorldCarnivalCrossUserField::TBL_FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldCarnivalCrossUserField::TBL_FIELD_PID, '=', $this->getPid()),
		);
		
		WorldCarnivalDao::updateCrossUser($arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */