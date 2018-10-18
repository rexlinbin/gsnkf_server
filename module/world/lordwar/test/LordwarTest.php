<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarTest.php 154125 2015-01-21 07:41:45Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/test/LordwarTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-01-21 07:41:45 +0000 (Wed, 21 Jan 2015) $
 * @version $Revision: 154125 $
 * @brief 
 *  
 **/
class LordwarTest extends PHPUnit_Framework_TestCase
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
		$console->level( 65 );
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

/*  	public function test_support_0()
	{
		$lordwar = LordwarLogic::support(0, LordwarTeamType::WIN);
		Logger::debug('my uid : %d', $this->uid);
		
		$mysupport = LordwarLogic::getMySupport($this->uid);
		var_dump( $mysupport );
	}  
	
	public function test_worship_0()
	{
		//$templeInfo = LordwarLogic::getTempleInfo();
		
		//var_dump( $templeInfo );
	}
	
	public function test_push_0()
	{
		//LordwarLogic::push(lordwar, $type);
	} 
	
	public function test_support_0()
	{
/* 		LordwarLogic::support(7, LordwarTeamType::WIN);
		$lordInfo = LordwarLogic::getLordInfo($this->uid);
		var_dump( $lordInfo ); 
		
	}
	
	public function test_getLordInfo_0()
	{/* 
		$lordInfo = LordwarLogic::getLordInfo($this->uid);
		var_dump( $lordInfo );
	 }
	
	public function test_worship()
	{/* 
		LordwarLogic::worship(0, 1);
		$ret = LordwarLogic::getLordInfo($this->uid);
		var_dump( $ret ); 
	 }
	
	public function test_upFight_0()
	{
		$lordInfo = LordwarLogic::getLordInfo($this->uid);
		var_dump( $lordInfo );
		
		LordwarLogic::updateFightInfo($this->uid);
		
		$lordInfo = LordwarLogic::getLordInfo($this->uid);
		var_dump( $lordInfo );
		
		LordwarLogic::clearFmtCd($this->uid);
		$lordInfo = LordwarLogic::getLordInfo($this->uid);
		
		var_dump( $lordInfo );
		
	 }
		*/
	public function test_part_0()
	{
		$roundStartTime = Util::getTime();//LordwarConfMgr::getInstance()->getRoundStartTime( LordwarRound::INNER_32TO16 );
		if (defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$lastMergeTime = strtotime( GameConf::MERGE_SERVER_OPEN_DATE );
			if( $lastMergeTime >= $roundStartTime )
			{
				return 'merged';
			}
		}
	} 
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */