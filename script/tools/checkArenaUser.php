<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkArenaUser.php 63503 2013-09-06 08:22:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkArenaUser.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-06 08:22:26 +0000 (Fri, 06 Sep 2013) $
 * @version $Revision: 63503 $
 * @brief 
 *  
 **/

class CheckArenaUser extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (empty($arrOption[0]) || $arrOption[0] == 'help')
		{
			$this->usage();
			return;
		}
		
		$option = $arrOption[0];
		if ($option == 'check') 
		{
			$fix = false;
		}
		elseif ($option == 'fix')
		{
			$fix = true;
		}
		else 
		{
			echo "invalid operation!\n";
			$this->usage();
			return;
		}
		
		$invalid = array();
		$count = ArenaDao::getCount();
		for ($i = 1; $i <= $count; $i++)
		{
			$ret = ArenaDao::getByPos($i, array('uid'));
			$uid = $ret['uid'];
			$userData = UserDao::getUserByUid($uid, array('uid'));
			if (empty($userData))
			{
				$invalid[$i] = $uid;
			}
		}
		
		echo "error data in arena: \n";
		print_r($invalid);
		
		if ($fix) 
		{
			foreach ($invalid as $pos => $uid)
			{
				//插入一个机器人
				$pid = 40000 + rand(0,9999);
				$utid = mt_rand(1, 2);
				$uname = 't' . $pid;
				$user = UserLogic::createUser($pid, $utid, $uname);
				$uid = $user['uid'];
				ArenaDao::updateByPos($pos, array('uid', '=', $uid));
				echo "fix position " . $pos . " by create a new user ". $uid ."!\n";
			}
		}
		
		echo "ok\n";
	}		
	
	private function usage()
	{
	
		echo "usage: btscript game001 checkArenaUser.php check|fix\n";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */