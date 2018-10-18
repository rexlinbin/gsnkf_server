<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteEntry.php 202685 2015-10-16 06:13:52Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldCompeteEntry.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-16 06:13:52 +0000 (Fri, 16 Oct 2015) $
 * @version $Revision: 202685 $
 * @brief 
 *  
 **/
 
class WorldCompeteEntry extends BaseScript
{
	private function printUsage()
	{
		printf("Usage:\n");
		printf("btscript game001 WorldCompeteEntry reward do|check	[1_9_5 ...] 炼狱挑战排名奖，可以单独给某个组，某个服，某个玩家发奖\n");
		printf("btscript game001 WorldCompeteEntry team   do|check [next|curr] 同步分组数据并且将新服自动分组, next代表同步下个周期的分组，curr代表这个周期\n");
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
		
		$validType = array('reward', 'team');
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
		
		Logger::info('***************** WORLD_COMPETE_ENTRY : type[%s] op[%s] Begin!!!! ********************', $type, $op);
		
		if ($type == 'reward') 
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
			
			WorldCompeteScriptLogic::reward($arrUid, $commit);
		}
		else
		{			
			$next = TRUE;
			if (!empty($arrOption) && strtolower($arrOption[0]) == 'curr')
			{
				$next = FALSE;
			}
			
			WorldCompeteScriptLogic::syncAllTeamFromPlat2Cross($commit, $next);
		}
		
		Logger::info('***************** WORLD_COMPETE_ENTRY : type[%s] op[%s] End!!!!! ********************', $type, $op);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */