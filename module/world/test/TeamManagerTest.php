<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TeamManagerTest.php 125614 2014-08-07 14:36:13Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/test/TeamManagerTest.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-08-07 14:36:13 +0000 (Thu, 07 Aug 2014) $
 * @version $Revision: 125614 $
 * @brief 
 *  
 **/
class TeamManagerTest extends PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
	
	}
	
	
	protected function setUp()
	{
		
	}
	
	protected function tearDown()
	{
	
	}
	
	
	public function testGetAllTeam()
	{
		$session = 1;
		$teamMgr = TeamManager::getInstance(ActivityName::LORDWAR, $session);
		
		$ret = $teamMgr->getAllTeam();
		
		Logger::info('getAllTeam:%s', $ret);
		$this->assertTrue(!empty($ret));
	}
	
	public function testGetServersByTeamId()
	{
		$session = 1;
		$teamMgr = TeamManager::getInstance(ActivityName::LORDWAR, $session);
		
		$ret = $teamMgr->getAllTeam();
		
		Logger::info('getAllTeam:%s', $ret);
		$this->assertTrue(!empty($ret));
		
		$arrTeamId = array_keys($ret);
		
		
		$index = rand(0, count($ret)-1 );
		$teamId = $arrTeamId[$index];
		$arrServerId = $ret[$teamId];
		
		
		$ret = $teamMgr->getServersByTeamId($teamId);
		$this->assertEquals($arrServerId, $ret);
		
	}
	
	public function testGetServersByServerId()
	{
		$session = 1;
		$teamMgr = TeamManager::getInstance(ActivityName::LORDWAR, $session);
	
		$ret = $teamMgr->getAllTeam();
	
		Logger::info('getAllTeam:%s', $ret);
		$this->assertTrue(!empty($ret));
	
		$arrTeamId = array_keys($ret);
	
	
		$index = rand(0, count($ret)-1 );
		$teamId = $arrTeamId[$index];
		$arrServerId = $ret[$teamId];
		
		$serverId = $arrServerId[rand(0, count($arrServerId)-1)];
	
	
		$key = TeamManager::genMemKeyTeamOfServer(ActivityName::LORDWAR, $serverId);
		$ret = McClient::del($key);
		
		$ret = $teamMgr->getTeamIdByServerId($serverId);
		$this->assertEquals($teamId, $ret);
		
		$ret = McClient::get($key);
		Logger::info('mc get:%s', $ret);
		$this->assertTrue(isset($ret['teamId']));
		$this->assertEquals($teamId, $ret['teamId']);
		
		
		$ret = $teamMgr->getServersByServerId($serverId);
		$this->assertEquals($arrServerId, $ret);
	
	}
	
	
	
	
}
	
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */