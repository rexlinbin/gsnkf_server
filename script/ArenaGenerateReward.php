<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaGenerateReward.php 149372 2014-12-26 10:02:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ArenaGenerateReward.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2014-12-26 10:02:55 +0000 (Fri, 26 Dec 2014) $
 * @version $Revision: 149372 $
 * @brief 
 *  
 **/

require MOD_ROOT . '/arena/index.php';

/**
 * 产生奖励
 * 在竞技场每轮结束后某个时间点执行
 * 22:00冻结竞技场, 这个脚本应该延后几秒开始执行
  * @author idyll
 *
 */
class ArenaGenerateReward extends BaseScript
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
		
		if (ArenaConf::NO_CRON == true)
		{
			Logger::info('ArenaConf::NO_CRON is set to true');
			exit();
		}
		
		$force = ((!empty($arrOption) && $arrOption[0] == 'force') ? TRUE : FALSE);
		 
		// 发奖脚本目前只生成竞技场排名快照
		ArenaRound::arenaRankSnap($force);
		
		/*$arrOption = array_map('strtolower', $arrOption);
		$redo = false;
		$limit = ArenaConf::REWARD_REDO_LIMIT_HOURS;
		
		$arrArgv  = $this->getOption($arrOption, "r::l::");
		if (isset($arrArgv['r']))
		{
			$redo = true;
		}
		
		if (isset($arrArgv['l']) && $arrArgv['l']!=0)
		{
			$limit = intval($arrArgv['l']);
		}
		
		if ($limit >= (ArenaDateConf::LAST_DAYS * 24 - ArenaConf::REWARD_REDO_LIMIT_HOURS_RETAIN))
		{
			Logger::fatal('limit %d is too large', $limit);
			exit('Usage: btscript -r -l 50 \nerr. limit is too large.\n');			
		}
		
		ArenaRound::generateReward($redo, $limit);*/
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */