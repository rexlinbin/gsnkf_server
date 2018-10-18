<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class BlackshopTest extends PHPUnit_Framework_TestCase
{
	private static $uid;
	
	 public static function setUpBeforeClass()
	{
		parent::setUp ();
		$pid = 40000 + rand(0,9999);
		$utid = 1;
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, $utid, $uname);
		$users = UserLogic::getUsers( $pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	protected function setUp()//运行一次函数就会运行一次setUp,所以可能导致重复建立账号
	{
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();		
	}
	public function test_getBlackshopInfo()
	{
		$obj = new BlackshopManage();
		$ret1 = $obj->getValidId();
		var_dump($ret1);
		$ret2 = BlackshopLogic::getBlackshopInfo();
		var_dump($ret2);	
	}
	
	
	public function test_exchangeBlackshop($id = 1,$num = 1)
	{
		$ret = BlackshopLogic::exchangeBlackshop(self::$uid, $id, $num);
		var_dump($ret);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */