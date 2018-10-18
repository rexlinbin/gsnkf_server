<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Welcomeback.class.php 258601 2016-08-26 08:51:33Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/Welcomeback.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-26 08:51:33 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258601 $
 * @brief 
 *  
 **/
class Welcomeback implements IWelcomeback
{
	public function getOpen()
	{
		return WelcomebackLogic::getOpen(EnUser::getUserObj()->getUid());
	}
	
	public function getInfo()
	{
		return WelcomebackLogic::getInfo(EnUser::getUserObj()->getUid());
	}
	
	public function gainReward($taskId, $select = 0)
	{
		return WelcomebackLogic::gainReward(EnUser::getUserObj()->getUid(), $taskId, $select);
	}
	
	public function buy($taskId, $num = 1)
	{
		return WelcomebackLogic::buy(EnUser::getUserObj()->getUid(), $taskId, $num);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */