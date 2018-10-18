<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkCompete.php 89879 2014-02-13 09:48:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkCompete.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-02-13 09:48:38 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89879 $
 * @brief 
 *  
 **/
class CheckCompete extends BaseScript
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
		$i = 0;
		$count = CData::MAX_FETCH_SIZE;
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$arrUidPoint = CompeteDao::getRankList($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			$count = count($arrUidPoint);
			$i++;
			//没有数据直接退出
			if ($count == 0)
			{
				break;
			}
			$arrUid = array_keys($arrUidPoint);
			$uid = current($arrUid);
			while ($uid != false)
			{
				$info = CompeteDao::select($uid);
				$rivals = $info[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST];
				foreach ($rivals as $id)
				{
					$data = UserDao::getUserByUid($id, UserDef::$USER_FIELDS);
					if (empty($data)) 
					{
						$invalid[] = $uid;
					}
				}
				$foes = $info[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST];
				foreach ($foes as $id)
				{
					$data = UserDao::getUserByUid($id, UserDef::$USER_FIELDS);
					if (empty($data))
					{
						$invalid[] = $uid;
					}
				}
				$uid = next($arrUid);
			}
		}

		echo "error data in compete: \n";
		print_r($invalid);

		if ($fix)
		{
			foreach ($invalid as $uid)
			{
				RPCContext::getInstance()->setSession('global.uid', $uid);
				CompeteLogic::refreshRivalList($uid);
				echo "fix uid " . $uid . "\n";
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