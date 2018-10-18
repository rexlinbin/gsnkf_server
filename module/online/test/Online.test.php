<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Online.test.php 61304 2013-08-26 07:14:48Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/test/Online.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-26 07:14:48 +0000 (Mon, 26 Aug 2013) $
 * @version $Revision: 61304 $
 * @brief 
 *  
 **/

require_once ('/home/pirate/rpcfw/def/Online.def.php');
require_once ('/home/pirate/rpcfw/conf/Online.cfg.php');

class OnlineTest extends PHPUnit_Framework_TestCase
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

 	public function test_getOnlineInfo_0()
	{
		$online = new Online();
		$onlineInfo = $online->getOnlineInfo();
		var_dump( $onlineInfo );
	} 

	public function test_getOnlineReward_0()
	{
		$online = new Online();
		OnlineLogic::login();
		$onlineInfo = OnlineLogic::getOnlineInfo();
		$onlineInfo[ 'accumulate_time' ] += 60;
		OnlineDao::update($this->uid, $onlineInfo);
		RPCContext::getInstance()->setSession(OnlineDef::SESSIONKEY, $onlineInfo);
		
		$onlineGiftArr = $online->gainGift( 1 );
		var_dump( $onlineGiftArr );
		//$itemid = BagManager::getInstance()->getBag()->getItemIdsByItemType( ItemDef::ITEM_TYPE_FEED );
		//$this->assertTrue( !empty( $itemid ) );
		
		try {$onlineGiftArr = $online->gainGift( 1 );}
		catch ( Exception $e)
		{
			echo '=========================1';
		}
		try {$onlineGiftArr = $online->gainGift( 2 );}
		catch ( Exception $e)
		{
			echo '=========================2';
		}

		$onlineInfo = OnlineLogic::getOnlineInfo();
		$onlineInfo[ 'accumulate_time' ] += 180;
		OnlineDao::update($this->uid, $onlineInfo);
		RPCContext::getInstance()->setSession(OnlineDef::SESSIONKEY, $onlineInfo);
		
		$soulBefore = EnUser::getUserObj()->getSoul();
		$online->gainGift( 2 );
		$soulAfter = EnUser::getUserObj()->getSoul();
		$this->assertTrue( $soulAfter > $soulBefore );
		
		OnlineLogic::logoff();
		
		$console = new Console();
		$console->resetOnlineInfo();
		
		$time = Util::getTime();
		$ret = OnlineLogic::getOnlineInfo();
		$this->assertTrue( $ret[ 'begin_time' ] == $time );
		var_dump( $this->uid );
		
		$console->modiOnlineTime( 2 , 900 );
		
	}

}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */