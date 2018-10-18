<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActiveTest.php 94186 2014-03-19 09:00:50Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/test/ActiveTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-03-19 09:00:50 +0000 (Wed, 19 Mar 2014) $
 * @version $Revision: 94186 $
 * @brief 
 *  
 **/
class ActiveTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 22828;

	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::ACTIVE);
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

	public function test_getActiveInfo()
	{
		Logger::debug('======%s======', __METHOD__);

		$active = new Active();
		$ret = $active->getActiveInfo();
		$this->assertEquals(0, $ret[ActiveDef::POINT]);
	}

	public function test_getPrize()
	{
		Logger::debug('======%s======', __METHOD__);

		$prizeId = 1;
		EnActive::addTask(1);
		EnActive::addTask(2,2);
		EnActive::addTask(3,2);
		$user = EnUser::getUserObj(self::$uid);
		$silverBefore = $user->getSilver();
		$goldBefore = $user->getGold();
		$jewelBefore = $user->getJewel();
		$active = new Active();
		$ret = $active->getPrize($prizeId);
		$this->assertEquals('ok', $ret);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */