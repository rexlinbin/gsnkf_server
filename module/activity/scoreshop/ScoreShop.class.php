<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ScoreShop.class.php 160200 2015-03-05 09:23:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/scoreshop/ScoreShop.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-03-05 09:23:20 +0000 (Thu, 05 Mar 2015) $
 * @version $Revision: 160200 $
 * @brief 
 *  
 **/
class ScoreShop extends Mall implements IScoreShop
{
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		parent::__construct($uid, MallDef::MALL_TYPE_SCORESHOP,
				StatisticsDef::ST_FUNCKEY_MALL_SCORESHOP_COST,StatisticsDef::ST_FUNCKEY_MALL_SCORESHOP_GET);
		
		if (FALSE == EnActivity::isOpen(ActivityName::SCORESHOP))
		{
			throw new FakeException('Act scoreshop is not open.');
		}
		
		$this->loadData();
		
		if (empty($this->dataModify))
		{
			$this->dataModify = array(
					MallDef::ALL => array(),
					ScoreShopDef::SQL_USED_POINT => 0,
					ScoreShopDef::SQL_UPDATE_TIME => Util::getTime(),
			);
		}
		
		$this->resetData();
		$this->refreshData();
	}
	
	public function getShopInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$sumPoint = self::getSumPoint();
		$hasUsedPoint = $this->dataModify[ScoreShopDef::SQL_USED_POINT];
		
		$point = 0;
		$point = $sumPoint - $hasUsedPoint;
		
		$hasBuyInfo = $this->getInfo();
		
		foreach ($hasBuyInfo as $key => $value)
		{
			if ( isset($value['time']) )
			{
				unset($hasBuyInfo[$key]['time']);
			}
		}
		
		$shopInfo = array(
				'point' => $point,
				'hasBuy' => $hasBuyInfo
		);
		
		Logger::trace('ScoreShop get info.shopInfo:%s',$shopInfo);
		
		return $shopInfo;
	}
	
	public function buy($goodsId, $num = 1)
	{
		Logger::trace('ScoreShop Buy Start.');
		
		$goodsId = intval($goodsId);
		$num = intval($num);
		
		if ( $goodsId <= 0 || $num <= 0 )
		{
			throw new FakeException('param invaild. goodsId:%d, num:%d.',$goodsId,$num);
		}
		
		$ret = $this->exchange($goodsId, $num);
		
		Logger::trace('User buy %d,num:%d,ret:%s.',$goodsId,$num,$ret);
		
		return 'ok';
	}
	
	public function getExchangeConf($exchangeId)
	{
		$conf = EnActivity::getConfByName(ActivityName::SCORESHOP);
		
		$items = $conf['data'][ScoreShopDef::ITEMS];
		
		if ( !in_array($exchangeId, array_keys($items) ))
		{
			throw new FakeException('GoodsId %d is not on sale.', $exchangeId);
		}
		
		return $items[$exchangeId];
	}
	
	public function subExtra($exchangeId, $num)
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$exchangeConf = self::getExchangeConf($exchangeId);
		$point4Each = $exchangeConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][ScoreShopDef::POINT];
		$needPoint = $point4Each * $num;
		
		$shopInfo = self::getShopInfo();
		$canUsePoint = $shopInfo['point'];
		
		if ( $canUsePoint < $needPoint )
		{
			return FALSE;
		}
		
		$this->dataModify[ScoreShopDef::SQL_USED_POINT] += $needPoint;
		return TRUE;
	}
	
	public function isInCurRound($time)
	{
		if ($time >= self::getActStartTime() && $time <= self::getActEndTime())
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function resetData()
	{
		if ($this->dataModify[ScoreShopDef::SQL_UPDATE_TIME] < self::getActStartTime())
		{
			$this->dataModify[ScoreShopDef::SQL_USED_POINT] = 0;
			$this->dataModify[ScoreShopDef::SQL_UPDATE_TIME] = Util::getTime();
		}
	}
	
	public function getSumPoint()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$conf = self::getActConfData();
		
		//这儿和策划说好了，即使有某项用不到，也不会配0，会配一个很大的数（20150303 TangL）
		$goldPerPoint = $conf[ScoreShopDef::TO_POINT][ScoreShopDef::GOLD_EACH_POINT];
		$executionPerPoint = $conf[ScoreShopDef::TO_POINT][ScoreShopDef::EXECUTION_EACH_POINT];
		$staminaPerPoint = $conf[ScoreShopDef::TO_POINT][ScoreShopDef::STAMINA_EACH_POINT];
		
		$gainPointDay = $conf[ScoreShopDef::GAIN_POINT_DAY];
		
		$startTime = self::getActStartTime();
		$startDate = intval( strftime( "%Y%m%d", $startTime ) );
		
		$endTime = intval(strtotime(date('Y-m-d', $startTime))) + $gainPointDay * SECONDS_OF_DAY - 1;
		$endDate = intval( strftime( "%Y%m%d", $endTime ) );
		
		$sumPoint = 0;
		$userObj = EnUser::getUserObj($uid);
		$accumGold = $userObj->getAccumSpendGold($startDate,$endDate);
		$accumExecution = $userObj->getAccumSpendExecution($startDate,$endDate);
		$accumStamina = $userObj->getAccumSpendStamina($startDate,$endDate);
		
		//此处为策划规定，三项各自向下取整完再相加
		$pointByGold = intval($accumGold / $goldPerPoint);
		$pointByExecution = intval($accumExecution / $executionPerPoint);
		$pointByStamina = intval($accumStamina / $staminaPerPoint);
		
		$sumPoint = $pointByGold + $pointByExecution + $pointByStamina;
		
		return $sumPoint;
	}
	
	public function getActStartTime()
	{
		$ret = EnActivity::getConfByName(ActivityName::SCORESHOP);
		return $ret['start_time'];
	}
	
	public function getActEndTime()
	{
		$ret = EnActivity::getConfByName(ActivityName::SCORESHOP);
		return $ret['end_time'];
	}
	
	public function getActConfData()
	{
		$ret = EnActivity::getConfByName(ActivityName::SCORESHOP);
		return $ret['data'];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */