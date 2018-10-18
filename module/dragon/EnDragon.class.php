<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnDragon.class.php 218503 2015-12-29 10:32:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/EnDragon.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-12-29 10:32:55 +0000 (Tue, 29 Dec 2015) $
 * @version $Revision: 218503 $
 * @brief 
 *  
 **/
 
class EnDragon
{
	public static function getTopActivityInfo()
	{
		$ret = array
		(
				'status' => 'ok',
				'extra' => array('num' => 0),
		);
		
		if (!EnSwitch::isSwitchOpen(SwitchDef::DRAGON))
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$dragon = DragonManager::getInstance($uid);
		$ret['extra']['num'] = $dragon->getFreeResetNum();
		
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */