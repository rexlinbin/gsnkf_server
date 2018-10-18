<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompete.class.php 208139 2015-11-09 10:00:18Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompete.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-11-09 10:00:18 +0000 (Mon, 09 Nov 2015) $
 * @version $Revision: 208139 $
 * @brief 
 *  
 **/
 
class WorldCompete implements IWorldCompete
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getWorldCompeteInfo()
	 */
	public function getWorldCompeteInfo()
	{
		self::checkSwitch();
		return WorldCompeteLogic::getWorldCompeteInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::attack()
	 */
	public function attack($serverId, $pid, $crazy = 1, $skip = 1)
	{
		self::checkSwitch();
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
		
		return WorldCompeteLogic::attack($this->uid, $serverId, $pid, $crazy, $skip);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::buyAtkNum()
	 */
	public function buyAtkNum($num)
	{
		self::checkSwitch();
		return WorldCompeteLogic::buyAtkNum($this->uid, $num);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::refreshRival()
	 */
	public function refreshRival()
	{
		self::checkSwitch();
		return WorldCompeteLogic::refreshRival($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getPrize()
	 */
	public function getPrize($num)
	{
		self::checkSwitch();
		$num = intval($num);
		if ($num <= 0) 
		{
			throw new FakeException('invalid param num[%d]', $num);
		}
		
		return WorldCompeteLogic::getPrize($this->uid, $num);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::worship()
	 */
	public function worship()
	{
		self::checkSwitch();
		return WorldCompeteLogic::worship($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getFighterDetail()
	 */
	public function getFighterDetail($aServerId, $aPid)
	{
		self::checkSwitch();
		$aServerId = intval($aServerId);
		$aPid = intval($aPid);
		if ($aServerId <= 0 || $aPid <= 0)
		{
			throw new FakeException('invalid serverId[%d] or pid[%d]', $aServerId, $aPid);
		}
	
		return WorldCompeteLogic::getFighterDetail($this->uid, $aServerId, $aPid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getRankList()
	 */
	public function getRankList()
	{
		self::checkSwitch();
		return WorldCompeteLogic::getRankList($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getChampion()
	 */
	public function getChampion()
	{
		self::checkSwitch();
		return WorldCompeteLogic::getChampion($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::getShopInfo()
	 */
	public function getShopInfo()
	{
		self::checkSwitch();
		return WorldCompeteLogic::getShopInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IWorldCompete::buyGoods()
	 */
	public function buyGoods($goodsId, $num)
	{
		self::checkSwitch();
		if (!isset(btstore_get()->WORLD_COMPETE_GOODS[$goodsId]))
		{
			throw new FakeException('no config of goods[%d]', $goodsId);
		}
		
		return WorldCompeteLogic::buyGoods($this->uid, $goodsId, $num);
	}
	
	public function getBattleFormation($serverId, $pid, $teamId)
	{
		return WorldCompeteUtil::getOtherUserBattleFormation($serverId, $pid, $teamId);
	}
	
	public function getBattleDataOfUsers($serverId, $pid)
	{
		return WorldCompeteUtil::getOtherUserBattleData($serverId, $pid);
	}
	
	public function checkSwitch()
	{
		if (!EnSwitch::isSwitchOpen(SwitchDef::WORLDCOMPETE))
		{
			throw new FakeException('switch not open!');
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */