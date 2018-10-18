<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnival.class.php 198045 2015-09-11 05:29:12Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/WorldCarnival.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 05:29:12 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198045 $
 * @brief 
 *  
 **/
 
class WorldCarnival implements IWorldCarnival
{
	private $uid;

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (!EnActivity::isOpen(ActivityName::WORLDCARNIVAL))
		{
			throw new FakeException('world carnival not open');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCarnival::getCarnivalInfo()
	 */
	public function getCarnivalInfo()
	{
		return WorldCarnivalLogic::getCarnivalInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCarnival::updateFmt()
	 */
	public function updateFmt()
	{
		return WorldCarnivalLogic::updateFmt($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCarnival::getRecord()
	 */
	public function getRecord($round)
	{
		$round = intval($round);
		if ($round < WorldCarnivalRound::ROUND_1
			|| $round > WorldCarnivalRound::ROUND_3) 
		{
			throw new FakeException('invalid round[%d] of getRecord', $round);
		}
		
		return WorldCarnivalLogic::getRecord($this->uid, $round);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCarnival::getFighterDetail()
	 */
	public function getFighterDetail($aServerId, $aPid)
	{
		$aServerId = intval($aServerId);
		$aPid = intval($aPid);
		if ($aServerId <= 0 || $aPid <= 0) 
		{
			throw new FakeException('invalid serverId[%d] or pid[%d]', $aServerId, $aPid);
		}
		
		return WorldCarnivalLogic::getFighterDetail($this->uid, $aServerId, $aPid);
	}
	
	//***************************************** 以下是内部调用的函数 *************************************
	
	public function getBattleFmt($serverId, $pid)
	{
		return WorldCarnivalLogic::getBattleFmt($serverId, $pid);
	}
	
	public function getBattleDataOfUsers($serverId, $pid)
	{
		return WorldCarnivalLogic::getBattleDataOfUsers($serverId, $pid);
	}
	
	public function push($serverId, $arrPid, $arrData)
	{
		return WorldCarnivalLogic::push($serverId, $arrPid, $arrData);
	}
	
	public static function getUserBasicInfo($serverId, $pid)
	{
		return WorldCarnivalLogic::getUserBasicInfo($serverId, $pid);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */