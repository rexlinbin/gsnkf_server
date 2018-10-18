<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildShop.class.php 144055 2014-12-03 12:40:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/guildshop/GuildShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-12-03 12:40:43 +0000 (Wed, 03 Dec 2014) $
 * @version $Revision: 144055 $
 * @brief 
 *  
 **/
class GuildShop implements IGuildShop
{
	/**
	 * 用户id
	 * @var $uid
	 */
	private $uid;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		if (EnSwitch::isSwitchOpen(SwitchDef::GUILD) == false)
		{
			throw new FakeException('user:%d does not open the guild', $this->uid);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildShop::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('GuildShop::getShopInfo Start.');
		
		$ret = GuildShopLogic::getShopInfo($this->uid);
		
		Logger::trace('GuildShop::getShopInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildShop::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('GuildShop::buy Start.');
	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		
		//检查商品是否存在
		if (empty(btstore_get()->GUILD_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$ret = GuildShopLogic::buy($this->uid, $goodsId, $num);
	
		Logger::trace('GuildShop::buy End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuildShop::refreshList()
	 */
	public function refreshList()
	{
		Logger::trace('GuildShop::refreshList Start.');
		
		$ret = GuildShopLogic::refreshList($this->uid);
		
		Logger::trace('GuildShop::refreshList End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */