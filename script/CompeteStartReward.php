<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteStartReward.php 75072 2013-11-15 07:30:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CompeteStartReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-15 07:30:26 +0000 (Fri, 15 Nov 2013) $
 * @version $Revision: 75072 $
 * @brief 
 *  
 **/
/**
 * 在比武每轮结束后的某个时间点执行
 * 23:00执行，给前端推送开始发奖的消息
 * @author tm
 */
class CompeteStartReward extends BaseScript
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

		CompeteLogic::startReward();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */