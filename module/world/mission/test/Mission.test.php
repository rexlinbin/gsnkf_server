<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mission.test.php 199498 2015-09-18 02:21:02Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/test/Mission.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-18 02:21:02 +0000 (Fri, 18 Sep 2015) $
 * @version $Revision: 199498 $
 * @brief 
 *  
 **/
class MissionTest extends PHPUnit_Framework_TestCase
{
	private $uid ;
	private $pid ;
	private $uname ;
	
	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 50000 + rand(0,9999);//42811;//40560;//-40560;
		$utid = 1;
		$this->uname = 't' . $this->pid;
		$users = UserLogic::getUsers( $this->pid );
		if( empty( $users ) )
		{
			$ret = UserLogic::createUser($this->pid, $utid, $this->uname);
			$users = UserLogic::getUsers( $this->pid );
		}
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
	
		$user = EnUser::getUserObj( $this->uid );
		$console = new Console();
		$console->level( 75 );
		$console->changeMissionLastTime(0, 1, 30, 0);
		$console->openMission(-100);
		$console->gold(10000);
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
		MissionUserObj::releaseInstance($this->uid);
		MissionCrossUserObj::releaseInstance();
		MissionConObj::release();
	}
	
  	function test_getUserInfo()
	{ 
		$miss = new Mission();
		$missInfo = $miss->getMissionInfo();
		var_dump( $missInfo );
	}
	  
	  function test_doMissionGold()
	{ 
		$miss = new Mission();
		$miss->doMissionGold(100);
		$missInfo = $miss->getMissionInfo();
		$serverId = Util::getServerIdOfConnection();
		$crossFame = MissionCrossUserObj::getInstance($this->pid, $serverId)->getFame();
		$this->assertTrue( $crossFame > 0 );
		var_dump( $missInfo );
		var_dump( $this->uid );
	 } 
	
 	function test_doMissionItem()
	{ 
		$bag = BagManager::getInstance()->getBag($this->uid);
		$bag->addItemByTemplateID(60001, 5);
		$itemIdArr = $bag->getItemIdsByTemplateID(60001);
		$miss = new Mission();
		$miss->doMissionItem(array( $itemIdArr[0] => 1 ));
		$missInfo = $miss->getMissionInfo();
		$serverId = Util::getServerIdOfConnection();
		$crossFame = MissionCrossUserObj::getInstance($this->pid, $serverId)->getFame();
		$this->assertTrue( $crossFame > 0 );
		var_dump( $missInfo );
		var_dump( $this->uid );
		var_dump( $this->pid );
	 } 
	
	function test_getRankList()
	{ 
		$miss = new Mission();
		$rankList = $miss->getRankList();
		var_dump( $rankList );		
	} 
	
/*   	function test_dayReward()
	{
		$miss = new Mission();
		$miss->receiveDayReward();
		$missInfo = $miss->getMissionInfo();
		var_dump( $missInfo );
		
	}  
	 */
/*   	function test_missionBack()
	{
		EnMission::doMission($this->uid, MissionType::ACOPY );
		$miss = new Mission();
		$missInfo = $miss->getMissionInfo();
		var_dump( $missInfo );
		$serverId = Util::getServerIdOfConnection();
		$crossFame = MissionCrossUserObj::getInstance($this->pid, $serverId)->getFame();
		$this->assertTrue( $crossFame > 0 );
	}
 */
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */