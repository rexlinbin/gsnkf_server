<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Levelfund.test.php 62949 2013-09-04 09:32:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/Levelfund.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-04 09:32:52 +0000 (Wed, 04 Sep 2013) $
 * @version $Revision: 62949 $
 * @brief 
 *  
 **/

class LevelfundTest extends PHPUnit_Framework_TestCase
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
	public function test_levelFund_0()
	{
		$inst = new Levelfund();
		$ret = $inst->getLevelfundInfo();
		var_dump( $ret );

		try 
		{
			$inst->getLevel;
		}
		catch ( Exception $e )
		{
			echo "1.=============================normal,lack level \n";
		}
		//用户升到需要的等级
		$userObj = EnUser::getUserObj();
		while ( $userObj->getLevel() < 10 )
		{
			$userObj->addExp( 1000 );
		}
		
		$silverBefore = $userObj->getSilver();
		$soulBefore = $userObj->getSoul();
		$goldBefore = $userObj->getGold();
		Logger::debug('$silverBefore is: %d',$silverBefore );
		
		$inst->gainLevelfundPrize( 1 );
		
		$silverAfter = $userObj->getSilver();
		$soulAfter = $userObj->getSoul();
		$goldAfter = $userObj->getGold();

 		$this->assertTrue( $silverAfter > $silverBefore );
 		$this->assertTrue( $goldAfter > $goldBefore );
		
		var_dump( $inst->getLevelfundInfo() );
		
		while ( $userObj->getLevel() < 30 )
		{
			$userObj->addExp( 1000 );
		}
		$inst->gainLevelfundPrize( 2 );
		
		var_dump( $inst->getLevelfundInfo() );
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */