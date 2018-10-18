<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Divine.test.php 257991 2016-08-23 10:44:19Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/test/Divine.test.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-23 10:44:19 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257991 $
 * @brief 
 *  
 **/

require_once ('/home/pirate/rpcfw/def/Divine.def.php');
require_once ('/home/pirate/rpcfw/conf/Divine.cfg.php');

class DivineTest extends PHPUnit_Framework_TestCase
{
	private $user;
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
		$console->level( 95 );
	
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
		RPCContext::getInstance()->unsetSession('divine.info');
		DivineObj::release();
	}

 	public function test_getDiviInfo_0()
	{
		$divi = new Divine();
		$diviInfo = $divi->getDiviInfo();
		//var_dump( $diviInfo );
	} 
	
	public function test_divine_0()
	{
		$uid = RPCContext::getInstance()->getUid();
		$divi = new Divine();
		$time = Util::getTime();
		$divi->divi( 0 );
		
		$diviInfo = $divi->getDiviInfo();
		//var_dump( $diviInfo );
		
		$this->assertTrue( $diviInfo[ 'divi_times' ] == 1 );
		//$this->assertTrue( $diviInfo[ 'refresh_time' ] == $time );
		$this->assertTrue( $diviInfo[ 'free_refresh_num' ] == 1 );
		$this->assertTrue( $diviInfo[ 'prize_step' ] == 0 );
		$this->assertTrue( $diviInfo[ 'target_finish_num' ] <=1 );
		$this->assertTrue( $diviInfo[ 'integral' ] > 0 );
		$this->assertTrue( $diviInfo[ 'prize_level' ] == 1 );
	}
	
	public function test_refresh_0()
	{
		$divi = new Divine();
		$divi->refreshCurstar();
		$diviInfo = $divi->getDiviInfo();
		$this->assertTrue( $diviInfo[ 'free_refresh_num' ] == 0 );
	}
	
	public function test_upgrade_0()
	{
		echo 'test test_upgrade_0 ========='."\n";
		$divi = new Divine();
		$divi->upgrade();
		$ret = $divi->getDiviInfo();
		var_dump( $ret );
	}
	
	public function test_drawPrize_0()
	{
		$divi = new Divine();
		$diviInfo = $divi->getDiviInfo();
		//var_dump( $diviInfo[ 'divi_times' ] );
		
		for ( $i = 0; $i < 15; $i++)
		{
			$divi->divi( 0 );
		}
		$divi->drawPrize( 0 );
		
	}
	 
	public function test_sendPrizetoCenter_0()
	{	
		//注掉前面的测试
		//测试奖励没有领取，第二天的时候奖励的自动发放
		$divi = new Divine();
		$diviInfo = $divi->getDiviInfo();
		var_dump( $diviInfo[ 'divi_times' ] );
		$integ = 0;
		$i = 0;
		while ( true )
		{
			$i++;
			$divi->divi( 0 );
			$diviInfo = $divi->getDiviInfo();
			
			if ( $i > 15 )
			{
				break;
			}
		}
		
		DivineDao::updateDiviInfo($this->uid, array( 'refresh_time' => Util::getTime()-86400));
		$diviInfo = $divi->getDiviInfo();
		$this->assertTrue( $diviInfo[ 'prize_step' ] == 0 );
	} 
	
	 public function test_util_0()
	{
		$ret = DivineUtil::refreshTargStars( 1 , 7 );
		var_dump( $ret );
	} 

	public function test_consoleResetDivine()
	{
		$divi = new Divine();
		$diviInfo = $divi->getDiviInfo();
		
		for ( $i = 0; $i < 15; $i++)
		{
		$divi->divi( 0 );
		}
		$divi->drawPrize( 0 );
		
		$console = new Console();
		$console->setDiviYesterday();
		//$console->resetDivine();
		
		$divi2 = new Divine();
		$diviInfo = $divi2->getDiviInfo();
		
		var_dump( 'now test divine console set yesterday' );
		var_dump( $diviInfo );
		
	}
	
	public function test_fakeDivi_0()
	{
		$divi = new Divine();
		
		echo 'fake1================'."\n";
		$ret = $divi->getDiviInfo();
		$this->assertTrue( $ret[ 'va_divine' ][ DivineDef::FAKE ] == 1 );
		var_dump( $ret );
//		$this->assertTrue( $ret[ 'va_divine' ][ DivineDef::CURRENT ][ DivineCfg::FAKE_POS ] == $ret[ 'va_divine' ][ DivineDef::TARGET ][ 0 ] );
		
		for ( $i = 0; $i < DivineCfg::TARGET_STARS_NUM; $i++ )
		{
			$count = $i +2;
			echo 'fake'."$count".'================'."\n";
			$divi->divi( 0 );
			$ret = $divi->getDiviInfo();
			$this->assertTrue( $ret[ 'va_divine' ][ DivineDef::FAKE ] == 1 );
// 			if ( $i != DivineCfg::TARGET_STARS_NUM -1 )
// 			{
// 				$this->assertTrue( $ret[ 'va_divine' ][ DivineDef::CURRENT ][ DivineCfg::FAKE_POS ] == $ret[ 'va_divine' ][ DivineDef::TARGET ][ $i + 1 ] );
// 			}
// 			else 
// 			{
// 				$this->assertTrue( $ret[ 'va_divine' ][ DivineDef::CURRENT ][ DivineCfg::FAKE_POS ] !=  $ret[ 'va_divine' ][ DivineDef::CURRENT ][ 1 ] );
// 			}

			var_dump( $ret );
		}
		
		$this->assertTrue( $ret[ 'divi_times' ] == DivineCfg::TARGET_STARS_NUM );
// 		$this->assertTrue( $ret[ 'target_finish_num' ] == 1 );
// 		$this->assertTrue( $ret[ 'integral' ] > DivineCfg::TARGET_STARS_NUM );

// 		var_dump( $ret );
		
// 		$divi->drawPrize( 0 );
		
// 		$ret = $divi->getDiviInfo();
// 		$this->assertTrue( !isset( $ret[ 'va_divine' ][ DivineDef::FAKE ] ) );
// 		$this->assertTrue( $ret[ 'prize_step' ] == 1 );
	}
	
	public function test_refreshPrize_0()
	{
		printf( 'begin test_refreshPrize_0' );
		$diviInst = new Divine();
		$diviInst->upgrade();
		$ret = $diviInst->getDiviInfo();
		var_dump( $ret );
		printf( 'mid test_refreshPrize_0' );
		$diviInst->refPrize();
		$ret = $diviInst->getDiviInfo();
		var_dump( $ret );
		printf( 'end test_refreshPrize_0' );
	}
	
	public function test_oneClickDivine_0()
	{
		print "begin test_oneClickDivine_0\n";
		$divi = new Divine();
		$uInfo = $divi->getDiviInfo();
		print "User initial divine info is: \n";
		var_dump($uInfo);
		$divi->oneClickDivine();
		$uInfo = $divi->getDiviInfo();
		print "User divine info after one-click-divine is: \n";
		var_dump($uInfo);
		print "end test_oneClickDivine_0\n";
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */