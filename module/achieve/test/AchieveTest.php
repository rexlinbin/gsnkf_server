<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AchieveTest.php 126009 2014-08-11 06:33:31Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/test/AchieveTest.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2014-08-11 06:33:31 +0000 (Mon, 11 Aug 2014) $
 * @version $Revision: 126009 $
 * @brief 
 *  
 **/

class AchieveTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;
	
	protected static $starId = 0;
	
	protected static $starTid = 0;
	
	public static function setUpBeforeClass()
	{
		self::$uid = self::createUser();
		printf("test user:%d\n", self::$uid);
		
		foreach(btstore_get()->STAR as $starTid => $value)
		{
			break;
		}
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
		$ret = EnStar::addNewStar(self::$uid, $starTid);
		self::$starTid = $starTid;
		self::$starId = key($ret);

		MyStar::getInstance(self::$uid)->update();
	}
	
	protected function setUp()
	{
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
	}
	
	protected function tearDown()
	{
	}
	
	
	public function testGetAddAttr()
	{
		$level = 10;
		$exp = StarLogic::getExpByLevel(self::$starTid, $level);
		$myStar = MyStar::getInstance(self::$uid);
		$myStar->setStarLevel(self::$starId, $level);
		$myStar->setStarExp(self::$starId, $exp);
		$myStar->update();
		
		$arrRet = EnAchieve::getAddAttrByAchieve(self::$uid);
		
		var_dump($arrRet);
	}
	
	public function testAddStarAchieve()
	{
		$userObj = EnUser::getUserObj();
		$userObj->addExp(99999999);
		$preStaminaMaxNum = $userObj->getStaminaMaxNum();
		
		$arrStarAchieve = self::getArrAchieveByType(AchieveDef::STAR_ALL_FAVOR);
		if(count($arrStarAchieve) < 3)
		{
			echo "ignore testAddStarAchieve\n";			
			return;
		}
		
		$myStar = MyStar::getInstance(self::$uid);
		$myStar->setStarExp(self::$starId, 0);
		$myStar->setStarLevel(self::$starId, 0);
		$myStar->update();
		
		$conf = $arrStarAchieve[0];
		$addExp = StarLogic::getExpByLevel(self::$starTid, $conf['arrCond'][0]);
		Logger::debug('star:%d, stid:%d, addExp:%d', self::$uid, self::$starTid, $addExp);
		StarLogic::addFavor(self::$uid, self::$starId, $addExp);
		$myStar->update();
		
		$this->assertEquals($preStaminaMaxNum + $conf['staminaMaxNum'], $userObj->getStaminaMaxNum());
		
		//在连续获取两个成就
		$conf = $arrStarAchieve[1];
		$addExp = StarLogic::getExpByLevel(self::$starTid, $conf['arrCond'][0]) - $addExp;
		Logger::debug('star:%d, stid:%d, addExp:%d', self::$uid, self::$starTid, $addExp);
		StarLogic::addFavor(self::$uid, self::$starId, $addExp);
		
		//在连续获取两个成就
		$conf = $arrStarAchieve[2];
		$addExp = StarLogic::getExpByLevel(self::$starTid, $conf['arrCond'][0]) - $addExp;
		Logger::debug('star:%d, stid:%d, addExp:%d', self::$uid, self::$starTid, $addExp);
		StarLogic::addFavor(self::$uid, self::$starId, $addExp);
		$myStar->update();
		
		$num = 0;
		for($i = 0; $i < 3; $i++)
		{
			$num += $arrStarAchieve[$i]['staminaMaxNum'];
			$level = $arrStarAchieve[$i]['arrCond'][0];
			$ability = StarLogic::getAbilityConf(self::$starTid, $level);
			if (!empty($ability[StarDef::STAR_ABILITY_STAMINA]))
			{
				$num += $ability[StarDef::STAR_ABILITY_STAMINA];
			}
		}
		$this->assertEquals($preStaminaMaxNum + $num, $userObj->getStaminaMaxNum());
	}
	
	protected static function getArrAchieveByType($type)
	{
		$arrAllAchieve =  btstore_get()->ACHIEVE;
		$arrAchieve = array();
		foreach( $arrAllAchieve as $id => $conf)
		{
			if( $conf['type'] == $type)
			{
				$arr = $conf->toArray();
				$arr['id'] = $id;
				$arrAchieve[] = $arr;
			}
		}
		return $arrAchieve;
	}
	
	protected static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		$uid = $ret['uid'];
	
		return $uid;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */