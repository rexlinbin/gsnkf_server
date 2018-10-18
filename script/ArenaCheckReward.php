<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaCheckReward.php 60641 2013-08-21 10:21:37Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ArenaCheckReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-08-21 10:21:37 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60641 $
 * @brief 
 *  
 **/
require MOD_ROOT . '/arena/index.php';

/**
 * 在竞技场开放前，检查所有竞技场用户的排名奖励是否发成功
 * @author tianming
 *
 */
class ArenaCheckReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		//没到开服不会发奖
		$curDate = strftime("%Y%m%d", Util::getTime());
		if (GameConf::SERVER_OPEN_YMD > $curDate )
		{
			Logger::warning('the server open date is not reach');
			exit();
		}

		ArenaRound::arenaCheckReward();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */