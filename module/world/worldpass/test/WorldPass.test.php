<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPass.test.php 182259 2015-07-03 07:56:13Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/test/WorldPass.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-03 07:56:13 +0000 (Fri, 03 Jul 2015) $
 * @version $Revision: 182259 $
 * @brief 
 *  
 **/
 
class WorldPassTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $pid = 0;
	private static $serverId = 0;

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
		self::$pid = $pid;
		self::$serverId = Util::getServerId();

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(1000000);
		$console->worldpass_setHellPoint(100000);

		var_dump(self::$uid);
		var_dump(self::$pid);
		var_dump(self::$serverId);
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

	protected static function getPrivateMethod($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}

	public function test_switch()
	{
		// 功能节点还没有打开
		try
		{
			$worldPassObj = new WorldPass();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}

		// 根据需要的等级，打开switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::WORLDPASS]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
	}

	public function test_getWorldPassInfo()
	{
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		var_dump($ret);
		if ($ret['ret'] == 'ok') 
		{
			$this->assertEquals(0, $ret['passed_stage']);
			$this->assertEquals(0, $ret['curr_point']);
			$this->assertEquals(100000, $ret['hell_point']);
			$this->assertEquals(intval(btstore_get()->WORLD_PASS_RULE['default_atk_num']), $ret['atk_num']);
			$this->assertEquals(0, $ret['buy_atk_num']);
			$this->assertEquals(0, $ret['refresh_num']);
			$this->assertEquals(WorldPassConf::STAGE_COUNT, count($ret['monster']));
			$this->assertEquals(WorldPassConf::CHOICE_COUNT, count($ret['choice']));
			$expectFormation = array();
			for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
			{
				$expectFormation[$i] = 0;
			}
			$this->assertEquals($expectFormation, $ret['formation']);
			$this->assertEmpty($ret['point']);
		}
	}
	
	public function test_attack()
	{
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		$arrChoice = $ret['choice'];
		
		// 略过第1个关卡，直接攻打第2个关卡，抛fake
		try
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->attack(2, array(0 => $arrChoice[0]));
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 攻打第1个关卡，但是使用一个不在备选武将列表里面的武将
		try
		{
			// 找到一个不在备选武将列表里的武将
			$aHtidNotInChoice = 0;
			$allHero = btstore_get()->WORLD_PASS_RULE['all_hero']->toArray();
			foreach ($allHero as $aHtid => $aInfo)
			{
				if (!in_array($aHtid, $arrChoice)) 
				{
					$aHtidNotInChoice = $aHtid;
					break;
				}
			}
			$this->assertTrue(!empty($aHtidNotInChoice));
			
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->attack(1, array(0 => $aHtidNotInChoice));
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 攻打第1个关卡，并且使用备选武将中的武将，应该没有问题
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->attack(1, array(0 => $arrChoice[0]));
		var_dump($ret);
		$this->assertEquals($ret['point'], WorldPassUtil::getPoint($ret['damage'], $ret['hp']));
		$this->assertEquals(0, $ret['hell_point']);
		
		// 攻打的是第1关，不扣次数
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		$curAtkNum = $ret['atk_num'];
		$this->assertEquals(intval(btstore_get()->WORLD_PASS_RULE['default_atk_num']), $curAtkNum);
		
		// 已经攻打过第1关啦，再攻打第1关抛fake
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		$arrChoice = $ret['choice'];
		try
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->attack(1, array(0 => $arrChoice[0]));
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 设置已经通关到倒数第2关。然后攻打最后
		$arrFakeFormation = array();
		$allHero = btstore_get()->WORLD_PASS_RULE['all_hero']->toArray();
		foreach ($allHero as $aHtid => $aInfo)
		{
			if (!in_array($aHtid, $arrChoice))
			{
				$arrFakeFormation[] = $aHtid;
				if (count($arrFakeFormation) == WorldPassConf::STAGE_COUNT - 1) 
				{
					break;
				}
			}
		}
		var_dump($arrFakeFormation);
		$this->assertEquals(WorldPassConf::STAGE_COUNT - 1, count($arrFakeFormation));
		$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance(self::$serverId, self::$pid, self::$uid);
		$worldPassInnerUserObj->setPassedStageForConsole(WorldPassConf::STAGE_COUNT - 1);
		$worldPassInnerUserObj->setCurrPointForConsole(100);
		$worldPassInnerUserObj->setFormationForConsole($arrFakeFormation);
		$worldPassInnerUserObj->update();
		
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		$this->assertEquals(WorldPassConf::STAGE_COUNT - 1, $ret['passed_stage']);
		$arrChoice = $ret['choice'];
		$arrFormation = $ret['formation'];
		$beforeHellPoint = $ret['hell_point'];
		var_dump($arrChoice);
		var_dump($arrFormation);
		
		// 攻打最后一关, 上阵人数不够的情况
		try
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->attack(WorldPassConf::STAGE_COUNT, array(0 => $arrChoice[0]));
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 正常攻打最后一关
		$worldPassObj = new WorldPass();
		$arrFormation[5] = $arrChoice[0];
		$ret = $worldPassObj->attack(WorldPassConf::STAGE_COUNT, $arrFormation);
		//var_dump($ret);
		$hellPoint = $ret['hell_point'];
		$curPoint = $ret['point'];
		
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		var_dump($ret);
		$this->assertEquals(WorldPassConf::STAGE_COUNT, $ret['passed_stage']);
		$this->assertEquals($curPoint + 100, $ret['curr_point']);
		$this->assertEquals($hellPoint + $beforeHellPoint, $ret['hell_point']);
		$this->assertEquals(intval(btstore_get()->WORLD_PASS_RULE['default_atk_num']) - 1, $ret['atk_num']);
		$this->assertEquals(0, $ret['buy_atk_num']);
		$this->assertEquals(0, $ret['refresh_num']);
		$this->assertEquals(WorldPassConf::STAGE_COUNT, count($ret['monster']));
		$this->assertEquals(WorldPassConf::CHOICE_COUNT, count($ret['choice']));
		$this->assertEquals(WorldPassConf::STAGE_COUNT, count($ret['formation']));
		$this->assertEquals(array(0 => $curPoint + 100), $ret['point']);
	}
	
	public function test_reset()
	{
		// 重置
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->reset();
		//var_dump($ret);
		
		// 验证信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$this->assertEquals(0, $ret['passed_stage']);
		$this->assertEquals(0, $ret['curr_point']);
		$expectFormation = array();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			$expectFormation[$i] = 0;
		}
		$this->assertEquals($expectFormation, $ret['formation']);
	}
	
	public function test_addAtkNum()
	{
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		
		// 开始时候的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$beforeAtkNum = $ret['atk_num'];
		$beforeBuyAtkNum = $ret['buy_atk_num'];
		
		// 购买1次
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->addAtkNum();
		var_dump($ret);
		
		// 买完的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$afterAtkNum = $ret['atk_num'];
		$afterBuyAtkNum = $ret['buy_atk_num'];
		
		// 验证一下
		$this->assertEquals($beforeAtkNum + 1, $afterAtkNum);
		$this->assertEquals($beforeBuyAtkNum + 1, $afterBuyAtkNum);
		
		// 买到最大次数
		$buyLimit = WorldPassUtil::getBuyLimit();
		for ($i = 1; $i < $buyLimit; ++$i)
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->addAtkNum();
			//var_dump($ret);
			$this->assertEquals('ok', $ret);
		}
		
		// 买完的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$afterAtkNum = $ret['atk_num'];
		$afterBuyAtkNum = $ret['buy_atk_num'];
		
		// 验证一下
		$this->assertEquals($beforeAtkNum + $buyLimit, $afterAtkNum);
		$this->assertEquals($beforeBuyAtkNum + $buyLimit, $afterBuyAtkNum);
		
		// 再买一次，超过最大次数，抛fake
		try
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->addAtkNum();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 验证扣的金币对不对
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = 0;
		for ($i = 1; $i <= $buyLimit; ++$i)
		{
			$needGold += WorldPassUtil::getBuyCost($i);
		}
		$this->assertEquals($beforeGold, $afterGold + $needGold);
	}
	
	public function test_getMyTeamInfo()
	{
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getMyTeamInfo();
		//var_dump($ret);
	}
	
	public function test_getRankList()
	{
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getRankList();
		var_dump($ret);
	}
	
	public function test_refreshHero()
	{
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		
		// 开始时候的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$beforeRfrNum = $ret['refresh_num'];
		
		// 刷新1次
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->refreshHeros();
		var_dump($ret);
		
		// 刷完的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$afterRfrNum = $ret['refresh_num'];
		
		// 验证一下
		$this->assertEquals($beforeRfrNum + 1, $afterRfrNum);
		
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = WorldPassUtil::getRefreshCost(1);
		$this->assertEquals($beforeGold, $afterGold + $needGold);
		
		// 增加道具，使用道具刷新
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$refreshItemId = intval(btstore_get()->WORLD_PASS_RULE['refresh_item_id']);
		$bag->addItemByTemplateID($refreshItemId, 1);
		$bag->update();
		$this->assertEquals(1, $bag->getItemNumByTemplateID($refreshItemId));
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->refreshHeros();
		var_dump($ret);
		$this->assertEquals(0, $bag->getItemNumByTemplateID($refreshItemId));
		$finalGold = EnUser::getUserObj(self::$uid)->getGold();
		$this->assertEquals($afterGold, $finalGold);
		
		// 再刷新还的用金币，没道具啦
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->refreshHeros();
		var_dump($ret);
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = WorldPassUtil::getRefreshCost(2);
		$this->assertEquals($beforeGold, $afterGold + $needGold);
		
		// 刷新到最大次数，刷新次数太多，几万次？先把这个单测注释掉吧
		/*$refreshLimit = WorldPassUtil::getRefreshLimit();
		for ($i = 1; $i < $refreshLimit; ++$i)
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->refreshHeros();
			//var_dump($ret);
		}
		
		// 买完的信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getWorldPassInfo();
		//var_dump($ret);
		$afterRfrNum = $ret['refresh_num'];
		
		// 验证一下
		$this->assertEquals($beforeRfrNum + $refreshLimit, $afterRfrNum);
		
		// 再刷一次，超过最大次数，抛fake
		try
		{
			$worldPassObj = new WorldPass();
			$ret = $worldPassObj->refreshHeros();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 验证扣的金币对不对
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = 0;
		for ($i = 1; $i <= $refreshLimit; ++$i)
		{
			$needGold += WorldPassUtil::getRefreshCost($i);
		}
		$this->assertEquals($beforeGold, $afterGold + $needGold);*/
	}
	
	public function test_getShopInfo()
	{
		// 获得商店信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getShopInfo();
		var_dump($ret);
		
		// 找个商品id买一下
		$aGoodsId = key(btstore_get()->WORLD_PASS_GOODS->toArray());
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->buyGoods($aGoodsId, 1);
		var_dump($ret);
		
		// 获得商店信息
		$worldPassObj = new WorldPass();
		$ret = $worldPassObj->getShopInfo();
		var_dump($ret);
	}
	
	public function test_reward()
	{
		WorldPassScriptLogic::reward(array(), FALSE, FALSE);
	}
	
	public function test_api()
	{
		$beginTime = WorldPassUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getAllTeam();
		var_dump($ret);
		$beginTime = WorldPassUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getServersByTeamId(1);
		var_dump($ret);
		$beginTime = WorldPassUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getServersByServerId(3003);
		var_dump($ret);
		$beginTime = WorldPassUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDPASS, 0, $beginTime)->getTeamIdByServerId(1);
		var_dump($ret);
	}
	
	public function test_team()
	{
		$ret = WorldPassUtil::getAllTeam();
		var_dump($ret);
		$ret = WorldPassUtil::getArrServerIdByTeamId(2);
		var_dump($ret);
		$ret = WorldPassUtil::getTeamIdByServerId(900);
		var_dump($ret);
	}
	
	public function test_randMonster()
	{
		$ret1 = WorldPassUtil::getRandMonster(1);
		var_dump($ret1);
		$ret2 = WorldPassUtil::getRandMonster(1);
		var_dump($ret2);
		$this->assertEquals($ret1, $ret2);
		
		$ret3 = WorldPassUtil::getRandMonster(2);
		var_dump($ret3);
		$ret4 = WorldPassUtil::getRandMonster(2);
		var_dump($ret4);
		$this->assertNotEquals($ret1, $ret3);//一般不会相同
	}
	
	public function test_getNameAllFromPlat()
	{
		$platform = ApiManager::getApi(true);
		$argv = array 
		(
				'platName' => PlatformConfig::PLAT_NAME,
		);
		$allServers = $platform->users('getNameAll', $argv);
		var_dump($allServers);
	}
	
	public function test_my_test()
	{
		WorldPassConf::$TEST_MODE = 1;
		$ret = WorldPassUtil::activityBeginTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldPassUtil::activityEndTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldPassConf::$TEST_MODE = 2;
		$ret = WorldPassUtil::activityBeginTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldPassUtil::activityEndTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldPassConf::$TEST_MODE = 1;
		$ret = WorldPassUtil::activityBeginTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldPassUtil::activityEndTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldPassConf::$TEST_MODE = 2;
		$ret = WorldPassUtil::activityBeginTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldPassUtil::activityEndTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		WorldPassConf::$TEST_MODE = 0;
	}
	
	public function test_serverOpenTime()
	{
		$ret = WorldPassUtil::serverOpenActivityTime();
		var_dump($ret);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
