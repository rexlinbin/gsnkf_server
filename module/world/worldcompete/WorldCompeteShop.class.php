<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteShop.class.php 214210 2015-12-07 04:02:47Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompeteShop.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-12-07 04:02:47 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214210 $
 * @brief 
 *  
 **/
 
class WorldCompeteShop extends Mall
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
		parent::__construct($uid, MallDef::MALL_TYPE_WORLDCOMPETE_SHOP, StatisticsDef::ST_FUNCKEY_WORLD_COMPETE_SHOP_COST);
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->WORLD_COMPETE_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId[%d]', $goodsId);
			return array();
		}
		return btstore_get()->WORLD_COMPETE_GOODS[$goodsId]->toArray();
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

		if (!isset(btstore_get()->WORLD_COMPETE_GOODS[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}

		$goodsConf = btstore_get()->WORLD_COMPETE_GOODS[$goodsId]->toArray();
		$subCrossHonor = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (!empty($subCrossHonor))
		{
			$subCrossHonor = intval($subCrossHonor) * $num;
			$serverId = Util::getServerIdOfConnection();
			$pid = WorldCompeteUtil::getPid($this->uid);
			$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $this->uid);
			if (!$worldCompeteInnerUserObj->addCrossHonor(-$subCrossHonor))
			{
				throw new FakeException('no enough cross honor need[%d], curr[%d]', $subCrossHonor, $worldCompeteInnerUserObj->getCrossHonor());
			}
		}

		return TRUE;
	}
	
	/** (non-PHPdoc)
	 * @see Mall::addExtra()
	 */
	public function addExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		
		if (empty(btstore_get()->WORLD_COMPETE_GOODS[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}
	
		$goodsConf = btstore_get()->WORLD_COMPETE_GOODS[$goodsId];
		$addCrossHonor = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
	
		if (!empty($addCrossHonor))
		{
			$addCrossHonor = intval($addCrossHonor) * $num;
			$serverId = Util::getServerIdOfConnection();
			$pid = WorldCompeteUtil::getPid($this->uid);
			$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $this->uid);
			$worldCompeteInnerUserObj->addCrossHonor($addCrossHonor);
		}
	
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::updateData()
	 */
	public function updateData()
	{
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($this->uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $this->uid);
		$worldCompeteInnerUserObj->update();
		parent::updateData();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */