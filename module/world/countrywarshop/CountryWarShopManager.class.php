<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarShopManager.class.php 215558 2015-12-14 09:10:32Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywarshop/CountryWarShopManager.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-12-14 09:10:32 +0000 (Mon, 14 Dec 2015) $
 * @version $Revision: 215558 $
 * @brief 
 *  
 **/
class CountryWarShopManager extends Mall
{
	private static $Instance = array();
	public function __construct($uid)
	{
		parent::__construct($uid, MallDef::MALL_TYPE_COUNTRYWAR_SHOP,
				StatisticsDef::ST_FUNCKEY_MALL_COUNTRYWARSHOP_COST,
				StatisticsDef::ST_FUNCKEY_MALL_COUNTRYWARSHOP_GET);
		$this->loadData();
	}
	/**
	 * @param $uid
	 * @return CountryWarShopManager
	 */
	public static function getInstance($uid)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			if (empty($uid))
			{
				throw new FakeException("the uid in session is null");
			}
		}
		if (empty(self::$Instance[$uid]))
		{
			self::$Instance[$uid] = new self($uid);
		}
		return self::$Instance[$uid];
	}
	
	/**
	 * 实现商店基类的商品配置读取函数
	 */
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->COUNTRY_WAR_SHOP[$goodsId]))
		{
			Logger::warning('not found goodsId:%d in conf, uid:%d', $goodsId, $this->uid);
			return array();
		}
		return  btstore_get()->COUNTRY_WAR_SHOP[$goodsId]->toArray();
	}
	/**
	 * 实现商店基类的扣除额外数值的函数
	 */
	public function subExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
	
		if (!isset(btstore_get()->COUNTRY_WAR_SHOP[$goodsId]))
		{
			throw new ConfigException('The goods is not existed, goodsId:%d', $goodsId);
		}
		$needCoPointNum = btstore_get()->COUNTRY_WAR_SHOP[$goodsId][CountryWarShopDef::REQ][CountryWarCsvField::PRICE] * $num;
		$serverId = Util::getServerId();
		$pid = EnUser::getUserObj($this->uid)->getPid();
		$CWCUObj = CountryWarCrossUser::getInstance($serverId, $pid);
		$ret = $CWCUObj->subCopoint($needCoPointNum);
		return $ret;
	}
	
	
	public function updateData()
	{
		$serverId = Util::getServerId();
		$pid = EnUser::getUserObj($this->uid)->getPid();
		CountryWarCrossUser::getInstance($serverId, $pid)->update();
		parent::updateData();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */