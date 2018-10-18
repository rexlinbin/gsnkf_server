<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWelcomeback.class.php 259764 2016-08-31 09:54:41Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/EnWelcomeback.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-31 09:54:41 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259764 $
 * @brief 
 *  
 **/
class EnWelcomeback
{
	public static function updateTask($taskTypeId, $num = 1)
	{
		try 
		{
			$openStatus = RPCContext::getInstance()->getSession(WelcomebackDef::SESSION_OPEN_STATUS);
			if (empty($openStatus))
			{//会进来吗？
				$welcomebackObj = WelcomebackObj::getInstance();
				if ($welcomebackObj->isOpen())
					RPCContext::getInstance()->setSession(WelcomebackDef::SESSION_OPEN_STATUS, WelcomebackDef::SESSION_OPEN);
				else 
					RPCContext::getInstance()->setSession(WelcomebackDef::SESSION_OPEN_STATUS, WelcomebackDef::SESSION_CLOSE);
				$openStatus = RPCContext::getInstance()->getSession(WelcomebackDef::SESSION_OPEN_STATUS);
			}
			
			if ($openStatus == WelcomebackDef::SESSION_OPEN)
			{
				$welcomebackObj = WelcomebackObj::getInstance();
				$welcomebackObj->updateTask($taskTypeId, $num);
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('cant update welcomeback task, $taskTypeId:[%d], $num:[%d], err:[%s]', $taskTypeId, $num, $e->getTraceAsString());
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */