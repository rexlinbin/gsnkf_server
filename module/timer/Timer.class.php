<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Timer.class.php 60628 2013-08-21 09:49:35Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/timer/Timer.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:49:35 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60628 $
 * @brief
 *
 **/

class Timer
{

	function execute($tid)
	{

		$arrTask = TimerDAO::getTask ( $tid );
		if (empty ( $arrTask ))
		{
			Logger::fatal ( "task:%d not found", $tid );
			return;
		}

		if ($arrTask ['status'] == TimerStatus::FAILED)
		{
			Logger::fatal ( "task:%d method:%s stop retrying", $tid, $arrTask ['execute_method'] );
		}
		else if ($arrTask ['status'] != TimerStatus::RETRY)
		{
			Logger::fatal ( "task:%d status:%d can't be executed", $tid, $arrTask ['status'] );
			return;
		}

		$uid = RPCContext::getInstance ()->getUid ();
		$taskUid = intval ( $arrTask ['uid'] );
		if ($taskUid != $uid)
		{
			if (empty ( $uid ))
			{
				Logger::trace ( "user:%d is not current user, set session for dummy",
						$arrTask ['uid'] );
				if ($taskUid >= FrameworkConfig::MIN_UID)
				{
					RPCContext::getInstance ()->setSession ( 'global.uid', $taskUid );
				}
			}
			else
			{
				Logger::fatal ( 'impossbile error, user:%d execute task of user:%d', $uid,
						$taskUid );
				throw new Exception ( 'inter' );
			}
		}

		if ($arrTask ['execute_time'] > Util::getTime () + 1)
		{
			Logger::fatal ( "execute_time:%s, now_time:%d, does not arrive yet",
					$arrTask ['execute_time'], Util::getTime () );
			throw new Exception ( 'inter' );
		}

		RPCContext::getInstance ()->getFramework ()->setMethod (
				"timer." . $arrTask ['execute_method'] );
		$arrRequest = array ('method' => $arrTask ['execute_method'],
				'args' => $arrTask ['va_args'] );
		RPCContext::getInstance ()->executeRequest ( $arrRequest );
		$arrUpdate ['execute_count'] = 1 + $arrTask ['execute_count'];
		$arrUpdate ['status'] = TimerStatus::FINISH;
		TimerDAO::updateTask ( $tid, $arrUpdate );
	}

	function addTask($uid, $time, $callback, $arrArg)
	{

		return TimerDAO::addTask ( $uid, $time, $callback, $arrArg );
	}

	function cancelTask($tid)
	{

		$arrTask = array ('status' => TimerStatus::CANCEL );
		TimerDAO::updateTask ( $tid, $arrTask );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
