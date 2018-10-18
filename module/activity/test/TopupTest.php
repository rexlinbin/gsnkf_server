<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TopupTest.php 87830 2014-01-20 07:19:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/TopupTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-20 07:19:52 +0000 (Mon, 20 Jan 2014) $
 * @version $Revision: 87830 $
 * @brief 
 *  
 **/
class TopupTest extends PHPUnit_Framework_TestCase
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


	public function test_topup_0()
	{
		
		//跑通
		$inst = new TopupFund();
		$ret = $inst->getTopupFundInfo();
		var_dump( $ret );
	}
	
	public function test_topup_reward()
	{
		$userObj = EnUser::getUserObj();
		$user = new User();
		$user->addGold4BBpay( $this->uid , 10001 , 1000 );
		
		$inst = new TopupFund();
		$ret = $inst->getTopupFundInfo();
		var_dump( $ret );
		
		$ret = $inst->gainReward( 1 );
		
		var_dump( $ret );
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */