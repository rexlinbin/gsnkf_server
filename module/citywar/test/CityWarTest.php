<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWarTest.php 138015 2014-10-30 05:41:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/test/CityWarTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-30 05:41:46 +0000 (Thu, 30 Oct 2014) $
 * @version $Revision: 138015 $
 * @brief 
 *  
 **/
class CityWarTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 23956;

	public static function setUpBeforeClass()
	{
		self::createUser(self::$uid);
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}

	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	public static function createUser($uid)
	{
		$arrField = array(
					CityWarDef::USER_ID => $uid,
					CityWarDef::CUR_CITY => 0,
					CityWarDef::ENTER_TIME => 0,
					CityWarDef::REWARD_TIME => 0,
					CityWarDef::MEND_TIME => 0,
					CityWarDef::RUIN_TIME => 0,
					CityWarDef::VA_CITY_WAR_USER => array(),
		);
		CityWarDao::insertOrUpdateUser($arrField);
	}
	
	public function test_clearCd()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$user = EnUser::getUserObj(self::$uid);
		$goldBefore = $user->getGold();
		
		$arrField = array(CityWarDef::MEND_TIME => Util::getTime());
		CityWarDao::updateUser(self::$uid, $arrField);
		
		$citywar = new CityWar();
		$citywar->clearCd(0);
		$goldAfter = $user->getGold();
		$num = btstore_get()->CITY_WAR_ATTACK[CityWarDef::CD_CLEAR];
		$this->assertEquals($goldBefore - $num, $goldAfter);
		$info = CityWarDao::selectUser(self::$uid);
		$this->assertEquals($info[CityWarDef::MEND_TIME], 0);
		
		$goldBefore = $user->getGold();
		
		$arrField = array(CityWarDef::RUIN_TIME => Util::getTime());
		CityWarDao::updateUser(self::$uid, $arrField);
		
		$citywar = new CityWar();
		$citywar->clearCd(1);
		$goldAfter = $user->getGold();
		$num = btstore_get()->CITY_WAR_ATTACK[CityWarDef::CD_CLEAR];
		$this->assertEquals($goldBefore - $num, $goldAfter);
		$info = CityWarDao::selectUser(self::$uid);
		$this->assertEquals($info[CityWarDef::RUIN_TIME], 0);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */