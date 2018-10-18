<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Black.test.php 138597 2014-11-05 08:39:03Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/test/Black.test.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2014-11-05 08:39:03 +0000 (Wed, 05 Nov 2014) $
 * @version $Revision: 138597 $
 * @brief
 *
 **/

class BlackTest extends PHPUnit_Framework_TestCase
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
		
		$user = EnUser::getUserObj( $this->uid );
		$user->setVip( 10 );
		$console = new Console();
		$console->level( 65 );
	
		$user->update();
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
		RPCContext::getInstance()->unsetSession('divine.info');
		DivineObj::release();
	}

 	public function test_getBakers_0()
	{
		$friend = new Friend();
		$ret = $friend->getBlackers();
		$this->assertTrue( is_array( $ret ) );
		
		$someUids = UserDao::getArrUser(0, 2, array('uid'));
		$oneUid = $someUids[0]['uid'];
		
		$friend->blackYou( $oneUid );
		
		$ret = $friend->getBlackers();
		$this->assertTrue( $ret[0]['uid'] == $oneUid );
		$this->assertTrue( count( $ret ) == 1 );
		
		$friend->unBlackYou( $oneUid );
		$ret = $friend->getBlackers();
		
		$this->assertTrue(empty( $ret ));
		
	} 
	
	public function test_console_0()
	{
		$console = new Console();
		$console->setBlack( 130 );
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
