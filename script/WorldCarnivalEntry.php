<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalEntry.php 196741 2015-09-06 11:52:22Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldCarnivalEntry.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-06 11:52:22 +0000 (Sun, 06 Sep 2015) $
 * @version $Revision: 196741 $
 * @brief 
 *  
 **/
 
class WorldCarnivalEntry extends BaseScript
{
	protected function executeScript($arrOption)
	{	
		// 文件锁
		$lockObj = new SimpleFileLock('/tmp/WORLD_CARNIVAL_CROSS_LOCK_FILE', TRUE);
		if ($lockObj->lock() == FALSE)
		{
			Logger::warning('some other process running, quit');
			return;
		}
		
		// 获取配置
		RPCContext::getInstance()->getFramework()->setDb(WorldCarnivalUtil::getCrossDbName());
		$curVersion = ActivityConfLogic::getTrunkVersion();
		ActivityConfLogic::doRefreshConf($curVersion, TRUE, FALSE);
		WorldCarnivalConfObj::getInstance(WorldCarnivalField::CROSS);
		
		// 战斗
		WorldCarnivalScriptLogic::fight();
		
		// 解锁
		$lockObj->unlock();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */