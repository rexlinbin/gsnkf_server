<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWorldCompete.class.php 218463 2015-12-29 08:45:36Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/EnWorldCompete.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-12-29 08:45:36 +0000 (Tue, 29 Dec 2015) $
 * @version $Revision: 218463 $
 * @brief 
 *  
 **/
 
class EnWorldCompete
{
	public static function getCrossHonor($serverId, $pid, $uid)
	{
		$crossHonor = 0;
		if (EnSwitch::isSwitchOpen(SwitchDef::WORLDCOMPETE, $uid))
		{
			$crossHonor = WorldCompeteInnerUserObj::getInstance($serverId, $pid)->getCrossHonor();
		}
		return $crossHonor;
	}
	
	public static function getTopActivityInfo()
	{
		return WorldCompeteUtil::getTopActivityInfo();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */