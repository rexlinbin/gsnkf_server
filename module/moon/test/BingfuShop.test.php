<?php

class BingfuShopTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('pnn' . $pid);
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

		// 加点兵符积分
		EnUser::getUserObj(self::$uid)->addTallyPoint(2000000);
		EnUser::getUserObj(self::$uid)->addGold(10000, StatisticsDef::ST_FUNCKEY_MOON_BINGFU_SHOP_REWARD);
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
	
	
	
	public function test_switch()
	{
		// 功能节点还没有打开
		try
		{
			$moon = new Moon();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	
		// 根据需要的等级，打开switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::MOON]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
	}
	
	public function test_buy()
	{
		$needLv = intval(btstore_get()->BINGFU_SHOP[1][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL]);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
		$obj = new BingfuShop(self::$uid);	
				// 1、玩家第一次进入商店
		$moon = new Moon();
		$ret = $moon->getTallyInfo();
		//var_dump($ret);
		//商品刷出来的数量和预期对比
		$this->assertEquals(count(btstore_get()->BINGFU_RULE['itemTeamNum']), count($ret['goods_list']));
		$this->assertEquals(0, $ret['gold_refresh_num']);
		$this->assertEquals(btstore_get()->BINGFU_RULE['freeRefreshNum'], $ret['free_refresh_num']);
	
		///2、免费刷新1次
		$ret2 = $moon->refreshTallyGoodsList();
		//var_dump($ret2);
		$this->assertEquals(btstore_get()->BINGFU_RULE['freeRefreshNum'] - 1, $ret2['free_refresh_num']);
	
		///3、免费的刷新次数用完
		for ($i = 1; $i < $ret2['free_refresh_num']; ++$i)
		{
			$ret = $moon->refreshTallyGoodsList();
		}
		
		$ret2 = $moon->refreshTallyGoodsList();
		$this->assertEquals(0, $ret2['free_refresh_num']);
	   
		///4、免费的刷新次数用完后，测试玩家是否是付费刷新
		$curGold = EnUser::getUserObj(self::$uid)->getGold();
		$costGold = intval(current(btstore_get()->BINGFU_RULE['goldGost']->toArray()));
		$ret3 = $moon->refreshTallyGoodsList();
		$this->assertEquals(count(btstore_get()->BINGFU_RULE['itemTeamNum']), count($ret['goods_list']));
		$this->assertEquals(1, $ret3['gold_refresh_num']);//玩家刷新了1次
		$this->assertEquals($curGold - $costGold, EnUser::getUserObj(self::$uid)->getGold());
		//$this->assertNotEquals($ret2['goods_list'], $ret['goods_list']);//极小概率刷完还相同
		//var_dump($ret3);
	
		// 5、验证玩家付费刷新次数达到上限
		$refreshLimit = intval(btstore_get()->BINGFU_RULE['refreshNum']);
		for ($i = 1; $i < $refreshLimit; ++$i)
		{
			$moon = new Moon();
			$ret = $moon->refreshTallyGoodsList();
			//var_dump($ret);
			$this->assertEquals(count(btstore_get()->BINGFU_RULE['itemTeamNum']), count($ret['goods_list']));
			$this->assertEquals($i + 1, $ret['gold_refresh_num']);
		}
		try
		{
			$moon = new Moon();
			$moon->refreshTallyGoodsList();
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
				BingfuShopField::TBL_FIELD_VA_ALL => $moonShop->getBuyInfo(),
				BingfuShopField::TBL_FIELD_VA_GOODS_LIST => $moonShop->getGoodsList(),
				BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME => $time,
				BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME => $time,
				BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM => $ret['gold_refresh_num'],
				BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM => $ret2['free_refresh_num'],
		);
		$arrField = array
		(
				'uid' => self::$uid,
				'mall_type' => MallDef::MALL_TYPE_BINGFU_SHOP,
				'va_mall' => $va_mall,
		);
		$data = new CData();
		$arrRet = $data->insertOrUpdate(MallDef::MALL_TABLE)
		->values($arrField) //insertOrUpdate函数的$arrField必须写全
		->where('uid', '=', self::$uid)
		->where('mall_type', '=', MallDef::MALL_TYPE_BINGFU_SHOP)
		->query();
	
		//这一步不可或缺,因为直接修改了数据库，但是menCache中还保留着老数据,需要重新获得一次对象,然后重新拉取数据时才更新了menCache
		$moonShop = new BingfuShop();   
		//var_dump( $moonShop->getLastSysRefreshTime() );
		//printf("*************shopInfo****************\n");
		$ret = $moon->getTallyInfo();
		//printf("*************************************\n");
		$this->assertEquals(btstore_get()->BINGFU_RULE['freeRefreshNum'], $ret['free_refresh_num']);
		// （2）玩家付费刷新重置
		$this->assertEquals(0, $ret['gold_refresh_num']);
		
		
	
		// 7、购买第一个商品
		$firstGoodsId = key($ret['goods_list']);
		$firstGoodsNum = current($ret['goods_list']);
		$moon = new Moon();
		$ret = $moon->buyTally($firstGoodsId);
		//var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);
	
		$moon = new Moon();
		$ret = $moon->getTallyInfo();
		//var_dump($ret);
		//目前商品配置数量都是1，所以购买一个后将从商品列表消失，更改测试方式
		if($firstGoodsNum > 1)
		{
			$this->assertEquals($firstGoodsNum - 1, $ret['goods_list'][$firstGoodsId]);
		}
		else 
		{
			if(!isset($ret['goods_list'][$firstGoodsId]));
			{
				printf("*************bingfushop test success!****************\n");
			}
		}
	}
	
}