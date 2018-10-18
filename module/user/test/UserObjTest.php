<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserObjTest.php 89099 2014-02-07 06:48:41Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/test/UserObjTest.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2014-02-07 06:48:41 +0000 (Fri, 07 Feb 2014) $
 * @version $Revision: 89099 $
 * @brief 
 *  
 **/


class UserObjTest extends PHPUnit_Framework_TestCase
{

	protected static $pid = 0;
	protected static $uid = 0;
	protected static $uname = '';
	
	public static function setUpBeforeClass()
	{
		self::$pid = time();
		$str = strval(self::$pid);
		self::$uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);

		$ret = UserLogic::createUser(self::$pid, 1, self::$uname);
		 
		if($ret['ret'] != 'ok')
		{
			echo "create use failed\n";
			exit();
		}
		self::$uid = $ret['uid'];
		 
	}


	protected function setUp()
	{
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
		
				
	}

	protected function tearDown()
	{
	}
	
	public function testStart()
	{
		Logger::debug('======%s======', __METHOD__ );
		EnUser::release(self::$uid);
		$userObj = EnUser::getUserObj(self::$uid);
		$this->assertTrue( $userObj instanceof UserObj );
	}
	/**
	 * 测试OtherUserObj类的refershStamina方法
	 */
	public function testStaminaRfr()
	{
	    Logger::debug('start testStaminaRfr.');
	    $userObj = EnUser::getUserObj(self::$uid);
	    
	    $userObj->subStamina($userObj->getStamina() / 2);
	    $stamina1    =    $userObj->getStamina();
	    Logger::debug('self::uid %s.stamina1 is %s.',self::$uid,$stamina1);
	    $stamina_time    =    strtotime("-5 hours");
	    self::updateUser(array('stamina_time'=>$stamina_time,'stamina'=>$stamina1));
	    $userObj = EnUser::getUserObj(self::$uid);
	    $userObj->refreshStamina();
	    $stamina2    =    $userObj->getStamina();
	    Logger::debug('stamina2 is %s.',$stamina2);
// 	    $this->assertEquals( 0, $stamina2-$stamina1);
	    $stamina_time    =    strtotime("-15 hours");
	    self::updateUser(array('stamina_time'=>$stamina_time,'stamina'=>$stamina2));
	    $userObj = EnUser::getUserObj(self::$uid);
	    $userObj->refreshStamina();
	    $stamina3    =    $userObj->getStamina();
	    Logger::debug('stamina3 is %s.',$stamina3);
// 	    $this->assertEquals( 20, $stamina3-$stamina2);
	}

	public function testGold()
	{
		Logger::debug('======%s======', __METHOD__ );
		
				
		$values = array(
				'gold_num' => 100,
				);
		self::updateUser($values);
		
		$userObj = EnUser::getUserObj(self::$uid);		
		$ret = $userObj->addGold(50, StatisticsDef::ST_FUNCKEY_BUY_EXECUTION);
		$userObj->update();
		$this->assertTrue( $ret );
		
		$ret = UserDao::getUserByUid( self::$uid, UserDef::$USER_FIELDS);		
		$this->assertEquals( 150, $ret['gold_num']);
		
		$ret = $userObj->subGold( 100, StatisticsDef::ST_FUNCKEY_BUY_EXECUTION);
		$userObj->update();
		$this->assertTrue( $ret );
		
		$ret = UserDao::getUserByUid( self::$uid, UserDef::$USER_FIELDS);
		$this->assertEquals( 50, $ret['gold_num']);
		$this->assertEquals( 100, $ret['va_user']['spend_gold'][date('Ymd')] );
		
	}
	
	public static function testFormation()
	{
		//@see MyFormationTest
	}
	
	
	public function testRollback()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		//修改user数据
		
		//修改，回滚，数据没有变化
		$userObj = EnUser::getUserObj(self::$uid);
		
		$preGold = $userObj->getGold();
		$preSilver = $userObj->getSilver();
		$userObj->addGold(10, StatisticsDef::ST_FUNCKEY_SYSTEM_COMPENSATION);
		$userObj->addSilver(12);
		
		$userObj->rollback();
		$userObj->update();
		
		$userObj = EnUser::getUserObj(self::$uid);
		$this->assertEquals($preGold, $userObj->getGold() );
		$this->assertEquals($preSilver, $userObj->getSilver());
	
		//修改1，回滚，修改2，修改2生效
		$preGold = $userObj->getGold();
		$preSilver = $userObj->getSilver();
		$userObj->addGold(10, StatisticsDef::ST_FUNCKEY_SYSTEM_COMPENSATION);
		$userObj->addSilver(12);
		
		$userObj->rollback();
		
		$userObj->addGold(11, StatisticsDef::ST_FUNCKEY_SYSTEM_COMPENSATION);
		$userObj->addSilver(15);
		$userObj->update();
		
		$userObj = EnUser::getUserObj(self::$uid);
		$this->assertEquals($preGold + 11, $userObj->getGold() );
		$this->assertEquals($preSilver + 15, $userObj->getSilver());
	   
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
			RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
		}
		UserDao::updateUser(self::$uid, $values);
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */