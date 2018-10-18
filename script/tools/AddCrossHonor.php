<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddCrossHonor.php 214820 2015-12-09 09:56:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddCrossHonor.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-09 09:56:22 +0000 (Wed, 09 Dec 2015) $
 * @version $Revision: 214820 $
 * @brief 
 *  
 **/
class AddCrossHonor extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddCrossHonor.php uid num\n";

		$uid = intval($arrOption[0]);
		$crossHonor = intval($arrOption[1]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($user->getServerId(), $user->getPid(), $uid);
		$worldCompeteInnerUserObj->addCrossHonor($crossHonor);
		$worldCompeteInnerUserObj->update();
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */