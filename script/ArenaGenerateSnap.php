<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaGenerateSnap.php 149605 2014-12-29 02:14:43Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ArenaGenerateSnap.php $
 * @author $Author: wuqilin $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-29 02:14:43 +0000 (Mon, 29 Dec 2014) $
 * @version $Revision: 149605 $
 * @brief 
 *  
 **/

/**
 * 生成竞技场排名快照
 * 在竞技场每轮结束后某个时间点执行
 * 22:00冻结竞技场, 这个脚本应该延后几秒开始执行
 * 接受唯一字符串参数‘force’，如果被设置，则强制生成快照
 */
class ArenaGenerateSnap extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$force = ((!empty($arrOption) && $arrOption[0] == 'force') ? TRUE : FALSE);
		if (!$force) 
		{
			if (Util::getTime() < (strtotime(GameConf::SERVER_OPEN_YMD . '000000') - SECONDS_OF_DAY))
			{
				Logger::warning('No need to generate arena snap, curr time[%s], server open day[%s]', strftime('%Y%m%d-%H%M%S', Util::getTime()), GameConf::SERVER_OPEN_YMD);
				//echo("no need now!\n");
				exit();
			}
		}
		
		ArenaRound::arenaRankSnap($force);
		if( $force )
		{
			echo("done!\n");
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */