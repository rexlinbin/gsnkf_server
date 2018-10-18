<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassShop.class.php 247692 2016-06-22 10:20:02Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPassShop.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-22 10:20:02 +0000 (Wed, 22 Jun 2016) $
 * @version $Revision: 247692 $
 * @brief 
 *  
 **/
 
class WorldPassShop extends Mall
{
	/**
	 * 构造函数
	 *
	 * @param int $uid
	 */
	public function __construct($uid = 0)
	{
		if(empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		parent::__construct($uid, MallDef::MALL_TYPE_WORLDPASS_SHOP, StatisticsDef::ST_FUNCKEY_WORLD_PASS_SHOP_COST, StatisticsDef::ST_FUNCKEY_WORLD_PASS_SHOP_GET);
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->WORLD_PASS_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed in MOON_GOODS, but want to getExchangeConf, goodsId[%d]', $goodsId);
			return array();
		}
		return btstore_get()->WORLD_PASS_GOODS[$goodsId]->toArray();
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('error param, goodsId[%d] num[%d]', $goodsId, $num);
		}

		if (!isset(btstore_get()->WORLD_PASS_GOODS[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}

		$goodsConf = btstore_get()->WORLD_PASS_GOODS[$goodsId]->toArray();
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraReq['hell_point']))
		{
			$subHellPoint = intval($extraReq['hell_point']) * $num;
			$serverId = Util::getServerIdOfConnection();
			$pid = WorldPassUtil::getPid($this->uid);
			$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $this->uid);
			if (!$worldPassInnerUserObj->subHellPoint($subHellPoint))
			{
				throw new FakeException('no enough hell point, need[%d], curr[%d]', $subHellPoint, $worldPassInnerUserObj->getHellPoint());
			}
		}

		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::updateData()
	 */
	public function updateData()
	{
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldPassUtil::getPid($this->uid);
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $this->uid);
		$worldPassInnerUserObj->update();
		parent::updateData();
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::addExtra()
	 */
	public function addExtra($goodsId, $num)
	{
		return TRUE;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */