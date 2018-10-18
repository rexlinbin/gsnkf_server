<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionMall.class.php 197568 2015-09-09 08:01:20Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/missionshop/MissionMall.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-09 08:01:20 +0000 (Wed, 09 Sep 2015) $
 * @version $Revision: 197568 $
 * @brief 
 *  
 **/

/**
 * Class MissionMall
 * all:array                        记录物品的购买次数
 * [
 * 		goodsId=>array
 * 		[
 * 			'num'=>int(兑换次数),
 *          'time'=>int(兑换时间)
 *      ]
 * ]
 */
class MissionMall extends Mall implements IMissionMall
{

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	
		parent::__construct($this->uid, MallDef::MALL_TYPE_MISSION_SHOP,
			StatisticsDef::ST_FUNCKEY_MALL_MISSION_SHOP_COST, StatisticsDef::ST_FUNCKEY_MALL_MISSION_SHOP_GET);
	
		$this->loadData();
	
		if (empty($this->dataModify))
		{
			$this->dataModify = array(
					MallDef::ALL => array(),
			);
		}

		$this->refreshData();
	}
	
	function getShopInfo()
	{
		return $this->getGoodList();
	}

	function buy($goodId, $num)
	{
		$goodList = $this->getGoodList();
		if(in_array($goodId, array_keys($goodList)) == false)
		{
			throw new FakeException("goodId:[%d] not in today goodList:[%s]", $goodId, $goodList);
		}
		$bag = BagManager::getInstance()->getBag($this->uid);
		if($bag->isFull())
		{
			throw new FakeException("bag is full");
		}
		$haveBuyNum = isset($this->dataModify[MallDef::ALL][$goodId][MallDef::NUM]) ? $this->dataModify[MallDef::ALL][$goodId][MallDef::NUM] : 0;
		$missMallConf = $this->getMissMallConf();
		$limitNum = $missMallConf[$goodId][MissionCsvField::MAX_BUY_NUM];
		if($haveBuyNum + $num > $limitNum)
		{
			throw new FakeException("buyNum limit:: haveBuyNum:[%d] num:[%d] limitNum:[%d]", $haveBuyNum, $num, $limitNum);
		}
		$this->exchange($goodId, $num);
		$this->update();

		return "ok";
	}

	private function getGoodList()
	{
		$missMallConf = $this->getMissMallConf();
		$goodList = array();
		foreach($missMallConf as $goodId => $goodConf)
		{
			$limitNum = $goodConf[MissionCsvField::MAX_BUY_NUM];
			$haveBuyNum = isset($this->dataModify[MallDef::ALL][$goodId][MallDef::NUM]) ? $this->dataModify[MallDef::ALL][$goodId][MallDef::NUM] : 0;
			if($haveBuyNum >= $limitNum)
			{
				continue;
			}
			$goodList[$goodId] = $limitNum - $haveBuyNum;
		}
		return $goodList;
	}

	private function getMissMallConf()
	{
		return $missMallConf = btstore_get()->MISSION_SHOP;
	}

	public function getExchangeConf($goodId)
	{
		$missMallConf = $this->getMissMallConf();
		if (!isset($missMallConf[$goodId]))
		{
			Logger::warning('goodId:%d not found', $goodId);
			return array();
		}

		$itemArr = $missMallConf[$goodId][MissionCsvField::ITEMARR];
		$exchangeConf = array();
		foreach($itemArr as $index => $eachItem)
		{
			$exchangeConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][$eachItem[1]] = $eachItem[2];
		}
		$exchangeConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA] = $missMallConf[$goodId][MissionCsvField::PRICE];
		$exchangeConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $missMallConf[$goodId][MissionCsvField::MAX_BUY_NUM];

		return $exchangeConf;
	}

	public function subExtra($goodId, $num)
	{
		if($goodId <= 0 || $num <= 0)
		{
			throw new ConfigException("error param");
		}
		$exchangeConf = $this->getExchangeConf($goodId);
		$needFame = $exchangeConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA] * $num;
		$ret = EnUser::getUserObj($this->uid)->subFameNum($needFame);
		return $ret;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */