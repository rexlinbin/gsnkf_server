<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyActive.class.php 219931 2016-01-07 03:09:17Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/MyActive.class.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2016-01-07 03:09:17 +0000 (Thu, 07 Jan 2016) $
 * @version $Revision: 219931 $
 * @brief 
 *  
 **/
class MyActive
{
	private $uid = 0;								// 用户id
	private $active = NULL;							// 修改数据
	private $activeOrg = NULL; 						// 原始数据
	private static $arrActive = array();			// 实例对象数组

	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return MyActive
	 */
	public static function getInstance($uid)
	{
		if (!isset(self::$arrActive[$uid]))
		{
			self::$arrActive[$uid] = new self($uid);
		}
		return self::$arrActive[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrActive = array();
		}
		else if (isset(self::$arrActive[$uid]))
		{
			unset(self::$arrActive[$uid]);
		}
	}

	private function __construct($uid)
	{
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		// 如果在用户当前的线程中，就从session中取数据，否则从数据库取数据
		if ($uid == RPCContext::getInstance()->getUid())
		{
			$active = RPCContext::getInstance()->getSession(ActiveDef::SESSION_KEY);
			if(empty($active))
			{
				$active = ActiveDao::select($uid);
				if (empty($active)) 
				{
					$active = $this->init($uid);
				}
				RPCContext::getInstance()->setSession(ActiveDef::SESSION_KEY, $active);
			}
		}
		else
		{
			$active = ActiveDao::select($uid);
			if (empty($active))
			{
				$active = $this->init($uid);
			}
		}
		$this->uid = $uid;
		$this->active = $active;
		$this->activeOrg = $active;
		$this->refresh();
	}
	
	public function init($uid)
	{
		$arrField = array(
				ActiveDef::UID => $uid,
				ActiveDef::POINT => 0,
				ActiveDef::LAST_POINT => 0,
				ActiveDef::UPDATE_TIME => 0,
				ActiveDef::VA_ACTIVE => array(),
		);
		
		return $arrField;
	}
	
	public function getInfo()
	{
		return $this->active;
	}
	
	public function refresh()
	{
		//补全step数据
		$this->getStep();
		$updateTime = $this->getUpdateTime();
		if (!empty($updateTime) && !Util::isSameDay($updateTime))
		{
			// 记录玩家昨天的积分，用以核心用户的统计
			if (Util::getDaysBetween($this->getUpdateTime()) == 1)
			{
				$this->active[ActiveDef::LAST_POINT] = $this->getPoint();
				
				Logger::trace('MyActive refresh: update time[%s], curr time[%s], set last_point with point[%d]', 
								strftime('%Y%m%d %H%M%S', $this->getUpdateTime()),
								strftime('%Y%m%d %H%M%S', Util::getTime()),
								$this->getPoint());
			}
			else 
			{
				$this->active[ActiveDef::LAST_POINT] = 0;
				
				Logger::trace('MyActive refresh: update time[%s], curr time[%s], set last_point with 0',
								strftime('%Y%m%d %H%M%S', $this->getUpdateTime()),
								strftime('%Y%m%d %H%M%S', Util::getTime()));
			}
			
			$reward = $this->getReward();
			$this->setPoint(0);
			$this->setTask(array());
			$this->setPrize(array());
			$this->setTaskRewardIdArr(array());
			$this->save();
			$this->sendReward($reward);
		}
	}
	
	public function getTaskReward()
	{
		$arrTaskReward = array();
		$hadFinishTaskArr = $this->getTask();
		$hadGainTaskRewardArr = $this->getTaskIdOFHadGainReward();
		$step = $this->getStep();
		$arrTaskId = btstore_get()->ACTIVE_OPEN[$step][ActiveDef::ACTIVE_TASK]->toArray();
		$conf = btstore_get()->ACTIVE;
		foreach ($conf as $taskId => $taskConf)
		{
			$hadFinishNum = 0;
			if (!empty($hadFinishTaskArr[$taskId]))
			{
				$hadFinishNum = $hadFinishTaskArr[$taskId];
			}
			if (in_array($taskId, $arrTaskId)
			&& $hadFinishNum >= $taskConf[ActiveDef::ACTIVE_NUM]
			&& !in_array($taskId, $hadGainTaskRewardArr))
			{
				$arrTaskReward[] = $taskConf[ActiveDef::ACTIVE_REWARD];
			}
		}
		
		return $arrTaskReward;
	}
	
	public function getReward()
	{
		$arrReward = array();
		$step = $this->getStep();
		$point = $this->getPoint();
		$prize = $this->getPrize();
		$conf = btstore_get()->ACTIVE_PRIZE;
		$arrPrizeId = btstore_get()->ACTIVE_OPEN[$step][ActiveDef::ACTIVE_PRIZE]->toArray();
		foreach ($conf as $prizeId => $prizeConf)
		{
			if (in_array($prizeId, $arrPrizeId)
			&& $point >= $prizeConf[ActiveDef::ACTIVE_POINT]
			&& !in_array($prizeId, $prize))
			{
				$arrReward[] = $prizeConf[ActiveDef::ACTIVE_PRIZE];
			}
		}
		$arrTaskReward = $this->getTaskReward();
		$arrReward = array_merge($arrReward, $arrTaskReward);
		
		return $arrReward;
	}
	
	public function sendReward($arrReward)
	{
		if (!empty($arrReward)) 
		{
			RewardUtil::reward3DtoCenter($this->uid, $arrReward, RewardSource::DAILY_TASK);
		}
	}
	
	public function getPoint()
	{
		return $this->active[ActiveDef::POINT];
	}
	
	public function setPoint($point)
	{
		$this->active[ActiveDef::POINT] = $point;
	}
	
	public function addPoint($point)
	{
		$this->active[ActiveDef::POINT] += $point;
	}
	
	public function getUpdateTime()
	{
		return $this->active[ActiveDef::UPDATE_TIME];
	}

	public function setUpdateTime($time)
	{
		$this->active[ActiveDef::UPDATE_TIME] = $time;
	}
	
	public function getStep()
	{
		if (empty($this->active[ActiveDef::VA_ACTIVE][ActiveDef::STEP]))
		{
			$this->active[ActiveDef::VA_ACTIVE][ActiveDef::STEP] = key(btstore_get()->ACTIVE_OPEN->toArray());
		}
		return $this->active[ActiveDef::VA_ACTIVE][ActiveDef::STEP];
	}
	
	public function addStep($num)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::STEP] += $num;
	}
	
	public function getTask()
	{
		if (empty($this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK])) 
		{
			return array();
		}
		return $this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK];
	}
	
	public function setTask($task)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK] = $task;
	}
	
	public function addTask($taskId, $num)
	{
		if (!isset($this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId]))
		{
			$this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId] = 0;
		}
		
		$conf = btstore_get()->ACTIVE[$taskId];
		if ($conf[ActiveDef::ACTIVE_NUM] > $this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId]) 
		{
			$this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId] += $num;
			if ($conf[ActiveDef::ACTIVE_NUM] <= $this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId])
			{
				$this->addPoint($conf[ActiveDef::ACTIVE_POINT]);
			}
		}
	}
	
	public function getPrize()
	{
		if (empty($this->active[ActiveDef::VA_ACTIVE][ActiveDef::PRIZE]))
		{
			return array();
		}
		return $this->active[ActiveDef::VA_ACTIVE][ActiveDef::PRIZE];
	}
	
	public function setPrize($prize)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::PRIZE] = $prize;
	}
	
	public function addPrize($prizeId)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::PRIZE][] = $prizeId;
	}
	
	public function getTaskIdOFHadGainReward()
	{
		if (empty($this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK_REWARD]))
		{
			return array();
		}
		return $this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK_REWARD];
	}
	
	public function setTaskRewardIdArr($taskIdArr)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK_REWARD] = $taskIdArr;
	}
	
	public function gainTaskReward($taskId)
	{
		$this->active[ActiveDef::VA_ACTIVE][ActiveDef::TASK_REWARD][] = $taskId;
	}
	
	public function upgrade()
	{
		$this->addStep(1);
		$this->setPoint(0);
		$this->setTask(array());
		$this->setPrize(array());
		$this->setTaskRewardIdArr(array());
	}

	/**
	 * 更新数据库
	 */
	public function save()
	{
		//目前只能在自己的连接中改自己的数据
		if($this->uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $this->uid);
		}
		if ($this->active != $this->activeOrg) 
		{
			//记录更新时间
			$this->setUpdateTime(Util::getTime());
			ActiveDao::insertOrUpdate($this->active);
			// 在自己的连接中，则更新到session中
			RPCContext::getInstance()->setSession(ActiveDef::SESSION_KEY, $this->active);
			// 同步数据
			$this->activeOrg = $this->active;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */