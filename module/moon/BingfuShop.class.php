<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class BingfuShop extends Mall
{
	/**
	 * 
	 * @param number $uid
	 */
	public function __construct($uid = 0)
	{
		if(empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		//功能节点未开启
		if(false == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
		{
			throw new InterException('tally switch not open.');
		}
		
		parent::__construct($uid, MallDef::MALL_TYPE_BINGFU_SHOP, StatisticsDef::ST_FUNCKEY_MOON_BINGFU_SHOP_COST, StatisticsDef::ST_FUNCKEY_MOON_BINGFU_SHOP_REWARD);
		$this->loadData();
		
		if(empty($this->dataModify)) // 玩家第一次进入兵符商店，初始化玩家信息
		{
			$this->dataModify = array
			(
					BingfuShopField::TBL_FIELD_VA_ALL => array(),
					BingfuShopField::TBL_FIELD_VA_GOODS_LIST => array(),
					BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME => 0,
					BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME => 0,
					BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM => 0,
					BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM => 0,
			);
			$this->refreshGoodsList(TRUE);
		}
		else // 玩家不是第一次进入兵符商店
		{
			$lastUsrRefreshTime = $this->getLastUsrRefreshTime();
			if(!Util::isSameDay($lastUsrRefreshTime))
			{
				//玩家刷新次数重置
				$this->dataModify[BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM] = 0;
				$this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
			}
			$this->refreshData();
		}
	}
	
	/**
	 * 刷新商品列表
	 *
	 * @param boolean $isSysRfr
	 * @throws FakeException
	 * @return array
	 */
	public function refreshGoodsList($isSysRfr = FALSE)
	{
		$userLevel = EnUser::getUserObj($this->uid)->getLevel();
		$arrTeam = btstore_get()->BINGFU_RULE['itemTeamNum']->toArray();
		$totalGoodsList = array();
		//按组商品取样
		foreach($arrTeam as $teamId => $randNum)
		{
			$realId = intval($teamId) + 1;
			$arrGoodsId = btstore_get()->BINGFU_RULE['itemTeam'.$realId];
			$arrBuyInfo = $this->getBuyInfo();
			$arrGoodsInfo = $this->getArrGoodsByArrId($arrGoodsId);
			//单组处理
			foreach($arrGoodsInfo as $goodsId => $goodsConf)
			{
				// 去掉购买次数超限的
				$saleNum = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
				$soldNum = 0;
				if(isset($arrBuyInfo[$goodsId]))
				{
					$soldNum = $arrBuyInfo[$goodsId]['num'];
				}
				if($soldNum >= $saleNum)
				{
					unset($arrGoodsInfo[$goodsId]);
					continue;
				}
				//去掉等级不够的
				$needLevel = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL];
				if ($userLevel < $needLevel)
				{
					unset($arrGoodsInfo[$goodsId]);
					continue;
				}
				//去掉已经前面的组抽过的
				if(in_array($goodsId, $totalGoodsList))
				{
					unset($arrGoodsInfo[$goodsId]);
					continue;
				}
			}
	
			if (empty($arrGoodsInfo))
			{
				throw new FakeException('bingfushop:no valid goods');
			}
		
			if(count($arrGoodsInfo) < $randNum)
			{
				$randNum = count($arrGoodsInfo);
				Logger::warning('bingfushop teamId:%S rand good not enough.', $realId);
			}
			$totalGoodsList = array_merge($totalGoodsList, Util::noBackSample($arrGoodsInfo, $randNum, 'weight'));//取到的商品合并
			
		}
		if ($isSysRfr)
		{
			//计算上次系统刷新时间
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $this->calcLastSysRefreshTime();
		}
		
		$this->dataModify[BingfuShopField::TBL_FIELD_VA_GOODS_LIST] = $totalGoodsList;
		return $totalGoodsList;
	}
	
	/**
	 * 获得商店商品信息
	 *
	 * @return array
	 */
	public function getShopInfo()
	{
		$shopInfo = array();
	
		$arrBuyInfo = $this->getBuyInfo();
		$goodsList = $this->getGoodsList();
		foreach($goodsList as $index => $goodsId)
		{
			if(!isset(btstore_get()->BINGFU_SHOP[$goodsId]))
			{
				unset($this->dataModify[BingfuShopField::TBL_FIELD_VA_GOODS_LIST][$index]);
				Logger::warning('bingfushop goodId:%s no config.', $goodsId);
				continue;
			}
	
			$saleNum = btstore_get()->BINGFU_SHOP[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
			$soldNum = 0;
			if(isset($arrBuyInfo[$goodsId]))
			{
				$soldNum = $arrBuyInfo[$goodsId]['num'];
			}
			if ($saleNum < $soldNum)
			{
				Logger::warning('bingfushop goods[%d] saleNum[%d] < soldNum[%d]', $goodsId, $saleNum, $soldNum);
			}
	
			$canBuyNum = ($saleNum < $soldNum ? 0 : $saleNum - $soldNum);
			$shopInfo['goods_list'][$goodsId] = $canBuyNum;
		}
	
		if (!isset($shopInfo['goods_list']))
		{
			Logger::warning('bingfushop do not have valid goods, level is low or buy enough');
			$shopInfo['goods_list'] = array();
		}
		//$shopInfo['refresh_cd'] = $this->calcNextSysRefreshTime(); 前端自己计算吧
		$shopInfo['gold_refresh_num'] = $this->getUsrRfrNum();
		//返回当天剩余的免费刷新次数
		$shopInfo['free_refresh_num'] = btstore_get()->BINGFU_RULE['freeRefreshNum'] - $this->getFreeRfrNum(); 
	
		return $shopInfo;
	}
	/**
	 * 计算上一次系统刷新的时间
	 *
	 * @param int $time
	 * @throws InterException
	 * @return int
	 */
	public function calcLastSysRefreshTime($time = 0)
	{
		$currTime = $time;
		if (empty($currTime))
		{
			$currTime = Util::getTime();
		}
		
		$sysRefreshArr = btstore_get()->BINGFU_RULE['cd']->toArray();
		if (empty($sysRefreshArr))
		{
			throw new InterException('BINGFU_RULE cd empty');
		}
		
		$currDay = strtotime(date('Ymd', $currTime) . '000000');
		$currDayOffset = $currTime - $currDay;
		//当前便宜小于最小偏移，刷新时间为昨天最后一次刷新
		if ($currDayOffset < $sysRefreshArr[0])
		{
			return $currDay - SECONDS_OF_DAY + end($sysRefreshArr);
		}
		
		$lastRefreshOffset = 0;
		foreach ($sysRefreshArr as $aTime)
		{
			if ($currDayOffset >= $aTime)
			{
				$lastRefreshOffset = $aTime;
			}
			else
			{
				break;
			}
		}
		
		return $currDay + $lastRefreshOffset;
		
	}
	/**
	 * 计算下一次系统刷新的时间
	 *
	 * @param int $time
	 * @throws InterException
	 * @return int
	 */
	public function calcNextSysRefreshTime($time = 0)
	{
		
		$currTime = $time;
		if (empty($currTime))
		{
			$currTime = Util::getTime();
		}
		
		$sysRefreshArr = btstore_get()->BINGFU_RULE['cd']->toArray();
		if (empty($sysRefreshArr))
		{
			throw new InterException('BINGFU_SHOP cd empty');
		}
		
		$currDay = strtotime(date('Ymd', $currTime) . '000000');
		$currDayOffset = $currTime - $currDay;
		//当前时间便宜大于配置的最大cd时间，下次刷新时间为第二天的第一次刷新
		if ($currDayOffset >= end($sysRefreshArr))
		{
			return $currDay + SECONDS_OF_DAY + $sysRefreshArr[0];
		}
		
		$nextRefreshOffset = 0;
		foreach ($sysRefreshArr as $aTime)
		{
			if ($currDayOffset < $aTime)
			{
				$nextRefreshOffset = $aTime;
				break;
			}
		}
		
		return $currDay + $nextRefreshOffset;
	}
	/**
	 * 判断是否需要系统刷新
	 *
	 * @param int $time
	 * @return boolean
	 */
	public function needSysRefresh($time = 0)
	{
		$currTime = (empty($time) ? Util::getTime() : $time);
		$lastRefreshTime = $this->getLastSysRefreshTime();
		$nextRefreshTime = $this->calcNextSysRefreshTime($lastRefreshTime);
		return $currTime >= $nextRefreshTime;
	}
	/**
	 * 获得上次系统刷新的时间
	 */
	public function getLastSysRefreshTime()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = 0;
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME];
	}

	/**
	 * 使用免费次数刷新商品列表
	 */
	public function freeRfrGoodsList()
	{
		if ($this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM] < btstore_get()->BINGFU_RULE['freeRefreshNum'])
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM] += 1;
			//后期加上，免费刷新次数每天0点重置
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = Util::getTime();
			$this->refreshGoodsList();
			//Logger::info('refreshTally once.use:%s', $this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM]);
		}
		else
		{
			throw new FakeException('no enough free refresh num, current refresh num:%d', $this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM]);
		}
	
	}
	/**
	 * 玩家使用金币刷新商品列表
	 */
	public function usrRfrGoodsList()
	{
		$this->dataModify[BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM] += 1;
		$this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = Util::getTime();
		$this->refreshGoodsList();
	}
	/**
	 * 获得玩家的刷新次数
	 */
	public function getUsrRfrNum()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM] = 0;
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM];
	}
	/**
	 * 获得当天玩家使用的免费刷新次数
	 */
	public function getFreeRfrNum()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM];
	}
	/**
	 * 获得当前的商品列表
	 */
	public function getGoodsList()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_GOODS_LIST]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_GOODS_LIST] = array();
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_GOODS_LIST];
	}
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->BINGFU_SHOP[$goodsId]))
		{
			Logger::fatal('The goods is not existed in BINGFU_SHOP, but want to getExchangeConf, goodsId[%d]', $goodsId);
			return array();
		}
		return btstore_get()->BINGFU_SHOP[$goodsId]->toArray();
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
	
		if (!isset(btstore_get()->BINGFU_SHOP[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}
	
		$goodsConf = btstore_get()->BINGFU_SHOP[$goodsId]->toArray();
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraReq[RewardConfType::TALLY_POINT]))
		{
			$subTg = intval($extraReq[RewardConfType::TALLY_POINT]) * $num;
			$userObj = EnUser::getUserObj($this->uid);
			if (!$userObj->subTallyPoint($subTg))
			{
				throw new FakeException('no enough tally_point num, need[%d], curr[%d]', $subTg, $userObj->getTallyPoint());
			}
		}
	
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::addExtra()
	 */
	public function addExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('error param, goodsId[%d] num[%d]', $goodsId, $num);
		}
	
		if (!isset(btstore_get()->BINGFU_SHOP[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}
	
		$goodsConf = btstore_get()->BINGFU_SHOP[$goodsId]->toArray();
		$extraAcq = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraAcq[RewardConfType::TALLY_POINT]))
		{
			$addTg = intval($extraAcq[RewardConfType::TALLY_POINT]) * $num;
			$userObj = EnUser::getUserObj($this->uid);
			$userObj->addTallyPoint($addTg);
		}
	
		return TRUE;
	}
	
	/**
	 * 获取商品的配置信息
	 *
	 * @param array $arrGoodsId
	 * @return array
	 */
	public function getArrGoodsByArrId($arrGoodsId)
	{
		$ret = array();
		foreach ($arrGoodsId as $goodsId)
		{
			if (isset(btstore_get()->BINGFU_SHOP[$goodsId]))
			{
				$ret[$goodsId] = btstore_get()->BINGFU_SHOP[$goodsId]->toArray();
			}
		}
		return $ret;
	}
	
	/**
	 * 获得已经购买的商品信息
	 */
	public function getBuyInfo()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_ALL]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_ALL] = array();
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_ALL];
	}
	
	/**
	 * 获取上一次玩家刷新时间
	 */
	public function getLastUsrRefreshTime()
	{
		if (!isset($this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME]))
		{
			$this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = 0;
		}
		return $this->dataModify[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME];
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */