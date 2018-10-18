<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pass.test.php 156412 2015-02-02 10:10:56Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/pass/test/Pass.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-02-02 18:10:56 +0800 (星期一, 02 二月 2015) $
 * @version $Revision: 156412 $
 * @brief 
 *  
 **/
class SweepTest extends PHPUnit_Framework_TestCase
{
	private $uid;
	
	protected function setUp()
	{
		parent::setUp ();
		$pid = 40000 + rand(0,9999);
		$utid = 1;
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, $utid, $uname);
		$users = UserLogic::getUsers( $pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		
		$user = EnUser::getUserObj( $this->uid );
		$user->setVip( 10 );
		$console = new Console();
		$console->level( 85 );
		$user->update();
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
		RPCContext::getInstance()->unsetSession('pass.info');
		PassObj::releaseInstance($this->uid);
	}
	
/* 	public function test_enter_0()
	{
		$enterInfo = PassLogic::enter($this->uid);
		var_dump( $enterInfo );
	} */
	
	public function test_Sweep()
	{
		$PassObj = PassObj::getInstance($this->uid);
		
		$SweepInfo = $PassObj->getSweepInfo();
		$SweepInfo['count'] = 10;
		$SweepInfo['isSweeped'] =false;
		
		
		$PassObj->setVaParticular('sweepInfo', $SweepInfo);
		
		$ret = PassLogic::sweep($this->uid, 10, 1);
		
		var_dump($ret);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */