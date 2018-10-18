<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassScript.php 148005 2014-12-22 02:38:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/PassScript.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-22 02:38:46 +0000 (Mon, 22 Dec 2014) $
 * @version $Revision: 148005 $
 * @brief 
 *  
 **/
class PassScript extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		if( !PassLogic::isHandsOffTime( time() ) )
		{
			Logger::fatal('not in hands off time');
			return;
		}
		PassLogic::rewardForRank();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */