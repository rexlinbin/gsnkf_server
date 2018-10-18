<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Shop.class.php 241656 2016-05-09 09:47:29Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/Shop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-09 09:47:29 +0000 (Mon, 09 May 2016) $
 * @version $Revision: 241656 $
 * @brief 
 *  
 **/
class Shop extends Mall implements IShop 
{	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_SHOP,
		StatisticsDef::ST_FUNCKEY_MALL_SHOP_COST,
		StatisticsDef::ST_FUNCKEY_MALL_SHOP_GET);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('Shop::getShopInfo Start.');
	
		$ret = ShopLogic::getShopInfo($this->uid);
		$ret['goods'] = $this->getInfo();
	
		Logger::trace('Shop::getShopInfo End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::bronzeRecruit()
	 */
	public function bronzeRecruit()
	{
		Logger::trace('Shop::bronzeRecruit Start.');
		
		$ret = ShopLogic::bronzeRecruit($this->uid);
		
		Logger::trace('Shop::bronzeRecruit End.');	
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::silverRecruit()
	 */
	public function silverRecruit($isCost)
	{
		Logger::trace('Shop::silverRecruit Start.');
		
		if (!in_array($isCost, ShopDef::$COST_VALID_TYPES)) 
		{
			throw new FakeException('Err para, isCost:%d', $isCost);
		}
	
		$ret = ShopLogic::recruit($this->uid, ShopDef::RECRUIT_TYPE_SILVER, $isCost, 1);
	
		Logger::trace('Shop::silverRecruit End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::GoldRecruit()
	 */
	public function goldRecruit($isCost, $num = 1)
	{
		Logger::trace('Shop::goldRecruit Start.');
		
		if (!in_array($isCost, ShopDef::$COST_VALID_TYPES) || $num <= 0 || $num > 10)
		{
			throw new FakeException('Err para, isCost:%d, num:%d', $isCost, $num);
		}
	
		$ret = ShopLogic::recruit($this->uid, ShopDef::RECRUIT_TYPE_GOLD, $isCost, $num);
	
		Logger::trace('Shop::goldRecruit End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::buyVipGift()
	 */
	public function buyVipGift($vip)
	{
		Logger::trace('Shop::buyVipGift Start.');
		
		if ($vip < 0) 
		{
			throw new FakeException('invalid para, vip:%d', $vip);
		}
		
		$ret = ShopLogic::buyVipGift($this->uid, $vip);
		
		Logger::trace('Shop::buyVipGift End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IShop::buyGoods()
	 */
	public function buyGoods($goodsId, $num)
	{
		Logger::trace('Shop:buyGoods Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $this->uid);
		}
		
		if (empty($goodsId) || empty($num))
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		
		$ret = $this->exchange($goodsId, $num);
		//加入每日任务
		if ($goodsId == 1) 
		{
			EnActive::addTask(ActiveDef::BUY_EXECUTION_PILL, $num);
		}
		if ($goodsId == 2) 
		{
			EnActive::addTask(ActiveDef::BUY_STAMINA_PILL, $num);
		}
		
		Logger::trace('Shop::buyGoods End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('shop::getExchangeConf Start.');
		
		if (empty($goodsId))
		{
			throw new FakeException('Err para, goodsId:%d!', $goodsId);
		}
		
		//检查商品是否存在
		if (!isset(btstore_get()->GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		$conf = btstore_get()->GOODS[$goodsId]->toArray();
		
		//购买商品时，判断是否有vip购买上限, 有的话就替代商品表的购买上限
		$user = EnUser::getUserObj();
		$vip = $user->getVip();
		if (!empty(btstore_get()->VIP[$vip]['buyGoodsIds'][$goodsId]))
		{
			$conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = btstore_get()->VIP[$vip]['buyGoodsIds'][$goodsId];
		}
		
		Logger::trace('shop::getExchangeConf End.');
		
		return $conf;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */