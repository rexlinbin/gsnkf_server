<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGift.test.php 208292 2015-11-10 03:47:54Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/RechargeGift.test.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-10 03:47:54 +0000 (Tue, 10 Nov 2015) $
 * @version $Revision: 208292 $
 * @brief 
 *  
 **/
class RechargeGiftUnitTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;
	protected static $accGold = 0;
	
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
		printf("RechargeGiftUnitTest uid:%d\n", self::$uid);
	}
	
	public function test_getRechargeGiftInfo_notOpened()
	{
		//(1)测试活动没开启的情况，把活动结束时间弄成比现在时间早
	
		//配置活动
		$activityName = 'rechargeGift';
		$startTime = strtotime('-5 day');
		$endTime = strtotime('-1 day');
		$needOpenTime = $startTime;
		$this->setActivityTime($activityName, $startTime, $endTime, $needOpenTime);		
		try
		{
			$rechargeGift = new RechargeGift();
			$ret = $rechargeGift->getInfo();
			$this->assertTrue(false);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}
	
	}
	
	public function test_getRechargeGiftInfo_opened()
	{
		Logger::debug('======%s======', __METHOD__);
		
		// (2)活动正常开启拉取数据信息,充值未领奖的情况
		$activityName = 'rechargeGift';
		$startTime = strtotime('-1 day');
		$endTime = strtotime('+5 day');
		$needOpenTime = $startTime;
		$this->setActivityTime($activityName, $startTime, $endTime, $needOpenTime);
	
		$rechargeGift = new RechargeGift();
 		// 第一次拉取信息,验证初始化信息中充值金币为50000,已领取奖励数组是否为空
		$console = new Console();
		self::$accGold = 50000;
		$console->addGoldOrder(self::$accGold);
		$ret = $rechargeGift->getInfo();
		Logger::trace('user:%d rechargeGift info:%s', self::$uid, $ret);
		$this->assertEquals(array(), $ret[RechargeGiftDef::HAD_REWARD]);
		$this->assertEquals(self::$accGold, $ret[RechargeGiftDef::ACC_GOLD]);
	}
	
	public function test_RechargeGiftObtainReward()
	{
		// 3 活动开启领奖
		Logger::debug('======%s======', __METHOD__);
		// (3.1) 领奖id错误
		$rechargeGift = new RechargeGift();
		$rewardId = 0;
		try
		{
			$ret = $rechargeGift->obtainReward($rewardId);
			$this->assertTrue(false);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}
		
		// 删选出配置中奖励中 可选已经不可选类型的 奖励id数组
		$unSelectRewardArr = array();
		$selectRewardArr = array();
		$confData = RechargeGiftUtil::getConfData();
		foreach ($confData as $rewardId => $info)
		{
			if (isset($info[RechargeGiftDef::UNSELECT_REWARD]))
			{
				$unSelectRewardArr[] = $rewardId;
			}
			else
			{
				$keys = array_keys($info[RechargeGiftDef::SELECT_REWARD]);
				$selectRewardArr[$rewardId] = $keys;
			}
		}
		
		// (3.2)正常领取奖励,领取不可选择类型的奖励
		$hadRewardArr = array();
		$rewardId = current($unSelectRewardArr);
		$confReqRechargeGold = $confData[$rewardId][RechargeGiftDef::REQ_GOLD];
		$console = new Console();
		$console->addGoldOrder($confReqRechargeGold);
		$ret = $rechargeGift->obtainReward($rewardId);
		Logger::debug('user:%d rechargeGift first gain unselectType recharge reward, ret:%s', self::$uid, $ret);
		$ret = $rechargeGift->getInfo();
		$hadRewardArr[] = $rewardId; 
		self::$accGold += $confReqRechargeGold; 
		$this->assertEquals(self::$accGold, $ret[RechargeGiftDef::ACC_GOLD]);
		$this->assertEquals($hadRewardArr, $ret[RechargeGiftDef::HAD_REWARD]);
		
		// (3.3)正常领取奖励,领取可选择类型的奖励
		$rewardId = key($selectRewardArr);
		$confReqRechargeGold = $confData[$rewardId][RechargeGiftDef::REQ_GOLD];
		$console->addGoldOrder($confReqRechargeGold);
		self::$accGold += $confReqRechargeGold;
		$select = current($selectRewardArr[$rewardId]);
		$ret = $rechargeGift->obtainReward($rewardId, $select);
		$this->assertEquals('ok', $ret);
		Logger::debug('user:%d rechargeGift first gain selectType recharge reward, ret:%s', self::$uid, $ret);
		$hadRewardArr[] = $rewardId;
		$ret = $rechargeGift->getInfo();
		$this->assertEquals($hadRewardArr, $ret[RechargeGiftDef::HAD_REWARD]);
		$this->assertEquals(self::$accGold, $ret[RechargeGiftDef::ACC_GOLD]);
	}
	
	public function test_RechargeGiftOverTime()
	{
		// (4)活动过期重新开一轮的情况,把最后一次领奖时间修改到活动开始时间之前来模拟
		Logger::debug('======%s======', __METHOD__);
		$conf = EnActivity::getConfByName(ActivityName::RECHARGEGIFT);
		$timesStamp = $conf['start_time'] - SECONDS_OF_DAY;
		$info = RechargeGiftDao::getAllInfo(self::$uid);
		$info[RechargeGiftDef::UPDATE_TIME] = $timesStamp;
		RechargeGiftDao::update(self::$uid, $info);
		RechargeGiftManager::release(self::$uid);
		RPCContext::getInstance()->resetSession();
		$rechargeGift = new RechargeGift();
		$ret = $rechargeGift->getInfo();
		$this->assertEquals(array(), $ret[RechargeGiftDef::HAD_REWARD]);
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
				'start_time' => intval($startTime),
				'end_time' => intval($endTime),
				'need_open_time' => intval($needOpenTime),
				'str_data' => $ret['str_data'],
				'va_data' => $ret['va_data'],
		);
		ActivityConfDao::insertOrUpdate($conf);
	
		$ret = ActivityConfLogic::updateMem();
	
		EnActivity::$confBuff = array();
	
		$msg = sprintf('setConf done. name:%s, start:%s, end:%s, needOpenTime:%s, version:%s',
				$name,
				date( 'Ymd H:i:s ', intval($startTime) ),
				date( 'Ymd H:i:s ', intval($endTime) ),
				date( 'Ymd H:i:s ', intval($needOpenTime) ),
				date( 'Ymd H:i:s ', $version ) );
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
	}	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */