<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWar.test.php 210074 2015-11-17 05:09:16Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/test/CountryWar.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-17 05:09:16 +0000 (Tue, 17 Nov 2015) $
 * @version $Revision: 210074 $
 * @brief 
 *  
 **/
class CountrywarTest extends PHPUnit_Framework_TestCase
{

	private $uid ;
	private $pid ;
	private $uname ;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 40000 + rand(0,9999);
		$utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);

		$user = EnUser::getUserObj( $this->uid );
		$console = new Console();
		$console->level( 90 );
		$console->silver( 9999999 );

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
	}
	
/* 	public function test_getCountryWarInfo()
	{
		$cw = new CountryWarInner();
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	} */
/* 	
 	public function test_team()
	{
		CountryWarScrLogic::syncAllTeamFromPlat2Cross();
	}  */
	 
/*       public function test_sign()
	{
		$cw = new CountryWarInner();
		$signInfo = $cw->signForOneCountry( 1 );
		var_dump($signInfo);
		
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	}    */
 	
/*  	public function test_range()
	{
		CountryWarLogic::scrRangeRoom(false);
		$cw = new CountryWarInner();
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	}  */
		 
/*  	public function test_getLoginInfo()
	{
		$cw = new CountryWarInner();
		$signInfo = $cw->signForOneCountry( 1 );
		var_dump($signInfo);
	
		$info = $cw->getLoginInfo();
		var_dump($info );
	}  */
	/*
	public function test_getLoginInfo()
	{
		$cw = new CountryWarInner();
		$info = $cw->getLoginInfo();
		var_dump($info );
	} */

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */