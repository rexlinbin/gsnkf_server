<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopy.test.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/test/GuildCopy.test.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/
 
class GuildCopyTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $guildId = 0;
	private static $arrMemberUid = array();
	
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
		
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[90]);
		$userObj->update();
		
		$ret = GuildLogic::createGuild(self::$uid, 'x'.$pid, TRUE, '', '', '');
		if ($ret['ret'] != 'ok') 
		{
			echo "create guild failed\n";
			exit();
		}
		self::$guildId = $ret['info']['guild_id'];
		
		//$console->setGuildLevel(1, 15);
		
		for ($i = 0; $i < 10; ++$i)
		{
			$pid = IdGenerator::nextId('uid');
			$uname = strval('mbg' . $pid);
			$ret = UserLogic::createUser($pid, 1, $uname);
			if($ret['ret'] != 'ok')
			{
				echo "create user failed\n";
				exit();
			}
			self::$arrMemberUid[] = $ret['uid'];
			usleep(10);
		}
		
		foreach (self::$arrMemberUid as $aUid)
		{
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aUid);
			$ret = GuildLogic::applyGuild($aUid, self::$guildId);
			if ($ret != 'ok')
			{
				echo "apply join guild failed\n";
				exit();
			}
			
			$console = new Console();
			$console->gold(10000);
		}
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		foreach (self::$arrMemberUid as $aUid)
		{
			$ret = GuildLogic::agreeApply(self::$uid, $aUid);
			if ($ret != 'ok')
			{
				echo "agree join guild failed\n";
				exit();
			}
		}
		
		var_dump(self::$uid);
		var_dump(self::$guildId);
		var_dump(self::$arrMemberUid);
		
		self::updateJoinGuildTime(self::$arrMemberUid, self::$guildId, Util::getTime() - 1);
	}
	
	/**
	 * 修改军团加入军团的时间，方便调试
	 * 
	 * @param array $arrMember
	 * @param int $guildId
	 * @param int $joinTime
	 */
	public static function updateJoinGuildTime($arrMember, $guildId, $joinTime)
	{
		$arrCond = array
		(
				array('uid', 'IN', $arrMember ),
				array('guild_id', '=', $guildId),
				array('record_type', '=', GuildRecordType::JOIN_GUILD),
		);
		
		$data = new CData();
		$data->select(array('grid', 'uid'))->from(GuildDef::TABLE_GUILD_RECORD);
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$arrInfo = $data->query();
		$arrInfo = Util::arrayIndex($arrInfo, 'uid');
				
		foreach ($arrMember as $aMemberUid)
		{
			$data = new CData();
			$data->update(GuildDef::TABLE_GUILD_RECORD)->set(array('record_time' => $joinTime))->where(array('grid', '=', intval($arrInfo[$aMemberUid]['grid'])));
			$data->query();
		}
	}
	
	public function addMember($count)
	{
		$arrNewMember = array();
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
			self::$arrMemberUid[] = $ret['uid'];
			$arrNewMember[] = $ret['uid'];
			usleep(10);
		}
		
		foreach ($arrNewMember as $aUid)
		{
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aUid);
			$ret = GuildLogic::applyGuild($aUid, self::$guildId);
			if ($ret != 'ok')
			{
				echo "apply join guild failed\n";
				exit();
			}
		}
		
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		foreach ($arrNewMember as $aUid)
		{
			$ret = GuildLogic::agreeApply(self::$uid, $aUid);
			if ($ret != 'ok')
			{
				echo "agree join guild failed\n";
				exit();
			}
		}
		
		return $arrNewMember;
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

	public function test_randCountryType()
	{
		// 测试生成随机国家序列,类似这种 array(1,3,3,4,2,4,3,2,1,4,4,1)
		$error = FALSE;
		for ($i = 0; $i < 10000; ++$i)
		{
			$baseCount = rand(2, 10);
			$ret = GuildCopyUtil::randCountryType($baseCount);
			if (count($ret) != 2 * $baseCount) 
			{
				$error = TRUE;
				break;
			}
			
			for ($j = 1; $j <= $baseCount; ++$j)
			{
				$first = 2 * $j - 2;
				$second = 2 * $j - 1;
				if ($ret[$first] <= 0 || $ret[$first] > 4 
					|| $ret[$second] <= 0 || $ret[$second] > 4 
					|| $ret[$first] == $ret[$second]) 
				{
					$error = TRUE;
					break;
				}
			}
			
			if ($error) 
			{
				break;
			}
			
		}
		$this->assertFalse($error);
		
		// 测试据点数低于2时候的错误情况
		try 
		{
			GuildCopyUtil::randCountryType(1);
			$this->assertTrue(0);
		} 
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_Switch()
	{
		//var_dump(self::$uid);
		//var_dump(self::$guildId);
		//var_dump(self::$arrMemberUid);
		
		// 没有升级军团建筑时候抛FAKE
		try 
		{
			$guildCopy = new GuildCopy();
			$this->assertTrue(0);
		} 
		catch (Exception $e) 
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 升级建筑
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$arrBuildCond = btstore_get()->GUILD_COPY_RULE['build_cond']->toArray();
		foreach ($arrBuildCond as $buildType => $needLevel)
		{
			//var_dump($buildType);
			//var_dump($needLevel);
			$ret = $console->setGuildLevel($buildType, $needLevel);
			//var_dump($ret);
			GuildObj::release(self::$guildId);   
			$curLevel = EnGuild::getBuildLevel(self::$uid, $buildType);
			//var_dump($curLevel);
		}
		
		$guildCopy = new GuildCopy();
		$this->assertTrue(TRUE);
	}
	
	public function test_getUserInfo()
	{
		$guildCopy = new GuildCopy();		
		$ret = $guildCopy->getUserInfo();
		
		//var_dump($ret);
		
		$this->assertEquals(1, $ret['curr']);
		$this->assertEquals(1, $ret['next']);
	}
	
	public function test_getCopyInfo()
	{
		$guildCopy = new GuildCopy();
		
		// 当前攻打的副本默认为第1个,所以穿参数2报错
		try 
		{
			$ret = $guildCopy->getCopyInfo(2);
			$this->assertTrue(FALSE);
		} 
		catch (Exception $e) 
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$ret = $guildCopy->getCopyInfo(1);
		//var_dump($ret);
	}
	
	public function test_setTarget()
	{
		// 测试普通军团成员设置攻打目标，抛fake
		RPCContext::getInstance()->setSession('global.uid', self::$arrMemberUid[0]);
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->setTarget(1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 测试设置不合法的target，当前只能设置副本Id为1
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->setTarget(2);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->setTarget(1);
		//var_dump($ret);
		
		$this->assertEquals('ok', $ret);
	}
	
	public function test_attack()
	{
		// 测试攻打非当前军团副本Id，抛fake
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->attack(2, 1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->attack(1, 1);
		//var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);
		
		//这次攻打造成的伤害
		$damage1 = $ret['damage'];
		
		// 一次攻击就给干死啦
		if ($ret['kill'] > 0) 
		{
			// 测试这个据点已经被干掉啦，再攻打返回已经死亡
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->attack(1, 1);
			$this->assertEquals(array('ret' => 'dead'), $ret);
		}
		
		// 验证次数，这个时候应该还有4次攻击次数，默认5次
		$this->assertEquals(intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']) - 1, GuildCopyUserObj::getInstance(self::$uid)->getAtkNum());
		
		// 验证玩家的总伤害是否正确
		$this->assertEquals($damage1, GuildCopyUserObj::getInstance(self::$uid)->getAtkDamage());
		
		// 攻打第二个据点，第二个据点配置的牛逼点，一次打不死，这样可以测血量继承的各种情况
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->attack(1, 2);
		var_dump($ret);
		$this->assertEquals('ok', $ret['ret']);
		
		// 次数又少了一次
		$this->assertEquals(intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']) - 2, GuildCopyUserObj::getInstance(self::$uid)->getAtkNum());
		
		// 验证玩家的总伤害是否正确
		$damage2 = $ret['damage'];
		$this->assertEquals($damage1 + $damage2, GuildCopyUserObj::getInstance(self::$uid)->getAtkDamage());
		
		// 验证玩家对这两个据点的总伤害记录
		$this->assertEquals($damage1, GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(1));
		$this->assertEquals($damage2, GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(2));
		
		// 验证军团每个据点最大伤害者信息
		$this->assertEquals($damage1, GuildCopyObj::getInstance(self::$guildId)->getMaxDamage(1));
		$this->assertEquals($damage2, GuildCopyObj::getInstance(self::$guildId)->getMaxDamage(2));
		
		// 对第2个据点连续攻击3次，把玩家的攻击次数用光，正常情况下不会给打死的
		for ($i = 0; $i < intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']) - 2; ++$i)
		{
			if ($ret['kill'] == 0) 
			{
				$ret = $guildCopy->attack(1, 2);
				$damage2 += $ret['damage'];
				//var_dump($ret);
			}
		}
		
		// 再次验证玩家对这两个据点的总伤害记录
		$this->assertEquals($damage1, GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(1));
		$this->assertEquals($damage2, GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(2));
		
		// 再次验证军团每个据点最大伤害者信息
		$this->assertEquals($damage1, GuildCopyObj::getInstance(self::$guildId)->getMaxDamage(1));
		$this->assertEquals($damage2, GuildCopyObj::getInstance(self::$guildId)->getMaxDamage(2));
		
		// 再次验证玩家的总伤害是否正确
		$this->assertEquals($damage1 + $damage2, GuildCopyUserObj::getInstance(self::$uid)->getAtkDamage());
		
		// 现在玩家已经没有攻击次数啦
		$this->assertEquals(0, GuildCopyUserObj::getInstance(self::$uid)->getAtkNum());
		
		// 测试玩家已经没有攻打次数啦，再攻打它抛异常
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->attack(1, 2);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 测试更新血量
		
	}
	
	public function test_getRankList()
	{
		/**
		 * 目前是第一个玩家，军团长打了1和2两个据点，且次数都用完啦
		 * 军团其他成员共10人，每个人打了一下第2个据点
		 */
		// 军团每个成员都打一下，看下军团排名里应该所有人都有啦
		$damage = 0;
		foreach (self::$arrMemberUid as $aMember)
		{
			RPCContext::getInstance ()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aMember);
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->attack(1, 2);
			//var_dump($ret);
			
			$damage += GuildCopyUserObj::getInstance($aMember)->getBaseDamage(2);
		}
		$damage += GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(2);
		
		/**
		 * 目前是第一个玩家，军团长打了1和2两个据点，且次数都用完啦
		 * 军团其他成员共10人，每个人打了一下第2个据点
		 */
		
		// 验证多个玩家攻打下的总伤害（第二个据点）
		$maxHp = GuildCopyObj::getInstance(self::$guildId)->getBaseMaxHp(2);
		$curHp = GuildCopyObj::getInstance(self::$guildId)->getBaseCurrHp(2);
		//var_dump($maxHp);
		//var_dump($curHp);
		//var_dump($damage);
		$this->assertEquals($damage + $curHp, $maxHp);
		
		// 验证第一个据点血量关系
		$damage = GuildCopyUserObj::getInstance(self::$uid)->getBaseDamage(1);
		$maxHp = GuildCopyObj::getInstance(self::$guildId)->getBaseMaxHp(1);
		$curHp = GuildCopyObj::getInstance(self::$guildId)->getBaseCurrHp(1);
		//var_dump($maxHp);
		//var_dump($curHp);
		//var_dump($damage);
		$this->assertEquals($damage + $curHp, $maxHp);
		
		
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->getRankList();
		//var_dump($ret);
		
		// 军团成员和军团长，现在军团伤害排名中去掉了伤害为0的成员，所以这个assert不一定成立
		//$this->assertEquals(count(self::$arrMemberUid) + 1, count($ret['guild']));
	}
	
	public function test_addAtkNum()
	{
		// 这个时候应该没有攻击次数来
		$this->assertEquals(0, GuildCopyUserObj::getInstance(self::$uid)->getAtkNum());
		
		// 买一次以后验证次数
		$guildCopy = new GuildCopy();
		$beforeAtkNum = GuildCopyUserObj::getInstance(self::$uid)->getAtkNum();
		$beforeGoldNum = EnUser::getUserObj(self::$uid)->getGold();
		$ret = $guildCopy->addAtkNum();
		//var_dump($ret);
		$this->assertEquals('ok', $ret);
		$afterAtkNum = GuildCopyUserObj::getInstance(self::$uid)->getAtkNum();
		$this->assertEquals($beforeAtkNum + 1, $afterAtkNum);
		
		// 验证玩家金币是否扣对啦， 第一次买
		$need = GuildCopyUtil::getBuyCostByNum(1);
		$afterGoldNum = EnUser::getUserObj(self::$uid)->getGold();
		$this->assertEquals($need + $afterGoldNum, $beforeGoldNum);
		
		// 玩家买的次数应该为1
		$this->assertEquals(1, GuildCopyUserObj::getInstance(self::$uid)->getBuyNum());
		
		// 把剩余的次数都买完
		$need = 0;
		for ($i = 2; $i <= intval(btstore_get()->GUILD_COPY_RULE['max_buy_num']); ++$i)
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->addAtkNum();
			$need += GuildCopyUtil::getBuyCostByNum($i);
			//var_dump($need);
			$this->assertEquals('ok', $ret);
			$this->assertEquals($i, GuildCopyUserObj::getInstance(self::$uid)->getBuyNum());
			$this->assertEquals($i, GuildCopyUserObj::getInstance(self::$uid)->getAtkNum());
		}
		$this->assertEquals($need + EnUser::getUserObj(self::$uid)->getGold(), $afterGoldNum);
		
		// 再买的话应该抛fake，因为次数已经用完啦
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->addAtkNum();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// TODO 验证军团副本通过后，不能购买次数
	}
	
	public function getRefreshMinVip()
	{
		$arrVipConf = btstore_get()->VIP->toArray();
		foreach ($arrVipConf as $vip => $aConf)
		{
			if ($aConf['allAttackOpen'] == 1)
			{
				return $vip;
			}
		}
		return 0;
	}
	
	public function test_refresh()
	{
		try
		{
			// 验证军团长来个全团突击，自己增加了次数，现在全团突击有vip限制，现在是vip为0
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->refresh();
			//var_dump($ret);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$minVip = $this->getRefreshMinVip();
		EnUser::getUserObj(self::$uid)->setVip4Test($minVip);
		EnUser::getUserObj(self::$uid)->update();
		
		// 验证军团长来个全团突击，自己增加了次数，现在全团突击有vip限制，现在是vip为0
		$guildCopy = new GuildCopy();
		$beforeAtkNum = GuildCopyUserObj::getInstance(self::$uid)->getAtkNum();
		$ret = $guildCopy->refresh();
		$this->assertEquals('ok', $ret);
		$afterAtkNum = GuildCopyUserObj::getInstance(self::$uid)->getAtkNum();
		$this->assertEquals($beforeAtkNum + intval(btstore_get()->GUILD_COPY_RULE['all_attack_add_num']), $afterAtkNum);
		$this->assertTrue(GuildCopyUserObj::getInstance(self::$uid)->isRefresh());
				
		//TODO 验证军团其他成员都增加了对应的次数，通过lcserver的callback转到各个玩家线程中搞的，单侧里没法验证
		foreach (self::$arrMemberUid as $aMember)
		{
			$atkNum = GuildCopyUserObj::getInstance($aMember)->getAtkNum();
			//var_dump($atkNum);
		}
		
		if (intval(btstore_get()->GUILD_COPY_RULE['all_attack_limit']) <= 1) 
		{
			// 总次数才1次，刚才已经搞过1次啦，现在次数肯定不够，返回lack
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->refresh();
			$this->assertEquals('lack', $ret);
		}
		else 
		{
			// 总次数够，但是军团长今天已经全团突击过啦，这里应该抛fake，没个玩家一天只能突击一次
			try
			{
				$guildCopy = new GuildCopy();
				$ret = $guildCopy->addAtkNum();
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
			
			// 有次数，让军团成员消耗完这个次数，all_attack_limit次数也就3,5次
			for ($i = 2; $i <= intval(btstore_get()->GUILD_COPY_RULE['all_attack_limit']); ++$i)
			{
				RPCContext::getInstance ()->resetSession();
				RPCContext::getInstance()->setSession('global.uid', self::$arrMemberUid[$i]);
				EnUser::getUserObj(self::$arrMemberUid[$i])->setVip4Test($minVip);
				EnUser::getUserObj(self::$arrMemberUid[$i])->update();
				$guildCopy = new GuildCopy();
				$ret = $guildCopy->refresh();
				$this->assertEquals('ok', $ret);
			}
			
			// 现在次数真的不够，返回lack
			RPCContext::getInstance ()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', self::$uid);
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->refresh();
			$this->assertEquals('lack', $ret);
		}
	}
	
	public function test_recvPassReward()
	{
		// 现在压根没通关，验证领取通关奖励时候会抛异常
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->recvPassReward();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}		
	}
	
	public function test_getBoxInfo()
	{
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->getBoxInfo();
		//var_dump($ret);
		
		// 现在压根没通关 ，一个宝箱也没有领走，应该没空
		$this->assertEmpty($ret);
	}
	
	public function test_openBox()
	{
		// 现在压根没通关，验证领取通关奖励时候会抛异常
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->openBox(1);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_afterCurrCopyPass()
	{
		// 现在当前副本没有通关
		$this->assertFalse(GuildCopyObj::getInstance(self::$guildId)->isCurrCopyDown());
		$this->assertEquals(0, GuildCopyObj::getInstance(self::$guildId)->getMaxPassCopy());
		
		// 手动让军团当前副本通关
		list($copyInfo, $isInit) = GuildCopyObj::getInstance(self::$guildId)->getCopyInfo();
		foreach ($copyInfo as $index => $value)
		{
			$copyInfo[$index]['hp'] = array();
		}
		GuildCopyObj::getInstance(self::$guildId)->setCopyInfo($copyInfo);
		GuildCopyObj::getInstance(self::$guildId)->passCurrCopy();
		GuildCopyObj::getInstance(self::$guildId)->update();
		
		// 现在军团当前副本已经通关啦
		$this->assertTrue(GuildCopyObj::getInstance(self::$guildId)->isCurrCopyDown());
		$this->assertEquals(1, GuildCopyObj::getInstance(self::$guildId)->getMaxPassCopy());
		
		// 玩家的啥奖励都没有领取
		$this->assertFalse(GuildCopyUserObj::getInstance(self::$uid)->isRecvPassReward());
		$this->assertFalse(GuildCopyUserObj::getInstance(self::$uid)->isRecvBoxReward());
		$this->assertFalse(GuildCopyUserObj::getInstance(self::$uid)->isRecvRankReward());
		
		// 验证第一次领取阳光普照奖，没问题
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->recvPassReward();
		$this->assertEquals('ok', $ret);
		
		// 验证奖励内容，获得的战功数量应该乘以玩家的剩余攻击次数
		
		// 验证第二次领取阳光普照奖，抛fake
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->recvPassReward();
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 军团新加一个成员，这个成员领取阳光普照奖，返回'after_pass'
		$arrNewMember = $this->addMember(1);
		self::updateJoinGuildTime($arrNewMember, self::$guildId, Util::getTime() + 1);
		$aNewUid = $arrNewMember[0];
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', $aNewUid);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->recvPassReward();
		$this->assertEquals('after_pass', $ret);
		
		// 验证第一次领取宝箱奖励
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->openBox(1);
		$this->assertEquals('ok', $ret['ret']);
		$reward = $ret['extra'];
		$ret = $guildCopy->getBoxInfo();
		$this->assertEquals(array(1 => array('uid' => self::$uid, 'htid' => EnUser::getUserObj(self::$uid)->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj(self::$uid)->getUname(), 'reward' => $reward)), $ret);
		
		// 验证第二次领取宝箱奖励，抛fake
		try
		{
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->openBox(2);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 另一个军团成员也领第一个箱子，但是第一个箱子已经被领走啦，返回already
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$arrMemberUid[0]);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->openBox(1);
		$this->assertEquals('already', $ret['ret']);
		$this->assertEquals(array('uid' => self::$uid, 'htid' => EnUser::getUserObj(self::$uid)->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj(self::$uid)->getUname(), 'reward' => $reward), $ret['extra']);
		
		// 军团新加一个成员，这个成员领宝箱奖励，返回'after_pass'
		$arrNewMember = $this->addMember(1);
		self::updateJoinGuildTime($arrNewMember, self::$guildId, Util::getTime() + 1);
		$aNewUid = $arrNewMember[0];
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', $aNewUid);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->openBox(3);
		$this->assertEquals('after_pass', $ret['ret']);
		
		// 再搞三个个正常的军团成员领取奖励
		$arrNewMember = $this->addMember(3);
		self::updateJoinGuildTime($arrNewMember, self::$guildId, Util::getTime() - 1);
		$boxId = 1;
		$arrReward = array();
		foreach ($arrNewMember as $aNewUid)
		{
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aNewUid);
			$guildCopy = new GuildCopy();
			$ret = $guildCopy->openBox(++$boxId);
			//var_dump($ret);
			$arrReward[] = $ret['extra'];
			$this->assertEquals('ok', $ret['ret']);
		}
		
		// 验证下宝箱领取情况
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->getBoxInfo();
		//var_dump($ret);
		$this->assertEquals(4, count($ret));
		$shouldBeInfo = array
		(
				1 => array('uid' => self::$uid, 'htid' => EnUser::getUserObj(self::$uid)->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj(self::$uid)->getUname(), 'reward' => $reward),
				2 => array('uid' => $arrNewMember[0], 'htid' => EnUser::getUserObj($arrNewMember[0])->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj($arrNewMember[0])->getUname(), 'reward' => $arrReward[0]),
				3 => array('uid' => $arrNewMember[1], 'htid' => EnUser::getUserObj($arrNewMember[1])->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj($arrNewMember[1])->getUname(), 'reward' => $arrReward[1]),
				4 => array('uid' => $arrNewMember[2], 'htid' => EnUser::getUserObj($arrNewMember[2])->getHeroManager()->getMasterHeroObj()->getHtid(), 'uname' => EnUser::getUserObj($arrNewMember[2])->getUname(), 'reward' => $arrReward[2]),
		);
		$this->assertEquals($shouldBeInfo, $ret);
		
		// 设置玩家的购买次数为0，然后购买，应该返回'already_pass'，因为已经通关啦
		GuildCopyUserObj::getInstance(self::$uid)->setBuyNumForTest(0);
		$this->assertEquals('0', GuildCopyUserObj::getInstance(self::$uid)->getBuyNum());
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->addAtkNum();
		$this->assertEquals('already_pass', $ret);
		
		// 设置玩家的全团突击次数为0，然后全团突击，应该返回already_pass，因为已经通关啦
		GuildCopyUserObj::getInstance(self::$uid)->resetRefreshTimeForTest();
		GuildCopyObj::getInstance(self::$guildId)->resetRefreshNumForTest();
		$this->assertFalse(GuildCopyUserObj::getInstance(self::$uid)->isRefresh());
		$this->assertEquals(0, GuildCopyObj::getInstance(self::$guildId)->getRefreshNum());
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->refresh();
		$this->assertEquals('already_pass', $ret);
	}
	
	public function test_getShopInfo()
	{
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->getShopInfo();
		//var_dump($ret);
		
		// 玩家还没买一个东西，这里没空
		$this->assertEmpty($ret);
	}
	
	public function test_buy()
	{
		// 这个商品需要战功，先加上
		$console = new Console();
		$console->addZg(100000);
		GuildMemberObj::release(self::$uid);
		
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->buy(1, 1);
		$this->assertEquals('ok', $ret);
		
		// 从商品列表找一个需要通关第2个副本才能买的东西，现在军团只通关了第一个副本，买这个商品抛fake
		$testGoodsId = 0;
		$goodsConf = btstore_get()->GUILD_COPY_GOODS->toArray();
		foreach ($goodsConf as $goodsId => $aGoodsConf)
		{
			if ($aGoodsConf['copy'] > 1) 
			{
				$testGoodsId = $goodsId;
				break;
			}
		}
		if ($testGoodsId > 0) 
		{
			try
			{
				$guildCopy = new GuildCopy();
				$ret = $guildCopy->buy($testGoodsId, 1);
				$this->assertTrue(FALSE);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
		}
	}
	
	public function test_rankReward()
	{
		GuildCopyScriptLogic::reward();
	}
	
	public function test_addCountryAddition()
	{
		$guildCopyObj = GuildCopyObj::getInstance(self::$guildId);
		$arrCountryType = $guildCopyObj->getBaseCountryType(1);
		var_dump($arrCountryType);
		
		$arrAddition = btstore_get()->GUILD_COPY_RULE['country_add']->toArray();
		var_dump($arrAddition);
		
		
		$userObj = EnUser::getUserObj(self::$uid);
		$userBattleFormation = $userObj->getBattleFormation();
		foreach ($userBattleFormation['arrHero'] as $pos => $aHeroInfo)
		{
			foreach ($arrAddition as $property => $addition)
			{
				printf("before:pos[%d], country[%d], value[%d].\n", $pos, $aHeroInfo[PropertyKey::COUNTRY], isset($aHeroInfo[$property]) ? $aHeroInfo[$property] : 0);
			}
		}
		$userBattleFormation = GuildCopyUtil::addCountryAddition($userBattleFormation, array(0));
		foreach ($userBattleFormation['arrHero'] as $pos => $aHeroInfo)
		{
			foreach ($arrAddition as $property => $addition)
			{
				printf("after:pos[%d], country[%d], value[%d].\n", $pos, $aHeroInfo[PropertyKey::COUNTRY], isset($aHeroInfo[$property]) ? $aHeroInfo[$property] : 0);
			}
		}
	}
	
	public function test_getRewardByRank()
	{
		$arrReward = btstore_get()->GUILD_COPY_REWARD->toArray();
		
		$reward = GuildCopyUtil::getRewardByRank(1);
		var_dump($reward);
		$this->assertEquals(reset($arrReward), $reward);
		
		$reward = GuildCopyUtil::getRewardByRank(GuildCopyCfg::REWARD_COUND);
		var_dump($reward);
		$this->assertEquals(end($arrReward), $reward);
		
		$reward = GuildCopyUtil::getRewardByRank(GuildCopyCfg::REWARD_COUND + 1);
		var_dump($reward);
		$this->assertEquals(end($arrReward), $reward);
		
		$reward = GuildCopyUtil::getRewardByRank(GuildCopyCfg::REWARD_COUND + GuildCopyCfg::REWARD_COUND);
		var_dump($reward);
		$this->assertEquals(end($arrReward), $reward);
		
		$reward = GuildCopyUtil::getRewardByRank(49);
		var_dump($reward);
	}
	
	public function test_randBoxReward()
	{
		$result = array();
		for ($i = 0; $i < 1000; ++$i)
		{
			list($rewardIndex, $rewardContent) = GuildCopyUtil::randBoxReward(1, array());
			if (!isset($result[$rewardIndex])) 
			{
				$result[$rewardIndex] = 0;
			}
			++$result[$rewardIndex];
		}
		var_dump($result);
	}
	
	public function test_compensate()
	{
		// 将pass_time和update_time设置为昨天
		$guildCopyObj = GuildCopyObj::getInstance(self::$guildId);
		$guildCopyObj->setPassTimeForTest(Util::getTime() - SECONDS_OF_DAY);
		$guildCopyObj->setUpdateTimeForTest(Util::getTime() - SECONDS_OF_DAY);
		$guildCopyObj->update();
		GuildCopyObj::releaseInstance(self::$guildId);
		
		// 让5个军团成员加入军团的时间为通关前，其他成员为军团通关之后
		$arrPartUid = array_slice(self::$arrMemberUid, 1, 5);
		$arrOtherUid = array_diff(self::$arrMemberUid, $arrPartUid);
		self::updateJoinGuildTime($arrPartUid, self::$guildId, Util::getTime() - SECONDS_OF_DAY - 600);
		
		// 这里会发送补偿奖励
		$guildCopyObj = GuildCopyObj::getInstance(self::$guildId);
		
		// 验证这5个军团成员都收到了补发的奖励
		foreach ($arrPartUid as $aUid)
		{
			$reward = EnReward::getRewardByUidTime($aUid, RewardSource::GUILDCOPY_COMPENSATIONE_REWARD, Util::getTime() - 100);
			$this->assertNotEmpty($reward);
		}
		
		// 再来一次
		GuildCopyObj::releaseInstance(self::$guildId);
		$guildCopyObj = GuildCopyObj::getInstance(self::$guildId);
		
		GuildCopyObj::releaseInstance(self::$guildId);
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->setTarget(1);
		var_dump($ret);
	}
	
	public function test_getLastBoxInfo()
	{		
		// 昨天的箱子
		$guildCopy = new GuildCopy();
		$ret = $guildCopy->getLastBoxInfo();
		var_dump($ret);
	}
	
	public function test_attackBoss()
	{
		$guildCopy = new GuildCopy();
		$guildCopy->attackBoss();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
