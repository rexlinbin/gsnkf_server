<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyFormationTest.php 161746 2015-03-16 09:59:27Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/test/MyFormationTest.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-03-16 09:59:27 +0000 (Mon, 16 Mar 2015) $
 * @version $Revision: 161746 $
 * @brief 
 *  
 **/

class MyFormationTest extends PHPUnit_Framework_TestCase
{
	protected static $pid = 0;
	protected static $uid = 0;
	protected static $uname = '';
	protected static $initInfo = array();
	protected static $masterHid = 0;

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

    	self::$initInfo = FormationDao::getByUid(self::$uid);
    	list(self::$masterHid, $value) = each(self::$initInfo['va_formation']['formation']);
    }
    
	protected function setUp()
	{		
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
	}

	protected function tearDown()
	{
	}
	
	public function testGet()
	{
		$myFormation = EnFormation::getFormationObj(self::$uid);
		
		$ret = $myFormation->getSquad();
		$this->assertTrue( !empty($ret ) );
		
		$ret = $myFormation->getFormation();
		$this->assertTrue( !empty($ret ) );
		
		$ret = $myFormation->getExtra();
		$this->assertTrue(empty($ret));
	}
	
	public function testAddHero()
	{
		$myFormation = EnFormation::getFormationObj(self::$uid);
		
		//放在一个开启的位置,目前只有0和1,0放置的是主角武将
		$ret = self::addNewHero(self::$uid, 10001);
		$hid = $ret['hid'];
		$ret = $myFormation->addHero($hid, 1);
		$myFormation->update();	
			
		//把前面的人挤掉
		$oldHid = $hid;
		$ret = self::addNewHero(self::$uid, 10002);
		$hid = $ret['hid'];
		$ret = $myFormation->addHero($hid, 1);
		$myFormation->update();
		$ret = FormationDao::getByUid(self::$uid);
		$this->assertEquals( 2 , count($ret['va_formation']['formation']) );
		$this->assertTrue( isset( $ret['va_formation']['formation'][$hid] ) );
		$this->assertTrue( ! isset( $ret['va_formation']['formation'][$oldHid] ) );
		
		//重复放
		try 
		{
			$ret = $myFormation->addHero($hid, 1);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{						
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//放在一个不能开启的位置
		try
		{
			$ret = self::addNewHero(self::$uid, 10003);
			$hid = $ret['hid'];
			$ret = $myFormation->addHero($hid, 2);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//hid不存在
		try
		{
			$ret = $myFormation->addHero(1, 0);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}

	}
	
	public function testDelHero()
	{
		$myFormation = EnFormation::getFormationObj(self::$uid);
		
		//先放上去一个武将
		$ret = self::addNewHero(self::$uid, 10001);
		$hid = $ret['hid'];
		$ret = $myFormation->addHero($hid, 1);
		$myFormation->update();
		
		
		//再把它删了
		$ret = $myFormation->delHero($hid);
		$myFormation->update();

		try
		{
			$ret = $myFormation->delHero(1);
			$this->assertTrue(0);
		}
		catch( Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
	}
	
	public function testSetFormation()
	{
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$user = EnUser::getUserObj(self::$uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$arrNumNeedLevel = btstore_get()->FORMATION['arrNumNeedLevel'];
		foreach ($arrNumNeedLevel as $level => $num)
		{
			if ($num >=3 ) 
			{
				$userLevel = $level;
				break;
			}
		}
		$user->addExp($expTable[$userLevel]);
		$user->update();
		$arrOpenSeq = btstore_get()->FORMATION['arrOpenSeq'];
		$pos0 = $arrOpenSeq[0];
		$pos1 = $arrOpenSeq[1];
		$pos2 = $arrOpenSeq[2];
		$pos3 = $arrOpenSeq[3];
		
		$hid1 = self::$masterHid;
		
		//先放上去两个武将
		$ret = self::addNewHero(self::$uid, 10002);
		$hid2 = $ret['hid'];
		$ret = $myFormation->addHero($hid2, 1);
		$myFormation->update();
		
		$ret = self::addNewHero(self::$uid, 10003);
		$hid3 = $ret['hid'];
		$ret = $myFormation->addHero($hid3, 2);
		$myFormation->update();
		
		//非法参数
		try 
		{
			$formation = array(
					$pos0 => $hid3,
					$pos0 => $hid2,
					$pos1 => $hid1,
					);
			$ret = $myFormation->setFormation($formation);
			$this->assertTrue( 0 );
		}
		catch ( Exception $e)
		{
			$this->assertEquals( 'fake', $e->getMessage());
		}
		
		//想放到没有开启对位置上
		try
		{
			$formation = array(
					$pos3 => $hid3,
					$pos0 => $hid2,
					$pos1 => $hid1,
			);
			$ret = $myFormation->setFormation($formation);
			$this->assertTrue( 0 );
		}
		catch ( Exception $e)
		{
			//$this->assertEquals( 'fake', $e->getMessage());
		}
		
		//hid不存在
		try
		{
			$formation = array(
					$pos0 => 1,
					$pos1 => $hid2,
					$pos2 => $hid1,
			);
			$ret = $myFormation->setFormation($formation);
			$this->assertTrue( 0 );
		}
		catch ( Exception $e)
		{
			$this->assertEquals( 'fake', $e->getMessage());
		}
		
		$formation = array(
				$pos0 => $hid3,
				$pos1 => $hid2,
				$pos2 => $hid1,
		);
		$ret = $myFormation->setFormation($formation);
		$ret = $myFormation->update();
		
		$rightInfo = array(
				$hid1 => array( 'index' => 0, 'pos' => $pos2 ),
				$hid2 => array( 'index' => 1, 'pos' => $pos1 ),
				$hid3 => array( 'index' => 2, 'pos' => $pos0 ),
				);
		$ret = FormationDao::getByUid(self::$uid);
		$this->assertEquals( $rightInfo, $ret['va_formation']['formation']);
		
	}
	
	public function testChangeFormation()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$arrOpenSeq = btstore_get()->FORMATION['arrOpenSeq'];
		$pos0 = $arrOpenSeq[0];
		$pos1 = $arrOpenSeq[1];
		$pos2 = $arrOpenSeq[2];
		
		$hid1 = self::$masterHid;

		//先放上去两个武将
		$ret = self::addNewHero(self::$uid, 10001);
		$hid2 = $ret['hid'];
		$ret = $myFormation->addHero($hid2, 1);
		
		$ret = self::addNewHero(self::$uid, 10002);
		$hid3 = $ret['hid'];
		$ret = $myFormation->addHero($hid3, 2);
		$myFormation->update();
		
		$userObj = EnUser::getUserObj(self::$uid);
		$ret = $userObj->getBattleFormation();
		$formationInBattle = Util::arrayIndexCol($ret['arrHero'], PropertyKey::POSITION, PropertyKey::HID);
		
		$this->assertEquals($myFormation->getFormation(), $formationInBattle);
		
		
		$formation = array(
				$pos2 => $hid1,
				$pos1 => $hid2,
				$pos0 => $hid3
				);
		
		$ret = $userObj->getBattleFormation($formation);
		$formationInBattle = Util::arrayIndexCol($ret['arrHero'], PropertyKey::POSITION, PropertyKey::HID);
		
		$this->assertEquals($formation, $formationInBattle);
	}
	
	public function testgetExtra()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$user = EnUser::getUserObj(self::$uid);
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$extraSize = $myFormation->getExtraSize($user->getLevel());
		$extra = $myFormation->getExtra();
		$this->assertEquals($extraSize, count($extra));
	}
	
	public function testaddExtra()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		$user = EnUser::getUserObj(self::$uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$arrExtraNeedLevel = btstore_get()->FORMATION['arrExtraNeedLevel'];
		foreach ($arrExtraNeedLevel as $level => $num)
		{
			if ($num >=3 )
			{
				$userLevel = $level;
				break;
			}
		}
		$user->addExp($expTable[$userLevel]);
		$user->update();
		
		$index0 = 0;
		$index1 = 1;
		$index2 = 2;
		$index3 = 3;
		
		//放在一个开启的位置,目前开了0,1,2
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$ret = self::addNewHero(self::$uid, 10010);
		$hid = $ret['hid'];
		$ret = $myFormation->addExtra($hid, $index0);
		$myFormation->update();
			
		//把前面的人挤掉
		$oldHid = $hid;
		$ret = self::addNewHero(self::$uid, 10011);
		$hid = $ret['hid'];
		$ret = $myFormation->addExtra($hid, $index0);
		$myFormation->update();
		$ret = FormationDao::getByUid(self::$uid);
		$this->assertEquals( 1 , count($ret['va_formation']['extra']) );
		$this->assertTrue( isset( $ret['va_formation']['extra'][$index0] ) );
		$this->assertTrue( ! in_array($oldHid, $ret['va_formation']['extra'] ) );
		
		//重复放
		try
		{
			$ret = $myFormation->addExtra($hid, $index1);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//放在一个不能开启的位置
		try
		{
			$ret = self::addNewHero(self::$uid, 10012);
			$hid = $ret['hid'];
			$ret = $myFormation->addExtra($hid, $index3);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//hid不存在
		try
		{
			$ret = $myFormation->addExtra(1, 0);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		//放一个阵容已有的武将
		try
		{
			$ret = self::addNewHero(self::$uid, 10014);
			$hid = $ret['hid'];
			$ret = $myFormation->addExtra($hid, $index2);
			$myFormation->update();
			$this->assertTrue(0);
		}
		catch ( Exception $e )
		{
			//$this->assertEquals( 'fake',  $e->getMessage());
		}
	}
	
	/**
	 * 开启两个属性小伙伴栏位，根据阵法
	 */
	public static function unlock2PosForAttrExtra()
	{
		$arrAttrExtraNeedCraft = btstore_get()->FORMATION['arrAttrExtraNeedCraft']->toArray();
		$needCount = $arrAttrExtraNeedCraft[1][1];
		$needLevel = $arrAttrExtraNeedCraft[1][2];
		
		$formationInst = new Formation();
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
		
		$count = 0;
		foreach ($craftConf as $oneCraft)
		{
			if (++$count > $needCount) 
			{
				break;
			}
			$craftId = $oneCraft['craftId'];
			for ($i = 1; $i <= $needLevel; ++$i)
			{
				self::addThingsForUp($craftId, $i, self::$uid);
				$formationInst->craftLevelup($craftId);
			}
		}
		
		$craftInfo = $formationInst->getWarcraftInfo();
		var_dump($craftInfo);
	}
	
	public static function addThingsForUp($craftId, $toLevel, $uid)
	{
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
	
		$needSilver = $levelUpConf[$toLevel]['needSilver'];
		$needItemArr = $levelUpConf[$toLevel]['needItem'];
	
		$bag = BagManager::getInstance()->getBag($uid);
		$user = EnUser::getUserObj($uid);
		foreach ( $needItemArr as $itemId => $itemNum )
		{
			$bag->addItemByTemplateID($itemId, $itemNum, true);
		}
		$user->addSilver( $needSilver );
		
		$bag->update();
		$user->update();
	}
	
	public function test_AttrExtra()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		// 初始都没有开启任何属性小伙伴
		$user = EnUser::getUserObj(self::$uid);
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$attrExtraSize = $myFormation->getAttrExtraSize($user->getLevel());
		$attrExtra = $myFormation->getAttrExtra();
		$this->assertEmpty($attrExtra);
		$this->assertEquals($attrExtraSize, count($attrExtra));
		
		$formation = new Formation();
		
		// 测试功能节点，此时等级为0级，功能节点没有开启
		try
		{
			$formation->getAttrExtra();
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		try
		{
			$ret = self::addNewHero(self::$uid, 10015);
			$formation->addAttrExtra($ret['hid'], 0);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		try
		{
			$formation->delAttrExtra($ret['hid'], 0);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		try
		{
			$formation->openAttrExtra(0);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 开启功能节点
		$needLevel = btstore_get()->SWITCH[SwitchDef::ATTREXTRA]['openLv'];
		$user = EnUser::getUserObj(self::$uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$user->addExp($expTable[$needLevel]);
		$user->update();
		
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$maxCount = FormationDef::ATTR_EXTRA_SIZE;
		if ($maxCount < count($attrExtraConf))
		{
			$maxCount = count($attrExtraConf);
		}
		
		$this->assertEquals($maxCount, count($arrInfo));
		$this->assertEquals(0, $myFormation->getAttrExtraSize($user->getLevel()));
		foreach ($arrInfo as $key => $value)
		{
			$this->assertEquals($value, -1);
			$this->assertFalse($myFormation->isAttrExtraValid($key));
		}
		
		// 测试开启的栏位信息，此时还没有开启
		$arrAttrExtraOpen = $myFormation->getAttrExtraOpen();
		var_dump($arrAttrExtraOpen);
		$this->assertEmpty($arrAttrExtraOpen);
		
		$user = EnUser::getUserObj(self::$uid);
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$user->addExp($expTable[90] - $user->getAllExp());
		$user->update();
		
		// 升级阵法一直到可以开启2个属性小伙伴栏位
		self::unlock2PosForAttrExtra();
		
		$myFormation = EnFormation::getFormationObj(self::$uid);
		
		// 等级达到解锁2个， 但是还没有开启呢
		$formation = new Formation();
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$maxCount = FormationDef::ATTR_EXTRA_SIZE;
		if ($maxCount < count($attrExtraConf))
		{
			$maxCount = count($attrExtraConf);
		}
		
		$this->assertEquals($maxCount, count($arrInfo));
		$this->assertEquals(2, $myFormation->getAttrExtraSize($user->getLevel()));
		foreach ($arrInfo as $key => $value)
		{
			$this->assertEquals($value, -1);
			if ($key < 2) 
			{
				$this->assertTrue($myFormation->isAttrExtraValid($key));
			}
			else 
			{
				$this->assertFalse($myFormation->isAttrExtraValid($key));
			}
		}
		
		// 测试开启的栏位信息，此时还没有开启，只是根据等级解锁啦
		$arrAttrExtraOpen = $myFormation->getAttrExtraOpen();
		var_dump($arrAttrExtraOpen);
		$this->assertEmpty($arrAttrExtraOpen);
		
		// 开启第1个
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$arrCost = $attrExtraConf[1]['cost'];
		var_dump($arrCost);
		foreach ($arrCost as $aCost)
		{
			if (1 == $aCost[0])//金币 
			{
				$console = new Console();
				$console->gold(intval($aCost[2]));
				$this->assertEquals($user->getGold(), intval($aCost[2]));
			}
			else if (2 == $aCost[0]) //银币
			{
				$console = new Console();
				$console->silver(intval($aCost[2]));
				$this->assertEquals($user->getSilver(), intval($aCost[2]));
			}
			else if (3 == $aCost[0]) // 道具
			{
				$orginCount = BagManager::getInstance()->getBag(self::$uid)->getItemNumByTemplateID(intval($aCost[1]));
				BagManager::getInstance()->getBag(self::$uid)->addItemByTemplateID(intval($aCost[1]), intval($aCost[2]));
				$curCount = BagManager::getInstance()->getBag(self::$uid)->getItemNumByTemplateID(intval($aCost[1]));
				$this->assertEquals($curCount, $orginCount + intval($aCost[2]));
			}
		}
		$formation->openAttrExtra(0);
		$user = EnUser::getUserObj(self::$uid);
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		foreach ($arrInfo as $key => $value)
		{
			if ($key == 0) 
			{
				$this->assertEquals($value, 0);
			}
			else 
			{
				$this->assertEquals($value, -1);
			}
			
			if ($key < 2) 
			{
				$this->assertTrue($myFormation->isAttrExtraValid($key));
			}
			else 
			{
				$this->assertFalse($myFormation->isAttrExtraValid($key));
			}
		}
		
		// 测试开启的栏位信息，此时开启了1个
		$arrAttrExtraOpen = $myFormation->getAttrExtraOpen();
		var_dump($arrAttrExtraOpen);
		$this->assertEquals(array(0=>0), $arrAttrExtraOpen);
		
		// 已经开启了第1个，继续开启第1个，会抛FAKE
		try 
		{
			$formation->openAttrExtra(0);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 开启第3个，因为第3个还没有解锁，所以也会抛FAKE
		try
		{
			$formation->openAttrExtra(2);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 给第1个位置加个英雄
		$ret = self::addNewHero(self::$uid, 10015);
		$firstHid = $ret['hid'];
		$formation->addAttrExtra($firstHid, 0);
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		foreach ($arrInfo as $key => $value)
		{
			if ($key == 0)
			{
				$this->assertEquals($value, $firstHid);
			}
			else
			{
				$this->assertEquals($value, -1);
			}
				
			if ($key < 2)
			{
				$this->assertTrue($myFormation->isAttrExtraValid($key));
			}
			else
			{
				$this->assertFalse($myFormation->isAttrExtraValid($key));
			}
		}
		
		// 给第2个位置，第3个位置加英雄，都会抛fake
		try
		{
			$ret = self::addNewHero(self::$uid, 10016);
			$hid = $ret['hid'];
			$formation->addAttrExtra($hid, 1);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		try
		{
			$ret = self::addNewHero(self::$uid, 10017);
			$hid = $ret['hid'];
			$formation->addAttrExtra($hid, 2);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 开启第2个
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$arrCost = $attrExtraConf[2]['cost'];
		var_dump($arrCost);
		foreach ($arrCost as $aCost)
		{
			if (1 == $aCost[0])//金币
			{
				$console = new Console();
				$console->gold(intval($aCost[2]));
				$this->assertEquals($user->getGold(), intval($aCost[2]));
			}
			else if (2 == $aCost[0]) //银币
			{
				$console = new Console();
				$console->silver(intval($aCost[2]));
				$this->assertEquals($user->getSilver(), intval($aCost[2]));
			}
			else if (3 == $aCost[0]) // 道具
			{
				$orginCount = BagManager::getInstance()->getBag(self::$uid)->getItemNumByTemplateID(intval($aCost[1]));
				BagManager::getInstance()->getBag(self::$uid)->addItemByTemplateID(intval($aCost[1]), intval($aCost[2]));
				$curCount = BagManager::getInstance()->getBag(self::$uid)->getItemNumByTemplateID(intval($aCost[1]));
				$this->assertEquals($curCount, $orginCount + intval($aCost[2]));
			}
		}
		$formation->openAttrExtra(1);
		$user = EnUser::getUserObj(self::$uid);
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		foreach ($arrInfo as $key => $value)
		{
			if ($key == 0) 
			{
				$this->assertEquals($value, $firstHid);
			}
			else if ($key == 1)
			{
				$this->assertEquals($value, 0);
			}
			else
			{
				$this->assertEquals($value, -1);
			}
				
			if ($key < 2)
			{
				$this->assertTrue($myFormation->isAttrExtraValid($key));
			}
			else
			{
				$this->assertFalse($myFormation->isAttrExtraValid($key));
			}
		}
		
		// 测试开启的栏位信息，此时开启了第1个和第2个
		$arrAttrExtraOpen = $myFormation->getAttrExtraOpen();
		var_dump($arrAttrExtraOpen);
		$this->assertEquals(array(0=>0,1=>1), $arrAttrExtraOpen);
		
		// 给第2个位置加个和第1个位置同样的英雄，会抛fake
		try
		{
			$ret = self::addNewHero(self::$uid, 10015);
			$hid = $ret['hid'];
			$formation->addAttrExtra($hid, 1);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 给第2个位置加和第1个位置同一个英雄，会抛fake
		try
		{
			$formation->addAttrExtra($firstHid, 1);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 给第2个位置上的加个武将
		$ret = self::addNewHero(self::$uid, 10016);
		$secondHid = $ret['hid'];
		$formation->addAttrExtra($secondHid, 1);
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		foreach ($arrInfo as $key => $value)
		{
			if ($key == 0)
			{
				$this->assertEquals($value, $firstHid);
			}
			else if ($key == 1) 
			{
				$this->assertEquals($value, $secondHid);
			}
			else
			{
				$this->assertEquals($value, -1);
			}
		}
		
		// 测试EnFormation::getArrHidInAttrExtra和EnFormation::isHidInAttrExtra
		$arrAttrHid = EnFormation::getArrHidInAttrExtra(self::$uid);
		var_dump($arrAttrHid);
		$this->assertEquals(array(0=>$firstHid,1=>$secondHid), $arrAttrHid);
		$this->assertTrue(EnFormation::isHidInAttrExtra($firstHid, self::$uid));
		$this->assertTrue(EnFormation::isHidInAttrExtra($secondHid, self::$uid));
		
		// 测试EnFormation::getAttrExtraProfit
		$arrProfit = EnFormation::getAttrExtraProfit(self::$uid);
		var_dump($arrProfit);
		$arrAttrObj = EnFormation::getArrHeroObjInAttrExtra(self::$uid);
		$this->assertEquals(2, count($arrAttrObj));
		
		//var_dump($arrAttrObj);
		
		$addArr = array();
		$firstHero = $arrAttrObj[0];
		$this->assertEquals($firstHid, $firstHero->getHid());
		$firstBattleInfo = $firstHero->getNakedBattleInfo();
		$firstAttr = $attrExtraConf[1]['attr'];
		$arrAttrExtraTempleteConf = btstore_get()->SECOND_FRIEND_TEMPLETE->toArray();
		foreach ($firstAttr as $aAttr)
		{
			if (!isset($addArr[$aAttr[1]])) 
			{
				$addArr[$aAttr[1]] = 0;
			}
			
			$addBase = 0;
			$curConf = $arrAttrExtraTempleteConf[$aAttr[0]];
			$formula = $curConf['formula'];
			if ($formula == 1) // 计算血量公式
			{
				$addBase = intval($firstBattleInfo[PropertyKey::MAX_HP]);
			}
			else if ($formula == 2) // 计算其他公式
			{
				$base = $curConf['base'];
				$base = empty($base) ? 0 : intval($firstBattleInfo[$base]);
					
				$add = $curConf['add'];
				$add = empty($add) ? 0 : intval($firstBattleInfo[$add]);
					
				$final = $curConf['final'];
				$final = empty($final) ? 0 : intval($firstBattleInfo[$final]);
					
				$addBase = $base * (1 + $add / UNIT_BASE) + $final;
			}
			
			$addArr[$aAttr[1]] += intval($addBase * $aAttr[2] / UNIT_BASE);
		}
		$secondHero = $arrAttrObj[1];
		$this->assertEquals($secondHid, $secondHero->getHid());
		$secondBattleInfo = $secondHero->getNakedBattleInfo();
		$secondAttr = $attrExtraConf[2]['attr'];
		foreach ($secondAttr as $aAttr)
		{
			if (!isset($addArr[$aAttr[1]])) 
			{
				$addArr[$aAttr[1]] = 0;
			}
			
			$addBase = 0;
			$curConf = $arrAttrExtraTempleteConf[$aAttr[0]];
			$formula = $curConf['formula'];
			if ($formula == 1) // 计算血量公式
			{
				$addBase = intval($secondBattleInfo[PropertyKey::MAX_HP]);
			}
			else if ($formula == 2) // 计算其他公式
			{
				$base = $curConf['base'];
				$base = empty($base) ? 0 : intval($secondBattleInfo[$base]);
					
				$add = $curConf['add'];
				$add = empty($add) ? 0 : intval($secondBattleInfo[$add]);
					
				$final = $curConf['final'];
				$final = empty($final) ? 0 : intval($secondBattleInfo[$final]);
					
				$addBase = $base * (1 + $add / UNIT_BASE) + $final;
			}
			
			$addArr[$aAttr[1]] += intval($addBase * $aAttr[2] / UNIT_BASE);
		}
		var_dump($addArr);
		$this->assertEquals($addArr, $arrProfit);
		
		// 测试EnFormation::getArrHeroObjInFormation
		$formation->delAttrExtra($firstHid, 0);
		$formation->delAttrExtra($secondHid, 1);
		$arrHeroObj = EnFormation::getArrHeroObjInFormation(self::$uid);
		$arrHeroInfo = EnFormation::changeObjToInfo($arrHeroObj);
		$arrBefore = array();
		foreach ($arrHeroInfo as $aInfo)
		{
			printf("hid:%d\n", $aInfo['hid']);
			foreach ($aInfo as $key => $value)
			{
				if (isset($arrProfit[$key])) 
				{
					printf("key:%s,value:%d\n", $key, $value);
					$arrBefore[$aInfo['hid']][$key] = $value;
				}
			}
		}
		//var_dump(EnFormation::changeObjToInfo($arrHeroObj));
		$formation->addAttrExtra($firstHid, 0);
		$formation->addAttrExtra($secondHid, 1);
		$arrHeroObj = EnFormation::getArrHeroObjInFormation(self::$uid);
		$arrHeroInfo = EnFormation::changeObjToInfo($arrHeroObj);
		$arrAfter = array();
		foreach ($arrHeroInfo as $aInfo)
		{
			printf("hid:%d\n", $aInfo['hid']);
			foreach ($aInfo as $key => $value)
			{
				if (isset($arrProfit[$key]))
				{
					printf("key:%s,value:%d\n", $key, $value);
					$arrAfter[$aInfo['hid']][$key] = $value;
				}
			}
		}
		
		var_dump($arrBefore);
		var_dump($arrProfit);
		var_dump($arrAfter);
		foreach ($arrHeroInfo as $aInfo)
		{
			$hid = $aInfo['hid'];
			$temp = $arrBefore[$hid];
			foreach ($arrProfit as $key => $value)
			{
				if (!isset($temp[$key])) 
				{
					$temp[$key] = 0;
				}
				$temp[$key] += $value;
			}
			$this->assertEquals($temp, $arrAfter[$hid]);
		}
		
		// 测试删除第1个小伙伴上的武将
		$formation->delAttrExtra($firstHid, 0);
		$arrInfo = $formation->getAttrExtra();
		var_dump($arrInfo);
		foreach ($arrInfo as $key => $value)
		{
			if ($key == 0)
			{
				$this->assertEquals($value, 0);
			}
			else if ($key == 1)
			{
				$this->assertEquals($value, $secondHid);
			}
			else
			{
				$this->assertEquals($value, -1);
			}
		}
		$arrAttrHid = EnFormation::getArrHidInAttrExtra(self::$uid);
		var_dump($arrAttrHid);
		$this->assertEquals(array(1=>$secondHid), $arrAttrHid);
		$this->assertFalse(EnFormation::isHidInAttrExtra($firstHid, self::$uid));
		$this->assertTrue(EnFormation::isHidInAttrExtra($secondHid, self::$uid));
		
		// 继续删除第1个位置上的武将，会抛fake，因为已经删除
		try
		{
			$formation->delAttrExtra($firstHid, 0);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// 删除第3个位置上的武将，会抛fake，因为第3个位置还没有解锁
		try
		{
			$formation->delAttrExtra($firstHid, 2);
			$this->assertTrue(0);
		}
		catch(Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function testdelExtra()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		//放在一个开启的位置,目前开了0,1,2
		$myFormation = EnFormation::getFormationObj(self::$uid);
		$ret = self::addNewHero(self::$uid, 10010);
		$hid = $ret['hid'];
		$index0 = 0;
		$ret = $myFormation->addExtra($hid, $index0);
		$myFormation->update();
		
		//再把它删了
		$ret = $myFormation->delExtra($hid, $index0);
		$myFormation->update();
		
		try
		{
			$ret = $myFormation->delExtra($hid, $index0);
			$this->assertTrue(0);
		}
		catch( Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function testupdate()
	{
		$formation = new Formation();
		$user = EnUser::getUserObj(self::$uid);
		echo 'user:'.self::$uid.'level:'.$user->getLevel();
		print_r($formation->getFormation());
		$user->getHeroManager()->addNewHero(10003);
		$user->getHeroManager()->addNewHero(10004);
		$i = 4;
		while ($user->getLevel() < 10)
		{
			$user->addExp(1000);
			$user->update();
			if ($user->getLevel() == $i) 
			{
				$i+=2;
				echo 'user:'.self::$uid.'level:'.$user->getLevel();
				print_r($formation->getFormation());
			}
		}
	}
	
	public static function updateUser($values)
	{
		EnUser::release(self::$uid);
		if( self::$uid == RPCContext::getInstance()->getUid())
		{
			RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
		}
		UserDao::updateUser(self::$uid, $values);
	}
	
	public static function addNewHero($uid, $htid)
	{
		$hid = IdGenerator::nextId('hid');
		if ( empty($hid) )
		{
			throw new Exception('get hid failed');
		}
		$heroAttr = HeroLogic::addNewHero($uid, $hid, $htid);
		return $heroAttr;
	}

}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */