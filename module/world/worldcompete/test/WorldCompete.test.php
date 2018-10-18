<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompete.test.php 202597 2015-10-15 13:00:10Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/test/WorldCompete.test.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-15 13:00:10 +0000 (Thu, 15 Oct 2015) $
 * @version $Revision: 202597 $
 * @brief 
 *  
 **/
 
class WorldCompeteTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $pid = 0;
	private static $serverId = 0;
	private static $teamId = 0;

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
		self::$teamId = WorldCompeteUtil::getTeamIdByServerId(self::$serverId);

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(1000000);
		$console->worldcompete_setCrossHonor(100000);

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
		parent::tearDown();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
		WorldCompeteInnerUserObj::releaseInstance(self::$serverId, self::$pid);
		WorldCompeteCrossUserObj::releaseInstance(self::$serverId, self::$pid);
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
			$worldCompeteObj = new WorldCompete();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}

		// 根据需要的等级，打开switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::WORLDCOMPETE]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
	}

	public function test_getWorldCompeteInfo()
	{
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		var_dump($ret);
		if ($ret['ret'] == 'ok') 
		{
			$this->assertEquals(0, $ret['max_honor']);
			$this->assertEquals(100000, $ret['cross_honor']);
			$this->assertEquals(0, $ret['atk_num']);
			$this->assertEquals(0, $ret['suc_num']);
			$this->assertEquals(0, $ret['buy_atk_num']);
			$this->assertEquals(0, $ret['refresh_num']);
			$this->assertEquals(0, $ret['worship_num']);
			$this->assertEquals(WorldCompeteConf::RIVAL_COUNT, count($ret['rival']));
			$this->assertEmpty($ret['prize']);
			$arrInclude = array();
			foreach ($ret['rival'] as $rivalInfo)
			{
				$key = WorldCompeteUtil::getKey($rivalInfo['server_id'], $rivalInfo['pid']);
				$this->assertTrue(!in_array($key, $arrInclude));
				$arrInclude[] = $key;
				$this->assertArrayHasKey('server_name', $rivalInfo);
				$this->assertArrayHasKey('uname', $rivalInfo);
				$this->assertArrayHasKey('vip', $rivalInfo);
				$this->assertArrayHasKey('level', $rivalInfo);
				$this->assertArrayHasKey('htid', $rivalInfo);
				$this->assertArrayHasKey('fight_force', $rivalInfo);
				$this->assertArrayHasKey('dress', $rivalInfo);
				$this->assertArrayHasKey('status', $rivalInfo);
				$this->assertEquals(0, $rivalInfo['status']);
			}
		}
	}
	
	public function test_attack()
	{
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$atkServerId = $ret['rival'][0]['server_id'];
		$atkPid = $ret['rival'][0]['pid'];
		
		// 打不存在的人
		try
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->attack($atkServerId, $atkPid+1, 0, 0);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 攻打第1个对手
		$crazy = 1;
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->attack($atkServerId, $atkPid, $crazy, 0);
		var_dump($ret);
		$isSuc = BattleDef::$APPRAISAL[$ret['appraisal']] <= BattleDef::$APPRAISAL['D'];
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$this->assertEquals($ret['atk_num'], WorldCompeteUtil::getCrazyCost());
		$worldCompeteCrossUserObj = WorldCompeteCrossUserObj::getInstance(self::$serverId, self::$pid, self::$uid, self::$teamId);
		$worldCompeteCrossRivalObj = WorldCompeteCrossUserObj::getInstance($atkServerId, $atkPid, 0, self::$teamId);
		$this->assertEquals($ret['max_honor'], WorldCompeteUtil::getAtkHonor($isSuc, $worldCompeteCrossUserObj->getFightForce(), $worldCompeteCrossRivalObj->getFightForce()));
		$this->assertEquals($ret['max_honor'], $worldCompeteCrossUserObj->getMaxHonor());
		foreach ($ret['rival'] as $rivalInfo)
		{
			if ($rivalInfo['server_id'] == $atkServerId && $rivalInfo['pid'] == $atkPid) 
			{
				$this->assertEquals($rivalInfo['status'], intval($isSuc));
			}
		}
		
		//攻打第2个对手,都胜利
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance(self::$serverId, self::$pid);
		$worldCompeteInnerUserObj->defeatRival($atkServerId, $atkPid, 1);
		$atkServerId = $ret['rival'][1]['server_id'];
		$atkPid = $ret['rival'][1]['pid'];
		$worldCompeteInnerUserObj->defeatRival($atkServerId, $atkPid, 1);
		
		//攻打第3个对手,胜利会返回刷新的对手
		$atkServerId = $ret['rival'][2]['server_id'];
		$atkPid = $ret['rival'][2]['pid'];
		$isSuc = 0;
		while ($isSuc == 0)
		{
			$worldCompeteInnerUserObj->setAtkNumForConsole(0);
			$ret = $worldCompeteObj->attack($atkServerId, $atkPid, $crazy, 1);
			$isSuc = BattleDef::$APPRAISAL[$ret['appraisal']] <= BattleDef::$APPRAISAL['D'];
		}
		$this->assertEquals(WorldCompeteConf::RIVAL_COUNT, count($ret['rival']));
	}
	
	public function test_buyAtkNum()
	{
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		
		// 开始时候的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$beforeBuyAtkNum = $ret['buy_atk_num'];
		
		// 购买1次
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->buyAtkNum(1);
		
		// 买完的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$afterBuyAtkNum = $ret['buy_atk_num'];
		
		// 验证一下
		$this->assertEquals($beforeBuyAtkNum + 1, $afterBuyAtkNum);
		
		// 买到最大次数
		$buyLimit = WorldCompeteUtil::getBuyLimit();
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->buyAtkNum($buyLimit - 1);
		$this->assertEquals('ok', $ret);
		
		// 买完的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$afterBuyAtkNum = $ret['buy_atk_num'];
		
		// 验证一下
		$this->assertEquals($beforeBuyAtkNum + $buyLimit, $afterBuyAtkNum);
		
		// 再买一次，超过最大次数，抛fake
		try
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->buyAtkNum(1);
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
			$needGold += WorldCompeteUtil::getBuyCost($i);
		}
		$this->assertEquals($beforeGold, $afterGold + $needGold);
	}
	
	public function test_getMyTeamInfo()
	{
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getMyTeamInfo();
	}
	
	public function test_getRankList()
	{
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getRankList();
	}
	
	public function test_refreshRival()
	{
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		
		// 开始时候的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$beforeRfrNum = $ret['refresh_num'];
		
		// 刷新1次
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->refreshRival();
		$this->assertEquals(WorldCompeteConf::RIVAL_COUNT, count($ret));
		
		// 刷完的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$afterRfrNum = $ret['refresh_num'];
		
		// 验证一下
		$this->assertEquals($beforeRfrNum + 1, $afterRfrNum);
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = WorldCompeteUtil::getRefreshCost();
		$this->assertEquals($beforeGold, $afterGold);
		
		// 刷新最大次数
		$refreshLimit = WorldCompeteUtil::getRefreshDefault();
		for ($i = 0; $i < $refreshLimit - 1; $i++)
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->refreshRival();
		}
		
		// 再刷新1次
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->refreshRival();
		
		// 刷完的信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$afterRfrNum = $ret['refresh_num'];
		
		// 验证一下
		$this->assertEquals($beforeRfrNum + $refreshLimit + 1, $afterRfrNum);
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$needGold = WorldCompeteUtil::getRefreshCost();
		$this->assertEquals($beforeGold, $afterGold + $needGold);
		
	}
	
	public function test_getPrize()
	{
		$allPrizeConf = btstore_get()->WORLD_COMPETE_PRIZE->toArray();
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$sucNum = $ret['suc_num'];
		
		if (!isset($allPrizeConf[$sucNum])) 
		{
			//领取不存在的奖励
			try
			{
				$worldCompeteObj = new WorldCompete();
				$ret = $worldCompeteObj->getPrize($sucNum);
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
		}
		
		$prize = array();
		foreach ($allPrizeConf as $key => $value)
		{
			if ($sucNum >= $key) 
			{
				$worldCompeteObj = new WorldCompete();
				$ret = $worldCompeteObj->getPrize($key);
				$this->assertEquals('ok', $ret);
				$prize[] = $key;
				$worldCompeteObj = new WorldCompete();
				$ret = $worldCompeteObj->getWorldCompeteInfo();
				$this->assertEquals($prize, $ret['prize']);
			}
		}
		
		//领取已领取的奖励
		try
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->getPrize($prize[0]);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_worship()
	{
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$worshipNum = $ret['worship_num'];
	
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->worship();
		$this->assertEquals('ok', $ret);
		
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$this->assertEquals($worshipNum + 1, $ret['worship_num']);
		
		//vip不够
		try
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->worship();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		list($needLevel, $needVip) = WorldCompeteUtil::getWorshipCon($worshipNum + 2);
		$user = EnUser::getUserObj(self::$uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$user->addExp($expTable[$needLevel]);
		$user->setVip($needVip);
		$user->update();
		
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->worship();
		$this->assertEquals('ok', $ret);
		
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getWorldCompeteInfo();
		$this->assertEquals($worshipNum + 2, $ret['worship_num']);
	
		//膜拜超过次数
		try
		{
			$worldCompeteObj = new WorldCompete();
			$ret = $worldCompeteObj->worship();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_getShopInfo()
	{
		// 获得商店信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getShopInfo();
		
		// 找个商品id买一下
		$aGoodsId = key(btstore_get()->WORLD_COMPETE_GOODS->toArray());
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->buyGoods($aGoodsId, 1);
		
		// 获得商店信息
		$worldCompeteObj = new WorldCompete();
		$ret = $worldCompeteObj->getShopInfo();
	}
	
	public function test_reward()
	{
		WorldCompeteScriptLogic::reward(array(), FALSE, FALSE);
	}
	
	public function test_api()
	{
		$beginTime = WorldCompeteUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getAllTeam();
		var_dump($ret);
		$beginTime = WorldCompeteUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getServersByTeamId(2);
		var_dump($ret);
		$beginTime = WorldCompeteUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getServersByServerId(10002);
		var_dump($ret);
		$beginTime = WorldCompeteUtil::activityBeginTime();
		$ret = TeamManager::getInstance(WolrdActivityName::WORLDCOMPETE, 0, $beginTime)->getTeamIdByServerId(10002);
		var_dump($ret);
	}
	
	public function test_team()
	{
		$ret = WorldCompeteUtil::getAllTeam();
		var_dump($ret);
		$ret = WorldCompeteUtil::getArrServerIdByTeamId(2);
		var_dump($ret);
		$ret = WorldCompeteUtil::getTeamIdByServerId(10002);
		var_dump($ret);
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
		WorldCompeteConf::$TEST_MODE = 1;
		$ret = WorldCompeteUtil::activityBeginTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldCompeteUtil::activityEndTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldCompeteConf::$TEST_MODE = 2;
		$ret = WorldCompeteUtil::activityBeginTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldCompeteUtil::activityEndTime();
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldCompeteConf::$TEST_MODE = 1;
		$ret = WorldCompeteUtil::activityBeginTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldCompeteUtil::activityEndTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		
		WorldCompeteConf::$TEST_MODE = 2;
		$ret = WorldCompeteUtil::activityBeginTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		$ret = WorldCompeteUtil::activityEndTime(Util::getTime() - 3600);
		var_dump(strftime('%Y%m%d %H%M%S', $ret));
		WorldCompeteConf::$TEST_MODE = 0;
	}
	
	public function test_serverOpenTime()
	{
		$ret = WorldCompeteUtil::serverOpenActivityTime();
		var_dump($ret);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
