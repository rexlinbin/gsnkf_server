<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: testMergeServer.php 136232 2014-10-15 05:39:25Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/test/testMergeServer.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-15 05:39:25 +0000 (Wed, 15 Oct 2014) $
 * @version $Revision: 136232 $
 * @brief 
 *  
 **/

class MergeServerTest extends PHPUnit_Framework_TestCase
{
	private $uid = 0;
	protected function setUp()
	{
		parent::setUp ();
		$this->uid = 21000;
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
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
	
	public function test_loginNotify()
	{
		EnMergeServer::loginNotify();
	}

	public function test_getCanGroup()
	{
		$userMergeServerObj = MergeServerObj::getInstance($this->uid);
		
		$loginCount = $userMergeServerObj->getLoginCount();
		$arrGot = $userMergeServerObj->getLoginGotGroup();
		$ret = $userMergeServerObj->getLoginCanGroup($loginCount, $arrGot);
		var_dump($ret);
		
		$rechargeNum = $userMergeServerObj->getRechargeNum();
		$arrGot = $userMergeServerObj->getRechargeGotGroup();
		$ret = $userMergeServerObj->getRechargeCanGroup($rechargeNum, $arrGot);
		var_dump($ret);
	}

	public function test_getRewardConfig()
	{
		$this->assertTrue( MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_LOGIN) instanceof BtstoreElement);
		$this->assertTrue( MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_RECHARGE) instanceof BtstoreElement);
		$this->assertTrue( MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_EXP_GOLD) instanceof BtstoreElement);
	}
	
	public function test_getRewardInfo()
	{
		$ret = MergeServerLogic::getRewardInfo($this->uid);
		printf("getRewardInfo result:\n");
		var_dump($ret);
	}

	public function test_receiveLoginReward()
	{
		try 
		{
			$ret = MergeServerLogic::receiveLoginReward($this->uid, 1);
			printf("receiveLoginReward uid:%d day:%d result:%s\n", $this->uid, 1, $ret);
		} 
		catch (FakeException $e) 
		{
			printf("%s\n", $e->getMsg());
		}
		

		try
		{
			$ret = MergeServerLogic::receiveLoginReward($this->uid, 2);
			printf("receiveLoginReward uid:%d day:%d result:%s\n", $this->uid, 2, $ret);
		} 
		catch (FakeException $e) 
		{
			printf("%s\n", $e->getMsg());
		}

		try
		{
			$ret = MergeServerLogic::receiveLoginReward($this->uid, 3);
			printf("receiveLoginReward uid:%d day:%d result:%s\n", $this->uid, 3, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}

		try
		{
			$ret = MergeServerLogic::receiveLoginReward($this->uid, 4);
			printf("receiveLoginReward uid:%d day:%d result:%s\n", $this->uid, 4, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}
	}

	public function test_receiveRechargeReward()
	{
		try
		{
			$ret = MergeServerLogic::receiveRechargeReward($this->uid, 1);	
			printf("receiveRechargeReward uid:%d num:%d result:%s\n", $this->uid, 1, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}

		try
		{
			$ret = MergeServerLogic::receiveRechargeReward($this->uid, 2);	
			printf("receiveRechargeReward uid:%d num:%d result:%s\n", $this->uid, 2, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}

		try
		{
			$ret = MergeServerLogic::receiveRechargeReward($this->uid, 3);	
			printf("receiveRechargeReward uid:%d num:%d result:%s\n", $this->uid, 3, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}

		try
		{
			$ret = MergeServerLogic::receiveRechargeReward($this->uid, 4);	
			printf("receiveRechargeReward uid:%d num:%d result:%s\n", $this->uid, 4, $ret);
		}
		catch (FakeException $e)
		{
			printf("%s\n", $e->getMsg());
		}
	}

	public function test_innerInterface()
	{
		$rate = EnMergeServer::getArenaPrestigeRewardRate();
		printf("type:%s, rate:%d\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_ARENA), $rate);
		//$this->assertEquals(2, $rate);
		
		$rate = EnMergeServer::getGoldTreeRewardRate();
		printf("type:%s gold tree, rate:%d\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_EXP_GOLD), $rate);
		//$this->assertEquals(2, $rate);

		$rate = EnMergeServer::getExpTreasureRewardRate();
		printf("type:%s exp treasure, rate:%d\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_EXP_GOLD), $rate);
		//$this->assertEquals(2, $rate);

		$effect = EnMergeServer::isMonthCardEffect();
		printf("type:%s, effect:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_MONTH_CARD), $effect ? "true" : "false");
		//$this->assertTrue($effect);
	}

	public function test_getActivityTime()
	{
		$start = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_LOGIN));
		$end = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_LOGIN));
		printf("type:%s, start:%s, end:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_LOGIN), $start, $end);
		//$this->assertEquals("20140923-000000", $start);
		//$this->assertEquals("20140930-000000", $end);

		$start = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_RECHARGE));
		$end = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_RECHARGE));
		printf("type:%s, start:%s, end:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_RECHARGE), $start, $end);
		//$this->assertEquals("20140923-000000", $start);
		//$this->assertEquals("20140926-000000", $end);

		$start = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_EXP_GOLD));
		$end = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_EXP_GOLD));
		printf("type:%s, start:%s, end:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_EXP_GOLD), $start, $end);
		//$this->assertEquals("20140923-000000", $start);
		//$this->assertEquals("20140926-000000", $end);

		$start = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_ARENA));
		$end = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_ARENA));
		printf("type:%s, start:%s, end:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_ARENA), $start, $end);
		//$this->assertEquals("20140923-001000", $start);
		//$this->assertEquals("20140926-001000", $end);

		$start = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_MONTH_CARD));
		$end = strftime("%Y%m%d-%H%M%S", MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_MONTH_CARD));
		printf("type:%s, start:%s, end:%s\n", MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_MONTH_CARD), $start, $end);
		//$this->assertEquals("20140923-000000", $start);
		//$this->assertEquals("20140930-000000", $end);
	}
	
	public function test_Console()
	{
		$console = new Console();
		$console->resetMergeServer("loginReward");
		$console->resetMergeServer("loginInfo");
		$console->resetMergeServer("rechargeReward");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
