<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Moon.test.php 172199 2015-05-11 09:36:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/test/Moon.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-11 09:36:41 +0000 (Mon, 11 May 2015) $
 * @version $Revision: 172199 $
 * @brief 
 *  
 **/
 
class MoonTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;

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

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(10000);
		
		// 加点天工令
		EnUser::getUserObj(self::$uid)->addTgNum(10000);
		EnUser::getUserObj(self::$uid)->update();

		var_dump(self::$uid);
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
	
	public function test_switch()
	{
		// 功能节点还没有打开
		try
		{
			$moon = new Moon();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 根据需要的等级，打开switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::MOON]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
	}

	public function test_getMoonInfo()
	{		
		// 验证初始化数值的正确性
		$moon = new Moon();
		$ret = $moon->getMoonInfo();
		//var_dump($ret);
		$this->assertEquals(EnUser::getUserObj(self::$uid)->getTgNum(), $ret['tg_num']);
		$this->assertEquals(0, MoonObj::getInstance(self::$uid)->getBuyNum());
		$this->assertEquals(0, MoonObj::getInstance(self::$uid)->getMaxPassCopy());
		$this->assertEquals(1, MoonObj::getInstance(self::$uid)->getCurrCopy());
		$this->assertEquals(intval(btstore_get()->MOON_RULE['default_atk_num']), MoonObj::getInstance(self::$uid)->getAtkNum());
		$gridInfo = array();
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			if ($i == intval(btstore_get()->MOON_COPY[1]['default_open_grid'])) 
			{
				$gridInfo[$i] = MoonGridStatus::UNLOCK;
			}
			else 
			{
				$gridInfo[$i] = MoonGridStatus::LOCK;
			}
		}
		$this->assertEquals($gridInfo, MoonObj::getInstance(self::$uid)->getGridInfo());
	}
	
	public function test_dealGrid()//包含attackMonster和openBox，这两个底层调用同一个函数
	{
		// 当前副本是第1个副本，攻打第2个副本，抛FAKE
		try 
		{
			$moon = new Moon();
			$ret = $moon->attackMonster(2, 1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 一个无效的grid
		try
		{
			$moon = new Moon();
			$ret = $moon->attackMonster(1, MoonConf::MAX_GRID_NUM + 1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('inter', $e->getMessage());
		}
		
		// 当前副本的这个格子没有解锁，处于lock阶段，应该抛fake
		$aDefaultLockGrid = 0;
		$defaultOpenGrid = intval(btstore_get()->MOON_COPY[1]['default_open_grid']);
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			if ($i != $defaultOpenGrid) 
			{
				$aDefaultLockGrid = $i;
				break;
			}
		}
		try
		{
			$moon = new Moon();
			$ret = $moon->attackMonster(1, $aDefaultLockGrid);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 这个格子是怪，才能调用attackBoss
		if (btstore_get()->MOON_COPY[1]['grid'][$defaultOpenGrid]['type'] == MoonGridType::MONSTER) 
		{
			printf("**************default open grid is monster**************\n");
			// 是个怪，却调用openBox，格子类型不符合
			try
			{
				$moon = new Moon();
				$ret = $moon->openBox(1, $defaultOpenGrid);
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
			
			$moon = new Moon();
			$ret = $moon->attackMonster(1, $defaultOpenGrid);
			var_dump($ret);
			
			$kill = BattleDef::$APPRAISAL[$ret['appraise']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
			if ($kill) 
			{
				printf("**************kill monster, yes**************\n");
				//var_dump(MoonObj::getInstance(self::$uid)->getGridInfo());
				
				// 开启的格子对不对
				$shouldOpenGrid = MoonConf::$UNLOCK_GRID[$defaultOpenGrid];
				$this->assertEquals($shouldOpenGrid, $ret['open_grid']);
				
				// 只打赢了1个格子，肯定没有开启BOSS
				$this->assertEquals(0, $ret['open_boss']);
				
				// 验证各个格子的状态
				for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
				{
					if ($i == $defaultOpenGrid) 
					{
						$this->assertEquals(MoonGridStatus::DONE, MoonObj::getInstance(self::$uid)->gridStatus($i));
					}
					else if (in_array($i, $ret['open_grid'])) 
					{
						$this->assertEquals(MoonGridStatus::UNLOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
					}
					else 
					{
						$this->assertEquals(MoonGridStatus::LOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
					}
				}
				
				// 已经攻打过这个格子啦，再攻打一次，应该抛fake
				try
				{
					$moon = new Moon();
					$ret = $moon->attackMonster(1, $defaultOpenGrid);
					$this->assertTrue(FALSE);
				}
				catch (Exception $e)
				{
					$this->assertEquals('fake', $e->getMessage());
				}
			}
			else 
			{
				printf("**************killed by monster, no**************\n");
				
				// 打怪失败啦，没开启新格子，没开启BOSS
				$this->assertEquals(0, $ret['open_boss']);
				$this->assertEquals(array(), $ret['open_grid']);
				
				// 验证各个格子的状态
				for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
				{
					if ($i == $defaultOpenGrid) 
					{
						$this->assertEquals(MoonGridStatus::UNLOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
					}
					else 
					{
						$this->assertEquals(MoonGridStatus::LOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
					}
				}
			}
		}
		else // 这个格子是个宝箱，不能调用attackBoss
		{
			printf("**************default open grid is box**************\n");
			
			// 是个箱子，你却要打怪，格子类型不符合
			try
			{
				$moon = new Moon();
				$ret = $moon->attackMonster(1, $defaultOpenGrid);
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
				
			$moon = new Moon();
			$ret = $moon->openBox(1, $defaultOpenGrid);
			var_dump($ret);
			
			// 和打怪不一样，开箱子肯定能成功，肯定没有开启BOSS，开启新的格子
			$shouldOpenGrid = MoonConf::$UNLOCK_GRID[$defaultOpenGrid];
			$this->assertEquals($shouldOpenGrid, $ret['open_grid']);
			
			// 只打赢了1个格子，肯定没有开启BOSS
			$this->assertEquals(0, $ret['open_boss']);
			
			// 验证各个格子的状态
			for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
			{
				if ($i == $defaultOpenGrid)
				{
					$this->assertEquals(MoonGridStatus::DONE, MoonObj::getInstance(self::$uid)->gridStatus($i));
				}
				else if (in_array($i, $ret['open_grid']))
				{
					$this->assertEquals(MoonGridStatus::UNLOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
				}
				else
				{
					$this->assertEquals(MoonGridStatus::LOCK, MoonObj::getInstance(self::$uid)->gridStatus($i));
				}
			}
			
			// 已经开过这个格子啦，再开一次，应该抛fake
			try
			{
				$moon = new Moon();
				$ret = $moon->attackMonster(1, $defaultOpenGrid);
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
		}		
	}
	
	public function test_attackBoss()
	{
		// 攻击一个没有解锁的副本的BOSS,抛FAKE
		try
		{
			$moon = new Moon();
			$ret = $moon->attackBOSS(2);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 攻打当前副本的BOSS，但是有格子还没处理完，抛FAKE
		try
		{
			$moon = new Moon();
			$ret = $moon->attackBOSS(1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 把通关副本从0变1，代表第1个副本已经通关，当前攻打的副本是第2个,并且把当前副本的9个格子都设置为done
		$moonObj = MoonObj::getInstance(self::$uid);
		$moonObj->setMaxPassCopyForConsole(1);
		$allDoneInfo = array();
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			$allDoneInfo[$i] = MoonGridStatus::DONE;
		}
		$moonObj->setGridInfoForConsole($allDoneInfo);
		$moonObj->update();

		// 验证攻打已经通关的副本的BOSS的各种情况
		$moon = new Moon();
		$ret = $moon->attackBOSS(1);
		$kill = BattleDef::$APPRAISAL[$ret['appraise']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
		if ($kill) // 击杀了已经通关副本的BOSS，应该扣次数，加奖励等
		{
			printf("**************kill pass boss, yes**************\n");
			var_dump($ret);
			$this->assertEquals(intval(btstore_get()->MOON_RULE['default_atk_num']) - 1, $moonObj->getAtkNum());
			$this->assertEquals(0, $ret['open_copy']);
		}
		else	// 没有击杀 已经通关副本的BOSS，不扣次数，没奖励
		{
			printf("**************killed by pass boss, no**************\n");
			var_dump($ret);
			$this->assertEquals(intval(btstore_get()->MOON_RULE['default_atk_num']), $moonObj->getAtkNum());
			$this->assertEquals(array(), $ret['drop']);
			$this->assertEquals(0, $ret['open_copy']);
		}
		
		// 攻打当前副本BOSS的各种情况
		$beforeAtkNum = MoonObj::getInstance(self::$uid)->getAtkNum();
		$moon = new Moon();
		$ret = $moon->attackBOSS(2);
		$kill = BattleDef::$APPRAISAL[$ret['appraise']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
		if ($kill) // 击杀了当前副本的BOSS，不扣次数，加奖励等
		{
			printf("**************kill curr boss, yes**************\n");
			var_dump($ret);
			$this->assertEquals($beforeAtkNum, $moonObj->getAtkNum());
			$this->assertEquals(3, $ret['open_copy']);
			$this->assertEquals(3, MoonObj::getInstance(self::$uid)->getCurrCopy());
			$this->assertEquals(2, MoonObj::getInstance(self::$uid)->getMaxPassCopy());
			$gridInfo = MoonObj::getInstance(self::$uid)->getGridInfo();
			foreach ($gridInfo as $index => $status)
			{
				if ($index == intval(btstore_get()->MOON_COPY[3]['default_open_grid'])) 
				{
					$this->assertEquals(MoonGridStatus::UNLOCK, $status);
				}
				else 
				{
					$this->assertEquals(MoonGridStatus::LOCK, $status);
				}
			}
		}
		else	// 没有击杀已经通关副本的BOSS，不扣次数，没奖励
		{
			printf("**************killed by curr boss, no**************\n");
			var_dump($ret);
			$this->assertEquals($beforeAtkNum, $moonObj->getAtkNum());
			$this->assertEquals(array(), $ret['drop']);
			$this->assertEquals(0, $ret['open_copy']);
		}
		
		// 把通关副本从0变1，代表第1个副本已经通关，当前攻打的副本是第2个,并且把当前副本的9个格子都设置为done
		$moonObj = MoonObj::getInstance(self::$uid);
		$moonObj->setMaxPassCopyForConsole(1);
		$allDoneInfo = array();
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			$allDoneInfo[$i] = MoonGridStatus::DONE;
		}
		$moonObj->setGridInfoForConsole($allDoneInfo);
		$moonObj->update();
		
		// 设置攻击次数为0，再攻击BOSS，抛fake
		$moonObj->setAtkNumForConsole(0);
		try
		{
			$moon = new Moon();
			$ret = $moon->attackBOSS(1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
						
		// 验证最后一个副本通关的特殊情况
		$allCopyConf = btstore_get()->MOON_COPY->toArray();
		$maxCopy = 0;
		foreach ($allCopyConf as $copyId => $info)
		{
			if ($copyId > $maxCopy) 
			{
				$maxCopy = $copyId;
			}
		}
		$moonObj = MoonObj::getInstance(self::$uid);
		$moonObj->setMaxPassCopyForConsole($maxCopy - 1);
		$allDoneInfo = array();
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			$allDoneInfo[$i] = MoonGridStatus::DONE;
		}
		$moonObj->setGridInfoForConsole($allDoneInfo);
		$moonObj->setAtkNumForConsole(5);
		$moonObj->update();
		
		$moon = new Moon();
		$ret = $moon->attackBOSS($maxCopy);
		var_dump($ret);
		$kill = BattleDef::$APPRAISAL[$ret['appraise']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
		if ($kill) // 最后一个副本的BOSS也被击杀啦
		{
			printf("**************kill last boss, yes**************\n");
			var_dump($ret);
			$this->assertEquals(0, $ret['open_copy']);//已经是最后一个副本啦，不开启新的
			$this->assertEquals($maxCopy, MoonObj::getInstance(self::$uid)->getCurrCopy());
			$this->assertEquals($maxCopy, MoonObj::getInstance(self::$uid)->getMaxPassCopy());
			$gridInfo = MoonObj::getInstance(self::$uid)->getGridInfo();
			$this->assertEquals(array(), $gridInfo);
		}
		else 
		{
			printf("**************killed by last boss, no**************\n");
		}
	}
	
	public function test_addAttackNum()
	{
		// 重置购买次数为0
		$moonObj = MoonObj::getInstance(self::$uid);
		$moonObj->setBuyNumForConsole(0);
		$moonObj->update();
		$beforeAtkNum = $moonObj->getAtkNum();
		
		// 购买1次，购买次数和攻击次数都增加1
		$moon = new Moon();
		$ret = $moon->addAttackNum();
		//var_dump($ret);
		$this->assertEquals(1, $moonObj->getBuyNum());
		$this->assertEquals($beforeAtkNum + 1, $moonObj->getAtkNum());
		
		// 购买次数超限的情况
		$buyLimit = MoonUtil::getBuyLimit();
		for ($i = 0; $i < $buyLimit - 1; ++$i) 
		{
			$moon = new Moon();
			$ret = $moon->addAttackNum();
			//var_dump($ret);
			$this->assertEquals('ok', $ret);
		}
		try
		{
			$moon = new Moon();
			$ret = $moon->addAttackNum();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_shop()
	{
		// 玩家第一次进入商店
		$moon = new Moon();
		$ret = $moon->getShopInfo();
		var_dump($ret);
		$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret['goods_list']));
		$this->assertEquals(0, $ret['refresh_num']);
		
		// 刷新1次
		$curGold = EnUser::getUserObj(self::$uid)->getGold();
		$costGold = intval(current(btstore_get()->MOON_SHOP['usr_refresh_cost']->toArray()));
		$moon = new Moon();
		$ret2 = $moon->refreshGoodsList();
		//var_dump($ret2);
		$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret2['goods_list']));
		$this->assertEquals(1, $ret2['refresh_num']);//玩家刷新了1次
		$this->assertEquals($curGold - $costGold, EnUser::getUserObj(self::$uid)->getGold());
		//$this->assertNotEquals($ret2['goods_list'], $ret['goods_list']);//极小概率刷完还相同
		
		// 验证刷新次数达到上限
		$refreshLimit = intval(btstore_get()->VIP[EnUser::getUserObj(self::$uid)->getVip()]['tgShopRefreshLimit']);
		for ($i = 1; $i < $refreshLimit; ++$i)
		{
			$moon = new Moon();
			$ret = $moon->refreshGoodsList();
			//var_dump($ret);
			$this->assertEquals(intval(btstore_get()->MOON_SHOP['rand_num']), count($ret['goods_list']));
			$this->assertEquals($i + 1, $ret['refresh_num']);
		}
		try
		{
			$moon = new Moon();
			$moon->refreshGoodsList();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
				
		// 购买第一个商品
		$firstGoodsId = key($ret['goods_list']);
		$firstGoodsNum = current($ret['goods_list']);
		$moon = new Moon();
		$ret = $moon->buyGoods($firstGoodsId);
		//var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);
		
		$moon = new Moon();
		$ret = $moon->getShopInfo();
		//var_dump($ret);
		$this->assertEquals($firstGoodsNum - 1, $ret['goods_list'][$firstGoodsId]);
	}
	
	public function testShopBox()
	{
		// 购买一次
		$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
		$moon = new Moon();
		$ret = $moon->buyBox();
		var_dump($ret);
		$this->assertEquals(1, MoonObj::getInstance(self::$uid)->getBoxNum());
		$afterGold = EnUser::getUserObj(self::$uid)->getGold();
		$this->assertEquals(intval(btstore_get()->MOON_RULE['box_cost'][1]), $beforeGold - $afterGold);
		
		// 购买知道上限
		$maxOpenNum = MoonUtil::getBuyBoxLimit();
		for ($i = 2; $i <= $maxOpenNum; ++$i)
		{
			$beforeGold = EnUser::getUserObj(self::$uid)->getGold();
			$moon = new Moon();
			$ret = $moon->buyBox();
			var_dump($ret);
			$this->assertEquals($i, MoonObj::getInstance(self::$uid)->getBoxNum());
			$afterGold = EnUser::getUserObj(self::$uid)->getGold();
			$this->assertEquals(intval(btstore_get()->MOON_RULE['box_cost'][$i]), $beforeGold - $afterGold);
		}
		
		// 在购买就会抛fake，次数超限
		try
		{
			$moon = new Moon();
			$moon->buyBox();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */