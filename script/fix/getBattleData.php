<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: getBattleData.php 257598 2016-08-22 07:26:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/getBattleData.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-22 07:26:53 +0000 (Mon, 22 Aug 2016) $
 * @version $Revision: 257598 $
 * @brief 
 *  
 **/
 
class getBattleData extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if (empty($arrOption))
		{
			printf("usage btscript game001 getBattleData.php uid\n");
			return;
		}
		
		$uid = intval($arrOption[0]);
		$userInfo = UserDao::getUserByUid($uid, array('uid', 'pid', 'uname'));
		if (empty($userInfo))
		{
			printf("not found userInfo of uid:%d\n", $uid);
			return;
		}
		
		RPCContext::getInstance()->setSession('global.uid', $uid);
		$userObj = EnUser::getUserObj($uid);
		$battleFmt = $userObj->getBattleFormation();
		printf("%s\n", serialize($battleFmt));
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */