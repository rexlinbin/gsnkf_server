<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddGrain.php 158951 2015-02-13 10:25:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddGrain.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-02-13 10:25:22 +0000 (Fri, 13 Feb 2015) $
 * @version $Revision: 158951 $
 * @brief 
 *  
 **/
class AddGrain extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddGrain.php uid num\n";

		$uid = intval($arrOption[0]);
		$num = intval($arrOption[1]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$member = GuildMemberObj::getInstance($uid);
		$member->addGrainNum($num);
		$member->update();
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */