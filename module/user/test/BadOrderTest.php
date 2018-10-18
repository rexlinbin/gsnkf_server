<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/


/**
 * 代充订单处理，功能测试
 */


require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/test/UserClient.php";


class UserObjTest extends PHPUnit_Framework_TestCase
{
	
	protected static $pid = 0;
	protected static $uid = 0;
	
	
	protected $serverIp;
	protected $port = 7777;

	public static function setUpBeforeClass()
	{
		$data = new CData();
		
		$arrField = array(
			'pid', 'uid', 'uname'
		);
		$arrRet = $data->select($arrField)
						->from('t_user')
						->where('uid', '>', 20000)
						->orderBy('level', false)
						->limit(0, 1)
						->query();
		
		if ( empty($arrRet) )
		{
			throw new InterException('not found user to test');
		}
		self::$pid = $arrRet[0]['pid'];
		self::$uid = $arrRet[0]['uid'];
	}
	
	
	protected function setUp()
	{
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
		
		$this->serverIp = ScriptConf::PRIVATE_HOST;
	}
	
	protected function tearDown()
	{
	}
	
	public function testAddBadOrder()
	{
		$proxy = new ServerProxy();
		
		$pid = self::$pid;
		$uid = self::$uid;
		
		$goldNum = 1000;
		$subNum = $goldNum;
		
		self::cleanBadOrder($uid);
		$orderId = sprintf('phpunit_addbad_%d_%d', $uid, time() );
		
		
		$ret = $proxy->syncExecuteRequest ( 'user.addBadOrder', array($uid, $orderId, $goldNum, $subNum));
		$this->assertEquals('not_found_order', $ret);
		
		//
		$user = new User();
		$user->addGold4BBpay($uid, $orderId, $goldNum);

		$ret = $proxy->syncExecuteRequest ( 'user.addBadOrder', array($uid, $orderId, $goldNum+1, $subNum));
		$this->assertEquals('invalid_gold_num', $ret);
		
		
		$userClient = new UserClient($this->serverIp, $this->port, $pid);
		$userClient->setClass('console');
		$ret = $userClient->execute('gold 0');
		
		
		$suc = false;
		$ret = $proxy->syncExecuteRequest ( 'user.addBadOrder', array($uid, $orderId, $goldNum, 0));
		if ( defined('PlatformConfig::COMPENSATE_BAD_ORDER') )
		{
			if ( PlatformConfig::COMPENSATE_BAD_ORDER > 0 )
			{
				$this->assertEquals('ok', $ret);
				$subNum = PlatformConfig::COMPENSATE_BAD_ORDER * $goldNum;
				$suc = true;
			}
			else
			{
				$this->assertEquals('not_set_sub_percent', $ret);
			}
		}
		else
		{
			$this->assertEquals('not_set_sub_percent', $ret);
		}
		if ( !$suc )
		{
			$ret = $proxy->syncExecuteRequest ( 'user.addBadOrder', array($uid, $orderId, $goldNum, $subNum));
			$this->assertEquals('ok', $ret);
		}
		
		//有badorder来了，被踢了
		$userClient->setClass('user');
		try
		{
			$ret = $userClient->getUser();
			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertEquals('lcclient', $e->getMessage());
		}
		
		
		
		$this->assertTrue(defined('PlatformConfig::COMPENSATE_BAD_ORDER'));
		$this->assertTrue( PlatformConfig::COMPENSATE_BAD_ORDER > 0);
		
		//再想登陆就登陆不了了
		Logger::debug('login again');
		$userClient = new UserClient($this->serverIp, $this->port, $pid, $uid, false);
		$userClient->setClass ( 'user' );
		$ret = $userClient->login ( $pid );
		$this->assertEquals('ok', $ret);
		
		
		$ret = $userClient->userLogin($uid);
		$this->assertEquals('badorder', $ret['ret']);
		$this->assertEquals($subNum, $ret['num']);
		var_dump($ret);
		
		//充点钱，金币够扣，就可以登陆了
		$orderId = sprintf('phpunit_addbad_2_%d_%d', $uid, time() );
		$user->addGold4BBpay($uid, $orderId, $goldNum);
		
		$userClient = new UserClient($this->serverIp, $this->port, $pid, $uid, false);
		$userClient->setClass ( 'user' );
		$ret = $userClient->login ( $pid );
		$this->assertEquals('ok', $ret);
		$ret = $userClient->userLogin($uid);
		$this->assertEquals('ok', $ret);
		
		$ret = $userClient->getUser($uid);
		$curGold = $ret['gold_num'];
		
		//再来一次badorder
		$ret = $proxy->syncExecuteRequest ( 'user.addBadOrder', array($uid, $orderId, $goldNum, $curGold +  $subNum));
		$this->assertEquals('ok', $ret);
		
		$userClient = new UserClient($this->serverIp, $this->port, $pid, $uid, false);
		$userClient->setClass ( 'user' );
		$ret = $userClient->login ( $pid );
		$this->assertEquals('ok', $ret);
		$ret = $userClient->userLogin($uid);
		
		$this->assertEquals('badorder', $ret['ret']);
		$this->assertEquals($subNum, $ret['num']);

	}
	
	public static function cleanBadOrder($uid)
	{
		$data = new CData();
		$arrValue = array(
			'status' => 2,
		);
		$ret = $data->update('t_bad_order')
					->set($arrValue)
					->where('uid', '=', $uid)
					->query();
	}
} 


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */