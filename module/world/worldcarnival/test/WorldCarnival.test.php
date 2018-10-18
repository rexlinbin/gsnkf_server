<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnival.test.php 199484 2015-09-17 10:55:02Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/test/WorldCarnival.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-17 10:55:02 +0000 (Thu, 17 Sep 2015) $
 * @version $Revision: 199484 $
 * @brief 
 *  
 **/
 
class WorldCarnivalTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $pid = 0;
	private static $serverId = 0;
	private static $fighterUid = 0;
	private static $fighterPid = 0;
	private static $fighterServerId = 0;
	private static $watcherUid = 0;
	private static $watcherPid = 0;
	private static $watcherServerId = 0;
	private static $confObj = NULL;

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('mbg' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];
		self::$pid = $pid;
		self::$serverId = Util::getServerId();

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(10000000);
		$console->silver(10000000);

		var_dump(self::$uid);
		var_dump(self::$pid);
		var_dump(self::$serverId);
		
		$confObj = WorldCarnivalConfObj::getInstance();
		self::$confObj = $confObj;
		
		$arrFighters = $confObj->getFighters();
		self::$fighterServerId = $arrFighters[1]['server_id'];
		self::$fighterPid = $arrFighters[1]['pid'];
		self::$fighterUid = WorldCarnivalUtil::getUid(self::$fighterServerId, self::$fighterPid);
		var_dump(self::$fighterUid);
		var_dump(self::$fighterPid);
		var_dump(self::$fighterServerId);
		
		$arrWatchers = $confObj->getWatchers();
		self::$watcherServerId = $arrWatchers[0]['server_id'];
		self::$watcherPid = $arrWatchers[0]['pid'];
		self::$watcherUid = WorldCarnivalUtil::getUid(self::$watcherServerId, self::$watcherPid);
		var_dump(self::$watcherUid);
		var_dump(self::$watcherPid);
		var_dump(self::$watcherServerId);
	}

	protected function setUp()
	{
		parent::setUp();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	protected static function getPrivateMethod($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}
	
	private function setRole($role)
	{
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
		if ($role == 'fighter') 
		{
			RPCContext::getInstance()->setSession('global.uid', self::$fighterUid);
		}
		else if ($role == 'watcher') 
		{
			RPCContext::getInstance()->setSession('global.uid', self::$watcherUid);
		}
		else 
		{
			RPCContext::getInstance()->setSession('global.uid', self::$uid);
		}
		
	}
	
	public function test_getCarnivalInfo()
	{
		// 非参赛者和旁观者拉信息
		$this->setRole('other');
		$worldCarnival = new WorldCarnival();
		$ret = $worldCarnival->getCarnivalInfo();
		var_dump($ret);
		$this->assertEquals('invalid', $ret['ret']);
		
		// 参赛者拉取信息
		$this->setRole('fighter');
		$worldCarnival = new WorldCarnival();
		$ret = $worldCarnival->getCarnivalInfo();
		var_dump($ret);
		$this->assertEquals('fighter', $ret['ret']);
		$this->assertEquals(4, count($ret['fighters']));
		
		// 旁观者拉取信息
		$this->setRole('watcher');
		$worldCarnival = new WorldCarnival();
		$ret = $worldCarnival->getCarnivalInfo();
		var_dump($ret);
		$this->assertEquals('watcher', $ret['ret']);
		$this->assertEquals(4, count($ret['fighters']));
	}
	
	public function test_updateFmt()
	{
		// 非参赛者和旁观者更新战斗信息
		try 
		{
			$this->setRole('other');
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->updateFmt();
			$this->assertEquals(0, 1);
		} 
		catch (Exception $e) 
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 旁观者更新信息
		try
		{
			$this->setRole('watcher');
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->updateFmt();
			$this->assertEquals(0, 1);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 参赛者更新信息
		$this->setRole('fighter');
		$worldCarnival = new WorldCarnival();
		$ret = $worldCarnival->updateFmt();
		$this->assertEquals('ok', $ret);
	}
	
	private function getRecord()
	{
		// 拉取战报数据，round超出范围
		try
		{
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->getRecord(4);
			$this->assertEquals(0, 1);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 正常拉取
		$procedureObj = WorldCarnivalProcedureObj::getInstance(self::$confObj->getSession(), self::$confObj->getActivityStartTime());
		for ($i = 1; $i <= 3; ++$i)
		{
			$this->setRole('fighter');
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->getRecord($i);
			var_dump($ret);
			$this->assertEquals($procedureObj->getBattleRecord($i), $ret);
			
			if (!empty($ret)) 
			{
				$aBrid = $ret[1]['brid'];
				$battle = new Battle();
				$ret = $battle->getRecord($aBrid);
				var_dump($ret);
				$this->assertTrue(!empty($ret));
			}
		}
	}
	
	public function test_getRecord()
	{
		// 非参赛者和旁观者更新拉取战报数据
		try 
		{
			$this->setRole('other');
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->getRecord(1);
			$this->assertEquals(0, 1);
		} 
		catch (Exception $e) 
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 验证参赛者
		$this->setRole('fighter');
		$this->getRecord();
		
		// 验证围观者
		$this->setRole('watcher');
		$this->getRecord();
	}
	
	private function getFighterDatail()
	{
		// 拉取阵容信息，但拉取的人不是参赛者
		try
		{
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->getFighterDetail(self::$serverId, self::$pid);
			$this->assertEquals(0, 1);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$worldCarnival = new WorldCarnival();
		$ret = $worldCarnival->getFighterDetail(self::$fighterServerId, self::$fighterPid);
		var_dump($ret);
		$this->assertTrue(!empty($ret));
	}
	
	public function test_getFighterDetail()
	{
		// 非参赛者和旁观者更新参赛者阵容信息
		try
		{
			$this->setRole('other');
			$worldCarnival = new WorldCarnival();
			$ret = $worldCarnival->getFighterDetail(self::$fighterServerId, self::$fighterPid);
			$this->assertEquals(0, 1);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 验证参赛者
		$this->setRole('fighter');
		$this->getFighterDatail();
		
		// 验证围观者
		$this->setRole('watcher');
		$this->getFighterDatail();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */