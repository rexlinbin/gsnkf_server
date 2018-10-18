<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddStar.php 126702 2014-08-13 09:39:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddStar.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-13 09:39:46 +0000 (Wed, 13 Aug 2014) $
 * @version $Revision: 126702 $
 * @brief 
 *  
 **/

class AddStar extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 AddStar.php check|fix uid stid\n";

		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}

		$flag = true;
		$uid = intval($arrOption[1]);
		$stid = intval($arrOption[2]);
		if ($fix)
		{
			Util::kickOffUser($uid);
		}
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		$num = $user->getHeroManager()->getHeroNumByHtid($stid);
		if (empty($num))
		{
			echo "hero is not-exist";
		}
		$myStar = MyStar::getInstance($uid);
		$allStid = $myStar->getAllStarTid();
		if (empty($allStid) || !in_array($stid, $allStid))
		{
			$flag = false;
		}
		if ($flag)
		{
			echo "star is exist\n";
		}
		else
		{
			echo "star is not-exist\n";
		}

		if (!$flag && $fix)
		{
			$myStar->addNewStar($stid);
			$myStar->update();
		}
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */