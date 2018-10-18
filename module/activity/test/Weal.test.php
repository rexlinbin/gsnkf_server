<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Weal.test.php 94709 2014-03-21 06:52:29Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/Weal.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-21 06:52:29 +0000 (Fri, 21 Mar 2014) $
 * @version $Revision: 94709 $
 * @brief 
 *  
 **/

class WealTest extends PHPUnit_Framework_TestCase
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
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
		EnUser::release();
		RPCContext::getInstance()->unsetSession( 'ka.info' );
		RPCContext::getInstance()->unsetSession('global.uid');
		KaObj::release();
		
	}
	
/* 	public function test_getWealInfo_0()
	{
		Logger::debug('now test_getWealInfo_0 begin=============== ');
		$inst = new Weal();
		$ret = $inst->refreshWealSession();
		
		var_dump( $ret );
		$ret1 = EnWeal::getWeal( WealDef::ACOPY_NUM );
		$ret2 = EnWeal::getWeal( WealDef::NCOPY_FUND );
		Logger::debug('now test_getWealInfo_0 end=============== ');
	}
	
	public function test_getKaInfo_0()
	{
		Logger::debug('now test_getKaInfo_0 begin=============== ');
		$inst = new Weal();
		$ret = $inst->getKaInfo();
		var_dump( $ret );
		
		Logger::debug('now test_getKaInfo_0 end=============== ');
	}
	
	public function test_addKaPoint_0()
	{
		Logger::debug('now test_addKaPoint_0 begin=============== ');
		$inst = new Weal();
		EnWeal::addKaPoints( 1 );
		EnWeal::addKaPoints( 2 );
		EnWeal::addKaPoints( 3 );
		
		$ret = $inst->getKaInfo();
		var_dump( $ret );
		
		Logger::debug('now test_addKaPoint_0 end=============== ');
	}  */
	
	public function test_kaOnce_0()
	{
		Logger::debug('now test_kaOnce_0 begin=============== ');
		$inst = new Weal();
		for ($i = 0;$i<40;$i++)
		{
			EnWeal::addKaPoints( 1 );
		}
	
		$ret = $inst->getKaInfo();
		var_dump( $ret );
		
		$reward = $inst->kaOnce();
		$ret = $inst->getKaInfo();
		var_dump( $ret );
		var_dump( $reward );
		Logger::debug('now test_kaOnce_0 end=============== ');
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */