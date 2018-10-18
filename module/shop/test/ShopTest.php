<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ShopTest.php 81105 2013-12-16 08:55:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/test/ShopTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-12-16 08:55:53 +0000 (Mon, 16 Dec 2013) $
 * @version $Revision: 81105 $
 * @brief 
 *  
 **/
class ShopTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 21806;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::SHOP);
		EnSwitch::getSwitchObj(self::$uid)->save();
	}

	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	public static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		echo "test user: " . self::$uid . "\n";
	}
	
	public static function getGoodsConf($type)
	{
		$goodsConf = btstore_get()->GOODS->toArray();
		
		foreach ($goodsConf as $id => $conf)
		{
			if (isset($conf[MallDef::MALL_EXCHANGE_ACQ][$type])) 
			{
				return array($id => $conf);
			}
		}
	}

	public function test_getShopInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$shop = new Shop();
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertEquals(1, $ret[ShopDef::SILVER_RECRUIT_NUM]);
		$this->assertEquals(0, $ret[ShopDef::GOLD_RECRUIT_NUM]);
	}
	
	public function test_bronzeRecruit()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag();
		$bag->clearBag();
		$bag->update();
		
		$conf = btstore_get()->SHOP[ShopDef::RECRUIT_TYPE_BRONZE];
		$items = $conf[ShopDef::RECRUIT_COST_ITEM];
		$bag->addItemsByTemplateID($items);
		
		$shop = new Shop();
		$ret = $shop->bronzeRecruit();
		$specialNum = $conf[ShopDef::RECRUIT_SPECIAL_NUM];
		for ($i = 0; $i < $specialNum[0] - 1; $i++)
		{
			$bag->addItemsByTemplateID($items);
			$ret = $shop->bronzeRecruit();
		}
		
		Logger::trace('bronze recruit:%s', $ret);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$user = EnUser::getUserObj();
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
	}
	
	public function test_silverRecruit()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$shop = new Shop();
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertTrue($ret[ShopDef::SILVER_RECRUIT_TIME] == 0);
		$this->assertEquals(ShopDef::NO_FREE_GOLD, $ret[ShopDef::SILVER_RECRUIT_STATUS]);
		
		$conf = btstore_get()->SHOP[ShopDef::RECRUIT_TYPE_SILVER];
		$cost = $conf[ShopDef::RECRUIT_COST_GOLD];
		$user = EnUser::getUserObj();
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$goldBefore = $user->getGold();
		
		//有免费次数时使用金币招将
		try
		{
			$ret = $shop->silverRecruit(1);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//免费首刷
		$ret = $shop->silverRecruit(0);
		
		//无免费次数时不使用金币招将
		try
		{
			$ret = $shop->silverRecruit(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//金币首刷，四星武将
		$ret = $shop->silverRecruit(1);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 4);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore-$cost, $goldAfter);
		
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertTrue($ret[ShopDef::SILVER_RECRUIT_TIME] > 0);
		$this->assertEquals(ShopDef::FREE_GOLD_NO, $ret[ShopDef::SILVER_RECRUIT_STATUS]);
		
		//非金币首刷
		$ret = $shop->silverRecruit(1);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertTrue($starLevel <= 5);
	}
	
	public function test_goldRecruit()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$shop = new Shop();
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertEquals(0, $ret['gold_recruit_sum']);
		$this->assertTrue($ret[ShopDef::GOLD_RECRUIT_TIME] > 0);
		$this->assertEquals(ShopDef::NO_FREE_GOLD, $ret[ShopDef::GOLD_RECRUIT_STATUS]);
		
		$conf = btstore_get()->SHOP[ShopDef::RECRUIT_TYPE_GOLD];
		$cost = $conf[ShopDef::RECRUIT_COST_GOLD];
		$costdicount = $conf[ShopDef::RECRUIT_MULTI_COST][10];
		
		$user = EnUser::getUserObj();
		$user->addGold($cost*5, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$goldBefore = $user->getGold();
		
		//无免费次数时不使用金币招将
		try
		{
			$ret = $shop->goldRecruit(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//金币首刷
		Logger::info("gold drop start");
		$ret = $shop->goldRecruit(1);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore-$cost, $goldAfter);
		
		for ($i = 0; $i < 3; $i++)
		{
			//默认金币掉落
			Logger::info("default gold drop start");
			$ret = $shop->goldRecruit(1);
			$this->assertEquals(count($ret['hero']), 1);
			$hid = key($ret['hero']);
			$htid = current($ret['hero']);
			$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
			$this->assertTrue($starLevel <= 5);
		}
		
		//累积第5次，五星武将
		Logger::info("special drop start");
		$ret = $shop->goldRecruit(1);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
		
		$ret = $shop->getShopInfo();
		$this->assertEquals(5, $ret['gold_recruit_sum']);
		$this->assertTrue($ret[ShopDef::GOLD_RECRUIT_TIME] > 0);
		$this->assertEquals(ShopDef::GOLD_NO_FREE, $ret[ShopDef::GOLD_RECRUIT_STATUS]);
		
		$user->addGold($cost*10, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$goldBefore = $user->getGold();
		//十连抽
		Logger::info("ten recruit start, special drop");
		$ret = $shop->goldRecruit(1, 10);
		$this->assertEquals(count($ret['hero']), 10);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore-$costdicount, $goldAfter);
		
		$sum = 0;
		foreach ($ret['hero'] as $hid => $htid)
		{
			$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
			if ($starLevel >= 5) 
			{
				$sum++;
			}
		}
		printf("five star hero num:%d\n", $sum);
		$this->assertTrue($sum >= 1 && $sum < 10);
		
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertEquals(15, $ret['gold_recruit_sum']);
		
		$arrField = array(
			ShopDef::GOLD_RECRUIT_NUM => 0,
			ShopDef::GOLD_RECRUIT_TIME => 0,
			ShopDef::GOLD_RECRUIT_STATUS => 0,
		);
		ShopDao::update(self::$uid, $arrField);
		
		//有免费次数时使用金币招将
		try
		{
			$ret = $shop->silverRecruit(1);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//免费首刷
		$user->addGold($cost*10, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		Logger::info("free drop start");
		$ret = $shop->goldRecruit(0);
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertEquals(1, $ret['gold_recruit_sum']);
		$this->assertTrue($ret[ShopDef::GOLD_RECRUIT_TIME] > 0);
		$this->assertEquals(ShopDef::FREE_NO_GOLD, $ret[ShopDef::GOLD_RECRUIT_STATUS]);
		
		//金币首刷加十连抽
		Logger::info("ten recruit start, gold drop and special drop");
		$ret = $shop->goldRecruit(1, 10);
		$this->assertEquals(count($ret['hero']), 10);
		$sum = 0;
		foreach ($ret['hero'] as $hid => $htid)
		{
			$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
			if ($starLevel >= 5)
			{
				$sum++;
			}
		}
		printf("five star hero num:%d\n", $sum);
		$this->assertTrue($sum >= 2 && $sum < 10);
		
		$ret = $shop->getShopInfo();
		Logger::trace('shop info:%s', $ret);
		$this->assertTrue(!empty($ret));
		$this->assertEquals(11, $ret['gold_recruit_sum']);
		$this->assertTrue($ret[ShopDef::GOLD_RECRUIT_TIME] > 0);
		$this->assertEquals(ShopDef::FREE_GOLD_NO, $ret[ShopDef::GOLD_RECRUIT_STATUS]);
		
		$arrField = array(
				ShopDef::GOLD_RECRUIT_NUM => 14,
				ShopDef::GOLD_RECRUIT_TIME => 0,
				ShopDef::GOLD_RECRUIT_STATUS => 1,
		);
		ShopDao::update(self::$uid, $arrField);
		
		//第十次免费，用累积掉落
		Logger::info("special drop start");
		$ret = $shop->goldRecruit(0);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
		
		$arrField = array(
				ShopDef::GOLD_RECRUIT_NUM => 14,
				ShopDef::GOLD_RECRUIT_TIME => Util::getTime() + 10,
				ShopDef::GOLD_RECRUIT_STATUS => 1,
		);
		ShopDao::update(self::$uid, $arrField);
		
		//第十次金币，用金币首刷掉落
		Logger::info("gold drop start");
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$ret = $shop->goldRecruit(1);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
		
		$arrField = array(
				ShopDef::GOLD_RECRUIT_NUM => 14,
				ShopDef::GOLD_RECRUIT_TIME => Util::getTime(),
				ShopDef::GOLD_RECRUIT_STATUS => 2,
		);
		ShopDao::update(self::$uid, $arrField);
		
		//第十次免费，用累积掉落
		Logger::info("special drop start");
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$ret = $shop->goldRecruit(0);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertEquals($starLevel, 5);
		
		$arrField = array(
				ShopDef::GOLD_RECRUIT_NUM => 13,
				ShopDef::GOLD_RECRUIT_TIME => Util::getTime(),
				ShopDef::GOLD_RECRUIT_STATUS => 1,
		);
		ShopDao::update(self::$uid, $arrField);
		
		//第9次免费，用默认免费掉落
		Logger::info("default free drop start");
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT);
		$ret = $shop->goldRecruit(0);
		$this->assertEquals(count($ret['hero']), 1);
		$hid = key($ret['hero']);
		$htid = current($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		$this->assertTrue($starLevel <= 5);
	}
	
	public function test_buyVipGift()
	{
		Logger::debug('======%s======', __METHOD__);

		$user = EnUser::getUserObj(self::$uid);
		$vip = $user->getVip();
		$goldBefore = $user->getGold();
		$subGold = $giftInfo = btstore_get()->VIP[$vip]['vipGift'][1];
		$shop = new Shop();
		$ret = $shop->buyVipGift($vip);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - $subGold, $goldAfter);
		$this->assertEquals('ok', $ret);
		$info = $shop->getShopInfo();
		$this->assertEquals(1, $info['va_shop']['vip_gift'][$vip]);
	}
	
	public function test_buyGoods()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		//购买商品获得物品
		$ret = $this->getGoodsConf(MallDef::MALL_EXCHANGE_ITEM);
		$goodsId = key($ret);
		$conf = current($ret);
		$items = $conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM];
		$itemTplId = key($items);
		$itemType = ItemManager::getInstance()->getItemType($itemTplId);
		$bagName = $bag->getBagNameByItemType($itemType);
		$ret = $bag->bagInfo();
		$bagBefore = $ret[$bagName];
		$shop = new Shop();
	 	$ret = $shop->buyGoods($goodsId, 1);
	 	$this->assertEquals('ok', $ret['ret']);
	 	$ret = $bag->bagInfo();
	 	$bagAfter = $ret[$bagName];
	 	$ret = array_diff($bagAfter, $bagBefore);
	 	$this->assertEquals(1, count($ret));
	 	
	 	//购买商品获得英雄
	 	$ret = $this->getGoodsConf(MallDef::MALL_EXCHANGE_HERO);
	 	$goodsId = key($ret);
	 	$conf = current($ret);
	 	$user = EnUser::getUserObj(self::$uid);
	 	$heros = $conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO];
	 	$htid = key($heros);
	 	$hnumBefore = $user->getHeroManager()->getHeroNumByHtid($htid);
	 	$ret = $shop->buyGoods($goodsId, 1);
	 	$this->assertEquals('ok', $ret['ret']);
	 	$hnumAfter = $user->getHeroManager()->getHeroNumByHtid($htid);
	 	$this->assertEquals($hnumBefore + 1, $hnumAfter);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */