<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWorldPass.class.php 218503 2015-12-29 10:32:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/EnWorldPass.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-12-29 10:32:55 +0000 (Tue, 29 Dec 2015) $
 * @version $Revision: 218503 $
 * @brief 
 *  
 **/
 
class EnWorldPass
{
	/**
	 * 给一个玩家加炼狱令，只能在玩家自己的线程里
	 * 
	 * @param int $uid
	 * @param int $hellPoint
	 */
	public static function addHellPoint($uid, $hellPoint)
	{
		$sessionUid = RPCContext::getInstance()->getUid();
		if ($sessionUid != $uid)
		{
			throw new FakeException('not in myconnection, can not add hell point');
		}
		
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldPassInnerUserObj->addHellPoint($hellPoint);
		$worldPassInnerUserObj->update();
	}
	
	public static function getTopActivityInfo()
	{
		$ret = array
		(
				'status' => 'ok',
				'extra' => array('num' => 0),
		);
		
		// 开关没开，返回invalid
		if (!EnSwitch::isSwitchOpen(SwitchDef::WORLDPASS)
			|| !WorldPassConf::$MY_SWITCH
			|| !WorldPassUtil::inActivity())
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		// 获得玩家serverId和pid
		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($uid);
		
		// 检查是否在一个分组内
		$teamId = WorldPassUtil::getTeamIdByServerId($serverId);
		if (empty($teamId))
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
		$ret['extra']['num'] = $worldPassInnerUserObj->getAtkNum(); 
		
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */