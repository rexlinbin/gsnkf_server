<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArena.class.php 184080 2015-07-14 06:06:30Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArena.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-14 06:06:30 +0000 (Tue, 14 Jul 2015) $
 * @version $Revision: 184080 $
 * @brief 
 *  
 **/
 
class WorldArena implements IWorldArena
{
	private $uid;

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		// 判断是否处在活动有效期内
		$confObj = WorldArenaConfObj::getInstance();
		if (!$confObj->isValid()) 
		{
			throw new FakeException('not in activity time, start time[%s], end time[%s], cur time[%s]', strftime('%Y%m%d %H:%M:%S', $confObj->getActivityStartTime()), strftime('%Y%m%d %H:%M:%S', $confObj->getActivityEndTime()), strftime('%Y%m%d %H:%M:%S', Util::getTime()));
		}
		
		if (!WorldArenaConf::$MY_SWITCH)
		{
			throw new FakeException('my switch not open.');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::getWorldArenaInfo()
	 */
	public function getWorldArenaInfo()
	{
		return WorldArenaLogic::getWorldArenaInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::signUp()
	 */
	public function signUp()
	{
		return WorldArenaLogic::signUp($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::updateFmt()
	 */
	public function updateFmt()
	{
		return WorldArenaLogic::updateFmt($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::attack()
	 */
	public function attack($serverId, $pid, $skip = 1)
	{
		$serverId = intval($serverId);
		if ($serverId <= 0) 
		{
			throw new FakeException('invalid param serverId[%d]', $serverId);
		}
		
		$pid = intval($pid);
		if ($pid <= 0) 
		{
			throw new FakeException('invalid param pid[%d]', $pid);
		}
		
		return WorldArenaLogic::attack($this->uid, $serverId, $pid, $skip);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::buyAtkNum()
	 */
	public function buyAtkNum($num)
	{
		$num = intval($num);
		if ($num <= 0)
		{
			throw new FakeException('invalid param num[%d]', $num);
		}
		
		return WorldArenaLogic::buyAtkNum($this->uid, $num);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::reset()
	 */
	public function reset($type)
	{
		if (!in_array($type, WorldArenaDef::$VALID_RESET_TYPE)) 
		{
			throw new FakeException('invalid reset type[%s], all valid type[%s]', $type, WorldArenaDef::$VALID_RESET_TYPE);
		}
		
		return WorldArenaLogic::reset($this->uid, $type);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::getRecordList()
	 */
	public function getRecordList()
	{
		return WorldArenaLogic::getRecordList($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldArena::getRankList()
	 */
	public function getRankList()
	{
		return WorldArenaLogic::getRankList($this->uid);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */