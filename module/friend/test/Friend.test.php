<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Friend.test.php 83518 2013-12-28 05:45:13Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/test/Friend.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-28 05:45:13 +0000 (Sat, 28 Dec 2013) $
 * @version $Revision: 83518 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/def/Friend.def.php');
require_once ('/home/pirate/rpcfw/conf/Friend.cfg.php');

class FriendTest extends PHPUnit_Framework_TestCase
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

//  	public function test_ARADFriend_0()
// 	{
// 		//跑通 申请、添加、拒绝、删除、获取、判定 好友
// 		$friend = new Friend();
// 		$data = new CData();
// 		$ret = $data->select( array( 'uid' ) )
// 		->from( 't_user' )
// 		->where( array( 'uid' , '>' ,0 ) )
// 		->limit( 0 , 2 )->query();
// 		$uid = $ret[ 0 ][ 'uid' ];
		
// 		$retA = $friend->applyFriend( $uid , 'add a friend~~');
// 		var_dump( $retA );
		
// 		$retB = $friend->rejectFriend( $uid );
// 		var_dump( $retB );
		
// 		$isFriend = $friend->isFriend( $uid );
// 		$this->assertTrue( !$isFriend , 'should not be friend but is' );
		
// 		$retC = $friend->addFriend( $uid );
// 		var_dump( $retC );
		
// 		$isFriend = $friend->isFriend( $uid );
// 		$this->assertTrue( $isFriend , 'should be friend but not' );
		
// 		$retD = $friend->delFriend( $uid );
// 		var_dump( $retD );
		
// 		$isFriend = $friend->isFriend( $uid );
// 		$this->assertTrue( !$isFriend , 'should not be friend but is' );
				
// 		try {
// 		$friend->getFriendInfo( $uid );
// 		}
// 		catch ( Exception $e )
// 		{
// 			echo '=======================================1'."\n";
// 		}
		
// 		$friend->addFriend( $uid );
// 		$friendInfo = $friend->getFriendInfo( $uid );
// 		//var_dump( $friendInfo ); 
// 	} 
	
// 	public function test_getFriendList_0()
// 	{
// 		//测试拉取自己的所有好友
// 		//先给他加上许多好友
// 		$data = new CData();
// 		$arrRet = $data->select( array( 'uid' ) )
// 		->from( 't_user' )->where( array( 'uid' , '>' , 0 ) )->limit(0, 20)->query();
		
// 		$friend = new Friend();
// 		$count = 0;
// 		while ( $count < count( $arrRet ) )
// 		{
// 			$friend->addFriend( $arrRet[ $count ][ 'uid' ] );
// 			$count++;
// 		}
// 		//获取
// 		$ret = $friend->getFriendInfoList();
// 		echo 'B========================================';
// 		//var_dump( $ret );
// 		$this->assertTrue( count( $ret ) == count( $arrRet ) );
// 		//重复加一个试试
// 		try 
// 		{
// 		$friend->addFriend( $arrRet[ 1 ][ 'uid' ] );
// 		}catch ( Exception $e )
// 		{
// 			echo '=======================================2'."\n";
// 		}
		
// 		//加超( 不报fake 返回给前端 )
// 		$arrRet = $data->select( array( 'uid' ) )
// 		->from( 't_user' )->where( array( 'uid' , '>' , 0 ) )->limit(20 , 90)->query();
// 		$count = 0;
// 		$return = 'ok';
// 		while ( $count < count( $arrRet ) )
// 		{
// 			$return = $friend->addFriend( $arrRet[ $count ][ 'uid' ] );
// 			$count++;
// 		}
// 		$friendNum = FriendDao::getFriendCount( $this->uid );
		
// 		$this->assertTrue( $return != 'ok' );
// 		$this->assertTrue( $friendNum == FriendCfg::MAX_FRIEND_NUM  );
		
// 	} 
	
// 	public function test_getRecmodFriends_0()
// 	{
// 		RPCContext::getInstance()->setSession('global.uid', $this->uid);
// 		//测试推荐好友
// 		//跑通
// 		$friend = new Friend();
// 		$arrRet = $friend->getRecomdFriends();
// 		var_dump( $arrRet );
// 	} 
	
// 	public function test_enFriend_0()
// 	{
// 		//跑通
// 		EnFriend::loginNotify( $this->uid );
// 		EnFriend::logoffNotify( $this->uid );
// 	}
	
// 	public function test_console_0()
// 	{
// 		$console = new Console();
// 		$console->addFriend(0, 50);
// 		$friend = new Friend();
// 		$ret = $friend->getFriendInfoList();
// 		//var_dump( $ret );
// 	}
	
// 	public function test_getbyname_0()
// 	{
// 		$friend = new Friend();
// 		$ret = $friend->getRecomdByName( 't' );
		
// 	}
// 	public function test_bug_0()
// 	{
// 		RPCContext::getInstance()->setSession('global.uid', $this->uid);
// 		$friend = new Friend();
// 		$ret = $friend->getFriendInfoList();
// 		//var_dump( $ret );
// 	}
	
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
		
		//未领取的耐力列表
		$unreceiveList = $friend->unreceiveLoveList();
		var_dump( " unreceiveList:  ");
		var_dump( $unreceiveList );
		//赠送之后的好友的信息
		$friend->loveFriend( $uid );
		echo "uid is $uid";
		$friendListAfter = $friend->getFriendInfoList();
		var_dump( " friendListAfter:  ");
		var_dump( $friendListAfter );
		//被赠送的体力列表
		$time = Util::getTime();
		$friend->lovedByOther($uid, $this->uid);
		$friend->lovedByOther($uid2, $this->uid);
		$friend->lovedByOther($uid3, $this->uid);
		
		$unreceiveLoveList3 = $friend->unreceiveLoveList();
		var_dump( 'unreceiveLoveList3: ' );
		var_dump( $unreceiveLoveList3 );
		
		//被赠送之后领了一次的列表
		$user = EnUser::getUserObj( $this->uid );
		$user->subExecution( 100 );
		$user->update();
		
		$friend->receiveLove($time, $uid);
		$unreceiveLoveList4 = $friend->unreceiveLoveList();
		var_dump( 'unreceiveLoveList4: ' );
		var_dump( $unreceiveLoveList4 );
		
		//测试好友删除了之后是否还可以领原来的
		$friend->delFriend( $uid2 );
		$unreceiveLoveList7 = $friend->unreceiveLoveList();
		var_dump( 'unreceiveLoveList7: ' );
		var_dump( $unreceiveLoveList7 );
		
		//所有都领了的列表
		$friend->receiveAllLove();
		$unreceiveLoveList5 = $friend->unreceiveLoveList();
		var_dump( 'unreceiveLoveList5: ' );
		var_dump( $unreceiveLoveList5 );
		
		//console
		$console = new Console();
		$console->resetReceiveNum();
		$unreceiveLoveList6 = $friend->unreceiveLoveList();
		var_dump( 'unreceiveLoveList6: ' );
		var_dump( $unreceiveLoveList6 );
		
		$console->setMeToFriend();
		$friendListAfterConsole = $friend->getFriendInfoList();
		var_dump( " friendListAfterConsole:  ");
		var_dump( $friendListAfterConsole );
		
		$console->setFriendToMe();
		
// 		RPCContext::getInstance()->setSession( 'global.uid' , $uid );
// 		$friend2 = new Friend();
// 		$friend2->loveFriend( $this->uid );
		
// 		RPCContext::getInstance()->setSession( 'global.uid' , $this->uid );
// 		$friend3 = new Friend();
// 		$unreceiveLoveList3 = $friend3->unreceiveLoveList();
// 		var_dump( 'unreceiveLoveList3: ' );
// 		var_dump( $unreceiveLoveList3 );
		
	}
	
	public function test_console_0()
	{
		$console = new Console();
		$console->addFriend( 0 , 99 );
		$console->lovedMe();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */