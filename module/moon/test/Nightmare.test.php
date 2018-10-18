<?php

class BingfuShopTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;

	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('pnn' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];

		RPCContext::getInstance()->setSession('global.uid', self::$uid);
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
		$this->assertEquals(0, MoonObj::getInstance(self::$uid)->getNightmareBuyNum());
		$this->assertEquals(0, MoonObj::getInstance(self::$uid)->getMaxNightmarePassCopy());
		$this->assertEquals(0, MoonObj::getInstance(self::$uid)->getNightmareAtkNum());
		//$this->assertEquals(intval(btstore_get()->MOON_RULE['default_atk_num']), MoonObj::getInstance(self::$uid)->getAtkNum());
		//$gridInfo = array();
		//$console = new Console();
		try
		{
			$ret = $moon->attackBoss(1,1);
			var_dump($ret);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}

	
}