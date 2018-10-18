<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MysteryShop.test.php 200772 2015-09-28 07:33:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/test/MysteryShop.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-28 07:33:39 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200772 $
 * @brief 
 *  
 **/
 
class WorldPassTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $pid = 0;
	private static $serverId = 0;

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
		$console->gold(1000000);
		$console->silver(1000000);
		$console->worldpass_setHellPoint(100000);

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
	
	public function addHero($arrHtidInfo)
	{
		$userObj = EnUser::getUserObj(self::$uid);
		$heroMng = $userObj->getHeroManager();
		$arrHid = $heroMng->addNewHeroes($arrHtidInfo);
		$userObj->update();
		return $arrHid;
	}
	
	public function test_switch()
	{
		// 功能节点还没有打开
		try
		{
			$arrHid = $this->addHero(array(10172 => 1));
			$mystery = new MysteryShop();
			$ret = $mystery->resolveHero2Soul($arrHid);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	
		// 根据需要的等级，打开switch
		$needLv = intval(btstore_get()->SWITCH[SwitchDef::REFINEFURNACE]['openLv']);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[$needLv]);
		$userObj->update();
	}
	
	public function test_resolveHero2Soul()
	{
		// 次数超过一次最大化魂武将个数
		try
		{
			$arrHid = $this->addHero(array(10211 => 6));
			$mystery = new MysteryShop();
			$ret = $mystery->resolveHero2Soul($arrHid);
			var_dump($ret);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 一个没有的武将
		try
		{
			$mystery = new MysteryShop();
			$ret = $mystery->resolveHero2Soul(array(7777777));//随便搞一个hid吧
			var_dump($ret);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 上锁的武将
		try
		{
			$arrHid = $this->addHero(array(10211 => 1));
			$hero = new Hero();
			$ret = $hero->lockHero($arrHid[0]);
			var_dump($ret);
			$mystery = new MysteryShop();
			$ret = $mystery->resolveHero2Soul($arrHid);//随便搞一个hid吧
			var_dump($ret);
			$this->assertTrue(FALSE);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 能正常化魂
		$userObj = EnUser::getUserObj(self::$uid);
		$heroMng = $userObj->getHeroManager();
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$beforeSilver = $userObj->getSilver();		
		$arrHid = $this->addHero(array(10211 => 5));
		$beforHeroNum = $heroMng->getHeroNumByHtid(10211);
		$FragTplInfo = Creature::getHeroConf(10211, CreatureAttr::RESOLVE_2_SOUL_FRAG_INFO);
		foreach ($FragTplInfo as $FragTplId => $num){break;}
		$beforeFragNum = $bag->getItemNumByTemplateID($FragTplId);
		
		$mystery = new MysteryShop();
		$ret = $mystery->resolveHero2Soul($arrHid);
		var_dump($ret);
		
		// 验证银币
		$starLv = Creature::getHeroConf(10211, CreatureAttr::STAR_LEVEL);
		$costSilver = 5 * intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_HERO_2_SOUL_COST][$starLv]);
		$afterSilver = $userObj->getSilver();
		$this->assertEquals($afterSilver, $beforeSilver - $costSilver);
		
		
		// 验证武将确实消耗啦
		$afterHeroNum = $heroMng->getHeroNumByHtid(10211);
		$this->assertEquals($afterHeroNum, $beforHeroNum - 5);
		
		// 验证碎片对不会
		$afterFragNum = $bag->getItemNumByTemplateID($FragTplId);
		$this->assertEquals($afterFragNum, $beforeFragNum + 5 * $num);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */