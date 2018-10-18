<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Spend.test.php 116521 2014-06-23 06:57:42Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/Spend.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-23 06:57:42 +0000 (Mon, 23 Jun 2014) $
 * @version $Revision: 116521 $
 * @brief 
 *  
 **/
class SpendTest extends PHPUnit_Framework_TestCase
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
	

	public function test_spend_0()
	{
		$userObj = EnUser::getUserObj();
		$userObj->subGold( 100 , StatisticsDef::ST_FUNCKEY_DIVI_REFRESH );
		//跑通
		//获取用户的信息
		$inst = new Spend();
		$ret = $inst->getInfo();
		var_dump( $ret );
		
		 //给用户足够的钱
		$userObj->addGold( 10000 , StatisticsDef::ST_FUNCKEY_DIVI_REWARD );
		//用户花钱
		$userObj->subGold( 2000 , StatisticsDef::ST_FUNCKEY_DIVI_REFRESH );
		
		//领奖前的信息
		$silverBefore = $userObj->getSilver();
		$soulBefore = $userObj->getSoul();
		$goldBefore = $userObj->getGold();
		
		//领奖
		
		$ret = $inst->gainReward( 1 );
		$ret = $inst->gainReward( 2 );
		var_dump( '=====================frag' );
		var_dump( $ret );
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		var_dump( $ret );
		
		$silverAfter = $userObj->getSilver();
		$soulAfter = $userObj->getSoul();
		$goldAfter = $userObj->getGold();
		$heros = $userObj->getAllUnusedHero();
		//输出英雄看一眼有没有发
		var_dump( '=================' );
		var_dump( $heros );
		
		$this->assertTrue( $silverAfter >= $silverBefore );
		$this->assertTrue( $soulAfter >= $soulBefore );
		$this->assertTrue( count( $heros ) >=1 );
		
// 		$ret = EnActivity::getConfByName( ActivityName::SPEND );
// 		var_dump( $ret );
	
	}

	public function test_getActivityConf_0()
	{
		$activity = new Activity();
		$ret = $activity->getActivityConf();
		
		var_dump( $ret );
	}
	
// 	public function test_spendConsole()
// 	{
// 		$console = new Console();
		
// 		$user = EnUser::getUserObj( $this->uid );
// 		$user->addGold( 10000 , StatisticsDef::ST_FUNCKEY_ARENA_LUCKY);
// 		$user->update();
// 		$user->subGold( 44 , StatisticsDef::ST_FUNCKEY_ARENA_CLEAR_CDTIME);
// 		$user->update();
		
// 		$user->subGold( 22 , StatisticsDef::ST_FUNCKEY_ARENA_CLEAR_CDTIME);
// 		$user->update();
		
// 		$ret = $console->getSpend( $this->uid );
// 		var_dump( $ret );
		
// 		$i = 20131201;
// 		for ( $a = 0; $a<15; $a++ )
// 		{
// 			$console->setSpend( $this->uid , $i+$a, 11*($a+1));
// 		}
// 		$ret = $console->getSpend( $this->uid );
// 		var_dump( $ret );
		
// 		$ret = $console->setSpend( $this->uid , 20140102, 4444);
// 		//$ret = $console->getSpend( $this->uid );
// 		var_dump( $ret );
		
// 		$console->setSpend( $this->uid , 20110102, 4444);
// 		$ret = $console->getSpend( $this->uid );
// 		var_dump( $ret );
		
// 	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */