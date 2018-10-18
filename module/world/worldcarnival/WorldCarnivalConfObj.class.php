<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalConfObj.class.php 198237 2015-09-11 14:30:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnivalConfObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 14:30:41 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198237 $
 * @brief 
 *  
 **/
 
class WorldCarnivalConfObj
{
	/**
	 * 唯一实例
	 * @var WorldCarnivalConfObj
	 */
	private static $sInstance = NULL;

	/**
	 * 活动版本
	 * @var int
	 */
	private $mVersion;

	/**
	 * 开始时间
	 * @var int
	 */
	private $mStartTime;

	/**
	 * 结束时间
	 * @var int
	 */
	private $mEndTime;

	/**
	 * 需要开启时间
	 * @var int
	 */
	private $mNeedOpenTime;

	/**
	 * 具体配置数据
	 * @var array
	 */
	private $mConf;

	/**
	 * 返回唯一实例
	 *
	 * @param string $field
	 * @return WorldCarnivalConfObj
	 */
	public static function getInstance($field = WorldCarnivalField::INNER)
	{
		if (!isset(self::$sInstance))
		{
			self::$sInstance = new self($field);
		}
		return self::$sInstance;
	}

	/**
	 * 释放实例
	 */
	public static function release()
	{
		if (isset(self::$sInstance))
		{
			unset(self::$sInstance);
		}
	}

	/**
	 * 构造函数
	 * 
	 * @param int $field
	 * @throws ConfigException
	 */
	function __construct($field)
	{
		if ($field == WorldCarnivalField::CROSS)
		{
			$activityConf = ActivityConfLogic::getConf4Backend(ActivityName::WORLDCARNIVAL, 0);
		}
		else
		{
			$activityConf = EnActivity::getConfByName(ActivityName::WORLDCARNIVAL);
		}

		$this->mVersion = $activityConf['version'];
		$this->mStartTime = floor($activityConf['start_time'] / 60) * 60;
		$this->mEndTime = floor($activityConf['end_time'] / 60) * 60;
		$this->mNeedOpenTime = floor($activityConf['need_open_time'] / 60) * 60;
		
		if (empty($activityConf['data']))
		{
			if ($this->mStartTime > 0)
			{
				throw new ConfigException('WorldCarnivalConfObj.construct failed, no data in activityConf[%s]', $activityConf);
			}
			Logger::info('WorldCarnivalConfObj.construct failed, empty activityConf[%s]', $activityConf);
		}
		else
		{
			$this->mConf = $activityConf['data'];
		}
		Logger::trace('WorldCarnivalConfObj cur conf[%s]', $this->mConf);
	}

	/**
	 * 获取当前活动的版本
	 * 
	 * @return number
	 */
	public function getActivityVersion()
	{
		return $this->mVersion;
	}

	/**
	 * 获取活动的开始时间
	 * 
	 * @return number
	 */
	public function getActivityStartTime()
	{
		return $this->mStartTime;
	}

	/**
	 * 获取活动的结束时间
	 * 
	 * @return number
	 */
	public function getActivityEndTime()
	{
		return $this->mEndTime;
	}

	/**
	 * 获取活动的需要服务器的最晚开服时间
	 * 
	 * @return number
	 */
	public function getActivityNeedOpenTime()
	{
		return $this->mNeedOpenTime;
	}

	/**
	 * 判断某个时间是否在活动的有效时间范围内
	 * 
	 * @param number $time
	 * @return boolean
	 */
	public function isValid($time = 0)
	{
		if ($time == 0)
		{
			$time = time();
		}

		if ($time < $this->mStartTime
			|| $time > $this->mEndTime
			|| $this->mStartTime == 0
			|| $this->mEndTime == 0)
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * 获得活动配置的某个字段的值
	 * 
	 * @param string $field
	 * @return
	 */
	public function getConf($field)
	{
		return $this->mConf[$field];
	}

	/**
	 * 获得活动的届数
	 * 
	 * @return number
	 */
	public function getSession()
	{
		if (!$this->isValid())
		{
			return 0;
		}

		if (isset($this->mConf['session']))
		{
			return $this->getConf('session');
		}

		return $this->getConf('id');
	}
	
	/**
	 * 判断某个玩家是否是可视的，用于线上测试
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @return boolean
	 */
	public function isVisible($serverId, $pid)
	{
		if ($this->isFighter($serverId, $pid)) 
		{
			$arrFighters = $this->getConf('fighters');
			foreach ($arrFighters as $pos => $aFighterInfo)
			{
				if ($serverId == $aFighterInfo['server_id'] && $pid == $aFighterInfo['pid']) 
				{
					return $aFighterInfo['visible'] > 0;
				}
			}
		}
		
		if ($this->isWatcher($serverId, $pid)) 
		{
			$arrWatchers = $this->getConf('watchers');
			foreach ($arrWatchers as $aWatcherInfo)
			{
				if ($serverId == $aWatcherInfo['server_id'] && $pid == $aWatcherInfo['pid'])
				{
					return $aWatcherInfo['visible'] > 0;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * 是否是参赛者
	 * 
	 * @param number $serverId
	 * @param number $pid
	 * @return boolean
	 */
	public function isFighter($serverId, $pid)
	{	
		$arrFighters = $this->getConf('fighters');
		
		foreach ($arrFighters as $pos => $aFighterInfo)
		{
			if ($serverId == $aFighterInfo['server_id'] && $pid == $aFighterInfo['pid']) 
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * 获取某个参赛者的位置
	 * 
	 * @param number $serverId
	 * @param number $pid
	 * @return number
	 */
	public function getFighterPos($serverId, $pid)
	{
		$arrFighters = $this->getConf('fighters');
		
		foreach ($arrFighters as $pos => $aFighterInfo)
		{
			if ($serverId == $aFighterInfo['server_id'] && $pid == $aFighterInfo['pid'])
			{
				return $pos;
			}
		}
		
		return 0;
	}
	
	/**
	 * 获得所有参赛者的serverId和pid
	 * 
	 * @return array
	 */
	public function getFighters()
	{
		return $this->getConf('fighters');
	}
	
	/**
	 * 根据位置获取参赛者信息
	 *  
	 * @param int $pos
	 * @throws InterException
	 * @return array
	 */
	public function getFighterByPos($pos)
	{
		$arrFighters = $this->getFighters();
		if (!isset($arrFighters[$pos])) 
		{
			throw new InterException('invalid pos[%d]', $pos);
		}
		
		return $arrFighters[$pos];
	}
	
	/**
	 * 是否是围观者
	 * 
	 * @param number $serverId
	 * @param number $pid
	 * @return boolean
	 */
	public function isWatcher($serverId, $pid)
	{
		$arrWatchers = $this->getConf('watchers');
		
		foreach ($arrWatchers as $aWatcherInfo)
		{
			if ($serverId == $aWatcherInfo['server_id'] && $pid == $aWatcherInfo['pid'])
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * 获得所有围观者的serverId和pid
	 *
	 * @return array
	 */
	public function getWatchers()
	{
		return $this->getConf('watchers');
	}
	
	/**
	 * 获得开始战斗的时间
	 *
	 * @return int
	 */
	public function getBeginTime()
	{
		return $this->getActivityStartTime() + $this->getConf('begin_time');
	}
	
	/**
	 * 获得小轮的周期
	 * 
	 * @return number
	 */
	public function getNormalPeriod()
	{
		return $this->getConf('normal_period');
	}
	
	/**
	 * 获得决赛前间隔
	 * 
	 * @return number
	 */
	public function getFinalPeriod()
	{
		return $this->getConf('final_period');
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */