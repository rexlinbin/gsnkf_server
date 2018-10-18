<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackTest.php 258601 2016-08-26 08:51:33Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/test/WelcomebackTest.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-26 08:51:33 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258601 $
 * @brief 
 *  
 **/
class WelcomebackTest extends PHPUnit_Framework_TestCase
{
	private static $uid;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	
	public static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		echo "test user: " . self::$uid . "\n";
	}
	protected function setUp()
	{
	}
	
	protected function tearDown()
	{
	}
	public function test_welcomeback()
	{
		$user = EnUser::getUserObj();
		$console = new Console();
		$welcomeback = new Welcomeback();
		$ctime = date('Y-m-d h:m:s');
		echo("current time is ".$ctime."\n");
		$console->setLastOffTime(2000);
		echo("offline time is ".date('Y-m-d h:m:s', $user->getLastLogoffTime())."\n");
		echo("level is ".$user->getLevel()."\n");
		echo("welcomeback activity is open?  ");
		echo($welcomeback->getOpen()."\n");
		$console->level(60);
		echo("level is ".$user->getLevel()."now \n");
		echo("welcomeback activity is open?  ");
		echo($welcomeback->getOpen()."\n");
		echo("------------------------------\n");
		
		$console->setLastOffTime(147600);//41个小时前
		echo("offline time is ".date('Y-m-d h:m:s', $user->getLastLogoffTime())."\n");
		echo("level is ".$user->getLevel()."\n");
		echo("welcomeback activity is open? answer is ");
		echo($welcomeback->getOpen()."\n");
		echo("activity finish info: \n");
		var_dump($welcomeback->getInfo());
		echo("now finish some task...\n");
		echo("------------------------------\n");
		echo("do ncopy 5 times\n");
		EnWelcomeback::updateTask(102, 5);
		echo("can gain reward? answer is ");
		echo($welcomeback->gainReward(102001)."\n");
		echo("activity finish info: \n");
		var_dump($welcomeback->getInfo());
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */