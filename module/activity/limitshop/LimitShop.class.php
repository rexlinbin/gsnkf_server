<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LimitShop.class.php 164197 2015-03-30 08:49:29Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/limitshop/LimitShop.class.php $
 * @author $Author: wuqilin $(zhengguohao@babeltime.com)
 * @date $Date: 2015-03-30 08:49:29 +0000 (Mon, 30 Mar 2015) $
 * @version $Revision: 164197 $
 * @brief 
 *  
 **/
class LimitShop extends Mall implements ILimitShop
{
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();

		parent::__construct($uid, MallDef::MALL_TYPE_LIMITSHOP,
				StatisticsDef::ST_FUNCKEY_MALL_LIMITSHOP_COST, StatisticsDef::ST_FUNCKEY_MALL_LIMITSHOP_GET);

		if (FALSE == EnActivity::isOpen(ActivityName::LIMITSHOP))
		{
			throw new FakeException('Act limitshop is not open.');
		}
	}

	/**
	 * 获得商品已购信息
	 * @see ILimitShop::getLimitShopInfo()
	 */
	public function getLimitShopInfo()
	{
		//TODO 此处记录了活动期间购买物品的所有信息，对商品id的唯一性有依赖，如果两天或多天都需买此商品id物品，则需更改dataModified['all']
		$hasBuyInfo = $this->getInfo();
		
		//去掉不是今天的
		$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
		$todayConf = self::getConfOfSomeday($day+1);
		
		foreach ($hasBuyInfo as $goodsId => $index)
		{
			if (!array($goodsId, array_keys($todayConf) ))
			{
				unset($hasBuyInfo[$goodsId]);
				break;
			}
			unset($hasBuyInfo[$goodsId]['time']);
		}
		return $hasBuyInfo;
	}

	/**
	 * 根据 goodsId 购买物品
	 * @return 'ok'
	 */
	public function buyGoods($goodsId, $num = 1)
	{
		$goodsId = intval($goodsId);
		if ($goodsId <= 0)
		{
			throw new FakeException('Err para, goodsId:%d', $goodsId);
		}

		$this->exchange($goodsId, $num);
		
		Logger::info('limitShop bug goodId:%d, num:%d', $goodsId, $num);

		return 'ok';
	}
	
	/**
	 * 获取活动某天的配置
	 * @param int $day
	 */
	public static function getConfOfSomeday($day)
	{
		$ret = EnActivity::getConfByName(ActivityName::LIMITSHOP);
		return $ret['data'][LimitShopDef::LIMITSHOP_DAY_INFO][$day];
	}

	public function getExchangeConf($goodId)
	{
		$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
		$todayConf = self::getConfOfSomeday($day+1);

		$needConf = array();

		foreach ($todayConf as $goodsId => $index)
		{
			$needConf[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD] = $index[LimitShopDef::NOW_COST];
			$needConf[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_VIP] = $index[LimitShopDef::LIMIT_VIP];
			$needConf[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $index[LimitShopDef::LIMIT_NUM];
			
			switch ($index[LimitShopDef::ITEMS][0])
			{
				case RewardConfType::SOUL:
					$soul = $index[LimitShopDef::ITEMS][2];
					$needConf[$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SOUL] = $soul;
					break;
				case RewardConfType::ITEM:
					$item = array($index[LimitShopDef::ITEMS][1] => $index[LimitShopDef::ITEMS][2]);
					$needConf[$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = $item;
					break;
				case RewardConfType::ITEM_MULTI:
					$item = array($index[LimitShopDef::ITEMS][1] => $index[LimitShopDef::ITEMS][2]);
					$needConf[$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = $item;
					break;
				case RewardConfType::HERO:
					$hero = array($index[LimitShopDef::ITEMS][1] => $index[LimitShopDef::ITEMS][2]);
					$needConf[$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = $hero;
					break;
				case RewardConfType::TREASURE_FRAG_MULTI:
					$treasFrags = array($index[LimitShopDef::ITEMS][1] => $index[LimitShopDef::ITEMS][2]);
					$needConf[$goodsId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_TREASFRAG] = $treasFrags;
					break;
				default :
					throw new FakeException('Unsupported ACQ type: %d.',$index[LimitShopDef::ITEMS][0]);
			}
		}
		
		if (!in_array($goodId, array_keys($needConf)))
		{
			throw new FakeException('GoodsId %d is not exist of day %d.',$goodId,$day+1);
		}
		
		return $needConf[$goodId];
	}
	
	//是否在本次活动内
	public function isInCurRound($time)
	{
		$actConf = EnActivity::getConfByName(ActivityName::LIMITSHOP);
		
		$startTime = $actConf['start_time'];
		$endTime = $actConf['end_time'];
		
		if ( $time >= $startTime && $time <= $endTime )
		{
			return TRUE;
		}
		
		return FALSE;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */