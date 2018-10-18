<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnActive.class.php 228099 2016-02-18 07:15:00Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/EnActive.class.php $
 * @author $Author: BaoguoMeng $(tianming@babeltime.com)
 * @date $Date: 2016-02-18 07:15:00 +0000 (Thu, 18 Feb 2016) $
 * @version $Revision: 228099 $
 * @brief 
 *  
 **/
class EnActive
{
	public static function addTask($taskType, $num = 1)
	{
		Logger::trace("add task type:%d num:%d", $taskType, $num);
		
		if (!in_array($taskType, ActiveDef::$VALID_TYPES) || $num < 0) 
		{
			throw new FakeException('invalid task type:%d or num:%d', $taskType, $num);
		}
		
		if (empty($num)) 
		{
			return ;
		}

		if (EnSwitch::isSwitchOpen(SwitchDef::ACTIVE) == false)
		{
			return;
		}
		
		$openDays = intval((Util::getTime() - strtotime(GameConf::SERVER_OPEN_YMD . "000000"))/86400) + 1;
		
		$uid = RPCContext::getInstance()->getUid();
		$myActive = MyActive::getInstance($uid);
		$step = $myActive->getStep();
		$task = $myActive->getTask();
		$arrTaskId = btstore_get()->ACTIVE_OPEN[$step][ActiveDef::ACTIVE_TASK]->toArray();
		//根据任务类型找到任务id
		foreach (btstore_get()->ACTIVE as $taskId => $taskConf)
		{
			if (in_array($taskId, $arrTaskId)
			&& $taskConf[ActiveDef::ACTIVE_TYPE] == $taskType) 
			{
				$needOpenDays = intval($taskConf[ActiveDef::ACTIVE_OPEN_LIMIT]);
				if ($openDays < $needOpenDays) 
				{
					Logger::trace('taskId[%d], taskType[%d], openDays[%d], needOpenDays[%d],continue', $taskId, $taskType, $openDays, $needOpenDays);
					continue;
				}
				
				if (!isset($task[$taskId])) 
				{
					$task[$taskId] = 0;
				}
				if ($taskConf[ActiveDef::ACTIVE_NUM] > $task[$taskId])
				{
					$need = $taskConf[ActiveDef::ACTIVE_NUM] - $task[$taskId];
					$need = $num >= $need ? $need : $num;
					$myActive->addTask($taskId, $need);
					$num -= $need;
					if ($num == 0) 
					{
						break;
					}
				}
			}
		}

		$myActive->save();
	}
	
	
	/**
	 * 获取昨天每日任务积分超过参数给定值的玩家的基本信息，用户平台核心用户统计
	 * 
	 * @param number $minPoint
	 * @return array
	 */
	public static function getActiveInfoByPoint($minPoint)
	{
		// 批量拉取update_time在昨天和今天的玩家积分信息，只有这种人，昨天才可能有每日积分
		$currMaxUid = 0;
		$arrActiveInfo = array();
		$myLimit = 1000;
		while (TRUE)
		{
			$arrField = array
			(
					ActiveDef::UID,
					ActiveDef::POINT,
					ActiveDef::LAST_POINT,
					ActiveDef::UPDATE_TIME,
			);
			$arrCond = array
			(
					array(ActiveDef::UPDATE_TIME, '>=', strtotime(date('Ymd', Util::getTime())) - SECONDS_OF_DAY),
					array(ActiveDef::UID, '>', $currMaxUid),
			);
			$data = new CData();
			$data->select($arrField)->from(ActiveDef::ACTIVE_TABLE);
			foreach ($arrCond as $aCond)
			{
				$data->where($aCond);
			}
			$data->orderBy(ActiveDef::UID, TRUE);
			$data->limit(0, $myLimit);
			$arrRet = $data->query();
			$arrActiveInfo = array_merge($arrActiveInfo, $arrRet);
			
			if (count($arrRet) < $myLimit)
			{
				break;
			}
			else
			{
				$last = end($arrRet);
				$currMaxUid = $last[ActiveDef::UID];
			}
		}		
		
		// 过滤掉分数不符合条件的
		$arrValidInfo = array();
		foreach ($arrActiveInfo as $aInfo)
		{
			if (Util::isSameDay($aInfo[ActiveDef::UPDATE_TIME]))
			{
				if ($aInfo[ActiveDef::LAST_POINT] < $minPoint) 
				{
					continue;
				}
				else 
				{
					$arrValidInfo[] = array('uid' => $aInfo[ActiveDef::UID], 'point' => $aInfo[ActiveDef::LAST_POINT]);
				}
			}
			else 
			{
				if ($aInfo[ActiveDef::POINT] < $minPoint) 
				{
					continue;
				}
				else 
				{
					$arrValidInfo[] = array('uid' => $aInfo[ActiveDef::UID], 'point' => $aInfo[ActiveDef::POINT]);
				}
			}
		}
		
		$arrValidInfo = Util::arrayIndex($arrValidInfo, 'uid');
		return $arrValidInfo;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */