<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Cardbigrun.test.php 62302 2013-08-31 05:40:59Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/Cardbigrun.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-31 05:40:59 +0000 (Sat, 31 Aug 2013) $
 * @version $Revision: 62302 $
 * @brief 
 *  
 **/
class CardbigrunTest extends PHPUnit_Framework_TestCase
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
	
	public function test_pickCard_0()
	{
		Logger::debug('test pick card begin');
		$ret = EnActivity::getConfByName( 'cardBigRun' );
		var_dump( $ret );
	}
	
	public function test_pickCard_1()
	{
		EnUser::getUserObj()->addGold( 2000 , StatisticsDef::ST_FUNCKEY_ARENA_LUCKY );
		$cardBigRun = new Cardbigrun();
		$cardBigRun->pickCard( 1 );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */