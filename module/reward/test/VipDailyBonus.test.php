<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipDailyBonus.test.php 97447 2014-04-03 08:17:47Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/VipDailyBonus.test.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-04-03 08:17:47 +0000 (Thu, 03 Apr 2014) $
 * @version $Revision: 97447 $
 * @brief 
 *  
 **/

class VipDailyBonusTest extends PHPUnit_Framework_TestCase
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
	
	public function test_sendVipBonus_0()
	{
		$user = EnUser::getUserObj();
		$user->setVip( 10 );
		$user->addExp( 10000 );
		$user->update();
		
		$vipbonus = new VipBonus();
		$ret = $vipbonus->getVipBonusInfo();
		$vipbonus->fetchVipBonus();
		var_dump( $ret );
	}
	
	public function test_sendVipBonus_1()
	{
		$user = EnUser::getUserObj();
		$user->setVip( 10 );
		$user->addExp( 10000 );
		$user->update();
	
		$vipbonus = new VipBonus();
		$vipbonus->fetchVipBonus();
			
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */