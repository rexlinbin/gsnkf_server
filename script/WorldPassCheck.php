<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassCheck.php 180319 2015-06-24 04:01:25Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldPassCheck.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-24 04:01:25 +0000 (Wed, 24 Jun 2015) $
 * @version $Revision: 180319 $
 * @brief 
 *  
 **/
 
class WorldPassCheck extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$commit = FALSE;
		if (!empty($arrOption) && strtolower($arrOption[0] == 'do'))
		{
			$commit = TRUE;
			array_shift($arrOption);
		}
		
		$next = FALSE;
		if (!empty($arrOption) && strtolower($arrOption[0] == 'next')) 
		{
			$next = TRUE;
			array_shift($arrOption);
		}
		
		WorldPassScriptLogic::checkTeamChange($commit, $next);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */