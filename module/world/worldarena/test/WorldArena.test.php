<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArena.test.php 183585 2015-07-10 09:16:27Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/test/WorldArena.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-10 09:16:27 +0000 (Fri, 10 Jul 2015) $
 * @version $Revision: 183585 $
 * @brief 
 *  
 **/
 
class WorldArenaTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $pid = 0;
	private static $serverId = 0;
	private static $arrOtherUserInfo = array();

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('mbg' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];
		self::$pid = $pid;
		self::$serverId = Util::getServerId();

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(10000000);
		$console->silver(10000000);

		var_dump(self::$uid);
		var_dump(self::$pid);
		var_dump(self::$serverId);
	}

	protected function setUp()
	{
		parent::setUp();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	protected static function getPrivateMethod($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}
	
	public function setTestStage($stage)
	{
		// 设置测试的test_mode
		$hour = date('H', Util::getTime());
		WorldArenaConf::$TEST_MODE = $hour % 2 ? 1 : 2;
		
		// 设置测试的偏移量test_offset
		if ($stage == WorldArenaDef::STAGE_TYPE_BEFORE_SIGNUP) 
		{
			WorldArenaConf::$TEST_OFFSET = array(3600,3601,3602,3603);
		}
		else if ($stage == WorldArenaDef::STAGE_TYPE_SIGNUP) 
		{
			WorldArenaConf::$TEST_OFFSET = array(0,3600,3601,3602);
		}
		else if ($stage == WorldArenaDef::STAGE_TYPE_RANGE_ROOM)
		{
			WorldArenaConf::$TEST_OFFSET = array(0,1,3600,3601);
		}
		else if ($stage == WorldArenaDef::STAGE_TYPE_ATTACK)
		{
			WorldArenaConf::$TEST_OFFSET = array(0,1,2,3600);
		}
		else if ($stage == WorldArenaDef::STAGE_TYPE_REWARD)
		{
			WorldArenaConf::$TEST_OFFSET = array(0,1,2,3);
		}
	}
	
	public function sighUp($count)
	{
		for ($i = 0; $i < $count; ++$i)
		{
			$pid = IdGenerator::nextId('uid');
			$uname = strval('mbg' . $pid);
			$ret = UserLogic::createUser($pid, 1, $uname);
			if($ret['ret'] != 'ok')
			{
				echo "create user failed\n";
				exit();
			}
			$uid = $ret['uid'];
			self::$arrOtherUserInfo[] = array($pid, $uid);
			
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $uid);
			$needLv = WorldArenaConfObj::getInstance()->getNeedLevel();
			$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
			$userObj = EnUser::getUserObj($uid);
			$userObj->addExp($expTable[$needLv]);
			$userObj->update();
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->signUp();
			$this->assertTrue(is_int($ret));
			WorldArenaCrossUserObj::releaseInstance(self::$serverId, $pid);
			WorldArenaInnerUserObj::releaseInstance(self::$serverId, $pid);
			printf("uid[%d] pid[%d] sign up\n", $uid, $pid);
		}
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	
	public function test_before_signUp_stage()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_BEFORE_SIGNUP);
	
		// before_signup阶段调用getWorldArenaInfo接口
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		var_dump($ret);
		
		$this->assertEquals(WorldArenaDef::STAGE_TYPE_BEFORE_SIGNUP, $ret['stage']);
		$this->assertEquals(0, $ret['signup_time']);//没报名呢，报名时间为0
		$this->assertEquals(0, $ret['room_id']);//没分房呢，房间号为0
		$this->assertEquals(array(), $ret['extra']);//这个阶段extra应该没有东西
	}
	
	public function test_signUp_stage()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_SIGNUP);
		
		// signup阶段还没有报名的情况下调用getWorldArenaInfo接口
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		var_dump($ret);
		
		$this->assertEquals(WorldArenaDef::STAGE_TYPE_SIGNUP, $ret['stage']);
		$this->assertEquals(0, $ret['signup_time']);//没报名呢，报名时间为0
		$this->assertEquals(0, $ret['room_id']);//没分房呢，房间号为0
		$this->assertEquals(array('update_fmt_time' => 0), $ret['extra']);//这个阶段extra应该有东西，是更新战斗信息时间，因为还没有报名，是0
		
		// 验证玩家等级不够时候，报名抛fake
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->signUp();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 验证玩家在没有报名的情况下更新战斗信息，抛fake
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->updateFmt();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 将玩家设置到对应的等级
		$needLv = WorldArenaConfObj::getInstance()->getNeedLevel();
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->signUp();
		var_dump($ret);
		$this->assertTrue(is_int($ret));
		$signUpTime = $ret;
		
		// signup阶段已经报名的情况下调用getWorldArenaInfo接口
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		var_dump($ret);
		
		$this->assertEquals(WorldArenaDef::STAGE_TYPE_SIGNUP, $ret['stage']);
		$this->assertEquals($signUpTime, $ret['signup_time']);//已经报名啦，报名的时间和返回的应该一致
		$this->assertEquals(0, $ret['room_id']);//没分房呢，房间号为0
		$this->assertEquals(array('update_fmt_time' => 0), $ret['extra']);//这个阶段extra应该有东西，是更新战斗信息时间，已经报名啦
		
		// 验证玩家已经报名的情况下，再报名抛fake
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->signUp();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 先更新下战斗数据
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->updateFmt();
		var_dump($ret);
		
		// 玩家正常更新战斗信息，刚更新完，已经有cd啦，再主动更新会有cd
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->updateFmt();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 清除cd以后再主动更新战斗信息，应该没有问题啦
		$myInnerObj = WorldArenaInnerUserObj::getInstance(Util::getServerId(), self::$pid, self::$uid);
		$myInnerObj->setUpdateFmtTimeForConsole(0);
		$myInnerObj->update();
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->updateFmt();
		var_dump($ret);
		$this->assertTrue(is_int($ret));
		$updateFmtTime = $ret;
		
		// signup阶段已经更新了一次战斗力的情况下调用getWorldArenaInfo接口
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		var_dump($ret);
		
		$this->assertEquals(WorldArenaDef::STAGE_TYPE_SIGNUP, $ret['stage']);
		$this->assertEquals($signUpTime, $ret['signup_time']);//已经报名啦，报名的时间和返回的应该一致
		$this->assertEquals(0, $ret['room_id']);//没分房呢，房间号为0
		$this->assertEquals(array('update_fmt_time' => $updateFmtTime), $ret['extra']);//这个阶段extra应该有东西，是更新战斗信息时间，已经报名啦，报名时间就是更新战斗信息时间
		
		// 验证各个基础信息是否已经更新上去啦
		$myCrossObj = WorldArenaCrossUserObj::getInstance(Util::getServerId(), self::$pid, self::$uid, WorldArenaUtil::getTeamIdByServerId(Util::getServerId()));
		$userObj = EnUser::getUserObj(self::$uid);
		$this->assertEquals($myCrossObj->getUname(), $userObj->getUname());
		$this->assertEquals($myCrossObj->getVip(), $userObj->getVip());
		$this->assertEquals($myCrossObj->getLevel(), $userObj->getLevel());
		$this->assertEquals($myCrossObj->getFightForce(), $userObj->getFightForce());
		$this->assertEquals($myCrossObj->getHtid(), $userObj->getHeroManager()->getMasterHeroObj()->getHtid());
		$this->assertEquals($myCrossObj->getDress(), $userObj->getDressInfo());
		WorldArenaCrossUserObj::releaseInstance(self::$serverId, self::$pid);
		
		// 再创建一些玩家让报名
		$this->sighUp(2);
	}
	
	public function test_range_room_stage()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_RANGE_ROOM);
		WorldArenaScriptLogic::rangeRoom();
	}
	
	public function test_attack_stage_attack()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		$confObj = WorldArenaConfObj::getInstance();
		
		// signup阶段已经更新了一次战斗力的情况下调用getWorldArenaInfo接口
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		var_dump($ret);
		$this->assertEquals(WorldArenaDef::STAGE_TYPE_ATTACK, $ret['stage']);
		$this->assertTrue($ret['room_id'] > 0);
		$this->assertEquals($confObj->getFreeAtkNum(), $ret['extra']['atk_num']);
		$this->assertEquals(0, $ret['extra']['buy_atk_num']);
		$this->assertEquals(0, $ret['extra']['silver_reset_num']);
		$this->assertEquals(0, $ret['extra']['gold_reset_num']);
		$this->assertEquals(0, $ret['extra']['kill_num']);
		$this->assertEquals(0, $ret['extra']['cur_conti_num']);
		$this->assertEquals(0, $ret['extra']['max_conti_num']);
		$this->assertTrue(isset($ret['extra']['player']));
		
		// 取任意一个对手，打一架
		if (!empty($ret['extra']['player'])) 
		{
			$allPlayerInfo = $ret['extra']['player'];
			foreach ($allPlayerInfo as $aPlayer)
			{
				if ($aPlayer[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID] != self::$serverId 
					|| $aPlayer[WorldArenaCrossUserField::TBL_FIELD_PID] != self::$pid) 
				{
					$targetServerId = $aPlayer[WorldArenaCrossUserField::TBL_FIELD_SERVER_ID];
					$targetPid = $aPlayer[WorldArenaCrossUserField::TBL_FIELD_PID];
					$worldArenaObj = new WorldArena();
					$ret = $worldArenaObj->attack($targetServerId, $targetPid, 0);
					printf("my tag\n");
					var_dump($ret);
					
					if ($ret['ret'] == 'protect') 
					{
						printf('in protect, skip');
					}
					else if ($ret['ret'] == 'out_range') 
					{
						printf('in out_range, impossible');
						$this->assertFalse(TRUE);
					}
					else // 正常的攻打完以后的判断 
					{
						$this->assertEquals($confObj->getFreeAtkNum() - 1, $ret['atk_num']);
						$this->assertEquals(0, $ret['buy_atk_num']);
						$this->assertEquals(0, $ret['silver_reset_num']);
						$this->assertEquals(0, $ret['gold_reset_num']);
						$kill = BattleDef::$APPRAISAL[$ret['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
						if ($kill) 
						{
							$this->assertEquals(1, $ret['kill_num']);
							$this->assertEquals(1, $ret['cur_conti_num']);
							$this->assertEquals(1, $ret['max_conti_num']);
							$this->assertTrue(isset($ret['player']));
							
							$myInnerObj = WorldArenaInnerUserObj::getInstance(self::$serverId, self::$pid, self::$uid);
							$arrInherit = $myInnerObj->getHpInfoForConsole();
							var_dump($arrInherit);
							$arrFmt = $myInnerObj->getFmt(WorldArenaDef::OFFSET_ONE);
							var_dump($arrFmt);
							foreach ($arrFmt['arrHero'] as $pos => $aInfo)
							{
								$orginHid = WorldArenaUtil::reChangeID($aInfo['hid']);
								$this->assertEquals($aInfo['currHp'], $arrInherit[$orginHid][0]);
								$this->assertEquals($aInfo['currRage'], $arrInherit[$orginHid][1]);
							}
						}
						else 
						{
							$this->assertEquals(0, $ret['kill_num']);
							$this->assertEquals(0, $ret['cur_conti_num']);
							$this->assertEquals(0, $ret['max_conti_num']);
							$this->assertTrue(isset($ret['player']));
						}
					}
					
					break;
				}
			}
		}
	}
	
	public function test_attack_stage_buyAtkNum()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		
		$confObj = WorldArenaConfObj::getInstance();
		
		// 获得买次数前的次数
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		$beforeAtkNum = $ret['extra']['atk_num'];
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
	
		// 买一次，验证返回值是否正确
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->buyAtkNum(1);
		var_dump($ret);
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$this->assertEquals($beforeAtkNum + 1, $ret);
		$this->assertEquals($afterGold + $confObj->getBuyAtkCost(1), $beforeGold);
		
		// 验证拉info信息时候是否正确
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		$afterAtkNum = $ret['extra']['atk_num'];
		$this->assertEquals($beforeAtkNum + 1, $afterAtkNum);
		$this->assertEquals(1, $ret['extra']['buy_atk_num']);
		
		// 设置购买次数已经到达上限
		$myInnerObj = WorldArenaInnerUserObj::getInstance(self::$serverId, self::$pid, self::$uid);
		$myInnerObj->setBuyAtkNumForConsole($confObj->getMaxBuyAtkNum());
		$myInnerObj->update();
		
		// 已经达到上限，无法购买
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->buyAtkNum(1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_attack_stage_reset()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		
		// 重置前的次数和金币银币
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		$beforeSilverResetNum = $ret['extra']['silver_reset_num'];
		$beforeGoldResetNum = $ret['extra']['silver_reset_num'];
		$beforeSilver = EnUser::getUserObj(self::$uid)->getSilver();
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
	
		// 金币重置
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->reset(WorldArenaDef::RESET_TYPE_GOLD);
		var_dump($ret);
	
		// 银币重置
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->reset(WorldArenaDef::RESET_TYPE_SILVER);
		var_dump($ret);
		
		// 重置完次数
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getWorldArenaInfo();
		$afterSilverResetNum = $ret['extra']['silver_reset_num'];
		$afterGoldResetNum = $ret['extra']['silver_reset_num'];
		$afterSilver = EnUser::getUserObj(self::$uid)->getSilver();
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		
		// 验证一下
		$confObj = WorldArenaConfObj::getInstance();
		$this->assertEquals($afterGoldResetNum, $beforeGoldResetNum + 1);
		$this->assertEquals($afterSilverResetNum, $beforeSilverResetNum + 1);
		$this->assertEquals($afterGold + $confObj->getResetCost(WorldArenaDef::RESET_TYPE_GOLD, 1), $beforeGold);
		$this->assertEquals($afterSilver + $confObj->getResetCost(WorldArenaDef::RESET_TYPE_SILVER, 1), $beforeSilver);
		
		// 两种重置次数都设置为最大
		$myInnerObj = WorldArenaInnerUserObj::getInstance(self::$serverId, self::$pid, self::$uid, FALSE);
		$myInnerObj->setSilverResetNumForConsole($confObj->getMaxResetNum(WorldArenaDef::RESET_TYPE_SILVER));
		$myInnerObj->setGoldResetNumForConsole($confObj->getMaxResetNum(WorldArenaDef::RESET_TYPE_GOLD));
		$myInnerObj->update();
		
		// 已经达到上限，无法金币重置
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->reset(WorldArenaDef::RESET_TYPE_GOLD);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 已经达到上限，无法银币重置
		try
		{
			$worldArenaObj = new WorldArena();
			$ret = $worldArenaObj->reset(WorldArenaDef::RESET_TYPE_SILVER);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_attack_stage_getRecordList()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getRecordList();
		var_dump($ret);
	}
	
	public function test_attack_stage_getRankList()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getRankList();
		var_dump($ret);
	}
	
	public function test_attack_stage_getRecord()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_ATTACK);
		
		$worldArenaObj = new WorldArena();
		$ret = $worldArenaObj->getRecordList();
		if (!empty($ret['my'][0])) 
		{
			$brid = $ret['my'][0]['brid'];
			$battleObj = new Battle();
			$ret = $battleObj->getRecord($brid);
			var_dump($ret);
		}
		else 
		{
			printf('maybe in protect, sikp getRecord test');
		}
	}
	
	public function test_reward_stage()
	{
		$this->setTestStage(WorldArenaDef::STAGE_TYPE_REWARD);
		
		WorldArenaScriptLogic::reward(FALSE);
	}
	
	public function test_getTargetRank()
	{
		$ret = WorldArenaUtil::getTargetRank(1);
		$this->assertEquals(array(2,3,4), $ret);
		
		$ret = WorldArenaUtil::getTargetRank(2);
		$this->assertEquals(array(1,3,4), $ret);
		
		$ret = WorldArenaUtil::getTargetRank(3);
		$this->assertEquals(array(1,2,4), $ret);
		
		$ret = WorldArenaUtil::getTargetRank(4);
		$this->assertEquals(array(1,2,3), $ret);
		
		$ret = WorldArenaUtil::getTargetRank(5);
		//var_dump($ret);
		
		$ret = WorldArenaUtil::getTargetRank(100);
		//var_dump($ret);
		
		$ret = WorldArenaUtil::getTargetRank(200);
		//var_dump($ret);
		
		$ret = WorldArenaUtil::getTargetRank(1000);
		//var_dump($ret);
		
		$ret = WorldArenaUtil::getTargetRank(10000);
		//var_dump($ret);
		
		$ret = WorldArenaUtil::getTargetRank(100000);
		//var_dump($ret);
	}
	
	public function test_SBSBSB()
	{
		$arrPid = array(20140332,2653658636,39169821,567,20140325,2155,40000811,3009096,40000795,37086122,111,77045,77047,77049,77051,77053,77055,77057,77059,77061,77063,77065,77067,77069,77071,77073,77075,77077,77079,77081,77083);
		foreach ($arrPid as $aPid)
		{
			try 
			{
				$aInfo = UserLogic::getUsers($aPid);
				$aUid = $aInfo[0]['uid'];
				RPCContext::getInstance()->resetSession();
				RPCContext::getInstance()->setSession('global.uid', $aUid);
				$worldArenaObj = new WorldArena();
				$ret = $worldArenaObj->signUp();
			} 
			catch (Exception $e) 
			{
			}
		}
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */