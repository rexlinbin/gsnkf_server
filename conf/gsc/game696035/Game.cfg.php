<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Game.cfg.php 79466 2013-12-09 04:12:49Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/gsc/game001/Game.cfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-09 12:12:49 +0800 (一, 2013-12-09) $
 * @version $Revision: 79466 $
 * @brief
 *
 **/
class GameConf
{

	/**
	 * 开服年月日
	 * @var string
	 */
const SERVER_OPEN_YMD = '20170311';

	/**
	 * 开服时分秒
	 * @var string
	 */
const SERVER_OPEN_TIME = '120000';

	/**
	 * boss 错峰时间偏移
	 * @var int
	 */
const BOSS_OFFSET = 0;

}

/**
 * 如果需要修改竞技场持续天数，
 * 应该也同时修改竞技场开始日期为当前日期
 *
 * @author idyll
 *
 */
class ArenaDateConf
{
	//持续天数
	const LAST_DAYS = 1;

	//锁定开始时间
	const LOCK_START_TIME = "22:00:00";

	//锁定结束时间
	const LOCK_END_TIME = "22:50:00";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
