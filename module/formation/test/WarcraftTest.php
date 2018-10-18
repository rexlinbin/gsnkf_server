<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WarcraftTest.php 144114 2014-12-04 03:09:42Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/test/WarcraftTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-04 03:09:42 +0000 (Thu, 04 Dec 2014) $
 * @version $Revision: 144114 $
 * @brief 
 *  
 **/

class WarcraftTest extends PHPUnit_Framework_TestCase
{
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 40000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);

		$user = EnUser::getUserObj( $this->uid );
		$user->setVip( 10 );
		$console = new Console();
		$console->level( 90 );

		$user->update();
		EnUser::release( $this->uid );
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	public function test_getWarcraftInfo_0()
	{
		printf( '================test getWarcraftInfo' );
		$formationInst = new Formation();
		$warcraftInfo = $formationInst->getWarcraftInfo();
		var_dump( $warcraftInfo );
	}
	
	
	public function test_setCurWarCraft_0()
	{
		printf( '================test test_setCurWarCraft_0' );
		$formationInst = new Formation(); 
		$craftConf = btstore_get()->WARCRAFT->toArray();
		
		$oneCraft = current( $craftConf );
		$craftId = $oneCraft['craftId'];
		Logger::debug('craftid is :%s', $craftId);
		
		$formationInst->setCurWarcraft($craftId);
	} 

	public function test_craftleveup_0()
	{
		printf( '================test test_craftleveup_0' );
		$formationInst = new Formation();
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
		
		$oneCraft = current( $craftConf );
		$craftId = $oneCraft['craftId'];
		self::addThingsForUp($craftId, 1, $this->uid);
		
		$formationInst->craftLevelup($craftId, 1);
		
		$warcraftInfo = $formationInst->getWarcraftInfo();
		
		$this->assertTrue( $warcraftInfo['warcraft'][$craftId]['level'] == 2 );
	}

	public function test_addition_0()
	{
		printf( "================test test_addition_0 \n" );
	
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
	
		$oneCraft = current( $craftConf );
		$craftId = $oneCraft['craftId'];
		$formationInst = new Formation();
	
		$profit1 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit1 );
	
		$formationInst->setCurWarcraft($craftId);
		$profit2 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit2 );
	
		self::addThingsForUp($craftId, 1,$this->uid);
		$formationInst->craftLevelup($craftId);
		$profit3 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit3 );
	
		$anotherCraft = next( $craftConf );
		$anotherCraftId = $anotherCraft['craftId'];
		self::addThingsForUp($anotherCraftId, 1, $this->uid);
		$formationInst->craftLevelup($anotherCraftId);
		$profit4 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit4 );
	
		$formationInst->setCurWarcraft($anotherCraftId);
		$profit5 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit5 );
	
		$craftUnionConf = btstore_get()->WARCRAFT_UNION ->toArray();
		$first = current( $craftUnionConf );
		$firstId = $first['id'];
		$allCraftId = array_keys( $craftConf );
		for ( $i = 0; $i< $first['numAndLevel'][0]; $i++ )
		{
			foreach ( $allCraftId as $craftId )
			{
				$curLevel = 1;
				$tmp = WarcraftLogic::getWarcraftInfo($this->uid);
				if( isset( $tmp['warcraft'][$craftId] ) )
				{
					$curLevel = $tmp['warcraft'][$craftId]['level'];
				}
				for ( $m = $curLevel; $m < $first['numAndLevel'][1]; $m++  )
				{
					self::addThingsForUp( $craftId , $m, $this->uid);
					$formationInst -> craftLevelup( $craftId );
				}
			}
			
		}
		
		$profit6 = EnFormation::getWarcraftProfit($this->uid);
		var_dump( $profit6 );
		
		$allCreature = EnFormation::getArrHeroObjInFormation( $this->uid );
		foreach ( $allCreature as $pos => $obj )
		{
			$attr = $allCreature[$pos]->getAddAttr();
			Logger::debug('attr for pos:%s, is :%s', $pos, $attr);
		}
	}
	
	
	public function test_openFriendNum_0()
	{ 	
		$index0 = 0;
		$index1 = 1;
		
		//放在一个开启的位置,目前开了0,1,2
		$myFormation = EnFormation::getFormationObj($this->uid);
		$ret = self::addNewHero($this->uid, 10010);
		$hid = $ret['hid'];
		$ret = $myFormation->addExtra($hid, $index0);
		$myFormation->update();
			
		//把前面的人挤掉
		$oldHid = $hid;
		$ret = self::addNewHero($this->uid, 10011);
		$hid = $ret['hid'];
		$ret = $myFormation->addExtra($hid, $index0);
		$myFormation->update();
		$ret = FormationDao::getByUid($this->uid);
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
		
		$extraInfo = $myFormation->getExtra();
		$extraOpen = $myFormation->getExtopen();
		
		$this->assertTrue( count( $extraInfo ) == 1 );
		$this->assertTrue( empty( $extraOpen ));
		
		$needCond = btstore_get()->FORMATION['arrExtraNeedCraft'];
		//第八个小伙伴
		$theEighth = $needCond[7];
		
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
		
		$oneCraft = current( $craftConf );
		$craftId = $oneCraft['craftId'];
		$formationInst = new Formation();
		
		$allCraftId = array_keys( $craftConf );
		for ( $i = 0; $i< $theEighth[1]; $i++ )
		{
			foreach ( $allCraftId as $craftId )
			{
				$curLevel = 1;
				$tmp = WarcraftLogic::getWarcraftInfo($this->uid);
				if( isset( $tmp['warcraft'][$craftId] ) )
				{
					$curLevel = $tmp['warcraft'][$craftId]['level'];
				}
				
				for ( $m = $curLevel; $m < $theEighth[2]; $m++  )
				{
					self::addThingsForUp( $craftId , $m, $this->uid);
					$formationInst -> craftLevelup( $craftId );
				}
			}
			
		}
		
		$craftInfo = $formationInst->getWarcraftInfo();
		//$this->assertTrue( count( $craftInfo['warcraft'] ) == 6 );
		//var_dump( $craftInfo );
		$before = $formationInst->getExtra();
		$formationInst->openExtra( 7 );
		$after = $formationInst->getExtra();
		
		var_dump( $before );
		var_dump( $after );
		
		$console = new Console();
		$console->resetExtra();
		EnFormation::release($this->uid);
		RPCContext::getInstance()->unsetSession( FormationDef::SESSION_KEY_FORMATION );
		
		//$formationInstAg = new Formation();//EnFormation::getFormationObj($this->uid);
		$afterafter = $formationInst->getExtra();
		var_dump( $afterafter );
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
	
	public static function addThingsForUp($craftId, $toLevel, $uid)
	{
		
		$craftConf = btstore_get()->WARCRAFT->toArray();
		$levelUpConf = btstore_get()->WARCRAFT_LEVELUP;
		
		//$oneCraft = current( $craftConf );
		//$craftId = $oneCraft['craftId'];
		
		$needSilver = $levelUpConf[$toLevel]['needSilver'];
		$needItemArr = $levelUpConf[$toLevel]['needItem'];
		
		$bag = BagManager::getInstance()->getBag($uid);
		$user = EnUser::getUserObj($uid);
		
		foreach ( $needItemArr as $itemId => $itemNum )
		{
			$bag->addItemByTemplateID($itemId, $itemNum,true);
		}
		$user->addSilver( $needSilver );
		$bag->update();
		$user->update();
	}
	


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */