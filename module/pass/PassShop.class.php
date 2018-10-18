<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassShop.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/PassShop.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
 
class PassShop extends Mall
{
	public function __construct($uid = 0)
	{
		if(empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		
		parent::__construct($uid, MallDef::MALL_TYPE_PASS, StatisticsDef::ST_FUNCKEY_MALL_PASSSHOP_COST, StatisticsDef::ST_FUNCKEY_MALL_PASSSHOP_GET);
		$this->loadData();
		
		if(empty($this->dataModify)) // 玩家第一次进入神兵商店，初始化玩家信息
		{
			$this->dataModify = array
			(
					PassDef::TBL_FIELD_VA_ALL => array(),
					PassDef::TBL_FIELD_VA_GOODS_LIST => array(),
					PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME => 0,
					PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME => 0,
					PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM => 0,
					PassDef::TBL_FIELD_VA_FREE_RFR_NUM => 0,
					PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM => 0,
			);
			$this->refreshGoodsList(TRUE);
		}
		else // 玩家不是第一次进入神兵商店
		{
			$lastUsrRefreshTime = self::getLastUsrRefreshTime();
			if(!Util::isSameDay($lastUsrRefreshTime))
			{
				$this->dataModify[PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM] = 0;
				$this->dataModify[PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM] = 0;
				
				$lastSysRefreshTime = self::getLastSysRefreshTime();
				if (!Util::isSameDay($lastSysRefreshTime)) 
				{
					$this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE] = array();
				}
			}
			
			$this->refreshData();
		}
	}
	
	public function refreshGoodsList($isSysRfr = FALSE)
	{
		$userLevel = EnUser::getUserObj($this->uid)->getLevel();	
		$arrGoodsNum = btstore_get()->PASS_SHOP[PassShopCsvTag::GOODS_NUM]->toArray();
		$arrGoodsGroup = btstore_get()->PASS_SHOP[PassShopCsvTag::GOODS_ARRAY]->toArray();
		$arrBuyInfo = self::getBuyInfo();
		
		// 从每一个商品组中进行指定个数的商品抽样
		$totalGoodsList = array();
		for ($i = 0; $i < count($arrGoodsNum); ++$i)
		{
			$currNum = $arrGoodsNum[$i];
			if (empty($currNum)) // 抽样个数为0，不需要抽样 
			{
				continue;
			}
			
			if (!isset($arrGoodsGroup[$i])) 
			{
				throw new InterException('arrGoodsGroup is not enough, need at least [%d] groups, curr is [%d]', count($arrGoodsNum), count($arrGoodsGroup));
			}
			$arrGoodsId = $arrGoodsGroup[$i];
			//Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] needNum[%d] arrGoodsId[%s]', $this->uid, $i, $currNum, $arrGoodsId);
			
			if (count($arrGoodsId) < $currNum) 
			{
				throw new InterException('arrId is not enough, need at least [%d] ids, curr is [%d]', $currNum, count($arrGoodsId));
			}
			$arrGoodsInfo = self::getArrGoodsByArrId($arrGoodsId);
			//Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] needNum[%d] arrGoodsId[%s] arrGoodsInfo[%s]', $this->uid, $i, $currNum, $arrGoodsId, $arrGoodsInfo);
			
			// 特殊格子做特殊处理
			if (in_array($i, PassDef::$ExcludeTeam) && !empty($this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$i]))
			{
				$arrExcludeGoods = $this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$i];
				//Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] is exclude team, before exclude, arrGoodsInfo[%s], arrExcludeGoods[%s]', $this->uid, $i, array_keys($arrGoodsInfo), $arrExcludeGoods);
				
				foreach ($arrExcludeGoods as $aExcludeGoods)
				{
					if (isset($arrGoodsInfo[$aExcludeGoods])) 
					{
						unset($arrGoodsInfo[$aExcludeGoods]);
					}
				}
				//Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] is exclude team, after exclude, arrGoodsInfo[%s], arrExcludeGoods[%s]', $this->uid, $i, array_keys($arrGoodsInfo), $arrExcludeGoods);
			}
			
			foreach($arrGoodsInfo as $goodsId => $goodsConf)
			{
				$saleNum = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
				$soldNum = 0;
				
				if(isset($arrBuyInfo[$goodsId]))
				{
					$soldNum = $arrBuyInfo[$goodsId]['num'];
				}
				
				if($soldNum >= $saleNum)
				{
					Logger::trace('PassShop.refreshGoodsList user[%d] goods[%d] soldNum[%d] exceed saleNum[%d], ignore this goods', $this->uid, $goodsId, $soldNum, $saleNum);
					unset($arrGoodsInfo[$goodsId]);
					continue;
				}
					
				$needLevel = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL];
				if ($userLevel < $needLevel)
				{
					Logger::trace('PassShop.refreshGoodsList user[%d] goods[%d] userLevel[%d] less than needLevel[%d], ignore this goods', $this->uid, $goodsId, $userLevel, $needLevel);
					unset($arrGoodsInfo[$goodsId]);
					continue;
				}
			}
			
			if (empty($arrGoodsInfo)) 
			{
				Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] have no valid goods, maybe all sold or level is too low', $this->uid, $i);
				continue;
			}
			
			foreach ($totalGoodsList as $aGoodsId)
			{
				if (isset($arrGoodsInfo[$aGoodsId])) 
				{
					unset($arrGoodsInfo[$aGoodsId]);
					Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] already rand goods[%d], unset it', $this->uid, $i, $aGoodsId);
				}
			}
			
			if(count($arrGoodsInfo) < $currNum)
			{
				Logger::trace('PassShop.refreshGoodsList user[%d] has buy too many goods, remaining goods num is [%d] less than need[%s]',$this->uid, count($arrGoodsInfo), $currNum);
				$currNum = count($arrGoodsInfo);
			}
			
			// 如果商品为空，则抛FAKE
			if (empty($arrGoodsInfo)) 
			{
				throw new FakeException('PassShop.refreshGoodsList user[%d] groupIndex[%d] empty arrGoodsInfo.', $this->uid, $i);
			}
			
			$goodsList = Util::noBackSample($arrGoodsInfo, $currNum, PassGoodsCsvTag::GOODS_WEIGHT);
			
			// 特殊格子做特殊处理
			if (in_array($i, PassDef::$ExcludeTeam))
			{
				$this->addExclude($i, $goodsList);
				Logger::trace('PassShop.refreshGoodsList user[%d] groupIndex[%d] is exclude team, add new exclude goods[%s]', $this->uid, $i, $goodsList);
			}
			
			$totalGoodsList = array_merge($totalGoodsList, $goodsList);
		}
		
		if ($isSysRfr) 
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = self::calcLastSysRefreshTime();
			$this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
		}
		
		if (empty($totalGoodsList)) 
		{
			Logger::warning('PassShop.refreshGoodsList do not have valid goods, level is low or buy enough');
		}
		
		$this->dataModify[PassDef::TBL_FIELD_VA_GOODS_LIST] = $totalGoodsList;
		Logger::trace('PassShop.refreshGoodsList user[%d] refreshGoodsList end, ret[%s]', $this->uid, $totalGoodsList);
		return $totalGoodsList;
	}
	
	public function getShopInfo()
	{
		$shopInfo = array();
		
		$arrBuyInfo = self::getBuyInfo();
		$goodsList = self::getGoodsList();
		foreach($goodsList as $index => $goodsId)
		{
			if(!isset(btstore_get()->PASS_GOODS[$goodsId]))
			{
				Logger::fatal('goods %d has beed deleted or can not be sold', $goodsId);
				unset($this->dataModify[PassDef::TBL_FIELD_VA_GOODS_LIST][$index]);
				continue;
			}
			
			$saleNum = btstore_get()->PASS_GOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
			$soldNum = 0;
			if(isset($arrBuyInfo[$goodsId]))
			{
				$soldNum = $arrBuyInfo[$goodsId]['num'];
			}
			
			if ($saleNum < $soldNum) 
			{
				Logger::warning('PassShop.getShopInfo goods[%d] saleNum[%d] < soldNum[%d]', $goodsId, $saleNum, $soldNum);
			}
			
			$canBuyNum = ($saleNum < $soldNum ? 0 : $saleNum - $soldNum);
			$shopInfo['goods_list'][$goodsId] = $canBuyNum;
		}
		
		if (!isset($shopInfo['goods_list'])) 
		{
			Logger::warning('PassShop.getShopInfo do not have valid goods, level is low or buy enough');
			$shopInfo['goods_list'] = array();
		}
		//$shopInfo['refresh_cd'] = self::calcNextSysRefreshTime();
		$shopInfo['free_refresh'] = btstore_get()->PASS_SHOP[PassShopCsvTag::FREE_REFRESH] - $this->getFreeRfrNum();
		$shopInfo['gold_refresh_num'] = self::getUsrRfrNum(PassDef::TYPE_RFR_GOLD);
		$shopInfo['stone_refresh_num'] = self::getUsrRfrNum(PassDef::TYPE_RFR_STONE);
		$shopInfo['coin'] = PassObj::getInstance($this->uid)->getCoin();
		$shopInfo['exclude'] = array();
		
		// 标记特殊格子里的商品，让前端知道哪些商品是来自特殊格子的
		$arrGoodsNum = btstore_get()->PASS_SHOP[PassShopCsvTag::GOODS_NUM]->toArray();
		for ($i = 0; $i < count($arrGoodsNum); ++$i)
		{
			// 不在特殊格子，忽略
			if (!in_array($i, PassDef::$ExcludeTeam)) 
			{
				continue;
			}
			
			// 没有排除的商品，忽略
			$arrExclude = $this->getExclude($i);
			if (empty($arrExclude)) 
			{
				continue;
			}
			
			// 找出最后被塞进去的商品
			$aNum = $arrGoodsNum[$i];
			$aInfo = array_slice($arrExclude, -$aNum);
			$shopInfo['exclude'][$i] = $aInfo;
		}

		return $shopInfo;
	}
	
	public function getBuyInfo()
	{
		if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_ALL]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_ALL] = array();
		}
		return $this->dataModify[PassDef::TBL_FIELD_VA_ALL];
	}
	
	public function getGoodsList()
	{
		if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_GOODS_LIST]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_GOODS_LIST] = array();
		}
		return $this->dataModify[PassDef::TBL_FIELD_VA_GOODS_LIST];
	}
	
	public function getLastSysRefreshTime()
	{
		if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = 0;
		}
		return $this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME];
	}
	
	public function getExclude($index)
	{
		if (!empty($this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index])) 
		{
			return $this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index];
		}
		
		return array();
	}
	
	public function addExclude($index, $goodsList)
	{
		if (!empty($this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index] = array_merge($this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index], $goodsList);
		}
		else 
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_EXCLUDE][$index] = $goodsList;
		}
	}
	
	public function needSysRefresh($time = 0)
	{
		$currTime = $time;
		if (empty($currTime))
		{
			$currTime = Util::getTime();
		}
		
		$lastRefreshTime = self::getLastSysRefreshTime();
		$nextRefreshTime = self::calcNextSysRefreshTime($lastRefreshTime);
		
		if ($currTime >= $nextRefreshTime) 
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function calcNextSysRefreshTime($time = 0)
	{
		$currTime = $time;
		if (empty($currTime))
		{
			$currTime = Util::getTime();
		}
		
		$sysRefreshArr = btstore_get()->PASS_SHOP[PassShopCsvTag::SYS_REFRESH_INTERVAL]->toArray();
		if (empty($sysRefreshArr))
		{
			throw new InterException('PASS_SHOP sys_refresh_interval empty');
		}
		
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
	
	public function calcLastSysRefreshTime($time = 0)
	{
		$currTime = $time;
		if (empty($currTime)) 
		{
			$currTime = Util::getTime();
		}
		
		$sysRefreshArr = btstore_get()->PASS_SHOP[PassShopCsvTag::SYS_REFRESH_INTERVAL]->toArray();
		if (empty($sysRefreshArr)) 
		{
			throw new InterException('PASS_SHOP sys_refresh_interval empty');
		}
		
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
	
	public function getLastUsrRefreshTime()
	{
		if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME] = 0;
		}
		return $this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME];
	}
	
	public function setLastUsrRefreshTimeForConsole($time)
	{
		$this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME] = $time;
	}
	
	public function setLastSysRefreshTimeForConsole($time)
	{
		$this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $time;
	}
	
	/**
	 * 获取玩家花费刷新石或者金币刷新的次数
	 * @param int $costType 刷新花费类型（1-金币，2-刷新石）
	 * @return int $rfrTimes
	 */
	public function getUsrRfrNum($costType)
	{
		if($costType == PassDef::TYPE_RFR_GOLD)
		{
			if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM]))
			{
				$this->dataModify[PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM] = 0;
			}
			return $this->dataModify[PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM];
		}
		else if($costType == PassDef::TYPE_RFR_STONE)
		{
			if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM]))
			{
				$this->dataModify[PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM] = 0;
			}
			return $this->dataModify[PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM];
		}
	}
	
	/**
	 * 玩家使用“神兵刷新石”或者金币刷新神兵商店商品列表
	 * @param int $costType 刷新花费类型（1-金币，2-刷新石）
	 * 			
	 */
	public function usrRfrGoodsList($costType)
	{
		if($costType == PassDef::TYPE_RFR_GOLD)
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_USR_GOLD_RFR_NUM] += 1;
		}
		else if($costType == PassDef::TYPE_RFR_STONE)
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_USR_STONE_RFR_NUM] += 1;
		}
		$this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME] = Util::getTime();
		
		$this->refreshGoodsList();
	}
	
	public function getFreeRfrNum()
	{
		if (!isset($this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM]))
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
		}
		return $this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM];
	}
	
	public function freeRfrGoodsList()
	{
		if ($this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM] < btstore_get()->PASS_SHOP[PassShopCsvTag::FREE_REFRESH])
		{
			$this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM] += 1;
			$this->refreshGoodsList();
		}
		else 
		{
			throw new FakeException('no enough free refresh num, current refresh num:%d', $this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM]);
		}
		
	}
	
	public function getExchangeConf($goodsId)
	{
		if (!isset(btstore_get()->PASS_GOODS[$goodsId]))
		{
			Logger::fatal('The goods is not existed in PASS_GOODS, but want to getExchangeConf, goodsId:%d', $goodsId);
			return array();
		}
	
		return btstore_get()->PASS_GOODS[$goodsId]->toArray();
	}
	
	public function subExtra($goodsId, $num)
	{
		Logger::trace('PassShop.subExtra param[goodsId:%d, num:%d] begin...', $goodsId, $num);
		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('PassShop.subExtra Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		
		if (!isset(btstore_get()->PASS_GOODS[$goodsId]))
		{
			throw new FakeException('PassShop.subExtra The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$goodsConf = btstore_get()->PASS_GOODS[$goodsId]->toArray();
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (!isset($extraReq['coin']))
		{
			throw new ConfigException('PassShop.subExtra no coin in req');
		}
	
		$subCoin = intval($extraReq['coin']) * $num;
		Logger::trace('PassShop.subExtra buy goods[%d] num[%d], need sub coin[%d]', $goodsId, $num, $subCoin);
		
		$passObj = PassObj::getInstance($this->uid);
		if (!$passObj->subCoin($subCoin)) 
		{
			throw new FakeException('PassShop.subExtra buy goods[%d] num[%d], need sub coin[%d], but not enough coin', $goodsId, $num, $subCoin);
		}
		$passObj->update();

		Logger::trace('PassShop.subExtra param[goodsId:%d, num:%d] end...', $goodsId, $num);
		return TRUE;
	}
	
	public function addExtra($goodsId, $num)
	{
		Logger::trace('PassShop.addExtra param[goodsId:%d, num:%d] begin...', $goodsId, $num);
		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('PassShop.addExtra Err para, goodsId:%d num:%d!', $goodsId, $num);
		}

		if (empty(btstore_get()->PASS_GOODS[$goodsId]))
		{
			throw new FakeException('PassShop.addExtra The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$goodsConf = btstore_get()->PASS_GOODS[$goodsId];
		$extraAcq = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		
		if (isset($extraAcq['coin']))
		{
			$addCoin = intval($extraAcq['coin']) * $num;
			Logger::trace('PassShop.addExtra buy goods[%d] num[%d], need add coin[%d]', $goodsId, $num, $addCoin);
			
			$passObj = PassObj::getInstance($this->uid);
			$passObj->addCoin($addCoin);
			$passObj->update();
		}
		
		if (isset($extraAcq['mul_silver'])) 
		{
			$userObj = EnUser::getUserObj($this->uid);
			$level = $userObj->getLevel();
			$addSilver = intval($extraAcq['mul_silver']) * intval($level) * $num;
			Logger::trace('PassShop.addExtra buy goods[%d] num[%d], level[%d] mul silver[%d] need add total silver[%d]', $goodsId, $num, $level, intval($extraAcq['mul_silver']), $addSilver);
			
			$userObj->addSilver($addSilver);
			$userObj->update();
		}
		
		if (isset($extraAcq['mul_soul'])) 
		{
			$userObj = EnUser::getUserObj($this->uid);
			$level = $userObj->getLevel();
			$addSoul = intval($extraAcq['mul_soul']) * $level * $num;
			Logger::trace('PassShop.addExtra buy goods[%d] num[%d], level[%d] mul soul[%d] need add total soul[%d]', $goodsId, $num, $level, intval($extraAcq['mul_soul']), $addSoul);
			
			$userObj->addSoul($addSoul);
			$userObj->update();
		}
		
		if (isset($extraAcq['mul_exp']))
		{
			$userObj = EnUser::getUserObj($this->uid);
			$level = $userObj->getLevel();
			$addExp = intval($extraAcq['mul_exp']) * intval($level) * $num;
			Logger::trace('PassShop.addExtra buy goods[%d] num[%d], level[%d] mul exp[%d] need add total exp[%d]', $goodsId, $num, $level, intval($extraAcq['mul_exp']), $addExp);
				
			$userObj->addExp($addExp);
			$userObj->update();
		}
		
		Logger::trace('PassShop.addExtra param[goodsId:%d, num:%d] end...', $goodsId, $num);
		return TRUE;
	}
	
	public function getArrGoodsByArrId($arrGoodsId)
	{
		$ret = array();
		
		foreach ($arrGoodsId as $goodsId)
		{
			if (!isset(btstore_get()->PASS_GOODS[$goodsId])) 
			{
				//Logger::trace('goodsId[%d] not in btstore file PASS_GOODS, may be can not sold', $goodsId);
				continue;
			}
			$ret[$goodsId] = btstore_get()->PASS_GOODS[$goodsId]->toArray();
		}
		
		return $ret;
	}
	
	public function resetFreeRefreshNumForConsole()    //给测试 和 前端 测试 使用的
	{
		$this->dataModify[PassDef::TBL_FIELD_VA_FREE_RFR_NUM] = 0;
	}
	
	public function resetLastSysRefreshTimeForConsole()		//给测试使用的
	{
		$currDay = strtotime(date('Ymd', Util::getTime()) . '000000');
		$oneDayago = $currDay - SECONDS_OF_DAY;
		$this->dataModify[PassDef::TBL_FIELD_VA_LAST_SYS_RFR_TIME] = $oneDayago;
	}
	
	public function resetLastUsrRefreshTimeForConsole()		//给测试使用的
	{
		$currDay = Util::getTime();
		$oneDayago = $currDay - SECONDS_OF_DAY;
		$this->dataModify[PassDef::TBL_FIELD_VA_LAST_USR_RFR_TIME] = $oneDayago;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */