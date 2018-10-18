<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SupplyTest.php 69153 2013-10-16 07:24:37Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/SupplyTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-10-16 07:24:37 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69153 $
 * @brief 
 *  
 **/
class SupplyTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;

	/* (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	*/

	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
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
	}
	
	public function test_getSupplyInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		$supply = new Supply();
		$ret = $supply->getSupplyInfo();
		Logger::trace('user:%d supply info:%s', self::$uid, $ret);
		$this->assertEquals(0, $ret);
	}
	
	public function test_supplyExecution()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$user = EnUser::getUserObj(self::$uid);
		$executionBefore = $user->getCurExecution();
		
		$supply = new Supply();
		$ret = $supply->supplyExecution();
		$this->assertEquals(ActivityConf::SUPPLY_NUM, $ret);
		
		$executionAfter = $user->getCurExecution();
		$this->assertEquals($executionBefore + ActivityConf::SUPPLY_NUM, $executionAfter);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */