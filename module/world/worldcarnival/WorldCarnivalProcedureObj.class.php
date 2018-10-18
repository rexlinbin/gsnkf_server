<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalProcedureObj.class.php 198218 2015-09-11 12:19:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalProcedureObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 12:19:52 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198218 $
 * @brief 
 *  
 **/
 
class WorldCarnivalProcedureObj
{
	/**
	 * 唯一实例
	 * @var WorldCarnivalProcedureObj
	 */
	private static $sInstance;

	/**
	 * 届
	 * @var int
	 */
	private $mSession;
	
	/**
	 * 当前大轮次
	 * @var int
	 */
	private $mCurRound;
	
	/**
	 * 当前届所有round的数据，按round升序排列
	 * @var array
	 */
	private $mArrRoundData;
	
	/**
	 * 数据备份
	 * @var array
	 */
	private $mArrRoundDataBack;

	/**
	 * 获取实例
	 *
	 * @param int $session
	 * @param int $startTime
	 * @throws InterException
	 * @return WorldCarnivalProcedureObj
	 */
	public static function getInstance($session, $startTime)
	{
		if(empty(self::$sInstance))
		{
			self::$sInstance = new self($session, $startTime);
		}
		else
		{
			if(self::$sInstance->getSession() != $session)
			{
				throw new InterException('already set session:%d, cant change to session:%d', self::$gInstance->getSession(), $session);
			}
		}
		return self::$sInstance;
	}

	/**
	 * 释放实例
	 *
	 * @param int $session
	 */
	public static function releaseInstance($session)
	{
		unset(self::$sInstance);
	}

	/**
	 * 构造函数
	 *
	 * @param int $session
	 * @param int $startTime
	 */
	private function __construct($session, $startTime)
	{
		$this->mSession = $session;
		
		$this->mArrRoundData = WorldCarnivalDao::getRoundData($session, $startTime);
		if (empty($this->mArrRoundData)) 
		{
			$initRoundData = $this->initRoundData(1);
			$this->mArrRoundData = array(1 => $initRoundData);
		}
		$this->mArrRoundDataBack = $this->mArrRoundData;
		
		// 获得当前大轮次
		$maxRound = 0;
		foreach ($this->mArrRoundData as $round => $roundData)
		{
			if ($round > $maxRound) 
			{
				$maxRound = $round;
			}
		}
		if (empty($maxRound)) 
		{
			throw new InterException('impossible');
		}
		$this->mCurRound = $maxRound;
	}
	
	/**
	 * 获得某个大轮次的初始化数据
	 * 
	 * @param int $round
	 * @return array
	 */
	public function initRoundData($round)
	{
		$initRoundData = array
		(
				WorldCarnivalProcedureField::TBL_FIELD_SESSION => $this->mSession,
				WorldCarnivalProcedureField::TBL_FIELD_ROUND => $round,
				WorldCarnivalProcedureField::TBL_FIELD_STATUS => WorldCarnivalProcedureStatus::FIGHTING,
				WorldCarnivalProcedureField::TBL_FIELD_SUB_ROUND => 1,
				WorldCarnivalProcedureField::TBL_FIELD_SUB_STATUS => WorldCarnivalProcedureSubStatus::FIGHTING,
				WorldCarnivalProcedureField::TBL_FIELD_UPDATE_TIME => time(),
				WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA => array(WorldCarnivalProcedureField::TBL_VA_EXTRA_RECORD => array()),
		);
		WorldCarnivalDao::updateRoundData($initRoundData);
		
		return $initRoundData;
	}

	/**
	 * 获得届数
	 *
	 * @return int
	 */
	public function getSession()
	{
		return $this->mSession;
	}
	
	/**
	 * 获得当前大轮次
	 * 
	 * @return int
	 */
	public function getCurRound()
	{
		return $this->mCurRound;
	}
	
	/**
	 * 获得当前大轮次的状态
	 * 
	 * @return int
	 */
	public function getCurStatus()
	{
		return $this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_STATUS];
	}
	
	/**
	 * 获得当前小轮次
	 * 
	 * @return int
	 */
	public function getCurSubRound()
	{
		return $this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_SUB_ROUND];
	}
	
	/**
	 * 获得当前小轮次状态
	 * 
	 * @return int
	 */
	public function getCurSubStatus()
	{
		return $this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_SUB_STATUS];
	}
	
	/**
	 * 获得某个大轮次的所有战报和结果
	 *
	 * @param number $round
	 * @return array
	 */
	public function getBattleRecord($round)
	{
		if (empty($this->mArrRoundData[$round][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_RECORD]))
		{
			return array();
		}
	
		return $this->mArrRoundData[$round][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_RECORD];
	}
	
	/**
	 * 增加当前大轮次下某个小轮次的战报
	 * 
	 * @param number $subRound
	 * @param number $attackerPos
	 * @param number $defenderPos
	 * @param number $result
	 * @param number $brid
	 */
	public function addBattleRecord($subRound, $attackerPos, $defenderPos, $result, $brid)
	{
		$record = array
		(
				'attacker_pos' => $attackerPos,
				'defender_pos' => $defenderPos,
				'result' => $result,
				'brid' => $brid,
		);
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_RECORD][$subRound] = $record;
	}
	
	/**
	 * 这里直接插入db，然后更新back
	 * 
	 * @param number $nextRound
	 */
	public function initNextRound($nextRound)
	{
		$initRoundData = $this->initRoundData($nextRound);
		$this->mArrRoundData[$nextRound] = $initRoundData;
		$this->mArrRoundDataBack = $this->mArrRoundData;
		$this->mCurRound = $nextRound;
	}
	
	/**
	 * 初始化下一个小轮次，这里直接同步db，然后更新back
	 * 
	 * @param number $nextSubRound
	 */
	public function initNextSubRound($nextSubRound)
	{
		$this->setSubRoundStatus($nextSubRound, WorldCarnivalProcedureSubStatus::FIGHTING);
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_UPDATE_TIME] = time();
		WorldCarnivalDao::updateRoundData($this->mArrRoundData[$this->mCurRound]);
		$this->mArrRoundDataBack = $this->mArrRoundData;
	}
	
	/**
	 * 设置当前大轮的大轮状态
	 * 
	 * @param number $status
	 */
	public function setRoundStatus($status)
	{
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_STATUS] = $status;
	}
	
	/**
	 * 设置当前小轮和小轮状态
	 * 
	 * @param number $subRound
	 * @param number $subStatus
	 */
	public function setSubRoundStatus($subRound, $subStatus)
	{
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_SUB_ROUND] = $subRound;
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_SUB_STATUS] = $subStatus;
	}
	
	/**
	 * 获取下场战斗时间
	 * 
	 * @param int $beginTime
	 * @param int $normalPeriod
	 * @param int $finalPeriod
	 * @return int
	 */
	public function getNextFightTime($beginTime, $normalPeriod, $finalPeriod)
	{
		$curRound = $this->getCurRound();
		$curStatus = $this->getCurStatus();
		$curSubRound = $this->getCurSubRound();
		$curSubStatus = $this->getCurSubStatus();
		
		// 如果比赛还没有 开始，返回开始时间
		if ($curSubRound == 1 
			&& $curSubStatus == WorldCarnivalProcedureSubStatus::FIGHTING
			&& $curRound == WorldCarnivalRound::ROUND_1) 
		{
			Logger::info('not begin, return begin time[%s]', strftime('%Y%m%d %H%M%S', $beginTime));
			return $beginTime;
		}
		
		// 获得上次战斗的时间
		$lastFightTime = 0;
		if ($curSubStatus == WorldCarnivalProcedureSubStatus::FIGHTEND)//当前小轮已经打完啦，用当前小轮的战斗结束时间 
		{
			$lastFightTime = $this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_FIGHT_TIME][$curSubRound];
			Logger::info('lastFightTime set as cur sub round fight time[%s]', strftime('%Y%m%d %H%M%S', $lastFightTime));
		}
		else if ($curSubRound > 1)//当前小轮没打完，但不是第一个小轮，则用上一个小轮的战斗结束时间
		{
			$lastFightTime = $this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_FIGHT_TIME][$curSubRound - 1];
			Logger::info('lastFightTime set as last sub round fight time[%s]', strftime('%Y%m%d %H%M%S', $lastFightTime));
		}
		else//用上一个大轮的结束时间
		{
			$arrLastRoundFightTime = $this->mArrRoundData[$this->mCurRound - 1][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_FIGHT_TIME];
			$lastSubRound = max(array_keys($arrLastRoundFightTime));
			$lastFightTime = $arrLastRoundFightTime[$lastSubRound];
			Logger::info('lastFightTime set as last round fight time[%s]', strftime('%Y%m%d %H%M%S', $lastFightTime));
		}
		
		// 如果是等待决赛，则需要用决赛的间隔
		if ($curRound == WorldCarnivalRound::ROUND_2 
			&& $curStatus == WorldCarnivalProcedureStatus::FIGHTEND) 
		{
			return $lastFightTime + $finalPeriod;
		}
		
		// 用普通的间隔
		return $lastFightTime + $normalPeriod;
	}
	
	/**
	 * 设置小轮的战斗时间
	 * 
	 * @param int $subRound
	 * @param int $time
	 */
	public function setFightTime($subRound, $time)
	{
		$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_VA_EXTRA][WorldCarnivalProcedureField::TBL_VA_EXTRA_FIGHT_TIME][$subRound] = $time;
	}
	
	/**
	 * 更新之
	 */
	public function update()
	{		
		if ($this->mArrRoundData[$this->mCurRound] != $this->mArrRoundDataBack[$this->mCurRound]) 
		{
			$this->mArrRoundData[$this->mCurRound][WorldCarnivalProcedureField::TBL_FIELD_UPDATE_TIME] = time();
			WorldCarnivalDao::updateRoundData($this->mArrRoundData[$this->mCurRound]);
		}
		$this->mArrRoundDataBack = $this->mArrRoundData;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */