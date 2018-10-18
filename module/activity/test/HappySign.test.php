<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySign.test.php 203215 2015-10-19 11:44:44Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/HappySign.test.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-10-19 11:44:44 +0000 (Mon, 19 Oct 2015) $
 * @version $Revision: 203215 $
 * @brief 
 *  
 **/

/*
 * 1、奖励的可领取档数以登陆天数为准，比如活动开7天但是只登陆了2天，则只能领取前2档奖励
 * 2、新一轮活动开启时数据是否刷新 
 * 这两个测试要点,我用btscript功能测试,而没用单元测试,因为模拟登录天数,这个需要改系统时间,单元测试不好实现
 * */
class HappySignTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;

	/* (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	*/
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
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
	}
	
	public function test_getHappySignInfo_notOpened()
	{
		//1、测试活动没开启的情况，把活动结束时间弄成比现在时间早
	
		//配置活动
		$activityName = 'happySign';
		$startTime = date( 'Ymd H:i:s', strtotime('-5 day') );
		$endTime = date( 'Ymd H:i:s', strtotime('-1 day') );
		$needOpenTime = $startTime;
		$this->setActivityTime($activityName, $startTime, $endTime, $needOpenTime);
	
		$happySign = new HappySign();
		try
		{
			$ret = $happySign->getSignInfo();
			$this->assertTrue(false);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}
	
	}
	
	public function test_getHappySignInfo_opened()
	{
		// 2、活动正常开启拉取数据信息
		$activityName = 'happySign';
		$startTime = date( 'Ymd H:i:s', strtotime('-5 day') );
		$endTime = date( 'Ymd H:i:s', strtotime('+5 day') );
		$needOpenTime = $startTime;
		$this->setActivityTime($activityName, $startTime, $endTime, $needOpenTime);
		
		Logger::debug('======%s======', __METHOD__);
		$happySign = new HappySign();
		
		// 第一次拉取信息,验证初始化信息中登陆天数是否为1,已领取奖励数组是否为空
		$ret = $happySign->getSignInfo();
		Logger::trace('user:%d happySign info:%s', self::$uid, $ret);
		$this->assertEquals(array(), $ret['hadSignIdArr']);
		$this->assertEquals(1, $ret['loginDayNum']);
	}
	
	public function test_receiveReward()
	{
		// 3、活动开启领奖
		
		// (1)领奖id错误
		Logger::debug('======%s======', __METHOD__);
		$happySign = new HappySign();
		$rewardId = 0;
		try
		{
			$ret = $happySign->gainSignReward($rewardId);
			$this->assertTrue(false);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'config', $e->getMessage() );
		}
		
		// (2)正常领取奖励,这里是领取第一档奖励
		$rewardId = 1;
		$ret = $happySign->gainSignReward($rewardId);
		Logger::trace('user:%d happySign gain first sign reward, ret:%s', self::$uid, $ret);
		$ret = $happySign->getSignInfo();
		$this->assertEquals(array($rewardId), $ret['hadSignIdArr']);
	}
	
	
	public function setActivityTime($name, $startTime, $endTime, $needOpenTime)
	{
		$ret = ActivityConfDao::getCurConfByName($name, ActivityDef::$ARR_CONF_FIELD);
		if( empty($ret) )
		{
			$msg = sprintf("WARN: no %s now", $name);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			return;
		}
	
		$version = Util::getTime();
		$conf = array(
				'name' => $name,
				'version' => $version,
				'start_time' => strtotime($startTime),
				'end_time' => strtotime($endTime),
				'need_open_time' => strtotime($needOpenTime),
				'str_data' => $ret['str_data'],
				'va_data' => $ret['va_data'],
		);
		ActivityConfDao::insertOrUpdate($conf);
	
		$ret = ActivityConfLogic::updateMem();
	
		EnActivity::$confBuff = array();
	
		$msg = sprintf('setConf done. name:%s, start:%s, end:%s, needOpenTime:%s, version:%s',
				$name,
				date( 'Ymd H:i:s ', strtotime($startTime) ),
				date( 'Ymd H:i:s ', strtotime($endTime) ),
				date( 'Ymd H:i:s ', strtotime($needOpenTime) ),
				date( 'Ymd H:i:s ', $version ) );
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}















/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */