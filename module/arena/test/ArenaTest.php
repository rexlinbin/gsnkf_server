<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaTest.php 92071 2014-03-04 08:56:39Z MingTian $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/test/ArenaTest.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2014-03-04 08:56:39 +0000 (Tue, 04 Mar 2014) $
 * @version $Revision: 92071 $
 * @brief 
 * 
 **/

class ArenaTest extends PHPUnit_Framework_TestCase
{
	protected static $uid1 = 0;
	protected static $uid2 = 0;
	protected static $uid3 = 0;
	protected static $dbHost = '';

	public static function setUpBeforeClass()
	{
		self::$uid1 = self::createUser();
		self::$uid2 = self::createUser();
		self::$uid3 = self::createUser();
		
		printf("test user1:%d, user2:%d, user3:%d\n", self::$uid1, self::$uid2, self::$uid3);
	}
	
	protected function setUp()
	{
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid1);
		ArenaLogic::$a_count = 0;
	}
	
	protected function tearDown()
	{
	}

	public function testFirstUser()
	{
		Logger::debug('======%s======', __METHOD__);
		if(ArenaConf::NPC_NUM == 0)
		{
			Logger::warning('no npc. ignore this test');
			return;
		}
		
		self::clearArena();
		$arena = new Arena();
		$ret = $arena->getArenaInfo();
		$this->assertEquals(ArenaConf::NPC_NUM + 1, $ret['res']['position']);
		$opptNum = ArenaConf::OPPONENT_BEFOR + ArenaConf::OPPONENT_AFTER;
		$arrOppt = $ret['res']['opponents'];
		$this->assertEquals($opptNum + 1, count($arrOppt));
		foreach($arrOppt as $oppt)
		{
			if( $oppt['uid'] == self::$uid1 )
			{
				continue;
			}
			$this->assertTrue( ArenaLogic::isNpc( $oppt['uid'] ) );	
		}
		
		self::changeUser(self::$uid2);
		$arena2 = new Arena();
		Logger::debug('test2');
		$ret = $arena2->getArenaInfo();
		$this->assertEquals(ArenaConf::NPC_NUM + 2, $ret['res']['position']);
		$arrOppt = $ret['res']['opponents'];
		$this->assertEquals( $opptNum + 1, count($arrOppt) );
		foreach( $arrOppt as $oppt)
		{
			if( $oppt['uid'] == self::$uid1 || $oppt['uid'] == self::$uid2  )
			{
				continue;
			}
			$this->assertTrue( ArenaLogic::isNpc( $oppt['uid'] ) );
		}
	}
	
	public function testChallengeNpc()
	{
		Logger::debug('======%s======', __METHOD__);
		if(ArenaConf::NPC_NUM == 0)
		{
			Logger::warning('no npc. ignore this test');
			return;
		}
		self::clearArena();
		$arena = new Arena();
		$ret = $arena->getArenaInfo();
		$arrOppt = $ret['res']['opponents'];
		
		self::makeUserWin(self::$uid1);
		EnUser::getUserObj(self::$uid1)->addStamina(2);
		EnUser::getUserObj(self::$uid1)->update();
		
		//挑战第一个npc，此时这个npc不存在
		$oppt1 = current($arrOppt);
		$ret = $arena->challenge($oppt1['position'], $oppt1['uid']);
		$this->assertEquals('ok', $ret['ret']);
		$this->assertTrue(BattleDef::$APPRAISAL[$ret['atk']['appraisal']] <= BattleDef::$APPRAISAL['D']);
		
		//npc现在存在，且排名=NPC_NUM+1
		$ret = self::getArenaData();
		$allArenaData = Util::arrayIndex($ret, 'uid');
		$this->assertEquals(2, count($allArenaData));
		$this->assertEquals($oppt1['position'], $allArenaData[self::$uid1]['position']);
		$this->assertEquals(ArenaConf::NPC_NUM + 1, $allArenaData[$oppt1['uid']]['position']);
						
		//第二个人先，打一下npc
		self::changeUser(self::$uid2);
		$arena2 = new Arena();
		$ret = $arena2->getArenaInfo();
		$arrOppt = $ret['res']['opponents'];
		foreach($arrOppt as $v)
		{
			if( ArenaLogic::isNpc($v['uid']) && $v != $oppt1)
			{ 
				$oppt2 = $v;
				break;
			}
		}
		self::makeUserLose(self::$uid2);
		EnUser::getUserObj(self::$uid2)->addStamina(2);
		EnUser::getUserObj(self::$uid2)->update();
		$ret = $arena2->challenge( $oppt2['position'], $oppt2['uid']);
		$this->assertEquals('ok', $ret['ret']);
		$this->assertTrue( BattleDef::$APPRAISAL[$ret['atk']['appraisal']] > BattleDef::$APPRAISAL['D'] );
		
		$ret = self::getArenaData();
		$allArenaData = Util::arrayIndex($ret, 'uid');
		$this->assertEquals(4, count($allArenaData));
		$this->assertEquals(ArenaConf::NPC_NUM + 2, $allArenaData[self::$uid2]['position']);
		$firstUserPos = $allArenaData[self::$uid1]['position'];
		
		//再打一次第一个人
		$ret = ArenaDao::get(self::$uid2, array('va_opponents') );
		if( !in_array($firstUserPos, $ret) )
		{
			Logger::debug('user1 not opponent of user2. set it');
			$ret[0] = $firstUserPos;
			$values = array(
					'va_opponents' => $ret,
					);
			ArenaDao::update(self::$uid2, $values);
		}
	}
	
	public function testChallengeOpponentChanged()
	{
		self::clearArena();
		
		Logger::debug('add user1:%d', self::$uid1);
		$arena = new Arena();
		$ret = $arena->getArenaInfo();
		$this->assertEquals('ok', $ret['ret']);
		$this->assertEquals(ArenaConf::NPC_NUM + 1, $ret['res']['position']);

		self::changeUser(self::$uid2);
		Logger::debug('add user2:%d', self::$uid2);
		$arena2 = new Arena();
		$ret = $arena2->getArenaInfo();
		$this->assertEquals('ok', $ret['ret']);
		$this->assertEquals(ArenaConf::NPC_NUM + 2, $ret['res']['position']);
		
		self::changeUser(self::$uid3);
		Logger::debug('add user3:%d', self::$uid3);
		$arena3 = new Arena();
		$ret = $arena3->getArenaInfo();
		$this->assertEquals('ok', $ret['ret']);
		$this->assertEquals(ArenaConf::NPC_NUM + 3, $ret['res']['position']);
		
		self::makeUserWin(self::$uid3);
		$ret = $arena3->challenge(ArenaConf::NPC_NUM + 2, self::$uid2);

		self::changeUser(self::$uid2);
		$ret = $arena2->challenge(ArenaConf::NPC_NUM + 3, self::$uid3);
		$this->assertEquals('position_err', $ret['ret']);
	}
	
	public function test_setUserOpponents()
	{
		$arena = new Arena();
		$ret = $arena->getArenaInfo();
		$opp = $ret['res']['opponents'];
		$i = 10;
		foreach ($opp as $pos =>$fo)
		{
			$info = array('uid' => $fo['uid'], 'position' => $pos - 30 + $i);
			$atkInfo = ArenaDao::getByPos($pos - 30 + $i, array('uid', 'position'));
			$atkInfo['position'] = $fo['position'];
			ArenaDao::updateChallenge($info, $atkInfo, $pos, $pos - 30 + $i);
			$i++;
		}
	}
	
	public function test_getArenaInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		$arena = new Arena();
		$ret = $arena->getArenaInfo();
		Logger::trace('arena info:\n%s', $ret);
		$this->assertEquals(11, count($ret['res']['opponents']));
	}

	public function test_challenge()
	{
		Logger::debug('======%s======', __METHOD__);
		$arena = new Arena();
		$ret1 = $arena->getArenaInfo();
		$pos = $ret1['res']['position'];
		$arrpos1 = array_keys($ret1['res']['opponents']);
		$atkpos = 0;
		
		foreach ($arrpos1 as $value)
		{
			if ($value != $pos) 
			{
				$atkpos = $value;
			}
		}
		$atkuid = $ret1['res']['opponents'][$atkpos]['uid'];
		
		$user = EnUser::getUserObj(self::$uid1);
		$userLevel = $user->getLevel();
		$silverBefore = $user->getSilver();
		$soulBefore = $user->getSoul();
		$expBefore = $user->getExp();
		$staminaBefore = $user->getStamina();
		$ret2 = $arena->challenge($atkpos, $atkuid);
		$isSuc = BattleDef::$APPRAISAL[$ret2['atk']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		if ($isSuc) 
		{
			$addSilver = btstore_get()->ARENA_PROPERTIES['fight_suc_silver'] * $userLevel;
			$addSoul = btstore_get()->ARENA_PROPERTIES['fight_suc_soul'] * $userLevel;
			$addExp = btstore_get()->ARENA_PROPERTIES['fight_suc_exp'] * $userLevel;
			$arrpos2 = array_keys($ret2['opponents']);
			$this->assertTrue($arrpos1 != $arrpos2);
			$this->assertTrue(isset($ret2['flop']));
		}
		else 
		{
			$addSilver = btstore_get()->ARENA_PROPERTIES['fight_fail_silver'] * $userLevel;
			$addSoul =  btstore_get()->ARENA_PROPERTIES['fight_fail_soul'] * $userLevel;
			$addExp = btstore_get()->ARENA_PROPERTIES['fight_fail_exp'] * $userLevel;
			$this->assertTrue(empty($ret2['opponents']));
		}
		$subStamina = btstore_get()->ARENA_PROPERTIES['fight_cost_stamina'];
		$silverAfter = $user->getSilver();
		$soulAfter = $user->getSoul();
		$expAfter = $user->getExp();
		$staminaAfter = $user->getStamina();
		$this->assertEquals($silverBefore + $addSilver, $silverAfter);
		$this->assertEquals($soulBefore + $addSoul, $soulAfter);
		$this->assertEquals($expBefore + $addExp, $expAfter);
		$this->assertEquals($staminaBefore - $subStamina, $staminaAfter);
	}

	public function test_getRankList()
	{
		Logger::debug('======%s======', __METHOD__);
		$arena = new Arena();
		$ret = $arena->getRankList();
		Logger::trace('rank list:%s', $ret);
		$this->assertEquals(10, count($ret));
	}
	
	public function test_getLuckyList()
	{
		Logger::debug('======%s======', __METHOD__);
		$arena = new Arena();
		$ret = $arena->getLuckyList();
		Logger::trace('lucky list:\n%s', $ret);
		$this->assertEquals(2, count($ret));
	}
	
	public function test_getGoodsInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		$ret = MallDao::select(23779, 6); 
	}
	
	protected static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		$uid = $ret['uid'];
	
		EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::ARENA);
		EnSwitch::getSwitchObj($uid)->save();
		return $uid;
	}
	
	protected static function getArenaData()
	{
		$data = new CData();
		$ret = $data->select(ArenaLogic::$arrField)->from(ArenaDao::tblName)
			->where(array('uid', '>', 0))->query();
		return $ret;
	}
	
	protected static function makeUserLose($uid)
	{
		$key = UserLogic::getBattleInfoKey($uid);
		EnUser::getUserObj($uid)->getBattleFormation();
		$battleData = McClient::get($key);
		foreach ($battleData['arrHero'] as & $hero)
		{
			$hero[PropertyKey::MAX_HP] = 1;
			$hero[PropertyKey::CURR_HP] = 1;
			$hero[PropertyKey::PHYSICAL_ATTACK_BASE] = 0;
			$hero[PropertyKey::PHYSICAL_ATTACK_ADDITION] = 0;
			$hero[PropertyKey::PHYSICAL_DEFEND_BASE] = 0;
			$hero[PropertyKey::PHYSICAL_DEFEND_ADDITION] = 0;
			$hero[PropertyKey::MAGIC_ATTACK_BASE] = 0;
			$hero[PropertyKey::MAGIC_ATTACK_ADDITION] = 0;
			$hero[PropertyKey::MAGIC_DEFEND_BASE] = 0;
			$hero[PropertyKey::MAGIC_DEFEND_ADDITION] = 0;
			$hero[PropertyKey::MAGIC_DEFEND_ADDITION] = 0;
			$hero[PropertyKey::GENERAL_ATTACK_BASE] = 0;
			$hero[PropertyKey::GENERAL_ATTACK_ADDITION] = 0;
		}
		unset($hero);
		EnUser::getUserObj($uid)->modifyBattleData();
		McClient::set($key, $battleData);
	}
	protected static function makeUserWin($uid)
	{
		$key = UserLogic::getBattleInfoKey($uid);
		EnUser::getUserObj($uid)->getBattleFormation();
		$battleData = McClient::get($key);
		foreach ($battleData['arrHero'] as & $hero)
		{
			$hero[PropertyKey::MAX_HP] = 100000;
			$hero[PropertyKey::CURR_HP] = 100000;
			$hero[PropertyKey::PHYSICAL_ATTACK_BASE] = 100000;
			$hero[PropertyKey::PHYSICAL_ATTACK_ADDITION] = 100000;
			$hero[PropertyKey::PHYSICAL_DEFEND_BASE] = 100000;
			$hero[PropertyKey::PHYSICAL_DEFEND_ADDITION] = 100000;
			$hero[PropertyKey::MAGIC_ATTACK_BASE] = 100000;
			$hero[PropertyKey::MAGIC_ATTACK_ADDITION] = 100000;
			$hero[PropertyKey::MAGIC_DEFEND_BASE] = 100000;
			$hero[PropertyKey::MAGIC_DEFEND_ADDITION] = 100000;
			$hero[PropertyKey::MAGIC_DEFEND_ADDITION] = 100000;
			$hero[PropertyKey::GENERAL_ATTACK_BASE] = 100000;
			$hero[PropertyKey::GENERAL_ATTACK_ADDITION] = 100000;
		}
		unset($hero);
		EnUser::getUserObj($uid)->modifyBattleData();
		McClient::set($key, $battleData);
	}
	
	protected static function changeUser($uid)
	{
		ArenaLogic::$a_count = 0;
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_USER, NULL);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
	}
	
	protected static function clearArena()
	{
		//$dbHost = '192.168.1.37';
		if(empty(self::$dbHost))
		{
			//这个取得是dataproxy的ip，在测试环境一般dataproxy和mysql在同一台机器上
			$proxy = new PHPProxy ( 'data' );
			$group = RPCContext::getInstance ()->getFramework ()->getGroup ();
			$arrModule = $proxy->getModuleInfo ( 'data', $group );
			Logger::debug ( "module:data get info:%s", $arrModule );
			self::$dbHost = $arrModule ['host'];
		}
		$dbHost = self::$dbHost;
		
		$dbName = 'piratetm';
		$ret = system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_arena'" );
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */