<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassSync.php 178426 2015-06-11 13:31:28Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldPassSync.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-11 13:31:28 +0000 (Thu, 11 Jun 2015) $
 * @version $Revision: 178426 $
 * @brief 
 *  
 **/
 
class WorldPassSync extends BaseScript
{
	private function printUsage()
	{
		printf("Usage:\n");
		printf("btscript game001 WorldPassSync.php serverId teamId do|check 同步一个服的数据到跨服库\n");
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 3)
		{
			$this->printUsage();
			exit(0);
		}
		
		$serverId = $arrOption[0];
		$teamId = $arrOption[1];
		$commit = strtolower($arrOption[2]) == 'do' ? TRUE : FALSE;
		
		WorldPassScriptLogic::syncInner2Cross($serverId, $teamId, $commit);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */