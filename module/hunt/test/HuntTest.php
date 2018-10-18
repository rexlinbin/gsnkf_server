<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HuntTest.php 91669 2014-02-27 12:56:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/test/HuntTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-02-27 12:56:41 +0000 (Thu, 27 Feb 2014) $
 * @version $Revision: 91669 $
 * @brief 
 *  
 **/
class HuntTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 22828;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::FIGHTSOUL);
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
	
	public function test_getHuntInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$conf = btstore_get()->HUNT->toArray();
		$place = key($conf);
		
		$hunt = new Hunt();
		$ret = $hunt->getHuntInfo();
		
		$this->assertEquals($ret, $place);
	}
	
	public function test_skip()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$user = EnUser::getUserObj(self::$uid);
		$user->setVip(5);
		$user->update();
		
		$conf = btstore_get()->VIP[5]['goldOpenExplore']->toArray();
		list($place, $gold, $itemTplId1, $itemTplId2) = $conf;
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->addItemByTemplateID($itemTplId1, 1);
		$bag->update();
		
		$hunt = new Hunt();
		$ret = $hunt->skip(0);
		$this->assertEquals($ret['place'], $place);
		$this->assertEquals(current($ret['item']), $itemTplId2);
		$itemNum = $bag->getItemNumByTemplateID($itemTplId1);
		$this->assertEquals(0, $itemNum);
		$itemNum = $bag->getItemNumByTemplateID($itemTplId2);
		$this->assertEquals(1, $itemNum);
		
		$user->addGold($gold, StatisticsDef::ST_FUNCKEY_HUNT_SKIP);
		$user->update();
		$goldBefore = $user->getGold();
		try 
		{
			$ret = $hunt->skip(1);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		$arrField = array(HuntDef::HUNT_PLACE => $place - 1);
		$ret = HuntDao::update(self::$uid, $arrField);
		
		$ret = $hunt->skip(1);
		$this->assertEquals($ret['place'], $place);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - 2 * $gold, $goldAfter);
	}

	public function test_huntSoul()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$info = HuntDao::select(self::$uid);
		$place = $info[HuntDef::HUNT_PLACE];
		$conf = btstore_get()->HUNT->toArray();
		$silver = $conf[$place][HuntDef::HUNT_PLACE_COST];
		$user = EnUser::getUserObj(self::$uid);
		$user->addSilver($silver);
		$user->update();
		$silverBefore = $user->getSilver();
		$hunt = new Hunt();
		$ret = $hunt->huntSoul(1);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $silver, $silverAfter);
		$this->assertEquals(1, count($ret['place']));
		$this->assertEquals(1, count($ret['item']));
		
		$user->addSilver($silver*10);
		$user->update();
		$ret = $hunt->huntSoul(10);
		$this->assertEquals(1, count($ret['place']));
		$this->assertEquals(10, count($ret['item']));
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */