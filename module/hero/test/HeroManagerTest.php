<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroManagerTest.php 89099 2014-02-07 06:48:41Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/test/HeroManagerTest.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2014-02-07 06:48:41 +0000 (Fri, 07 Feb 2014) $
 * @version $Revision: 89099 $
 * @brief 
 *  
 **/





class HeroManagerTest extends PHPUnit_Framework_TestCase
{

	protected static $pid = 0;
	protected static $uid = 0;
	protected static $uname = '';
	protected static $masterHid = 0;
	protected static $initUserInfo = NULL;

	public static function setUpBeforeClass()
    {
    	self::$pid = IdGenerator::nextId('uid');
		self::$uname = strval(self::$pid);		
    		
    	$ret = UserLogic::createUser(self::$pid, 1, self::$uname);
    	
    	if($ret['ret'] != 'ok')
    	{
    		echo "create use failed\n";
    		exit();
    	}
    	self::$uid = $ret['uid'];
    	
    	EnUser::release(self::$uid);
    	
    	self::$initUserInfo = UserDao::getUserByUid( self::$uid, UserDef::$USER_FIELDS );
  
    }
    
    
	protected function setUp()
	{		
		EnUser::release(self::$uid);
		self::updateUser(self::$initUserInfo);
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
	}

	protected function tearDown()
	{
		
	}
	
	public function testAddHero()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
        $htid    =    $this->getNormalHtid();
        $heroNum = $heroManager->getHeroNum();		
		$hid1 = $heroManager->addNewHero($htid);
		$ret = $heroManager->addNewHeroWithLv($htid, 5);
		$hid2 = current(array_keys($ret['hero']));
		$userObj->update();		
		$ret = UserDao::getUserByUid( self::$uid, UserDef::$USER_FIELDS );		
	
		$this->assertTrue(isset($ret['va_hero']['unused'][$hid1]));
		$this->assertTrue(isset($ret['va_hero']['unused'][$hid2]));
		$this->assertEquals($htid, $ret['va_hero']['unused'][$hid1][UserDef::UNUSED_HERO_HTID]);
		$this->assertEquals($htid, $ret['va_hero']['unused'][$hid2][UserDef::UNUSED_HERO_HTID]);
		$this->assertEquals(5, $ret['va_hero']['unused'][$hid2][UserDef::UNUSED_HERO_LEVEL]);
		
		$ret = $heroManager->getAllHero();
		$this->assertEquals($heroNum+2, count($ret));
	}
	
	private function getNormalHtid()
	{
	    $heroes = btstore_get()->HEROES->toArray();
	    foreach($heroes as $htid => $heroInfo)
	    {
	        if( Creature::getHeroConf($htid, CreatureAttr::CAN_BE_RESOLVED) == 0  )
	        {
	            unset($heroes[$htid]);
	        }
	    }
	    $htid    =    array_rand($heroes,1);
	    return $htid;
	}
	/**
	 * 初始化unused hero到hero表里
	 */
	public function testInitHero()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		$unusedHerosBeforeAdd = $userObj->getAllUnusedHero();
		$newHtid = $this->getNormalHtid();
		$newHid = $heroManager->addNewHero($newHtid);		
		$userObj->update();	
		
		$unusedHerosAfterAdd = $userObj->getAllUnusedHero();
		foreach( $unusedHerosAfterAdd as $hid => $htid)
		{
			if( !isset( $unusedHerosBeforeAdd[$hid] ) )
			{
				$this->assertEquals( $newHid, $hid);
			}
		}
		$this->assertTrue($newHid > 0);
		$tmp = $unusedHerosAfterAdd;
		unset($tmp[$newHid]);
		$this->assertEquals($unusedHerosBeforeAdd, $tmp );
		
		$arrHeroBeforeInit = HeroDao::getArrHeroeByUid(self::$uid, HeroDef::$HERO_FIELDS);
		
		$heroManager->initHero($newHid);
		$userObj->update();
		$unusedHerosAfterInit = $userObj->getAllUnusedHero();
		$this->assertEquals( $unusedHerosBeforeAdd, $unusedHerosAfterInit);
		
		$arrHeroAfterInit = HeroDao::getArrHeroeByUid(self::$uid, HeroDef::$HERO_FIELDS);
		$this->assertTrue( isset( $arrHeroAfterInit[$newHid] ) );
		$tmp = $arrHeroAfterInit;
		unset($tmp[$newHid]);
		$this->assertEquals( $arrHeroBeforeInit, $tmp);
		
	}
	
	
	public function testDelHero()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		//准备一个用过的，一个没有用过的武将
		$htidUsed = $this->getNormalHtid();
		$htidUnused = $this->getNormalHtid();
		$hidUsed = $heroManager->addNewHero($htidUsed);
		$hidUnused = $heroManager->addNewHero($htidUnused);		
		$heroManager->initHero($hidUsed);
		$userObj->update();
		
		$ret = UserDao::getUserByUid(self::$uid, UserDef::$USER_FIELDS);
		$this->assertEquals( $htidUnused, $ret['va_hero']['unused'][$hidUnused][UserDef::UNUSED_HERO_HTID]);
		
		$ret = HeroDao::getArrHeroeByUid(self::$uid, HeroDef::$HERO_FIELDS);
		$this->assertTrue( isset($ret[$hidUsed]) );
		
		//删
		$heroManager->delHeroByHid($hidUsed);
		$heroManager->delHeroByHid($hidUnused);
		$userObj->update();
		
		$ret = UserDao::getUserByUid(self::$uid, UserDef::$USER_FIELDS);
		$this->assertTrue( !isset($ret['va_hero']['unused'][$hidUnused]) );
		
		$ret = HeroDao::getArrHeroeByUid(self::$uid, HeroDef::$HERO_FIELDS);
		$this->assertTrue( !isset($ret[$hidUsed]) );
	}
	
	
	public function testByOtherUser()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		RPCContext::getInstance()->setSession('global.uid', 0);
		EnUser::release(self::$uid);
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		$allMethods = get_class_methods(get_class($userObj));
	
		$this->assertTrue( !in_array('addUnusedHero', $allMethods) );
		$this->assertTrue( !in_array('initHero', $allMethods) );
		$this->assertTrue( !in_array('delUnusedHero', $allMethods) );
		
	}
	
	
	public function testInitWhenUpdate()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		$userObj->addExp(10000);
		$hid1 = $heroManager->addNewHero(10002);
		$ret = $heroManager->addNewHeroWithLv(10003, 5);
		$hid5 = current(array_keys($ret['hero']));
		$ret = $userObj->update();
		
		$ret = UserDao::getUserByUid(self::$uid, UserDef::$USER_FIELDS);
		$this->assertEquals( array(UserDef::UNUSED_HERO_HTID=>10002) , $ret['va_hero']['unused'][$hid1]);
		$this->assertEquals( array(UserDef::UNUSED_HERO_HTID=>10003, UserDef::UNUSED_HERO_LEVEL=>5) , $ret['va_hero']['unused'][$hid5]);
				
		$heroObj = $heroManager->getHeroObj($hid1);		
		$heroObj->addSoul(10);		
		$heroObj->update();
		
		$heroObj = $heroManager->getHeroObj($hid5);
		$heroObj->addSoul(10);
		$heroObj->update();
		
		$ret = UserDao::getUserByUid(self::$uid, UserDef::$USER_FIELDS);
		$this->assertTrue( !isset($ret['va_hero']['unused'][$hid1]) );
		$this->assertTrue( !isset($ret['va_hero']['unused'][$hid5]) );
			
		$ret = HeroDao::getArrHeroeByUid(self::$uid, HeroDef::$HERO_FIELDS);
		$this->assertTrue( isset($ret[$hid1]) );
		$this->assertEquals(1, $ret[$hid1]['level']);
		$this->assertTrue( isset($ret[$hid5]) );
		$this->assertEquals(5, $ret[$hid5]['level']);
		
	}
	
	public function testRollback()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp(10000); //给玩家升级
		$heroManager = $userObj->getHeroManager();

		$hidUnusedToChange = $heroManager->addNewHero(10002);
		$hidUsedToChange = $heroManager->addNewHero(10002);
		$hidUnusedToDel = $heroManager->addNewHero(10003);
		$hidUsedToDel = $heroManager->addNewHero(10003);
		
		$heroManager->getHeroObj($hidUsedToChange)->addSoul(1);
		
		$userObj->update();
		self::clearData();
		
		
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		$allHeroInfoPre = $heroManager->getAllHero();
		
		$hidToAdd = $heroManager->addNewHero(10004);
		
		$heroObjUnusedToChange = $heroManager->getHeroObj($hidUnusedToChange);
		$heroObjUnusedToChange->addSoul(10);
		
		$heroObjUsedToChange = $heroManager->getHeroObj($hidUnusedToChange);
		$heroObjUsedToChange->addSoul(20);
		
		$heroManager->delHeroByHid($hidUnusedToDel);
		$heroManager->delHeroByHid($hidUsedToDel);
		
		$userObj->rollback();
		$userObj->update();
		self::clearData();
	
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		
		$this->assertEquals($allHeroInfoPre, $heroManager->getAllHero() );
		
		
		
		$hidToAdd = $heroManager->addNewHero(10004);
		
		$heroObjUnusedToChange = $heroManager->getHeroObj($hidUnusedToChange);
		$heroObjUnusedToChange->addSoul(10);
		
		$heroObjUsedToChange = $heroManager->getHeroObj($hidUsedToChange);
		$heroObjUsedToChange->addSoul(20);
		
		$heroManager->delHeroByHid($hidUnusedToDel);
		$heroManager->delHeroByHid($hidUsedToDel);
		
		$userObj->rollback();
		
		
		$hidToAdd = $heroManager->addNewHero(10004);
		
		$heroObjUnusedToChange = $heroManager->getHeroObj($hidUnusedToChange);
		$heroObjUnusedToChange->addSoul(1);
		
		$heroObjUsedToChange = $heroManager->getHeroObj($hidUsedToChange);
		$heroObjUsedToChange->addSoul(2);
		
		$heroManager->delHeroByHid($hidUnusedToDel);
		$heroManager->delHeroByHid($hidUsedToDel);
		
		
		$userObj->update();
		self::clearData();
		
		$userObj = EnUser::getUserObj(self::$uid);
		$heroManager = $userObj->getHeroManager();
		$allHeroInfo = $heroManager->getAllHero();
		
		$this->assertTrue( isset($allHeroInfo[$hidToAdd]) );
		$this->assertTrue( empty($allHeroInfo[$hidUnusedToDel]) );
		$this->assertTrue( empty($allHeroInfo[$hidUsedToDel]) );
		
		unset($allHeroInfo[$hidToAdd]);
		
		unset( $allHeroInfoPre[$hidUnusedToDel] );
		unset( $allHeroInfoPre[$hidUsedToDel] );
		$allHeroInfoPre[$hidUnusedToChange]['soul'] += 1;
		$allHeroInfoPre[$hidUsedToChange]['soul'] += 2;
		

		Logger::debug('hidUnusedToChange:%d, hidUsedToChange:%d, hidUnusedToDel:%d, hidUsedToDel:%d, hidToAdd:%d',
		$hidUnusedToChange, $hidUsedToChange, $hidUnusedToDel, $hidUsedToDel, $hidToAdd);
		
		$this->assertEquals($allHeroInfoPre, $allHeroInfo);
		
		
	}
	
	public static function clearData()
	{
		EnUser::release(self::$uid);
		RPCContext::getInstance()->resetSession();
		CData::$QUERY_CACHE = NULL;
	}
	
	public static function updateUser($values)
	{
		EnUser::release(self::$uid);
		if( self::$uid == RPCContext::getInstance()->getUid())
		{
			RPCContext::getInstance()->unsetSession('global.uid');
		}
		UserDao::updateUser(self::$uid, $values);
	}
	
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */