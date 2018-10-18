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

class DragonManagerTest extends PHPUnit_Framework_TestCase
{
	protected static $pid = 0;
	protected static $uid = 0;
	protected static $uname = '';

	public static function setUpBeforeClass()
	{
		self::$pid = IdGenerator::nextId('uid');
		self::$uname = strval(self::$pid);
		$ret = UserLogic::createUser(self::$pid, 1, self::$uname);
		 
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];
		self::openSwitch(self::$uid);
	}

	protected function setUp()
	{
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
	}

	protected function tearDown()
	{
	}
	

	/**
	 * 测试初始化数据
	 */
	public function testInit()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		//1.准备测试环境。 测试初始化需要确保数据库中原来没有这个用户的数据，所以需要新建一个用户
		//$pid = IdGenerator::nextId('uid');//使用一个新的uid作为pid，不能确保没有重复，但是概率很小
		//$uname = strval($pid);
		//$ret = UserLogic::createUser($pid, 1, $uname);//使用pid作为用户名，并不能确保没有重复。但概率很小
		//$this->assertEquals('ok', $ret['ret'] );
		
		//$uid = $ret['uid'];
		//self::openSwitch($uid);

		$infoInDb = DragonDao::loadData(self::$uid);
		$this->assertEmpty($infoInDb); 
		
		//2.执行被测试接口
		$mgr = DragonManager::getInstance(self::$uid);
		//$mgr->save();
		
		//3.测试结果
		$infoInDb = DragonDao::loadData(self::$uid);
		$this->assertTrue( !empty($infoInDb['uid']) );
		$this->assertEquals(self::$uid, $infoInDb['uid']);
		self::checkInitOrResetInfo($infoInDb);
		
	}
	
	/**
	 * 测试每日刷新
	 */
	public function testRefreshDay()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$uid = self::$uid;
		
		//1.准备测试环境。
		$vip = EnUser::getUserObj($uid)->getVip();
		$resetNumLimit = btstore_get()->VIP[$vip]['exploreLongNum'];
		
		$mgr = DragonManager::getInstance($uid);
		$info = $mgr->getMap();
		if( $info['resetnum'] >= $resetNumLimit )
		{
			Logger::debug('cant reset');
		}
		else
		{
			$mgr->reset($uid);
		}
		$mgr->save();
		
		$infoInDb = DragonDao::loadData($uid);
		$this->assertTrue($infoInDb['resetnum'] > 0);
		$this->assertTrue( Util::isSameDay($infoInDb['last_time']) );
		
		$arrValue = array(
			'last_time' => Util::getTime() - 86400
		);
		DragonDao::update($arrValue, $uid);
		
		DragonManager::release($uid);
		
		//2.调用被测接口
		$mgr = DragonManager::getInstance($uid);
		$map = $mgr->getMap();
		
		//3.检测结果
		$this->assertEquals(0, $map['resetnum']);
		$mgr->save();
		//没有结果获取last_time，直接从db查
		$infoInDb = DragonDao::loadData($uid);
		$this->assertEquals(0, $infoInDb['resetnum']);
		$this->assertTrue( Util::isSameDay($infoInDb['last_time']) );
		
	}
	
	public function testBuyAct()
	{
		Logger::debug('======%s======', __METHOD__ );

        $uid = self::$uid;
        //准备测试环境
        $mgr = DragonManager::getInstance($uid);
        $info = DragonDao::loadData($uid);
        $this->assertEquals($info[TblDragonDef::ACT], btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::INITACT]);
        $this->assertEquals($info[TblDragonDef::BUY_ACT_NUM], DragonDef::INIT_BUYACTNUM);
        $vip = EnUser::getUserObj($uid)->getVip();
        $limitBuyActNum = btstore_get()->VIP[$vip]['exploreLongActNum'];
        $arrField = array(
            TblDragonDef::BUY_ACT_NUM => 10,
            TblDragonDef::ACT => 0,
        );
        DragonDao::update($arrField, $uid);
        $info = DragonDao::loadData($uid);
        $this->assertEquals($info[TblDragonDef::ACT], 0);
        $this->assertEquals($info[TblDragonDef::BUY_ACT_NUM], 10);
        //调用被测接口
        $mgr = DragonManager::getInstance($uid);
        $map = $mgr->getMap();
        $this->assertEquals(10, $map['buyactnum']);

        $gold1 = EnUser::getUserObj($uid)->getGold();
        Logger::trace('gold1:%d', $gold1);
        $mgr->buyAct(0, 1);
        $mgr->save();
        EnUser::getUserObj($uid)->update();
        $gold2 = EnUser::getUserObj($uid)->getGold();
        Logger::trace('gold2:%d', $gold2);
        $info = DragonDao::loadData($uid);

        $actPay = btstore_get()->DRAGON[1][DragonCsvDef::ACTPAY][0];
        $addActPay = btstore_get()->DRAGON[1][DragonCsvDef::ADDACT];//行动力购买上线组
        $needGold = min(($actPay[0] + $addActPay[0] * 10), $addActPay[1]);
        for($i = 1; $i < 1; $i++)
        {
            $needGold += min(($actPay[0] + $addActPay[0] * (10 + $i)), $addActPay[1]);
        }
        Logger::trace('needgold:%d', $needGold);

        //检验结果
        /*$this->assertEquals($needGold, $gold1 - $gold2);
        $this->assertEquals($info[TblDragonDef::ACT], $actPay[1] * 5);
        $this->assertEquals($info[TblDragonDef::BUY_ACT_NUM], 10);*/

	}
	
	
	public function testBuyHp()
	{
		Logger::debug('======%s======', __METHOD__ );
		//要测试是否扣除了金币
	}
	
	
	public function testReset()
	{
		Logger::debug('======%s======', __METHOD__ );
	}
	
	
	
	public function testRegeneratesHp()
	{
		Logger::debug('======%s======', __METHOD__ );
		
	}
	
	
	
	/**
	 * 检查初始化信息是否正确
	 */
	public static function checkInitOrResetInfo($info)
	{
		 $initInfo = array(
            TblDragonDef::LASTTIME => Util::getTime(),
            TblDragonDef::ACT => btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::INITACT],
            TblDragonDef::BUY_ACT_NUM => DragonDef::INIT_BUYACTNUM,
            TblDragonDef::BUY_HP_NUM => DragonDef::INIT_BUYHPNUM,
            TblDragonDef::POINT => DragonDef::INIT_POINT,
            TblDragonDef::FLOOR => DragonDef::INIT_FLOOR,
        );
		
		foreach( $initInfo as $key => $value )
		{
			self::assertEquals($value, $info[$key]);
		}
		
		self::assertTrue($info[TblDragonDef::HP_POOL] > 0);
		self::assertTrue(!empty($info[TblDragonDef::VA_DATA][DragonDef::MAP]));
		self::assertTrue(!empty($info[TblDragonDef::VA_BF]));
	} 
	
	
	public static function openSwitch($uid)
	{
		$switchObj = EnSwitch::getSwitchObj($uid);
		$switchObj->addNewSwitch(SwitchDef::DRAGON);
		$switchObj->save();
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */