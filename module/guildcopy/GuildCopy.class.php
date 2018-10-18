<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopy.class.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopy.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/
 
class GuildCopy implements IGuildCopy
{
	private $uid;

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		if (!empty($this->uid))//这里别的线程抛过来的请求addAtkNumFromOther，session里没有uid这些，就不判断啦
		{
			if (!GuildCopyUtil::isGuildCopyOpen($this->uid))
			{
				throw new FakeException('guild copy not open because build level!');
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getUserInfo()
	 */
	public function getUserInfo()
	{
		return GuildCopyLogic::getUserInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getCopyInfo()
	 */
	public function getCopyInfo($copyId)
	{
		$copyId = intval($copyId);
		if ($copyId <= 0) 
		{
			throw new FakeException("invalid copyId:%d", $copyId);
		}
		
		return GuildCopyLogic::getCopyInfo($this->uid, $copyId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::setTarget()
	 */
	public function setTarget($copyId)
	{
		$copyId = intval($copyId);
		if ($copyId <= 0) 
		{
			throw new FakeException("invalid copyId:%d", $copyId);
		}
		
		return GuildCopyLogic::setTarget($this->uid, $copyId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::attack()
	 */
	public function attack($copyId, $baseIndex)
	{
		$copyId = intval($copyId);
		if ($copyId <= 0) 
		{
			throw new FakeException("invalid copyId:%d", $copyId);
		}
		
		$baseIndex = intval($baseIndex);
		if ($baseIndex <= 0 || $baseIndex > GuildCopyCfg::BASE_COUNT) 
		{
			throw new FakeException("invalid baseId:%d", $baseIndex);
		}
		
		return GuildCopyLogic::attack($this->uid, $copyId, $baseIndex);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getRankList()
	 */
	public function getRankList()
	{
		return GuildCopyLogic::getRankList($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::addAtkNum()
	 */
	public function addAtkNum()
	{
		return GuildCopyLogic::addAtkNum($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::refresh()
	 */
	public function refresh()
	{
		return GuildCopyLogic::refresh($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::recvPassReward()
	 */
	public function recvPassReward()
	{
		return GuildCopyLogic::recvPassReward($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getBoxInfo()
	 */
	public function getBoxInfo()
	{
		return GuildCopyLogic::getBoxInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getLastBoxInfo()
	 */
	public function getLastBoxInfo()
	{
		return GuildCopyLogic::getLastBoxInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::openBox()
	 */
	public function openBox($boxId)
	{
		$boxId = intval($boxId);
		if ($boxId <= 0)
		{
			throw new FakeException("invalid boxId:%d", $boxId);
		}
		
		return GuildCopyLogic::openBox($this->uid, $boxId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::getShopInfo()
	 */
	public function getShopInfo()
	{
		$shop = new GuildCopyShop();
		return $shop->getShopInfo();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildCopy::buy()
	 */
	public function buy($goodsId, $num)
	{
		$shop = new GuildCopyShop();
		return $shop->buy($goodsId, $num);
	}
	
	/**
	 * 别的玩家刷新增加军团成员的攻击次数次数
	 * 
	 * @param int $aUid
	 * @param string $uname
	 * @param int $addAtkNum
	 * @param int $type
	 */
	public function addAtkNumFromOther($aUid, $uname, $addAtkNum)
	{
		return GuildCopyLogic::addAtkNumFromOther($aUid, $uname, $addAtkNum);
	}
	
	/**
	 * Boss信息
	 * */
	public function bossInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		return GuildCopyLogic::bossInfo($uid);
	}
	
	/**
	 * 购买BOSS攻击次数
	 * */
	public function buyBoss($count)
	{
		$uid = RPCContext::getInstance()->getUid();
		return GuildCopyLogic::buyBoss($uid, $count);
	}
	
	/**
	 * 攻击BOSS
	 * */
	public function attackBoss()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		return GuildCopyLogic::attackBoss($uid);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */