<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GrowUp.test.php 67705 2013-10-09 03:23:44Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/GrowUp.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-10-09 03:23:44 +0000 (Wed, 09 Oct 2013) $
 * @version $Revision: 67705 $
 * @brief 
 *  
 **/
class GrowupTest extends PHPUnit_Framework_TestCase
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

	/**
	 * @group signUp
	 */
	public function test_growUp_0()
	{
		$inst = new GrowUp();
		//用户没有购买
		$ret = $inst->getInfo();
		$this->assertTrue( $ret == 'unactived' );
		$userObj = EnUser::getUserObj( $this->uid );
		$userObj->setVip( 7 );
		$userObj->addGold( 10000 , StatisticsDef::ST_FUNCKEY_DIVI_REWARD);
		//用户购买之后的信息
		$ret = $inst->activation();
		$ret = $inst->getInfo();
		var_dump($ret);
		//用户再购买
		try
		{
			$ret = $inst->activation();
		}
		catch ( Exception $e )
		{
			echo '1==================================normal,already bought';
		}
		//用户领取奖励
		try 
		{
			$ret = $inst->fetchPrize(0);
		}
		catch ( Exception $e )
		{
			echo '2==================================normal,lack level';
		}
		$userObj = EnUser::getUserObj();
		while ( $userObj->getLevel() < 10 )
		{
			$userObj->addExp( 1000 );
		}
		$goldBefore = $userObj->getGold();
		$inst->fetchPrize( 0 );
		$goldAfter = $userObj->getGold();
		$this->assertTrue( $goldAfter > $goldBefore);
	}
	
	public function test_console_0()
	{
		$inst = new GrowUp();
		//用户没有购买
		$ret = $inst->getInfo();
		$userObj = EnUser::getUserObj( $this->uid );
		$userObj->setVip( 10 );
		$userObj->addGold( 10000 , StatisticsDef::ST_FUNCKEY_DIVI_REWARD);
		$inst->activation();
		$console = new Console();
		$console->resetGrowup();
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */