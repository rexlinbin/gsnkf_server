<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RegisterForLordwarCross.php 141633 2014-11-24 07:19:01Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/RegisterForLordwarCross.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-24 07:19:01 +0000 (Mon, 24 Nov 2014) $
 * @version $Revision: 141633 $
 * @brief 
 *  
 **/
class RegisterForLordwarCross extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		RPCContext::getInstance()->getFramework()->setDb( LordwarUtil::getCrossDbName() );
			
		//跨服db上只有跨服活动的配置，这就导致db上的主干版本号始终低于平台版本号。
		$curVersion = ActivityConfLogic::getTrunkVersion();
		ActivityConfLogic::doRefreshConf($curVersion, true, false );
		LordwarConfMgr::getInstance(LordwarField::CROSS);
		//要在跨服机器上执行
		$teamId = $arrOption[0];
		if( empty( $teamId) || $teamId < 0 || !is_numeric( $teamId ) )
		{
			echo 'invalid';
		}
		else 
		{
			LordwarLogic::registerForCross( $teamId );
		}
		
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */