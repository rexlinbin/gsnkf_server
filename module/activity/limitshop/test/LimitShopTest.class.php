<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LimitShopTest.class.php 150607 2015-01-07 05:35:34Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/limitshop/test/LimitShopTest.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-07 05:35:34 +0000 (Wed, 07 Jan 2015) $
 * @version $Revision: 150607 $
 * @brief 
 *  
 **/
class LimitShopTest extends PHPUnit_Framework_TestCase
{
	private static $uid;
	private static $pid;
	private static $tblName = 't_mall';
	
	public static function setUpBeforeClass()
	{
		self::$pid = IdGenerator::nextId('uid');
		$utid = 1;
		$uname = strval(self::$pid);
		$ret = UserLogic::createUser(self::$pid, $utid, $uname);
	
		if ($ret['ret'] != 'ok')
		{
			echo "create user failed \n";
			exit();
		}
	
		self::$uid = $ret['uid'];
	}
	
	protected function setUp()
	{
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	
	protected function tearDown()
	{
		
	}
	
	public function testGetInfo()
	{
		$myLimitShop = new LimitShop();
		$hasBuyInfo = $myLimitShop->getLimitShopInfo();
		
		$vaMall = MallDao::select(self::$uid, MallDef::MALL_TYPE_LIMITSHOP);
		
		if ( empty($vaMall) )
		{
			$allBuyInfo = array();
		}
		else 
		{
			$allBuyInfo = $vaMall['all'];
		}
		
		if ( empty($allBuyInfo) )
		{
			$allBuyInfo = array();
		}
		else 
		{
			foreach ( $allBuyInfo as $goodsId => $index)
			{
				if ( !isset($index['time']) || !isset($index['num']) )
				{
					unset($allBuyInfo[$goodsId]);
					break;
				}
				
				if (FALSE == $myLimitShop->isInCurRound($index['time']))
				{
					unset($allBuyInfo[$goodsId]);
					break;
				}
	
				unset($allBuyInfo[$goodsId]['time']);
			}
		}
		
		$this->assertEquals($allBuyInfo, $hasBuyInfo);
	}
	
	public function testBuyGoods()
	{
		// goodsId 是0 直接报错
		$myLimitShop = new LimitShop();
		try {
			$goodsId = 0;
			$myLimitShop->buyGoods($goodsId);
			
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 购买不在商品列表中的物品 
		$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
		$todayConf = LimitShop::getConfOfSomeday($day+1);
		
		try {
			$goodsId = 1;
			$arrGoodsId = array_keys($todayConf);
			while (TRUE)
			{
				if ( !in_array($goodsId, $arrGoodsId) )
				{
					break;
				}
				$goodsId++;
			}
			
			$myLimitShop->buyGoods($goodsId);
			
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		try {
			$goodsId = 1;
			$arrGoodsId = array_keys($todayConf);
			while (TRUE)
			{
				if ( in_array($goodsId, $arrGoodsId) )
				{
					break;
				}
				$goodsId++;
			}

			$num = 0;
			$myLimitShop->buyGoods($goodsId,$num);
				
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// VIP等级不足
		foreach ($todayConf as $goodsId => $index)
		{			
			if ( $index[LimitShopDef::LIMIT_VIP] >= 1 )
			{
				try {
					$user = EnUser::getUserObj(self::$uid);
					$user->setVip(0);
					$user->update();
					
					$myLimitShop = new LimitShop();
					$myLimitShop->buyGoods($goodsId);
					
					$this->assertTrue(0);
				}
				catch (Exception $e)
				{
					$this->assertEquals('fake', $e->getMessage());
				}
				break;
			}
		}
		
		// 超过限购数量
		foreach ($todayConf as $goodsId => $index)
		{
			if ($index[LimitShopDef::LIMIT_NUM] >= 1)
			{
				$user = EnUser::getUserObj(self::$uid);
				$user->setVip($index[LimitShopDef::LIMIT_VIP]);
				$user->addGold(200000, 0);
				$user->update();
				$myLimitShop = new LimitShop();
				
				try {
					$myLimitShop->buyGoods($goodsId, $index[LimitShopDef::LIMIT_NUM] + 1);
					$this->assertTrue(0);
				}
				catch (Exception $e)
				{
					$this->assertEquals('fake', $e->getMessage());
				}
				break;
			}
		}
		
		//金币不足
		foreach ($todayConf as $goodsId => $index)
		{
			if ($index[LimitShopDef::LIMIT_NUM] >= 1 && $index[LimitShopDef::NOW_COST] > 0)
			{
				$user = EnUser::getUserObj(self::$uid);
				$curGoldNum = $user->getGold();
				$user->subGold($curGoldNum, 0);
				$user->setVip($index[LimitShopDef::LIMIT_VIP]);
				$user->update();
				$myLimitShop = new LimitShop();
				
				try {
					$myLimitShop->buyGoods($goodsId);
					$this->assertTrue(0);
				}
				catch (Exception $e)
				{
					$this->assertEquals('fake', $e->getMessage());
				}
				break;
			}
		}
		
		//正常购买
		foreach ($todayConf as $goodsId => $index)
		{
			$hasBuyInfo = $myLimitShop->getLimitShopInfo();
			
			if (!isset($hasBuyInfo[$goodsId]) || $hasBuyInfo[$goodsId]['num'] < $index[LimitShopDef::LIMIT_NUM])
			{
				$user = EnUser::getUserObj(self::$uid);
				$user->setVip($index[LimitShopDef::LIMIT_VIP]);
				$user->addGold(20000, 0);
				
				$myLimitShop->buyGoods($goodsId);
				break;
			}
		}
	}
	
	public function test_getConf()
	{
		$myLimitShop = new LimitShop();
		$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
		$todayConf = LimitShop::getConfOfSomeday($day+1);
		
		try {
			$goodsId = 1;
			$arrGoodsId = array_keys($todayConf);
			while (TRUE)
			{
				if ( !in_array($goodsId, $arrGoodsId) )
				{
					break;
				}
				$goodsId++;
			}
			
			$myLimitShop->getExchangeConf($goodsId);
			
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
// 		$actConf = ActivityConfDao::getCurConfByName(ActivityName::LIMITSHOP, ActivityDef::$ARR_CONF_FIELD);
// 		$actConfVaData = $actConf['va_data'];
// 		try {
			
// 			if( empty($actConf) )
// 			{
// 				throw new FakeException('Act limitshop conf is empty.');
// 			}
			
// 			$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
// 			$todayConf = LimitShop::getConfOfSomeday($day+1);
			
// 			foreach ($todayConf as $goodsId => $index)
// 			{
// 				$actConf['version'] = Util::getTime();
// 				$actConf['va_data']['day_info'][$day+1][$goodsId][LimitShopDef::ITEMS][0] = RewardConfType::SILVER;
// 				ActivityConfDao::insertOrUpdate($actConf);
// 				ActivityConfLogic::updateMem();
// 			}
			
// 			$day = EnActivity::getActivityDay(ActivityName::LIMITSHOP);
// 			$todayConf = LimitShop::getConfOfSomeday($day+1);
			
// //			$todayConf = $myLimitShop -> getExchangeConf($goodsId);
			
// 			var_dump($todayConf);
			
// 			$this->assertTrue(0);
// 		}
// 		catch (Exception $e)
// 		{
// 			$actConf['version'] = Util::getTime();
// 			$actConf['va_data'] = $actConfVaData;
// 			ActivityConfDao::insertOrUpdate($actConf);
// 			ActivityConfLogic::updateMem();
			
// 			$this->assertEquals('fake', $e->getMessage());
// 		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */