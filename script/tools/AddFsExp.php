<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddFsExp.php 244738 2016-05-30 10:20:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddFsExp.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-30 10:20:46 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244738 $
 * @brief 
 *  
 **/
class AddFsExp extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddFsExp.php uid num\n";

		$uid = intval($arrOption[0]);
		$num = intval($arrOption[1]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		$user->addFsExp($num);
		$user->update();
		echo "done\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */