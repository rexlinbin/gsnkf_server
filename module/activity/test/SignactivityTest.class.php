<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SignactivityTest.class.php 88100 2014-01-21 06:55:45Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/SignactivityTest.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-21 06:55:45 +0000 (Tue, 21 Jan 2014) $
 * @version $Revision: 88100 $
 * @brief 
 *  
 **/
class SignactivityTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 40000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		EnUser::release( $this->uid );
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	public function test_getSignInfo_0()
	{
		$inst = new Signactivity();
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
	}
	
	public function test_reward_0()
	{
		$inst = new Signactivity();
		$inst->getSignactivityInfo();
		$ret = $inst->gainSignactivityReward(1);
		
	}
	
	public function test_console_0()
	{
		$console = new Console();

		$inst = new Signactivity();
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
		$inst->gainSignactivityReward(1);
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
		
		$console -> signActi( 20140121010101 , 3);
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
		$inst->gainSignactivityReward(1);
		$inst->gainSignactivityReward(2);
		$inst->gainSignactivityReward(3);
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
		
		$console -> signActi( 20140121010101 , 3, 0);
		$ret = $inst->getSignactivityInfo();
		var_dump( $ret );
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */