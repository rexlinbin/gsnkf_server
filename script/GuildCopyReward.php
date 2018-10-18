<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyReward.php 164330 2015-03-30 13:00:47Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GuildCopyReward.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-30 13:00:47 +0000 (Mon, 30 Mar 2015) $
 * @version $Revision: 164330 $
 * @brief 
 *  
 **/
 
class GuildCopyReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$commit = TRUE;         // 默认的操作方式是会直接提交数据库
		$arrUid = array();  	// 默认的操作对象是所有在排行榜中的玩家
		
		if (!empty($arrOption) &&  strtolower($arrOption[0]) == 'check') 
		{
			$commit = FALSE;	// 只打印日志，不提交数据库，方便验证
			array_shift($arrOption);
		}
		
		foreach ($arrOption as $aUid)
		{
			if (!in_array(intval($aUid), $arrUid)) 
			{
				$arrUid[] = $aUid;
			}
		}
		
		Logger::info('GUILD_COPY_REWARD_ENTRY : commit[%s], uid range[%s]', ($commit ? 'yes' : 'no'), (empty($arrUid) ? 'all' : $arrUid));
		
		GuildCopyScriptLogic::reward($commit, $arrUid);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */