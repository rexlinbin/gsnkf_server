<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonShop.class.php 190142 2015-08-11 07:20:01Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/MoonShop.class.php $
 * @author $Author: JiexinLin $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-11 07:20:01 +0000 (Tue, 11 Aug 2015) $
 * @version $Revision: 190142 $
 * @brief 
 *  
 **/
 
class MoonShop extends Mall
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

		parent::__construct($uid, MallDef::MALL_TYPE_TGSHOP, StatisticsDef::ST_FUNCKEY_MOON_TGSHOP_COST, StatisticsDef::ST_FUNCKEY_MOON_TGSHOP_GET);
		$this->loadData();

		if(empty($this->dataModify)) // 玩家第一次进入天工阁，初始化玩家信息
		{
			$this->dataModify = array
			(
					MoonShopField::TBL_FIELD_VA_ALL => array(),
					MoonShopField::TBL_FIELD_VA_GOODS_LIST => array(),
					MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME => 0,
					MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME => 0,
					MoonShopField::TBL_FIELD_VA_USR_RFR_NUM => 0,
					MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM => 0,
			);
			$this->refreshGoodsList(TRUE);
		}
		else // 玩家不是第一次进入神兵商店
		{
			$lastUsrRefreshTime = $this->getLastUsrRefreshTime();
			if(!Util::isSameDay($lastUsrRefreshTime))
			{
				$this->dataModify[MoonShopField::TBL_FIELD_VA_USR_RFR_NUM] = 0;
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
		$randNum = intval(btstore_get()->MOON_SHOP['rand_num']);
		$arrGoodsId = btstore_get()->MOON_SHOP['all_goods']->toArray();
		$arrBuyInfo = $this->getBuyInfo();
					
		$arrGoodsInfo = $this->getArrGoodsByArrId($arrGoodsId);
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
		}
		
		if (empty($arrGoodsInfo))
		{
			throw new FakeException('no valid goods');
		}
				
		if(count($arrGoodsInfo) < $randNum)
		{
			$randNum = count($arrGoodsInfo);
		}
		$totalGoodsList = Util::noBackSample($arrGoodsInfo, $randNum, 'goods_weight');

		if ($isSysRfr)
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $this->calcLastSysRefreshTime();
			$this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM] = 0; //每天触发系统刷新时同时重置免费刷新次数，不在构造函数中判断是否在同一天来重置免费刷新，因为没有记录免费刷新时间
		}

		$this->dataModify[MoonShopField::TBL_FIELD_VA_GOODS_LIST] = $totalGoodsList;
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
			if(!isset(btstore_get()->MOON_GOODS[$goodsId]))
			{
				unset($this->dataModify[MoonShopField::TBL_FIELD_VA_GOODS_LIST][$index]);
				continue;
			}
				
			$saleNum = btstore_get()->MOON_GOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
			$soldNum = 0;
			if(isset($arrBuyInfo[$goodsId]))
			{
				$soldNum = $arrBuyInfo[$goodsId]['num'];
			}
			if ($saleNum < $soldNum)
			{
				Logger::warning('goods[%d] saleNum[%d] < soldNum[%d]', $goodsId, $saleNum, $soldNum);
			}
				
			$canBuyNum = ($saleNum < $soldNum ? 0 : $saleNum - $soldNum);
			$shopInfo['goods_list'][$goodsId] = $canBuyNum;
		}

		if (!isset($shopInfo['goods_list']))
		{
			Logger::warning('do not have valid goods, level is low or buy enough');
			$shopInfo['goods_list'] = array();
		}
		//$shopInfo['refresh_cd'] = $this->calcNextSysRefreshTime(); 由于现在系统刷新时间的配置只在0点有，所以这个cd时间可以去掉了，但是如果以后改回与之前多个系统刷新时间，就重新使用
		$shopInfo['gold_refresh_num'] = $this->getUsrRfrNum();
		$shopInfo['free_refresh_num'] = btstore_get()->MOON_SHOP['free_refresh_num'] - $this->getFreeRfrNum();  //返回当天剩余的免费刷新次数

		return $shopInfo;
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
			if (isset(btstore_get()->MOON_GOODS[$goodsId]))
			{
				$ret[$goodsId] = btstore_get()->MOON_GOODS[$goodsId]->toArray();
			}
		}
		return $ret;
	}

	/**
	 * 获得已经购买的商品信息
	 */
	public function getBuyInfo()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_ALL]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_ALL] = array();
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_ALL];
	}

	/**
	 * 获得当前的商品列表
	 */
	public function getGoodsList()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_GOODS_LIST]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_GOODS_LIST] = array();
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_GOODS_LIST];
	}

	/**
	 * 获得上次系统刷新的时间
	 */
	public function getLastSysRefreshTime()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = 0;
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME];
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
	 * 计算下一次系统刷新的时间
	 * 
	 * @param int $time
	 * @throws InterException
	 * @return int
	 */
	public function calcNextSysRefreshTime($time = 0)
	{
		$currTime = (empty($time) ? Util::getTime() : $time);
		$sysRefreshArr = btstore_get()->MOON_SHOP['sys_refresh_interval']->toArray();

		$currDay = strtotime(date('Ymd', $currTime) . '000000');
		$currDayOffset = $currTime - $currDay;
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
	 * 计算上一次系统刷新的时间
	 * 
	 * @param int $time
	 * @throws InterException
	 * @return int
	 */
	public function calcLastSysRefreshTime($time = 0)
	{
		$currTime = (empty($time) ? Util::getTime() : $time);
		$sysRefreshArr = btstore_get()->MOON_SHOP['sys_refresh_interval']->toArray();

		$currDay = strtotime(date('Ymd', $currTime) . '000000');
		$currDayOffset = $currTime - $currDay;
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
	 * 获得当天玩家使用的免费刷新次数
	 */
	public function getFreeRfrNum()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM];
	}
	
	/**
	 * 使用免费次数刷新商品列表
	 */
	public function freeRfrGoodsList()
	{
		if ($this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM] < btstore_get()->MOON_SHOP['free_refresh_num'])
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM] += 1;
			$this->refreshGoodsList();
		}
		else 
		{
			throw new FakeException('no enough free refresh num, current refresh num:%d', $this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM]);
		}
		
	}
	
	/**
	 * 获取上一次玩家刷新时间
	 */
	public function getLastUsrRefreshTime()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = 0;
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME];
	}
	
	/**
	 * 获得玩家的刷新次数
	 */
	public function getUsrRfrNum()
	{
		if (!isset($this->dataModify[MoonShopField::TBL_FIELD_VA_USR_RFR_NUM]))
		{
			$this->dataModify[MoonShopField::TBL_FIELD_VA_USR_RFR_NUM] = 0;
		}
		return $this->dataModify[MoonShopField::TBL_FIELD_VA_USR_RFR_NUM];
	}

	/**
	 * 玩家使用金币刷新商品列表
	 */
	public function usrRfrGoodsList()
	{
		$this->dataModify[MoonShopField::TBL_FIELD_VA_USR_RFR_NUM] += 1;
		$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = Util::getTime();
		$this->refreshGoodsList();
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->MOON_GOODS[$goodsId]))
		{
			Logger::fatal('The goods is not existed in MOON_GOODS, but want to getExchangeConf, goodsId[%d]', $goodsId);
			return array();
		}
		return btstore_get()->MOON_GOODS[$goodsId]->toArray();
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

		if (!isset(btstore_get()->MOON_GOODS[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}

		$goodsConf = btstore_get()->MOON_GOODS[$goodsId]->toArray();
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraReq['tg']))
		{
			$subTg = intval($extraReq['tg']) * $num;
			$userObj = EnUser::getUserObj($this->uid);
			if (!$userObj->subTgNum($subTg)) 
			{
				throw new FakeException('no enough tg num, need[%d], curr[%d]', $subTg, $userObj->getTgNum());
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
		
		if (!isset(btstore_get()->MOON_GOODS[$goodsId]))
		{
			throw new FakeException('the goods[%d] is not existed', $goodsId);
		}
		
		$goodsConf = btstore_get()->MOON_GOODS[$goodsId]->toArray();
		$extraAcq = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraAcq['tg']))
		{
			$addTg = intval($extraAcq['tg']) * $num;
			$userObj = EnUser::getUserObj($this->uid);
			$userObj->addTgNum($addTg);
		}
		
		return TRUE;
	}
	
	/**
	 * 测试或控制台使用，设置上次的玩家刷新时间
	 * 
	 * @param int $time
	 */
	public function setLastUsrRefreshTimeForConsole($time)
	{
		$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = $time;
	}
	
	/**
	 * 测试或控制台使用，设置上次的系统刷新时间
	 *
	 * @param int $time
	 */
	public function setLastSysRefreshTimeForConsole($time)
	{
		$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $time;
	}
	
	public function resetFreeRefreshNumForConsole()    //给测试 和 前端 测试 使用的
	{
		$this->dataModify[MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
	}
	
	public function resetLastSysRefreshTimeForConsole()		//给测试使用的
	{
		$currDay = strtotime(date('Ymd', Util::getTime()) . '000000');
		$oneDayago = $currDay - SECONDS_OF_DAY;
		$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $oneDayago;
	}
	
	public function resetLastUsrRefreshTimeForConsole()		//给测试使用的
	{
		$currDay = Util::getTime();
		$oneDayago = $currDay - SECONDS_OF_DAY;
		$this->dataModify[MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME] = $oneDayago;
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */