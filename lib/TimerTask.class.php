<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TimerTask.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/TimerTask.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/


class TimerTask
{

	static function addTask($uid, $time, $callback, $arrArgs)
	{

		$timer = new Timer ();
		return $timer->addTask ( $uid, $time, $callback, $arrArgs );
	}

	static function cancelTask($tid)
	{

		$timer = new Timer ();
		$timer->cancelTask ( $tid );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
