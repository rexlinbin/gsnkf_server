<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Sale.class.php 108675 2014-05-16 03:05:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/sale/Sale.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-05-16 03:05:41 +0000 (Fri, 16 May 2014) $
 * @version $Revision: 108675 $
 * @brief 
 *  
 **/
class Sale extends Mall implements ISale
{
	
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_SALE,
		StatisticsDef::ST_FUNCKEY_MALL_SALE_COST,
		StatisticsDef::ST_FUNCKEY_MALL_SALE_GET);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISale::getGoodsInfo()
	 */
	public function getGoodsInfo()
	{
		Logger::trace('Sale::getGoodsInfo Start.');
	
		$ret = $this->getInfo();
	
		Logger::trace('Sale::getGoodsInfo End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISale::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('Sale:buy Start.');
	
		if (empty($goodsId) || empty($num))
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
	
		$ret = $this->exchange($goodsId, $num);
	
		Logger::trace('Sale::buy End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($exchangeId)
	{
		Logger::trace('sale::getExchangeConf Start.');
	
		$ret = EnActivity::getConfByName(ActivityName::SALE);
	
		if (!isset($ret['data'][$exchangeId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $exchangeId);
		}
	
		Logger::trace('sale::getExchangeConf End.');
	
		return $ret['data'][$exchangeId];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */