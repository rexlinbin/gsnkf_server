<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ChatTest.php 113824 2014-06-12 09:25:19Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/test/ChatTest.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2014-06-12 09:25:19 +0000 (Thu, 12 Jun 2014) $
 * @version $Revision: 113824 $
 * @brief
 *
 **/

class ChatTest extends PHPUnit_Framework_TestCase
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
		$chat = new Chat();
		$ret = $chat->getBlackers();
		$this->assertTrue( is_array( $ret ) );
		
		$someUids = UserDao::getArrUser(0, 2, array('uid'));
		$oneUid = $someUids[0]['uid'];
		
		$chat->blackYou( $oneUid );
		
		$ret = $chat->getBlackers();
		//$this->assertTrue( $ret[0]['uid'] == $oneUid );
		//$this->assertTrue( count( $ret ) == 1 );
		
		var_dump( 'after blackone'."\n" );
		var_dump( $ret );
		
		$chat->unBlackYou( $oneUid );
		$ret = $chat->getBlackers();
		
		$this->assertTrue(empty( $ret ));
		
	} 
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
