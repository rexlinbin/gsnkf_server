<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Fragseize.test.php 119858 2014-07-11 06:04:47Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/test/Fragseize.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-07-11 06:04:47 +0000 (Fri, 11 Jul 2014) $
 * @version $Revision: 119858 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/def/Fragseize.def.php');
require_once ('/home/pirate/rpcfw/conf/Fragseize.cfg.php');
class fragseizeTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 50000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		//EnUser::release( $this->uid );
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
		FragseizeObj::release( $this->uid );
	}
	
	public function test_addFrag_0()
	{
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		
		var_dump( '1.================ all the frags: ' );
		var_dump( $ret );
	}
	
	public function test_fuse_0()
	{
		Logger::trace('begin test_fuse_0');
		self::addFrags( $this->uid );
		$retOne = FragseizeLogic::getSeizerInfo( $this->uid );
		var_dump( '2.================ all the frags: ' );
		var_dump( $retOne );
		
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$numBefore = $bag->getItemNumByTemplateID( 501301 );
		FragseizeLogic::fuse( $this->uid , 501301 );
		$numAfter = $bag->getItemNumByTemplateID( 501301 );
		
		$this->assertTrue( $numAfter == $numBefore +1 );
		
		$retTwo = FragseizeLogic::getSeizerInfo( $this->uid );

		foreach ( $retTwo as $id => $num )
		{
			$this->assertTrue( $retOne[ $id ] == $num +1 );
		}
		
	}
	
	public function test_recRicher_0()
	{
		self::addFrags( $this->uid );
		$ret = FragseizeLogic::getRecRicher( $this->uid , 5013011, 4);
		
		var_dump($ret);
	}
	
	public function test_seize_0()
	{
		logger::trace('begin test_seize_0 ');
		$user = EnUser::getUserObj( $this->uid );
		while( $user->getLevel() < 24 )
		{
			$user->addExp( 10000 );
		}
		$user->update();
		
		self::addFrags( $this->uid );
		$ret = FragseizeLogic::getRecRicher($this->uid, 5013011, 4);
		
		$be = $ret[ 0 ][ 'uid' ];
		$npc = 1;
		
		foreach ( $ret as $val )
		{
			if ( $val[ 'npc' ] == 0 )
			{
				$be = $val[ 'uid' ];
				$npc = 0;
				var_dump( 'now is seizing real user' );
				break;
			}
		}
		if ( $npc == 0 )
		{
			$instBe = FragseizeObj::getInstance( $be );
			$allFragBe = $instBe->getAllFrags();
		}
		
		$ret = FragseizeLogic::seize( $this->uid , $be, 5013011, $npc);
		var_dump( $ret );
		if ( isset( $ret['reward']['fragNum'] ) )
		{
			var_dump( 'now is successed seizing user or npc' );
			$inst = FragseizeObj::getInstance( $this->uid );
			$allfrags = $inst-> getAllFrags();
			Logger::debug('test_seize,fragnum now is: %d', $allfrags[ 5013011 ]);
			//我给加了一个 初始化给加了2个抢了一个
			$this->assertTrue( $allfrags[ 5013011 ] == 4 );
			
			if ( $npc == 0 )
			{
				$instBeTwo = FragseizeObj::getInstance( $be );
				$allFragBeTwo = $instBeTwo->getAllFrags();
				$this->assertTrue( $allFragBeTwo[ 5013011 ] +1 == $allFragBe[ 5013011 ] );
			}
		}
		
	}
	
	function addFrags( $uid )
	{
		$fragArr = array(
				5013011 => 2,
				5013012 => 3,
				5013013 => 5,
		);
		$issucc = EnFragseize::addTreaFrag($uid, $fragArr);
		
		FragseizeObj::release( $uid );//全部清掉
	}
	
	public function test_consoleAdd_0()
	{
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		$console = new Console();
		$console -> addTFrag( 5013011 , 10);
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		$this->assertTrue( $ret[ 5013011 ] == 11);
		$user = EnUser::getUserObj( $this->uid );
		$user->addStamina( 50 );
		FragseizeLogic::seizeNPC($this->uid , 606001, 5013011, false);
		$retTwo = FragseizeLogic::getSeizerInfo( $this->uid );
		$this->assertTrue( $retTwo[ 5013011 ] >= $ret[ 5013011 ] );
	}
	
	//本测试用例有依赖性=================================================
// 	public function test_lastFrag_0()
// 	{
// 		EnUser::release();
// 		RPCContext::getInstance()->unsetSession( 'global.uid' );
		
// 		//本测试用例最好清空t_fragseize表 或者清空等级>20的
// 		$pid = 40000 + rand(0,9999);
// 		$uname = 't' . $pid;
// 		$ret = UserLogic::createUser($pid, 1, $uname);
// 		$users = UserLogic::getUsers( $pid );
// 		$beUid = $users[0]['uid'];
// 		$console = new Console();
		
// 		RPCContext::getInstance()->setSession('global.uid', $beUid);
// 		$console->level( 30 );
// 		EnFragseize::addTreaFrag( $beUid , array( 5013021 => 1 ));
		
// 		EnUser::release();
		
// 		RPCContext::getInstance()->setSession('global.uid', $this->uid);
// 		$console->level( 30 );
// 		EnFragseize::addTreaFrag( $this->uid , array( 5013021 => 1 ));
		
// 		$ret = FragseizeLogic::getRecRicher($this->uid, 5013021, 4);
// 		$arrUid = Util::arrayExtract( $ret , 'uid');
// 		$this->assertTrue( !in_array( 26734 , $arrUid) );
		
// 	}

	public function test_default_0()
	{
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		foreach ( FragseizeConf::$default as $id => $num )
		{
			$this->assertTrue( $ret[ $id ] == $num );
		}
	}
	
	public function test_default_1()
	{
		EnFragseize::addTreaFrag( $this->uid , array( 5013011 => 1 ));
		FragseizeObj::getInstance( $this->uid )->loadNeed( 501301 );
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		foreach ( FragseizeConf::$default as $id => $num )
		{
			if ( $id == 5013011 )
			{
				$this->assertTrue( $ret[ $id ] == $num +1 );
			}
			else 
			{
				$this->assertTrue( $ret[ $id ] == $num );
			}
		}
		EnFragseize::addTreaFrag( $this->uid , array( 5013021 => 1 ));
		$newret = FragseizeLogic::getSeizerInfo( $this->uid );
		foreach ( FragseizeConf::$default as $newid => $newnum )
		{
			if ( $newid == 5013011 )
			{
				$this->assertTrue( $newret[ $newid ] == $newnum +1 );
			}
			else
			{
				$this->assertTrue( $newret[ $newid ] == $newnum );
			}
		}
		$this->assertTrue( $newret[ 5013021 ] == 1 );
		
	}

	public function test_guide_0()
	{
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		foreach ( FragseizeConf::$default as $id => $num )
		{
			$this->assertTrue( isset( $ret[ $id ] ) );
		}
		
	}
	
	public function test_white_0()
	{
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$bag->addItemByTemplateID( 60005 , 10);
		FragseizeLogic::whiteFlag( $this->uid , 2, array( 60005 => 1 ));
		$ret = FragseizeLogic::getWhiteFlagEndTime( $this->uid );
		var_dump( $ret );
	}
	
	public function test_forceSeize_0()
	{
		$user = EnUser::getUserObj( $this->uid );
		while( $user->getLevel() < 24 )
		{
			$user->addExp( 10000 );
		}
		$user->addStamina( 50 );
		$user->update();
		for ( $i =0; $i<15;$i++ )
		{
			$ret = FragseizeLogic::getRecRicher($this->uid, 5013011, 4);
			
			$be = $ret[ 0 ][ 'uid' ];
			$npc = 1;
			
			foreach ( $ret as $val )
			{
				if ( $val[ 'npc' ] == 0 )
				{
					$be = $val[ 'uid' ];
					self::addFrags( $be );
					$npc = 0;
					var_dump( 'times: %d now is seizing real user', $i );
					break;
				}
			}
			if ( $npc == 0 )
			{
				$instBe = FragseizeObj::getInstance( $be );
				$allFragBe = $instBe->getAllFrags();
			}
			
			$ret = FragseizeLogic::seize( $this->uid , $be, 5013011, $npc);
			if ( isset( $ret['reward']['fragNum'] ) )
			{
				var_dump( "times: $i now is successed seizing user or npc" );
				$inst = FragseizeObj::getInstance( $this->uid );
				$allfrags = $inst-> getAllFrags();
				Logger::debug('test_force,fragnum now is: %d', $allfrags[ 5013011 ]);
				if ( $npc == 0 )
				{
					$instBeTwo = FragseizeObj::getInstance( $be );
					$allFragBeTwo = $instBeTwo->getAllFrags();
					$this->assertTrue( $allFragBeTwo[ 5013011 ] +1 == $allFragBe[ 5013011 ] );
				}
			}
			$i++;
		}
	} 
	
	public function test_negFragNum_0()
	{
		//测试碎片被减到0的情况
		Logger::trace('begin test_negFragNum_0');
		self::addFrags( $this->uid );
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$retOne = FragseizeLogic::getSeizerInfo( $this->uid );
		//只可以合成三次
		for ( $i = 0;$i < 5; $i++ )
		{
			try 
			{
				$numBefore = $bag->getItemNumByTemplateID( 501301 );
				FragseizeLogic::fuse( $this->uid , 501301 );
				$numAfter = $bag->getItemNumByTemplateID( 501301 );
				$this->assertTrue( $numAfter == $numBefore +1 );
			}
			catch( Exception $e )
			{
				if ( $i > 1)
				{
					echo 'normal: negtive frags'."\n";
				}
				else 
				{
					echo 'abnormal: engough frags?'."\n";
				}
			}
		}
		
		$retTwo = FragseizeLogic::getSeizerInfo( $this->uid );

		foreach ( $retTwo as $id => $num )
		{
			$this->assertTrue( $retOne[ $id ] == $num + 3 );
		}
	}
	
	public function test_negFragNum_1()
	{
		$pid = 40000 + rand(0,9999);
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, 1, $uname);
		$users = UserLogic::getUsers( $pid );
		$uid = $users[0]['uid'];
		
		try 
		{
			$updateArr = array(5013011 => array( 'frag_num' => 0, 'seize_num' => 0 ));
			FragseizeDAO::updateFrags($uid, $updateArr);
		}
		catch ( Exception $e )
		{
			echo 'db exception';
		}
		
		$ret1 = FragseizeDAO::getFragByUid($uid);
		var_dump( 'fragsInfo1:' );
		var_dump( $ret1 );
		
		try
		{
			$updateArr = array(5013011 => array( 'frag_num' => -1, 'seize_num' => 0 ));
			FragseizeDAO::updateFrags($uid, $updateArr);
		}
		catch ( Exception $e )
		{
			echo 'normal: db exception';
		}
		
		$ret2 = FragseizeDAO::getFragByUid($uid);
		var_dump( 'fragsInfo2:' );
		var_dump( $ret2 );
		
		try
		{
			$updateArr = array(5013011 => array( 'frag_num' => 0, 'seize_num' => 0 ));
			FragseizeDAO::updateFrags($uid, $updateArr);
		}
		catch ( Exception $e )
		{
			echo 'normal: db exception';
		}
		
		$ret2 = FragseizeDAO::getFragByUid($uid);
		var_dump( 'fragsInfo2:' );
		var_dump( $ret2 );
		
		
// 		try
// 		{}
// 		catch ( Exception $e )
// 		{}
	}
	
	public function test_quickSeize_0()
	{
	
		logger::trace('begin test_quickSeize_0 ');
		$user = EnUser::getUserObj( $this->uid );
		$console = new Console();
		$console -> level( 30 );
		
		self::addFrags( $this->uid );
		$ret = FragseizeLogic::getRecRicher($this->uid, 5013011, 4);
		$be = $ret[ 3 ][ 'uid' ];
		
		$ret = FragseizeLogic::quickSeize($this->uid, $be, 5013011, 10);
		var_dump( $ret );
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */