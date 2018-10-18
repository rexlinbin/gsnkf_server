<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTaskObj.class.php 117931 2014-06-30 11:22:55Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/GuildTaskObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-30 11:22:55 +0000 (Mon, 30 Jun 2014) $
 * @version $Revision: 117931 $
 * @brief 
 *  
 **/
class GuildTaskObj
{
	private $taskInfo;
	private $taskInfoBack;
	private $uid;
	private static $instance = NULL;
	
	/**
	 * 获取唯一实例
	 * @return GuildTaskObj
	 */
	public static function getInstance( $uid )
	{
		if ( self::$instance == null)
		{
			self::$instance = new self( $uid );
		}
		return self::$instance;
	}
	
	public static function release()
	{
		if (self::$instance != null)
		{
			self::$instance = null;
		}
	}
	
	private function __construct( $uid )
	{
		//暂不支持其他人，要写脚本的话改一下会好
		$this->uid = $uid;
		$guid = RPCContext::getInstance()->getUid();
		if ( $uid != $guid )
		{
			throw new FakeException( 'not cur user: %d', $uid);
		}
		
		$this->taskInfo = RPCContext::getInstance()->getSession(GuildTaskDef::GUILD_TASK_SESSION);
		if ( empty( $this->taskInfo ) )
		{
			$this->taskInfo = GuildTaskDao::getTaskInfo($this->uid);
			if ( empty( $this->taskInfo ) )
			{
				$this->taskInfo = $this->initGuildTask( $this->uid );
			}
			RPCContext::getInstance()->setSession( GuildTaskDef::GUILD_TASK_SESSION , $this->taskInfo );
		}
			
		$this->taskInfoBack = $this->taskInfo;
		
		$this->adapetRefresh();
	}
	
	public function initGuildTask($uid)
	{
		$randomPosArr = $this->getFullPosArr();
		$newTaskArr = $this->randomTask($randomPosArr);
		
		$initValArr = array(
				'uid' => $uid,
				GuildTaskDef::RESET_TIME => Util::getTime(),
				GuildTaskDef::REF_NUM => 0,
				GuildTaskDef::TASK_NUM => 0,
				GuildTaskDef::FORGIVE_TIME => 0,
				GuildTaskDef::VA_GUILDTASK => 
				array(
						'task'=> $newTaskArr,
		),
		);
		//必须的
		GuildTaskDao::insertOrUpdate($uid,$initValArr);
		return $initValArr;
	}
	
	public function adapetRefresh()
	{
		$guildtaskConf = btstore_get()->GUILD_TASK;
		if ( Util::isSameDay( $this->taskInfo[GuildTaskDef::RESET_TIME] ) )
		{
			return;
		}
		$beforeRef = $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'];
		
		$randomPosArr = $this->getFullPosArr();
		foreach ( $randomPosArr as $index => $randpos )
		{
			if ($beforeRef[$randpos]['status'] == GuildTaskDef::ACCEPT
			&&$beforeRef[$randpos]['num'] < $guildtaskConf[$beforeRef[$randpos]['id']][GuildTaskDef::BTS_FINISH_COND][2])
			{
				Logger::debug('now unset one: %d',$index);
				unset($randomPosArr[$index]);
				break;//保证只有一个是unset的因为最多只能接一个任务
			}	
		}
		Logger::debug('all random are : %s', $randomPosArr);
		$newTaskArr = $this->randomTask($randomPosArr)+$beforeRef;
			
		$this->taskInfo[GuildTaskDef::RESET_TIME] = Util::getTime();
		$this->taskInfo[GuildTaskDef::TASK_NUM] = 0;
		$this->taskInfo[GuildTaskDef::REF_NUM] = 0;
		$this->taskInfo[GuildTaskDef::FORGIVE_TIME] = 0;
		$this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'] = $newTaskArr;
		
		$this->update();
		$this->handleFinishTask($beforeRef);
		
	}
	
	public function handleFinishTask($alltask)
	{
		$guildTaskConf = btstore_get()->GUILD_TASK;
		//$alltask = $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'];
		Logger::debug('alltask: %s, ', $alltask);
		foreach ( $alltask as $pos => $oneTaskInfo )
		{
			$oneGuildTask = $guildTaskConf[$oneTaskInfo[GuildTaskDef::BTS_TASKID]];
			if ( $oneTaskInfo['num'] >= $oneGuildTask[GuildTaskDef::BTS_FINISH_COND][2]  )
			{
				$reward3D = $oneGuildTask[GuildTaskDef::BTS_REWARD];
				RewardUtil::reward3DtoCenter($this->uid, array($reward3D), RewardSource::GUILD_TASK);
			}
		}
		
	}
	
	public function getTaskInfo()
	{
		return $this->taskInfo;
	}
	
	public function getTaskDetail()
	{
		return $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'];
	}
	
	public function getRefNum()
	{
		return $this->taskInfo[GuildTaskDef::REF_NUM];
	}
	
	public function getForgiveTime()
	{
		return $this->taskInfo[GuildTaskDef::FORGIVE_TIME];
	}
	
	public function getTaskNum()
	{
		return $this->taskInfo[GuildTaskDef::TASK_NUM];
	}
	
	public function getIndoingNum($pos)
	{
		return $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'][$pos]['num'];
	}
	
	public function setTask( $arr )
	{
		$this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'] = $arr;
	}
	
	public function addTaskNum($num)
	{
		$this->taskInfo[GuildTaskDef::TASK_NUM] += $num;
	}
	
	public function addRefNum($num)
	{
		$this->taskInfo[GuildTaskDef::REF_NUM] += $num;
	}
	
	public function setForgiveTime()
	{
		$this->taskInfo[GuildTaskDef::FORGIVE_TIME] = Util::getTime();
	}
	
	public function doTaskOnce( $pos,$num )
	{
		$this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'][$pos]['num'] += $num;
	}
	
	public function getIndoingTaskPos()
	{
		$ret = 1000;//为了返回的格式统一
		foreach ( $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'] as $pos => $oneTaskInfo )
		{
			if ($oneTaskInfo['status'] == GuildTaskDef::ACCEPT )
			{
				return $pos;
			}
		}
		return $ret;
	}
	
	public function getIndoingTaskId()
	{
		foreach ( $this->taskInfo[GuildTaskDef::VA_GUILDTASK]['task'] as $pos => $oneTaskInfo )
		{
			if ($oneTaskInfo['status'] == GuildTaskDef::ACCEPT )
			{
				return $oneTaskInfo['id'];
			}
		}
		return 0;
		
	}
	
	public function update()
	{
		$arrchanged = array();
		if ( $this->taskInfo == $this->taskInfoBack )
		{
			return;
		}
		
		GuildTaskDao::insertOrUpdate($this->uid, $this->taskInfo);
		$this->taskInfoBack = $this->taskInfo;
		
		$guid = RPCContext::getInstance()->getUid();
		if ($this->uid == $guid)
		{
			RPCContext::getInstance()->setSession(GuildTaskDef::GUILD_TASK_SESSION, $this->taskInfo);
		}
	}
	
	//这俩应该不放在这。。。。蛋疼。。。。
	public function randomTask( $taskPosArr )
	{
		$guildTaskLimit = btstore_get()->GUILD_TASK_LIMIT->toArray();
		$guildTask = btstore_get()->GUILD_TASK;
		
		$buildLv = EnGuild::getBuildLevel($this->uid,GuildDef::TASK);//12;
		$cityId = EnCityWar::getGuildCityId($this->uid);
		
		$randomResult = array();
		foreach ( $taskPosArr as $index => $onePos )
		{
			Logger::debug('now random position: %d',$onePos);
			if ( !isset( $guildTaskLimit[GuildTaskDef::BTS_REF_TASKARR][$onePos] ) )
			{
				throw new ConfigException( 'no pos: %d info', $onePos );
			}
			
			$allRandomTask = $guildTaskLimit[GuildTaskDef::BTS_REF_TASKARR][$onePos];
			$baseArr = array();
			foreach ( $allRandomTask as $confIndex => $taskId )
			{
				if ( ($cityId<=0 && ($guildTask[$taskId][GuildTaskDef::BTS_TYPE] == GuildTaskType::MEND_CITY||$guildTask[$taskId][GuildTaskDef::BTS_NEED_CITY] == 1))
						||($buildLv < $guildTask[$taskId][GuildTaskDef::BTS_NEED_BUILDLV]))
				{
					Logger::debug('unset one taskid cos need city or level: %d',$taskId);
					unset($allRandomTask[$confIndex]);
				}
				else
				{
					if( $guildTask[$taskId][GuildTaskDef::BTS_TYPE] == GuildTaskType::BASE)
					{
						$baseArr[] = $guildTask[$taskId][GuildTaskDef::BTS_FINISH_COND][1];
					}
					$allRandomTask[$confIndex] = array('id'=> $taskId,'weight' => $guildTask[$taskId][GuildTaskDef::BTS_WEIGHT]);
				}
			}
			
			if (empty( $allRandomTask ))
			{
				throw new FakeException( 'no task left' );
			}
			
			Logger::debug('$allRandomTask before check base are: %s',$allRandomTask );
			
			if(!empty($baseArr))
			{
				$baseArr = array_unique($baseArr);
				Logger::debug('baseArr are: %s',$baseArr );
				$isBaseArrPass = CopyUtil::isArrBasePassed( $baseArr );
				Logger::debug('isbasearrpase are: %s',$isBaseArrPass);
				foreach ( $allRandomTask as $randomOneIndex => $randomOneInfo )
				{
					$thisGuildtask = $guildTask[$randomOneInfo['id']];
					if ( $thisGuildtask[GuildTaskDef::BTS_TYPE] == GuildTaskType::BASE)
					{
						if ( !isset( $isBaseArrPass[$thisGuildtask[GuildTaskDef::BTS_FINISH_COND][1]] )
							|| $isBaseArrPass[$thisGuildtask[GuildTaskDef::BTS_FINISH_COND][1]] != 1 )
						{
							Logger::debug('unset one taskid cos base unpassed: %d',$taskId);
							unset($allRandomTask[$randomOneIndex]);
						}
					}
				}
			}
			
			Logger::debug('$allRandomTask after check base are: %s',$allRandomTask );
			if (empty( $allRandomTask ))
			{
				throw new FakeException( 'no task left' );
			}
			$oneRandomRet = Util::backSample($allRandomTask, 1);
			
			Logger::debug('$oneRandomRet is %s',$oneRandomRet);
			if ( isset( $randomResult[$onePos] ) )
			{
				throw new InterException( 'random for same pos,posarr:%s',$taskPosArr );
			}
			else
			{
				$randomResult[$onePos] = array( 
						'id' => $allRandomTask[$oneRandomRet[0]]['id'],
						'status' => GuildTaskDef::NOT_ACCEPT,
						'num' => 0,
				 );
			}
		}
		
		return $randomResult;
	}
	
	public function getFullPosArr()
	{
		$conf = btstore_get()->GUILD_TASK_LIMIT;
		$taskArr = $conf[GuildTaskDef::BTS_REF_TASKARR];
		
		$tarTaskNum = count( $taskArr );
		$tmp = array_fill(0, $tarTaskNum, 1);
		$randomPosArr = array_keys($tmp);
		return $randomPosArr;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */