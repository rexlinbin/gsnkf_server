<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddHonor.php 137018 2014-10-22 04:00:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddHonor.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-22 04:00:30 +0000 (Wed, 22 Oct 2014) $
 * @version $Revision: 137018 $
 * @brief 
 *  
 **/
class AddHonor extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddHonor.php uid num\n";

		$uid = intval($arrOption[0]);
		$honor = intval($arrOption[1]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		CompeteLogic::addHonor($uid, $honor);
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */