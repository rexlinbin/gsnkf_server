<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DragonShop.class.php 138114 2014-10-30 10:33:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/dragonshop/DragonShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-30 10:33:53 +0000 (Thu, 30 Oct 2014) $
 * @version $Revision: 138114 $
 * @brief 
 *  
 **/
class DragonShop extends Mall implements IDragonShop
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_DRAGON);
		
		if (EnSwitch::isSwitchOpen(SwitchDef::DRAGON) == false)
		{
			throw new FakeException('user:%d does not open the dragon shop', $uid);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IDragonShop::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('DragonShop::getShopInfo Start.');
		
		$ret = $this->getInfo();
		
		Logger::trace('DragonShop::getShopInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IDragonShop::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('DragonShop::buy Start.');

		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		if (empty(btstore_get()->DRAGON_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
	
		$this->exchange($goodsId, $num);
		DragonManager::getInstance($this->uid)->save();
	
		Logger::trace('DragonShop::buy End.');
	
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('DragonShop::getExchangeConf Start.');
	
		if (empty(btstore_get()->DRAGON_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId:%d', $goodsId);
			return array();
		}
	
		$ret = btstore_get()->DRAGON_GOODS[$goodsId];
	
		Logger::trace('DragonShop::getExchangeConf End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		Logger::trace('DragonShop::subExtra Start.');
	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		//检查商品是否存在
		if (empty(btstore_get()->DRAGON_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		$subPoint = btstore_get()->DRAGON_GOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		$ret = DragonManager::getInstance($this->uid)->subTotalPoint($subPoint * $num);
	
		Logger::trace('DragonShop::subExtra End.');
	
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */