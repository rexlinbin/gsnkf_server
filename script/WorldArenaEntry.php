<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaEntry.php 194733 2015-08-27 02:25:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldArenaEntry.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-27 02:25:53 +0000 (Thu, 27 Aug 2015) $
 * @version $Revision: 194733 $
 * @brief 
 *  
 **/
 
class WorldArenaEntry extends BaseScript
{
	private function printUsage()
	{
		printf("Usage:\n");
		printf("btscript game001 WorldArenaEntry.php team   do|check 分组\n");
		printf("btscript game001 WorldArenaEntry.php range  do|check 分房\n");
		printf("btscript game001 WorldArenaEntry.php reward do|check [1_9_5 ...] 发奖，可以单独给某个组，某个服，某个玩家发奖\n");
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 2)
		{
			$this->printUsage();
			exit(0);
		}

		$validType = array('team', 'range', 'reward');
		$type = strtolower($arrOption[0]);
		array_shift($arrOption);
		if (!in_array($type, $validType))
		{
			$this->printUsage();
			exit(0);
		}

		$validOp = array('do', 'check');
		$op = strtolower($arrOption[0]);
		array_shift($arrOption);
		if (!in_array($op, $validOp))
		{
			$this->printUsage();
			exit(0);
		}
		$commit = ($op == 'do' ? TRUE : FALSE);

		Logger::info('***************** WORLD_ARENA_ENTRY : type[%s] op[%s] Begin!!!! ********************', $type, $op);
		
		//跨服db上只有跨服活动的配置，这就导致db上的主干版本号始终低于平台版本号。
		RPCContext::getInstance()->getFramework()->setDb(WorldArenaUtil::getCrossDbName());
		$curVersion = ActivityConfLogic::getTrunkVersion();
		ActivityConfLogic::doRefreshConf($curVersion, TRUE, FALSE);
		WorldArenaConfObj::getInstance(WorldArenaField::CROSS);

		if ($type == 'reward') // 发奖
		{
			$arrUid = array();
			foreach ($arrOption as $aUserInfo)
			{
				$arrUserInfo = array_map('intval', explode('_', $aUserInfo));
				if (count($arrUserInfo) == 1)
				{
					$aTeamId = $arrUserInfo[0];
					$arrUid[$aTeamId] = array();
				}
				else if (count($arrUserInfo) == 2)
				{
					$aTeamId = $arrUserInfo[0];
					$aServerId = $arrUserInfo[1];
					if (!isset($arrUid[$aTeamId])
					|| !isset($arrUid[$aTeamId][$aServerId])
					|| !empty($arrUid[$aTeamId][$aServerId]))
					{
						$arrUid[$aTeamId][$aServerId] = array();
					}
				}
				else if (count($arrUserInfo) == 3)
				{
					$aTeamId = $arrUserInfo[0];
					$aServerId = $arrUserInfo[1];
					$aUid = $arrUserInfo[2];
					if (!isset($arrUid[$aTeamId])
					|| !isset($arrUid[$aTeamId][$aServerId])
					|| (!empty($arrUid[$aTeamId][$aServerId]) && !isset($arrUid[$aTeamId][$aServerId][$aUid])))
					{
						$arrUid[$aTeamId][$aServerId][$aUid] = TRUE;
					}
				}
				else
				{
					// do nothing
				}
			}
			
			WorldArenaScriptLogic::reward($commit, $arrUid);
		}
		else if ($type == 'range') // 分房
		{
			$force = FALSE;
			if (!empty($arrOption) && strtolower($arrOption[0]) == 'force')
			{
				$force = TRUE;
			}
			
			WorldArenaScriptLogic::rangeRoom($commit, $force);
		}
		else // 分组
		{
			$force = FALSE;
			if (!empty($arrOption) && strtolower($arrOption[0]) == 'force') 
			{
				$force = TRUE;
			}
			
			WorldArenaScriptLogic::syncAllTeamFromPlat2Cross($commit, $force);
		}

		Logger::info('***************** WORLD_ARENA_ENTRY : type[%s] op[%s] End!!!!! ********************', $type, $op);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */