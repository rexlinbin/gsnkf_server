<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteEndReward.php 89896 2014-02-13 10:40:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CompeteEndReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-02-13 10:40:25 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89896 $
 * @brief 
 *  
 **/
/**
 * 在比武每轮结束后的某个时间点执行
 * 23:00执行，给前端推送开始发奖的消息
 * @author tm
 */
class CompeteEndReward extends BaseScript
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
		
		CompeteLogic::endReward();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */