<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ShopExchange.class.php 114199 2014-06-13 09:07:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shopexchange/ShopExchange.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-13 09:07:43 +0000 (Fri, 13 Jun 2014) $
 * @version $Revision: 114199 $
 * @brief 
 *  
 **/
class ShopExchange extends Mall implements IShopExchange
{

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();

		parent::__construct($uid, MallDef::MALL_TYPE_SHOPEXCHANGE,
		StatisticsDef::ST_FUNCKEY_MALL_SHOPEXCHANGE_COST,
		StatisticsDef::ST_FUNCKEY_MALL_SHOPEXCHANGE_GET);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShopExchange::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('ShopExchange::buy Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $this->uid);
		}
	
		if (empty($goodsId) || empty($num))
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
	
		$ret = $this->exchange($goodsId, $num);
		ShopLogic::subUserPoint($this->uid, $goodsId, $num);
		
		Logger::trace('ShopExchange::buy End.');
	
		return $ret['ret'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('ShopExchange::getExchangeConf Start.');
	
		if (empty($goodsId))
		{
			throw new FakeException('Err para, goodsId:%d!', $goodsId);
		}
		
		//检查商品是否存在
		if (!isset(btstore_get()->SHOP_EXCHANGE[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
	
		$ret = btstore_get()->SHOP_EXCHANGE[$goodsId];
	
		Logger::trace('ShopExchange::getExchangeConf End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		Logger::trace('ShopExchange::subExtra Start.');
	
		if (empty($goodsId) || empty($num))
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
	
		$ret = ShopLogic::isPointEnough($this->uid, $goodsId, $num);
	
		Logger::trace('ShopExchange::subExtra End.');
	
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */