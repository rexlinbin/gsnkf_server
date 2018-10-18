<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteGenerateReward.php 89897 2014-02-13 10:40:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CompeteGenerateReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-02-13 10:40:41 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89897 $
 * @brief 
 *  
 **/
/**
 * 产生奖励
 * 在比武每轮结束后某个时间点执行
 * 23点结束，23点01分发奖
 */
class CompeteGenerateReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		//没到开服不会发奖
		$curDate = strftime("%Y%m%d", Util::getTime());
		if (GameConf::SERVER_OPEN_YMD > $curDate )
		{
			Logger::warning('the server open date is not reach');
			exit();
		}

		CompeteLogic::generateReward();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */