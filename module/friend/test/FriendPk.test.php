<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FriendPk.test.php 109030 2014-05-17 08:43:22Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/test/FriendPk.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-05-17 08:43:22 +0000 (Sat, 17 May 2014) $
 * @version $Revision: 109030 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/def/Friend.def.php');
require_once ('/home/pirate/rpcfw/conf/Friend.cfg.php');

class FriendPkTest extends PHPUnit_Framework_TestCase
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
		parent::tearDown();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	public function test_loveOther_0()
	{
		$data = new CData();
		$ret = $data->select( array( 'uid' ) )
		->from( 't_user' )
		->where( array( 'uid' , '>' ,0 ) )
		->limit( 0 , 3 )->query();
		$uid = $ret[ 0 ][ 'uid' ];
		$uid2= $ret[1]['uid'];
		$uid3 = $ret[2]['uid'];
		
		$friend = new Friend();
		$friend->addFriend( $uid );
		$friend->addFriend( $uid2 );
		$friend->addFriend( $uid3 );
		
		//获取好友列表
		$friendList = $friend->getFriendInfoList();
		var_dump( " friendList:  ");
		var_dump( $friendList );
		
		$ret = $friend->getPkInfo($uid2);
		var_dump( "getPkInfo:  ");
		var_dump( $ret );
		
		$ret = $friend->pkOnce($uid2);
		var_dump( "pkOnce:  ");
		var_dump( $ret );
		
		$console = new Console();
		$console->resetFriendPk();
		
		$ret = $friend->getPkInfo($uid2);
		var_dump( "getPkInfo:  ");
		var_dump( $ret );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */