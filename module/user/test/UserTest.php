<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserTest.php 218552 2015-12-30 02:15:37Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/test/UserTest.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-30 02:15:37 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218552 $
 * @brief 
 *  
 **/



class UserTest extends PHPUnit_Framework_TestCase
{
	
	protected static $pid = 0;
	protected static $uname = '';
	protected $uid = 0;
	protected $utid = 2;
	
	public static function setUpBeforeClass()
	{
		self::$pid = IdGenerator::nextId('uid');
		self::$uname = strval(self::$pid);		
	}
	
	
	protected function setUp()
	{
		if($this->uid == 0)
		{
			$this->loginTest();
		}
	}

	protected function tearDown()
	{
	}
	
	
	public function loginTest()
	{
		$userClass = new User();
		
		$ret = $userClass->login( self::$pid   );
		$this->assertEquals('ok', $ret);
		
		$ret = $userClass->getUsers();
		if( empty($ret) )
		{
			Logger::debug('no user, create one');
			$ret = $userClass->createUser( $this->utid, self::$uname);
			$this->assertEquals('ok', $ret);
			
			$ret = $userClass->getUsers();
			
			EnUser::release( $ret[0]['uid'] );
		}	
		
		$this->uid = $ret[0]['uid'];		
		
		$ret = $userClass->userLogin($this->uid);
		
		$ret = $userClass->getUser($this->uid);
		$this->assertTrue(!empty($ret));
		
		$ret = UserDao::getUserByUid($this->uid, UserDef::$USER_FIELDS);
		$masterHid = $ret['master_hid'];
		$this->assertEquals($this->utid, $ret['utid']);
		$this->assertTrue( HeroUtil::isHero( $masterHid ) );
		
		//检查一下主角武将
		$ret = HeroDao::getArrHeroeByUid($this->uid, HeroDef::$HERO_FIELDS);
		$this->assertEquals(1, count($ret));
		$this->assertTrue( isset($ret[$masterHid]) );
		
		//检查一下阵型
		$ret = FormationDao::getByUid($this->uid);
		$this->assertTrue( !empty($ret) );
		$this->assertEquals(1, count($ret['va_formation']['formation']) );
		$this->assertTrue( isset( $ret['va_formation']['formation'][$masterHid] ) );
		
	}
	//暂时没有购买体力这个功能了
// 	public function testBuyExecution()
// 	{

// 		$userClass = new User();
		
// 		//不涉及行动力刷新
// 		$values = array(
// 				'execution' => 10,
// 				'execution_time' => time(),
// 				'buy_execution_accum' => 0,
// 				);
// 		$this->updateUser($values);
		
// 		$ret = $userClass->buyExecution(10);
// 		$this->assertEquals('ok', $ret);
// 		$ret = UserDao::getUserByUid($this->uid, UserDef::$USER_FIELDS);
// 		$this->assertEquals(20, $ret['execution']);
		
// 		//涉及行动力刷新
// 		$values = array(
// 				'execution' => 10,
// 				'execution_time' => Util::getTime() - 3600,
// 				'buy_execution_accum' => 0,
// 				);
// 		$this->updateUser($values);
		
// 		$userObj = EnUser::getUserObj($this->uid);
// 		$execution = $userObj->getCurExecution();
// 		$this->assertEquals(10 + 3600/UserConf::SECOND_PER_EXECUTION ,  $execution );
		
		
// 		$ret = $userClass->buyExecution(10);
// 		$this->assertEquals('ok', $ret);
// 		$ret = UserDao::getUserByUid($this->uid, UserDef::$USER_FIELDS);
// 		$this->assertEquals( $execution + 10, $ret['execution']);
// 	}
	
	
	public function testPayback()
    {
        $arr = array(
            5040=> 10000,
            100 => 200,
            200 => 210,
            500 => 1000,
            1000 => 2000,
            6000 => 10115,
            3000 => 4115,
        );

        foreach( $arr as $k => $v)
        {
            $ret = UserLogic::getPayBack($k, true);
            Logger::debug( "%d, need:%d, real:%d\n" , $k ,$v, $ret);
         	$this->assertEquals($v, $ret);
        }

        $arr = array(
            10000 => 1400,
            100 => 10,
            200 => 20,
            500 => 55,
            1000 => 115,
            6000 => 765,
            3000 => 355,
        );

        foreach( $arr as $k => $v)
        {
            $ret = UserLogic::getPayBack($k, false);
            Logger::debug( "%d, need:%d, real:%d\n" , $k ,$v, $ret);
         	$this->assertEquals($v, $ret);
        }
    }

    
    public function test_getCoreUser()
    {
    	$user = new User();
    	$ret = $user->getCoreUser(50);
    	var_dump($ret);
    }
    
    public function test_getTopActivityInfo()
    {
    	RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, 20001);
    	$user = new User();
    	$ret = $user->getTopActivityInfo();
    	var_dump($ret);
    }
	
	public function updateUser($values)
	{
		EnUser::release($this->uid);
		RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
		UserDao::updateUser($this->uid, $values);
	}
	
	
	

}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */