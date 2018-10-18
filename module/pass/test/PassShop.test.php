<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassShop.test.php 258443 2016-08-25 08:43:46Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/test/PassShop.test.php $
 * @author $Author: MingmingZhu $(linjiexin@babeltime.com)
 * @date $Date: 2016-08-25 08:43:46 +0000 (Thu, 25 Aug 2016) $
 * @version $Revision: 258443 $
 * @brief 
 *  
 **/
class PassShopText extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('ljx' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		

		// 加点天工令
// 		EnUser::getUserObj(self::$uid)->addTgNum(10000);
// 		EnUser::getUserObj(self::$uid)->update();

		var_dump(self::$uid);
	}

	protected function setUp()
	{
		parent::setUp();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
// 		RPCContext::getInstance()->resetSession();
// 		RPCContext::getInstance()->unsetSession('global.uid');
	}

	public function test_shop()
	{
		$temp = 0;
		// 根据需要的等级，打开商店的switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::PASS]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();

		// 1、玩家第一次进入商店
		$pass = new Pass();
		$ret = $pass->getShopInfo();
		// var_dump($ret['free_refresh']);
		$this->assertEquals(0, $ret['gold_refresh_num']);
		$this->assertEquals(btstore_get()->PASS_SHOP['free_refresh'], $ret['free_refresh']);

		///2、免费刷新1次
		
		$ret2 = $pass->refreshGoodsList();
// 		var_dump($ret2);
		$this->assertEquals(btstore_get()->PASS_SHOP['free_refresh'] - 1, $ret2['free_refresh']);

		///3、免费的刷新次数用完
		for ($i = 1; $i < $ret2['free_refresh']; ++$i)
		{
			$pass->refreshGoodsList();
		}
		$ret2 = $pass->refreshGoodsList();
		$this->assertEquals(0, $ret2['free_refresh']);
// 		var_dump($ret2);
		///4、免费的刷新次数用完后，测试玩家是否是消耗物品或者付费刷新
		$console = new Console();
		$console->gold(10000);
		$itemId = btstore_get()->PASS_SHOP[PassShopCsvTag::USR_REFRESH_STONE]['templ_id'];
		$itemNum = btstore_get()->PASS_SHOP[PassShopCsvTag::USR_REFRESH_STONE]['cost_num'];
		$itemRet = $console->addItem($itemId, $itemNum);
		Logger::debug("Console.addItem: %s", $itemRet);
		$curGold = EnUser::getUserObj(self::$uid)->getGold();
		$costGold = intval(current(btstore_get()->PASS_SHOP['usr_refresh_cost']->toArray()));
		echo "Usr refresh goodlist using rfr-stone\n";
		Logger::debug("Usr refresh goodlist using rfr-stone");
		$ret3 = $pass->refreshGoodsList();
		$this->assertEquals(1, $ret3['stone_refresh_num']);//玩家使用“神兵刷新石”刷新了1次
		$this->assertEquals($curGold, EnUser::getUserObj(self::$uid)->getGold());
		echo "Usr refresh goodlist using gold. Origin gold num: $curGold , gold cost: $costGold\n";
		Logger::debug("Usr refresh goodlist using rfr-stone. Origin gold num: $curGold");
		$ret3 = $pass->refreshGoodsList();
// 		var_dump($ret3);
		//$this->assertEquals(intval(btstore_get()->PASS_SHOP['goods_num']), count($ret3['goods_num']));
		$this->assertEquals(1, $ret3['gold_refresh_num']);//玩家刷新了2次
		$this->assertEquals($curGold - $costGold, EnUser::getUserObj(self::$uid)->getGold());
		//$this->assertNotEquals($ret2['goods_list'], $ret['goods_list']);//极小概率刷完还相同
		//var_dump($ret3);

// 		// 5、验证玩家付费刷新次数达到上限
		$refreshLimit = intval(btstore_get()->PASS_SHOP['refresh_limit']);
		for ($i = 1; $i < $refreshLimit; ++$i)
		{
			$ret = $pass->refreshGoodsList();
			//var_dump($ret);
			//$this->assertEquals(intval(btstore_get()->PASS_SHOP['goods_num']), count($ret['goods_list']));
			$this->assertEquals($i + 1, $ret['gold_refresh_num']);
		}
		$this->assertEquals(btstore_get()->PASS_SHOP['refresh_limit'], $ret['gold_refresh_num']);
		try
		{
			$pass = new Pass();
			$ret = $pass->refreshGoodsList();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}



// 		7、购买第一个商品
		$firstGoodsId = key($ret['goods_list']);
		$firstGoodsNum = current($ret['goods_list']);
		$pass = new Pass();
		$ret = $pass->buyGoods($firstGoodsId);
		//var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);

		$pass = new Pass();
		$ret = $pass->getShopInfo();
		//var_dump($ret);
		$this->assertEquals($firstGoodsNum - 1, $ret['goods_list'][$firstGoodsId]);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */