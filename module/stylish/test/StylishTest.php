<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StylishTest.php 242331 2016-05-12 07:28:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/test/StylishTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-12 07:28:46 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242331 $
 * @brief 
 *  
 **/
class StylishTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::STYLISH);
		EnSwitch::getSwitchObj(self::$uid)->save();
	}
	
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}
	
	public static function createUser() 
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		echo "test user: " . self::$uid . "\n";
	}
	
	public function test_getStylishInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$stylish = new Stylish();
		$ret = $stylish->getStylishInfo();
		$this->assertEquals($ret, array('title' => array()));
	}
	
	public function test_activeTitle()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$titleConf = btstore_get()->TITLE;
		
		//激活称号1
		$id = 1;
		$itemTplId = $titleConf[$id][StylishDef::TITLE_COST_ITEM];
		
		list($itemId) = ItemManager::getInstance()->addItem($itemTplId);
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->addItem($itemId);
		$bag->update();
	
		$stylish = new Stylish();
		$ret = $stylish->activeTitle($id, $itemId);
		$this->assertEquals($ret, 'ok');
		
		$ret = $stylish->getStylishInfo();
		$deadline = empty($titleConf[$id][StylishDef::TITLE_LAST_TIME]) ? 0 : Util::getTime() + $titleConf[$id][StylishDef::TITLE_LAST_TIME];
		$this->assertEquals($ret, array('title' => array($id => $deadline)));
		$this->assertEquals(ItemManager::getInstance()->getItem($itemId), NULL);
	}
	
	public function test_setTitle()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$user = EnUser::getUserObj(self::$uid);
		$this->assertEquals($user->getTitle(), 0);
		
		$id = 1;
		$stylish = new Stylish();
		$ret = $stylish->setTitle($id);
		$this->assertEquals($ret, 'ok');
		$this->assertEquals($user->getTitle(), $id);
	} 
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */