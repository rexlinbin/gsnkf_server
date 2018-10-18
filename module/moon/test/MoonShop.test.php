<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonShop.test.php 189073 2015-08-05 09:50:41Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/test/MoonShop.test.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-08-05 09:50:41 +0000 (Wed, 05 Aug 2015) $
 * @version $Revision: 189073 $
 * @brief 
 *  
 **/
class MoonShopText extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('mbg' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(10000);
		$console->prestige(10000);

		// 加点天工令
		EnUser::getUserObj(self::$uid)->addTgNum(10000);
		EnUser::getUserObj(self::$uid)->update();

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
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}
	
	public function test_shop()
	{
		
		// 根据需要的等级，打开商店的switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::MOON]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
		
		// 1、玩家第一次进入商店
		$moon = new Moon();
		$ret = $moon->getShopInfo();
		//var_dump($ret);
		$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret['goods_list']));
		$this->assertEquals(0, $ret['gold_refresh_num']);
	
		///2、免费刷新1次
		$this->assertEquals(btstore_get()->MOON_SHOP['free_refresh_num'], $ret['free_refresh_num']);
		$ret2 = $moon->refreshGoodsList();
		var_dump($ret2);
		$this->assertEquals(btstore_get()->MOON_SHOP['free_refresh_num'] - 1, $ret2['free_refresh_num']);
	
		///3、免费的刷新次数用完
		for ($i = 1; $i < $ret2['free_refresh_num']; ++$i)
		{
			$moon->refreshGoodsList();
		}
		$ret2 = $moon->refreshGoodsList();
		$this->assertEquals(0, $ret2['free_refresh_num']);
	
		///4、免费的刷新次数用完后，测试玩家是否是付费刷新
		$curGold = EnUser::getUserObj(self::$uid)->getGold();
		$costGold = intval(current(btstore_get()->MOON_SHOP['usr_refresh_cost']->toArray()));
		$ret3 = $moon->refreshGoodsList();
		$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret2['goods_list']));
		$this->assertEquals(1, $ret3['gold_refresh_num']);//玩家刷新了1次
		$this->assertEquals($curGold - $costGold, EnUser::getUserObj(self::$uid)->getGold());
		//$this->assertNotEquals($ret2['goods_list'], $ret['goods_list']);//极小概率刷完还相同
		//var_dump($ret3);
	
		// 5、验证玩家付费刷新次数达到上限
		$refreshLimit = intval(btstore_get()->VIP[EnUser::getUserObj(self::$uid)->getVip()]['tgShopRefreshLimit']);
		for ($i = 1; $i < $refreshLimit; ++$i)
		{
			$moon = new Moon();
			$ret = $moon->refreshGoodsList();
			//var_dump($ret);
			$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret['goods_list']));
			$this->assertEquals($i + 1, $ret['gold_refresh_num']);
		}
		try
		{
			$moon = new Moon();
			$moon->refreshGoodsList();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	
		// 6、验证刷新次数重置
	
		// （1）系统刷新次数和免费刷新次数的重置
		//先把记录的最后一次刷新时间调回到一天前
		$moonShop = new MoonShop();
		$time = strtotime(date('Ymd', Util::getTime())) - SECONDS_OF_DAY; //把时间调回到前一天0点
		$va_mall = array
		(
				MoonShopField::TBL_FIELD_VA_ALL => $moonShop->getBuyInfo(),
				MoonShopField::TBL_FIELD_VA_GOODS_LIST => $moonShop->getGoodsList(),
				MoonShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME => $time,
				MoonShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME => $time,
				MoonShopField::TBL_FIELD_VA_USR_RFR_NUM => $ret['gold_refresh_num'],
				MoonShopField::TBL_FIELD_VA_FREE_RFR_NUM => $ret2['free_refresh_num'],
		);
		$arrField = array
		(
				'uid' => self::$uid,
				'mall_type' => MallDef::MALL_TYPE_TGSHOP,
				'va_mall' => $va_mall,
		);
		$data = new CData();
		$arrRet = $data->insertOrUpdate(MallDef::MALL_TABLE)
		->values($arrField) //insertOrUpdate函数的$arrField必须写全
		->where('uid', '=', self::$uid)
		->where('mall_type', '=', MallDef::MALL_TYPE_TGSHOP)
		->query();
	
		$moonShop = new MoonShop();   //这一步不可或缺,因为直接修改了数据库，但是menCache中还保留着老数据,需要重新获得一次对象,然后重新拉取数据时才更新了menCache
		//var_dump( $moonShop->getLastSysRefreshTime() );
		$ret = $moon->getShopInfo();
		$this->assertEquals(btstore_get()->MOON_SHOP['free_refresh_num'], $ret['free_refresh_num']);
		// （2）玩家付费刷新重置
		$this->assertEquals(0, $ret['gold_refresh_num']);
		
		
	
		// 7、购买第一个商品
		$firstGoodsId = key($ret['goods_list']);
		$firstGoodsNum = current($ret['goods_list']);
		$moon = new Moon();
		$ret = $moon->buyGoods($firstGoodsId);
		//var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);
	
		$moon = new Moon();
		$ret = $moon->getShopInfo();
		//var_dump($ret);
		$this->assertEquals($firstGoodsNum - 1, $ret['goods_list'][$firstGoodsId]);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */