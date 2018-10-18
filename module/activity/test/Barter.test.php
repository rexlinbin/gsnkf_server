<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Barter.test.php 56175 2013-07-22 10:13:52Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/Barter.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-07-22 10:13:52 +0000 (Mon, 22 Jul 2013) $
 * @version $Revision: 56175 $
 * @brief 
 *  
 **/
class BarterTest extends PHPUnit_Framework_TestCase
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
	public function test_barter_0()
	{
		$barter = new Barter();
		$ret = $barter->getBarterInfo();
		var_dump( $ret );
		
		$userObj = EnUser::getUserObj();
		$userObj->addSilver( 5000 );
		$userObj->addGold( 5000, StatisticsDef::ST_FUNCKEY_DIVI_REWARD );
		$userObj->addSoul( 5000 );
		
		//$userObj->getHeroManager()->addNewHero(  );
		$userObj->setVip( 2 );
		
		$bag = BagManager::getInstance()->getBag();
		$bag->addItemByTemplateID( 10001 , 1 );
		
		while ( $userObj->getLevel() < 10 )
		{
			$userObj->addExp( 1000 );
		}
		
		$silverBefore = $userObj->getSilver();
		$soulBefore = $userObj->getSoul();
		$goldBefore = $userObj->getGold();
		
		$ret = $barter->barterExchange( 1 );
		
		var_dump( $ret );
		$heros = $userObj->getAllUnusedHero();
		$this->assertTrue( count( $heros ) == 1 );
		
		$silverAfter = $userObj->getSilver();
		$soulAfter = $userObj->getSoul();
		$goldAfter = $userObj->getGold();
		
		$this->assertTrue( $silverAfter < $silverBefore );
		$this->assertTrue( $soulAfter < $soulBefore );
		$this->assertTrue( $goldAfter < $goldBefore );
		
		//TODO 使用武将兑换没有测
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */