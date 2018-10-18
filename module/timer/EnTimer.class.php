<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnTimer.class.php 83578 2013-12-28 09:42:40Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/timer/EnTimer.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-28 09:42:40 +0000 (Sat, 28 Dec 2013) $
 * @version $Revision: 83578 $
 * @brief
 *
 **/
class EnTimer
{

	/**
	 * 检查一个任务是否正常
	 * @param int $tid
	 * @param string $excuteMethod
	 * @return bool false表示执行失败，true表示其他
	 */
	static function checkTask($tid, $excuteMethod = '')
	{

		$arrTask = TimerDAO::getTask ( $tid );
		if (empty ( $arrTask ))
		{
			Logger::warning ( "timer:%d not found", $tid );
			throw new Exception ( 'inter' );
		}
		if (!EMPTY($excuteMethod) && $excuteMethod != $arrTask['execute_method'])
		{
			Logger::fatal ( "the timer is wrong. timer:%d, method:%s",
								$tid, $arrTask['execute_method'] );
			throw new Exception ( 'inter' );
		}
		return $arrTask ['status'] != TimerStatus::FAILED;
	}

	/**
	 * 重置任务状态
	 * @param int $tid
	 */
	static function resetTask($tid)
	{

		$arrTask = array ('execute_count' => 0, 'status' => TimerStatus::UNDO );
		$arrRet = TimerDAO::updateTask ( $tid, $arrTask );
		if ($arrRet ['affected_rows'] != 1)
		{
			Logger::fatal ( "timer:%d update affected_rows:%d", $tid, $arrRet ['affected_rows'] );
		}
	}
	
	/**
	 * 获得某个方法名为$taskName,状态在$arrStatus中，执行时间在$startTime之后的timer
	 * 
	 */
	public static function getArrTaskByName($taskName, $arrStatus = array(), $startTime = 0)
	{
		return TimerDAO::getArrTaskByName($taskName, $arrStatus, $startTime);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */