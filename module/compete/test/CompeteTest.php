<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteTest.php 76379 2013-11-22 10:44:07Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/test/CompeteTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-22 10:44:07 +0000 (Fri, 22 Nov 2013) $
 * @version $Revision: 76379 $
 * @brief 
 *  
 **/
class CompeteTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 10001;
	protected static $round = 1;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::ROB);
		EnSwitch::getSwitchObj(self::$uid)->save();
	}

	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	protected static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		echo "test user:" . self::$uid . "\n";
	}
	
	protected static function setWeek($isRestTime = FALSE, $w = -1, $offset = 0)
	{	
		$conf = current(btstore_get()->COMPETE->toArray());
		self::$round = $conf[CompeteDef::COMPETE_TEMPLATE_ID];
		$realWeek = Util::getTodayWeek();
		
		if( $w < 0 )
		{
			if ($isRestTime == false) 
			{
				if (in_array($realWeek, $conf[CompeteDef::COMPETE_LAST_TIME])) 
				{
					$w = $realWeek;
				}
				else 
				{
					$count = count($conf[CompeteDef::COMPETE_LAST_TIME]);
					$w = $conf[CompeteDef::COMPETE_LAST_TIME][$count - 1];
				}
			}
			else 
			{
				if (in_array($realWeek, $conf[CompeteDef::COMPETE_REST_TIME]))
				{
					$w = $realWeek;
				}
				else
				{
					$count = count($conf[CompeteDef::COMPETE_REST_TIME]);
					$w = $conf[CompeteDef::COMPETE_REST_TIME][$count - 1];
				}
			}
		}
		if (empty($offset)) 
		{
			$offset = Util::getTime();
		}
		
		$time = $offset + ($w - $realWeek) * SECONDS_OF_DAY;
		$frame = RPCContext::getInstance()->getFramework();
		$frame->initExtern($frame->getGroup(), $frame->getServerIp(), $frame->getLogid()-1, $frame->getDb(), $time);
	
		self::assertEquals($w, Util::getTodayWeek());
		Logger::debug('the day is:%d', $w);
	}

	public function test_getCompeteInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$compete = new Compete();
		$conf = btstore_get()->COMPETE[self::$round];
		$initPoint = $conf[CompeteDef::COMPETE_INIT_POINT];
		
		//设置时间为比武时间
		self::setWeek();
		$ret = $compete->getCompeteInfo();
		Logger::trace('getCompeteInfo info:%s', $ret);
		$this->assertEquals($initPoint, $ret[CompeteDef::COMPETE_POINT]);
		$this->assertTrue($ret['rank'] >= 1);
		$this->assertEquals(1, $ret['state']);
		$this->assertEquals(CompeteConf::COMPETE_RIVAL_NUM, count($ret['rivalList']));
		$this->assertEquals(array(), $ret['rankList']);
		$this->assertEquals(array(), $ret['foeList']);
		
		//设置时间为休息时间
		self::setWeek(true);
		$ret = $compete->getCompeteInfo();
		Logger::trace('getCompeteInfo info:%s', $ret);
		$this->assertEquals(0, $ret[CompeteDef::COMPETE_POINT]);
		$this->assertTrue($ret['rank'] >= 1);
		$this->assertEquals(array(), $ret['rivalList']);
		$this->assertEquals(CompeteConf::COMPETE_RIVAL_NUM, count($ret['rankList']));
		$this->assertEquals(array(), $ret['foeList']);
	}
	
	public function test_refreshRivalList()
	{
		Logger::debug('======%s======', __METHOD__);
		
		//设置时间为比武时间
		self::setWeek();
		$compete = new Compete();
		
		$ret = $compete->getCompeteInfo();
		Logger::trace('getCompeteInfo info:%s', $ret);
		$rivalList1 = Util::arrayExtract($ret['rivalList'], 'uid');
		
		$ret = $compete->refreshRivalList();
		Logger::trace('refreshRivalList info:%s', $ret);
		$rivalList2 = Util::arrayExtract($ret, 'uid');
		$this->assertNotEquals($rivalList1, $rivalList2);
		$this->assertEquals(CompeteConf::COMPETE_RIVAL_NUM, count($ret));
		
		$ret = $compete->getCompeteInfo();
		Logger::trace('getCompeteInfo info:%s', $ret);
		$this->assertTrue($ret['refresh'] >= Util::getTime());
	}
	
	public function test_contest()
	{
		Logger::debug('======%s======', __METHOD__);
		
		//设置时间为比武时间
		self::setWeek();
		$compete = new Compete();
		$conf = btstore_get()->COMPETE[self::$round];
		$subStamina = $conf[CompeteDef::COMPETE_COST_STAMINA];
		
		$ret = $compete->getCompeteInfo();
		$rivalList = Util::arrayExtract($ret['rivalList'], 'uid');
		$pointBefore = $ret['point'];
		$key = rand(0, CompeteConf::COMPETE_RIVAL_NUM - 1);
		$atkedUid = $ret['rivalList'][$key]['uid'];
		$atkedInfo = CompeteDao::select($atkedUid);
		$sucPoint = $conf[CompeteDef::COMPETE_SUC_POINT]
		+ intval(min($conf[CompeteDef::COMPETE_MAX_POINT], $atkedInfo[CompeteDef::COMPETE_POINT] * $conf[CompeteDef::COMPETE_SUC_RATE] / 10000));
		
		$user = EnUser::getUserObj(self::$uid);
		$staminaBefore = $user->getStamina();
		$silverBefore = $user->getSilver();
		$soulBefore = $user->getSoul();
		$goldBefore = $user->getGold();
		$expBefore = $user->getExp();
		
		$ret = $compete->contest($atkedUid);
		$info = CompeteDao::select(self::$uid);
		$isSuc = BattleDef::$APPRAISAL[$ret['atk']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		$addSilver = 0;
		$addSoul = 0;
		$addGold = 0;
		$addPoint = 0;
		if ($isSuc) 
		{
			if (isset($ret['flop']['real']['rob'])) 
			{
				$addSilver = $ret['flop']['real']['rob'];
			}
			if (isset($ret['flop']['real']['silver'])) 
			{
				$addSilver = $ret['flop']['real']['silver'];
			}
			if (isset($ret['flop']['real']['soul'])) 
			{
				$addSoul = $ret['flop']['real']['soul'];
			}
			if (isset($ret['flop']['real']['gold'])) 
			{
				$addGold = $ret['flop']['real']['gold'];
			}
			$addExp = $conf[CompeteDef::COMPETE_SUC_EXP] * $user->getLevel();
			$addPoint = $sucPoint;
			$this->assertNotEquals($rivalList, $info[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST]);
		}
		else 
		{
			$addExp = $conf[CompeteDef::COMPETE_FAIL_EXP] * $user->getLevel();
			$this->assertEquals($rivalList, $info[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST]);
		}
		$this->assertEquals($staminaBefore - $subStamina, $user->getStamina());
		$this->assertEquals($silverBefore + $addSilver, $user->getSilver());
		$this->assertEquals($soulBefore + $addSoul, $user->getSoul());
		$this->assertEquals($goldBefore + $addGold, $user->getGold());
		$this->assertEquals($expBefore + $addExp, $user->getExp());
		$this->assertEquals($pointBefore + $addPoint, $info[CompeteDef::COMPETE_POINT]);
	}
	
	public function test_getRankList()
	{
		Logger::debug('======%s======', __METHOD__);
		
		//设置时间为休息时间
		self::setWeek(true);
		$compete = new Compete();
		$ret = $compete->getRankList();
		Logger::trace('getRankList info:%s', $ret);
		$this->assertEquals(CompeteConf::COMPETE_TOP_TEN, count($ret));
		$point = $ret[0]['point'] + 1;
		$uid = $ret[0]['uid'];
		$info = CompeteDao::select($uid);
		$time = $info[CompeteDef::POINT_TIME];
		foreach ($ret as $userInfo)
		{
			if ($userInfo['point'] < $point)
			{
				continue;
			}
			$info = CompeteDao::select($userInfo['uid']);
			if ($time < $info[CompeteDef::POINT_TIME]) 
			{
				continue;
			}
			if ($uid < $userInfo['uid']) 
			{
				continue;
			}
			$this->assertTrue(false);
			$point = $userInfo['point'];
			$uid = $userInfo['uid'];
			$time = $info[CompeteDef::POINT_TIME];
		}
	}
	
	public function test_generateReward()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$conf = btstore_get()->COMPETE[self::$round];
		$count = count($conf[CompeteDef::COMPETE_LAST_TIME]);
		$day = $conf[CompeteDef::COMPETE_LAST_TIME][$count - 1];
		$now = Util::getTime();
		$date = intval(strftime("%Y%m%d", $now));
		$rewardTime = strtotime($date . " " . CompeteConf::REWARD_START_TIME);
		self::setWeek(false, $day, $rewardTime + 1);
		CompeteLogic::generateReward();
	}
	
	public function test_getSegmentIndex()
	{
		Logger::debug('======%s======', __METHOD__);
		$confPointSeg = array(-1, 100, 200, 300);
		
		$this->assertEquals(0, CompeteLogic::getSegmentIndex(100, $confPointSeg));
		$this->assertEquals(1, CompeteLogic::getSegmentIndex(101, $confPointSeg));
		$this->assertEquals(2, CompeteLogic::getSegmentIndex(300, $confPointSeg));
		$this->assertEquals(3, CompeteLogic::getSegmentIndex(301, $confPointSeg));
	}
	
	public function test_randUsers()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$confPointSeg = array(
				-1,
				100,
				200,
				300,
				400,
				500
		);
		
		//自己段有，但是相邻两段没有
		$arrUidPoint = array(
				1000 => 1,
				1001 => 1,
				1002 => 1,
				1003 => 350,
				);
		$ret = CompeteLogic::randUsers(0, $arrUidPoint, $confPointSeg);
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1003, $ret) );
		
		//低一段有，但本段和高一段没有
		$arrUidPoint = array(
				1000 => 1,
				1001 => 1,
				1002 => 1,
				1003 => 450,
		);
		$ret = CompeteLogic::randUsers(1, $arrUidPoint, $confPointSeg);
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1003, $ret) );
		
		//高一段有，但本段和低一段没有
		$arrUidPoint = array(
				1000 => 250,
				1001 => 250,
				1002 => 250,
				1003 => 350,
		);
		$ret = CompeteLogic::randUsers(1, $arrUidPoint, $confPointSeg);
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1003, $ret) );
		
		//三段分别有
		$arrUidPoint = array(
				1000 => 1,
				1001 => 150,
				1002 => 250,
				1003 => 350,
		);
		$ret = CompeteLogic::randUsers(1, $arrUidPoint, $confPointSeg);
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1003, $ret) );
		
		//三段内不够，选择较低段
		$arrUidPoint = array(
				1000 => 1,
				1001 => 1,
				1002 => 1,
				1003 => 150,
		);
		$ret = CompeteLogic::randUsers(3, $arrUidPoint, $confPointSeg);
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );

		$arrUidPoint = array(
				1000 => 1,
				1001 => 101,
				1002 => 101,
				1003 => 350,
		);
		$ret = CompeteLogic::randUsers(3, $arrUidPoint, $confPointSeg);		
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1000, $ret) );
		
		//三段内不够，选择较高段
		$arrUidPoint = array(
				1000 => 1,
				1001 => 101,
				1002 => 101,
				1003 => 350,
		);
		$ret = CompeteLogic::randUsers(1, $arrUidPoint, $confPointSeg);		
		$this->assertEquals( CompeteConf::COMPETE_RIVAL_NUM, count($ret) );
		$this->assertTrue( !in_array(1003, $ret) );
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */