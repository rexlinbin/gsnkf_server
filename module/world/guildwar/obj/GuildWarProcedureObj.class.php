<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarProcedureObj.class.php 155873 2015-01-29 02:43:33Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/obj/GuildWarProcedureObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-29 02:43:33 +0000 (Thu, 29 Jan 2015) $
 * @version $Revision: 155873 $
 * @brief 
 *  
 **/
 
class GuildWarTeamRound
{
	/**
	 * 数据
	 * @var array
	 */
	private $mData;
	
	/**
	 * 备份
	 * @var array
	 */
	private $mBack;

	/**
	 * 构造函数
	 * 
	 * @param array $data
	 */
	public function __construct( $data)
	{
		$this->mData = $data;
		$this->mBack = $data;
	}

	/**
	 * 获得数据
	 * 
	 * @return array:
	 */
	public function getData()
	{
		return $this->mData;
	}
	
	/**
	 * 设置数据
	 * 
	 * @param array
	 */
	public function setData($data)
	{
		$this->mData = $data;
	}

	/**
	 * 获取轮次状态
	 * 
	 * @return int
	 */
	public function getStatus()
	{
		return $this->mData[GuildWarProcedureField::TBL_FIELD_STATUS];
	}

	/**
	 * 设置轮次状态
	 * 
	 * @param int $status
	 */
	public function setStatus($status)
	{
		$this->mData[GuildWarProcedureField::TBL_FIELD_STATUS] = $status;
	}
	
	/**
	 * 获取当前轮次所处小轮
	 * 
	 * @return int
	 */
	public function getSubRound()
	{
		return $this->mData[GuildWarProcedureField::TBL_FIELD_SUB_ROUND];
	}
	
	/**
	 * 设置当前轮次所处小轮
	 * 
	 * @param int
	 */
	public function setSubRound($subRound)
	{
		$this->mData[GuildWarProcedureField::TBL_FIELD_SUB_ROUND] = $subRound;
	}
	
	/**
	 * 获得当前小轮状态
	 * 
	 * @return int
	 */
	public function getSubStatus()
	{
		return $this->mData[GuildWarProcedureField::TBL_FIELD_SUB_STATUS];
	}
	
	/**
	 * 设置当前小轮状态
	 * 
	 * @param int
	 */
	public function setSubStatus($subStatus)
	{
		$this->mData[GuildWarProcedureField::TBL_FIELD_SUB_STATUS] = $subStatus;
	}
	
	/**
	 * 初始化小轮次
	 * 
	 * @return boolean
	 */
	public function initNextSubRound()
	{
		Logger::trace('GuildWarTeamRound.initNextSubRound begin...');
		Logger::trace('GuildWarTeamRound.initNextSubRound team round data[%s]', $this->mData);
		
		// 根据配置获得目前处于什么小轮次
		$curSubRoundByConf = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getCurSubRound();
		$curSubRound = $this->getSubRound();
		
		// 如果当前小轮大于等于配置的小轮，不需要初始化下个小轮
		if ($curSubRound >= $curSubRoundByConf) 
		{
			Logger::trace('GuildWarTeamRound.initNextSubRound failed, curSubRound:%d, $curSubRoundByConf:%d, no need initNextSubRound', $curSubRound, $curSubRoundByConf);
			return FALSE;
		}
		
		// 如果是一个大轮中的最后一个小轮，也不需要再初始化下个小轮
		if ($curSubRound >= GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSubRoundCount())
		{
			Logger::trace('GuildWarTeamRound.initNextSubRound failed, curSubRound:%d, subRoundCount:%d, no need initNextSubRound', $curSubRound, GuildWarConfObj::getInstance(GuildWarField::CROSS)->getSubRoundCount());
			return FALSE;
		}
		
		// 下个小轮次如果超过了根据配置得到的小轮次，也没必要初始化下个小轮
		$nextSubRound = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getNextSubRound($curSubRound);
		if ($nextSubRound > $curSubRoundByConf)
		{
			Logger::trace('GuildWarTeamRound.initNextSubRound failed, not reach the time. curSubRound:%d, nextSubRound:%d, curSubRoundByConf:%d', $curSubRound, $nextSubRound, $curSubRoundByConf);
			return FALSE;
		}
		
		// 如果当前小轮次还没有全部打完，也不能初始化下个小轮
		$curSubStatus = $this->getSubStatus();
		if ($curSubRound > 0 && $curSubStatus != GuildWarSubStatus::FIGHTEND)
		{
			Logger::trace('GuildWarTeamRound.initNextSubRound failed, curSubRound:%d, curSubStatus:%d cant initNextSubRound', $curSubRound, $curSubStatus);
			return FALSE;
		}
		
		// 可以初始化啦，把下个小轮次的状态置为FIGHTING
		$this->setSubRound($nextSubRound);
		$this->setSubStatus(GuildWarSubStatus::FIGHTING);
		
		Logger::info('GuildWarTeamRound.initNextSubRound success, curSubRound:%d, nextSubRound:%d', $curSubRound, $nextSubRound);
		return TRUE;
	}
	
	/**
	 * 判断是否需要更新数据
	 * 
	 * @return boolean
	 */
	public function needUpdate()
	{
		return $this->mData != $this->mBack;
	}

	/**
	 * 更新
	 */
	public function update()
	{
		if ($this->mData == $this->mBack)
		{
			return;
		}
		
		$this->mData[GuildWarProcedureField::TBL_FIELD_UPDATE_TIME] = time();

		GuildWarDao::updateGuildWarProcedure($this->mData);
		$this->mBack = $this->mData;
	}
}

class GuildWarTeam
{
	/**
	 * 届
	 * @var int
	 */
	private $mSession;
	
	/**
	 * 组
	 * @var int
	 */
	private $mTeamId;
	
	/**
	 * 当前所处轮次
	 * @var int
	 */
	private $mCurRound;
	
	/**
	 * 所有轮次的对象
	 * @var array
	 */
	private $mArrTeamRoundObj;

	/**
	 * 构造函数
	 * 
	 * @param int $session
	 * @param int $teamId
	 */
	public function __construct($session, $teamId)
	{
		$this->mSession = $session;
		$this->mTeamId = $teamId;
		$this->init();
	}
	
	/**
	 * 初始化
	 */
	public function init()
	{
		// 获取配置所处的阶段
		$curRoundByConf = GuildWarConfObj::getInstance()->getCurRound();
		
		// 如果处于报名阶段或者更早的阶段，记录下阶段就行啦
		if($curRoundByConf <= GuildWarRound::SIGNUP)
		{
			$this->mCurRound = $curRoundByConf;
		}
		// 其他阶段需要从进度表初始化
		else
		{
			$this->initFromDb();
			
			// 是不是需要初始化下一个大轮次
			$status = $this->getCurStatus();
			if ($this->mCurRound < $curRoundByConf 
				&& $status == GuildWarStatus::DONE)
			{
				$this->initNextRound();
			}
		}
	}
	
	/**
	 * 从数据库初始化
	 * 
	 * @return array
	 */
	public function initFromDb()
	{
		$roundData = GuildWarDao::getLastRoundData($this->mSession, $this->mTeamId);
		if (empty($roundData))
		{
			$this->mCurRound = GuildWarRound::AUDITION;
			$roundData = self::getInitTeamRoundData($this->mSession, $this->mTeamId, $this->mCurRound);
			$this->mArrTeamRoundObj[$this->mCurRound] = new GuildWarTeamRound($roundData);
		}
		else
		{
			$this->mCurRound = $roundData[GuildWarProcedureField::TBL_FIELD_ROUND];
			$this->mArrTeamRoundObj[$this->mCurRound] = new GuildWarTeamRound($roundData);
		}
		return $roundData;
	}
	
	/**
	 * 获得某一轮的初始化数据
	 * 
	 * @param int $session
	 * @param int $teamId
	 * @param int $round
	 * @param int $status
	 * @return array
	 */
	public static function getInitTeamRoundData($session, $teamId, $round, $status = GuildWarStatus::FIGHTING)
	{
		$subRound = ($round >= GuildWarRound::ADVANCED_16 && $round <= GuildWarRound::ADVANCED_2 ? 1 : 0);
		$subStatus = ($round >= GuildWarRound::ADVANCED_16 && $round <= GuildWarRound::ADVANCED_2 ? GuildWarSubStatus::FIGHTING : GuildWarSubStatus::NO);
		return array
		(
				GuildWarProcedureField::TBL_FIELD_SESSION => $session,
				GuildWarProcedureField::TBL_FIELD_TEAM_ID => $teamId,
				GuildWarProcedureField::TBL_FIELD_ROUND => $round,
				GuildWarProcedureField::TBL_FIELD_STATUS => $status,
				GuildWarProcedureField::TBL_FIELD_SUB_ROUND => 0,
				GuildWarProcedureField::TBL_FIELD_SUB_STATUS => 0,
				GuildWarProcedureField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
		);
	}

	/**
	 * 初始化下个轮次的状态（如果上一轮状态已经为GuildWarStatus::DONE,并且已经到了下一轮开始的时间，则需要调用这个函数）
	 * 
	 * @return boolean
	 */
	public function initNextRound()
	{
		// 根据配置获得目前处于什么阶段
		$curRoundByConf = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getCurRound();
		
		// 如果轮次为GuildWarRound::SIGNUP或者GuildWarRound::ADVANCED_2，都没必要初始化下轮
		if ($this->mCurRound <= GuildWarRound::SIGNUP
			|| $this->mCurRound >= GuildWarRound::ADVANCED_2)
		{
			Logger::fatal('GuildWarTeam.initNextRound failed, curRound:%d, no need initNextRound', $this->mCurRound);
			return FALSE;
		}

		// 下个轮次如果超过了根据配置得到的轮次，也没必要初始化下轮
		$nextRound = GuildWarConfObj::getInstance(GuildWarField::CROSS)->getNextRound($this->mCurRound);
		if ($nextRound > $curRoundByConf)
		{
			Logger::fatal('GuildWarTeam.initNextRound failed, not reach the time. mCurRound:%d, nextRound:%d, curRoundByConf:%d', $this->mCurRound, $nextRound, $curRoundByConf);
			return FALSE;
		}

		// 如果当前轮次还没有全部处理完，也不能初始化下轮
		$curStatus = $this->getCurStatus();
		if ($curStatus != GuildWarStatus::DONE)
		{
			Logger::fatal('GuildWarTeam.initNextRound failed, curRound:%d, curStatus:%d cant initNextRound', $this->mCurRound, $curStatus);
			return FALSE;
		}

		// 可以初始化啦，把下个轮次的状态置为FIGHTING
		$this->mCurRound = $nextRound;
		$roundData = self::getInitTeamRoundData($this->mSession, $this->mTeamId, $this->mCurRound);
		$this->mArrTeamRoundObj[$this->mCurRound] =  new GuildWarTeamRound($roundData);

		Logger::info('GuildWarTeam.initNextRound, teamId:%d, round:%d', $this->mTeamId, $this->mCurRound);
		return TRUE;
	}

	/**
	 * 获取当前所处的轮次
	 * 
	 * @return int
	 */
	public function getCurRound()
	{
		return $this->mCurRound;
	}
	
	/**
	 * 获得当前轮次的状态
	 * 
	 * @return int
	 */
	public function getCurStatus()
	{
		// 报名阶段没有啥状态
		if ($this->mCurRound <= GuildWarRound::SIGNUP)
		{
			return GuildWarStatus::NO;
		}
		return $this->mArrTeamRoundObj[$this->mCurRound]->getStatus();
	}
	
	/**
	 * 获得当前所处的小轮次
	 * 
	 * @return int
	 */
	public function getCurSubRound()
	{
		// 海选赛及以前的轮次，都没有小轮及小轮状态这个事
		if ($this->mCurRound <= GuildWarRound::AUDITION)
		{
			return 0;
		}
		return $this->mArrTeamRoundObj[$this->mCurRound]->getSubRound();
	}
	
	/**
	 * 获得当前所处小轮的状态
	 * 
	 * @return int
	 */
	public function getCurSubStatus()
	{
		// 海选赛及以前的轮次，都没有小轮及小轮状态这个事
		if ($this->mCurRound <= GuildWarRound::AUDITION)
		{
			return GuildWarSubStatus::NO;
		}
		return $this->mArrTeamRoundObj[$this->mCurRound]->getSubStatus();
	}

	/**
	 * 根据轮次，直接获取这个轮次所处的状态，
	 * 
	 * @param int $round 只能是当前轮次或者上个轮次，否则会抛出Inter
	 * @throws InterException
	 */
	public function getStatusByRound($round)
	{
		if (empty($this->mArrTeamRoundObj[$round]))
		{
			throw new InterException('get some other round:%d, curRound:%d', $round, $this->mCurRound);
		}		
		return $this->mArrTeamRoundObj[$round]->getStatus();
	}

	/**
	 * 根据轮次，直接获取这个轮次的对象
	 * 
	 * @param int $round 只能是当前轮次或者上个轮次，否则会抛出Inter
	 * @throws InterException
	 * @return GuildWarTeamRound
	 */
	public function getTeamRound($round)
	{
		if (empty($this->mArrTeamRoundObj[$round]))
		{
			throw new InterException('get some other round:%d, curRound:%d', $round, $this->mCurRound);
		}
		
		$roundObj = $this->mArrTeamRoundObj[$round];
		
		// 如果是晋级赛，需要刷新小轮次
		if ($round >= GuildWarRound::ADVANCED_16
			&& $round <= GuildWarRound::ADVANCED_2) 
		{
			$roundObj->initNextSubRound();
		}
		
		return $roundObj;
	}

	/**
	 * 只更新当前round的数据
	 */
	public function update()
	{
		foreach ($this->mArrTeamRoundObj as $round => $obj)
		{
			if ($round == $this->mCurRound)
			{
				$obj->update();
			}
			else
			{
				if ($obj->needUpdate())
				{
					Logger::fatal('no cur round need update. data:%s', $obj->getData());
				}
			}
		}
	}

	/**
	 * 从db获取这个轮次的数据
	 * 
	 * @param int $teamId
	 * @param int $session
	 * @param int $round
	 * @return array
	 */
	public static function getTeamRoundData($teamId, $session, $round)
	{
		return GuildWarDao::getRoundData($session, $teamId, $round);
	}
}

class GuildWarProcedureObj
{
	/**
	 * 唯一实例
	 * @var GuildWarProcedureObj
	 */
	private static $sInstance;
	
	/**
	 * 届
	 * @var int
	 */
	private $mSession;
	
	/**
	 * 不同组对象
	 * @var array
	 */
	private $mArrTeamObj;

	/**
	 * 获取实例
	 * 
	 * @param int $session
	 * @throws InterException
	 * @return GuildWarProcedureObj
	 */
	public static function getInstance($session)
	{
		if(empty(self::$sInstance))
		{
			self::$sInstance = new self($session);
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
	 */
	private function __construct($session)
	{
		$this->mSession = $session;
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
	 * 获得某个组的对象
	 * 
	 * @return GuildWarTeam
	 */
	public function getTeamObj($teamId)
	{
		if (empty($this->mArrTeamObj[$teamId]))
		{
			$this->mArrTeamObj[$teamId] = new GuildWarTeam($this->mSession, $teamId);
		}
		return $this->mArrTeamObj[$teamId];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */