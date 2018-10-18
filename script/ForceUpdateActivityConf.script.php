<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ForceUpdateActivityConf.script.php 145578 2014-12-11 12:03:44Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ForceUpdateActivityConf.script.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2014-12-11 12:03:44 +0000 (Thu, 11 Dec 2014) $
 * @version $Revision: 145578 $
 * @brief 
 *  
 **/



class ForceUpdateActivityConf extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		
		$sync = false;
		
		if( isset($arrOption[0]) && $arrOption[0] == 'sync')
		{
			$sync = true;
		}
		
		if( isset($arrOption[1]) && $arrOption[1] == 'nocheck' )
		{
			ActivityConf::$STRICT_CHECK_CONF = false;
		}
		
		
		if( $sync )
		{
			ActivityConfLogic::doRefreshConf(0, true, false);
			RPCContext::getInstance()->delAllCallBack();
			printf("refresh activity conf done\n");
		}
		else
		{
			$curVersion = ActivityConfLogic::getTrunkVersion();
			
			ActivityConfLogic::refreshConf($curVersion, true);
			
			printf("curVersion:%d, send refresh request done\n", $curVersion);
		}
		
		
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */