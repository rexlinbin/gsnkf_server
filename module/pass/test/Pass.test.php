<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pass.test.php 156412 2015-02-02 10:10:56Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/test/Pass.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-02-02 10:10:56 +0000 (Mon, 02 Feb 2015) $
 * @version $Revision: 156412 $
 * @brief 
 *  
 **/
class PassTest extends PHPUnit_Framework_TestCase
{
	private $uid;
	
	protected function setUp()
	{
		parent::setUp ();
		$pid = 40000 + rand(0,9999);
		$utid = 1;
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, $utid, $uname);
		$users = UserLogic::getUsers( $pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		
		$user = EnUser::getUserObj( $this->uid );
		$user->setVip( 10 );
		$console = new Console();
		$console->level( 85 );
		$user->update();
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
		RPCContext::getInstance()->unsetSession('pass.info');
		PassObj::releaseInstance($this->uid);
	}
	
/* 	public function test_enter_0()
	{
		$enterInfo = PassLogic::enter($this->uid);
		var_dump( $enterInfo );
	} */
	
	public function test_getOppoInfo_0()
	{

		
		$console = new Console();
		self::runStream( $this->uid, 1, 3, 0, false, true, true );
		$console->recoverHpForPass();
		self::runStream( $this->uid, 2, 3, 0, false, true, true );
		$console->recoverHpForPass();
		self::runStream( $this->uid, 3, 3, 0, false, false, true );
		$console->recoverHpForPass();
		self::runStream( $this->uid, 4, 3, 0, false, false, false );
				
		var_dump( $this->uid );

	}
	
	function runStream( $uid, $baseId, $degree, $buyBuffPos, $ignoreChest, $ignoreBuff, $doneBase  )
	{
		Logger::trace('run stream for baseId: %s', $baseId);
		
		$passObj = PassObj::getInstance($uid);
		$hid = EnUser::getUserObj($uid)->getMasterHid();
		$opponentList = PassLogic::getOpponentList($uid, $baseId);
		//var_dump( $opponentList );
		
		$ret = PassLogic::attack( $uid, $baseId, $degree, array() );
		//var_dump( $ret );
		
		$passInfo = PassLogic::getPassInfo( $uid );
		var_dump( $passInfo );
		
		if( isset( $passInfo['va_pass']['chestShow']['freeChest']  )
		&& $passInfo['va_pass']['chestShow']['freeChest'] != PassDef::CHEST_STATUS_DEAL
		&& !$ignoreChest )
		{
			PassLogic::dealChest( $uid, $baseId, false );
		}
		
		if( isset( $passInfo['va_pass']['chestShow']['goldChest']  )
		&& $passInfo['va_pass']['chestShow']['goldChest'] != PassDef::CHEST_STATUS_DEAL
		&& 	!$ignoreChest )
		{
			PassLogic::dealChest( $uid, $baseId, true );
		}
		
		if( isset( $passInfo['va_pass']['chestShow']['goldChest']  )
		&& $passInfo['va_pass']['chestShow']['goldChest'] != PassDef::CHEST_STATUS_DEAL  
		&& $doneBase)
		{
			PassLogic::leaveLuxuryChest( $uid, $baseId );
		}
		
		if( !empty( $passInfo['va_pass']['buffShow']  )
		&& $passInfo['va_pass']['buffShow'][$buyBuffPos]['status'] != PassDef::BUFF_STATUS_DEAL
		&& !$ignoreBuff )
		{
			$passObj = PassObj::getInstance($uid);
			$heroPartInfo = $passObj->getVaParticular(PassDef::VA_HEROINFO);
			$buffHaveBefore = $passObj->getVaParticular( PassDef::VA_BUFFINFO );
			
			$buffId = $passInfo['va_pass']['buffShow'][$buyBuffPos]['buff'];
			$buffConf = btstore_get()->PASS_BUFF[$buffId]['buffArr'];
			foreach ( $buffConf as $oneIndex => $buffArr )
			{
				if( $buffArr[0] == 1 )
				{
				}
				else if( $buffArr[0] == 2 )
				{
					$heroPartInfo[$hid][PassDef::HP_PERCENT] = 5000000;
					$passObj->setVaParticular(PassDef::VA_HEROINFO, $heroPartInfo);
					
				}
				else if( $buffArr[0] == 3 )
				{
					$heroPartInfo[$hid][PassDef::RAGE] = 0;
					$passObj->setVaParticular(PassDef::VA_HEROINFO, $heroPartInfo);
				}
				else if( $buffArr[0] == 4 )
				{
					$heroPartInfo[$hid][PassDef::HP_PERCENT] = 0;
					$passObj->setVaParticular(PassDef::VA_HEROINFO, $heroPartInfo);
				}
			}
			$passObj->update();
			
			
			PassLogic::dealBuff($uid, $baseId, $buyBuffPos, array( $hid ) );
			
			$heroInfoPartAfter = $passObj->getVaParticular( PassDef::VA_HEROINFO );
			
			foreach ( $buffConf as $oneIndexAgain => $buffArrAgain )
			{
				if( $buffArrAgain[0] == 1 )
				{
					$buffHaveNow = $passObj->getVaParticular( PassDef::VA_BUFFINFO );
					$this->assertTrue( $buffHaveBefore != $buffHaveNow );
				}
				else if( $buffArrAgain[0] == 2 )
				{
					$this->assertTrue( $heroInfoPartAfter[$hid][PassDef::HP_PERCENT] > $heroPartInfo[$hid][PassDef::HP_PERCENT] );
				}
				else if( $buffArrAgain[0] == 3 )
				{
					$heroPartInfo[$hid][PassDef::RAGE] = 0;
					$this->assertTrue( $heroInfoPartAfter[$hid][PassDef::RAGE] > $heroPartInfo[$hid][PassDef::RAGE] );
				}
				else if( $buffArrAgain[0] == 4 )
				{
					$this->assertTrue( $heroInfoPartAfter[$hid][PassDef::HP_PERCENT] > 0 );
				}
			}
			
		}
		
		if( !empty( $passInfo['va_pass']['buffShow'])  
		&& $passInfo['va_pass']['buffShow'][$buyBuffPos]['status'] != PassDef::BUFF_STATUS_DEAL
		&& $doneBase   )
		{
			PassLogic::dealBuff($uid, $buyBuffPos, PassDef::LEAVE_BUFF, array( $hid ));
		}
		
	}
	
  	public function test_setFormation_0()
	{
		$console = new Console();
		$console->resetPass();
		$console->level( 80 );
		$user = EnUser::getUserObj($this->uid);
		$hid1 = IdGenerator::nextId('hid');
		$user->addUnusedHero($hid1, 10021);
		$hid2 = IdGenerator::nextId('hid');
		$user->addUnusedHero($hid2, 10022);
		$hid3 = IdGenerator::nextId('hid');
		$user->addUnusedHero($hid3, 10023);
		$user->update();
		
		$formation = EnFormation::getFormationObj($this->uid);
		$allHero = $user->getHeroManager()->getAllHero();
		$masterHid = $user->getMasterHid();
		
		$formation->addHero($hid1, 1);
		$formation->addHero($hid2, 3);
		$formation->addHero($hid3, 4);
		$formation->update();
		$user->update();
		
		self::runStream( $this->uid, 1, 3, 0, false, true, true );
		
		$pass = new Pass();
		$passObj = PassObj::getInstance( $this->uid );
		$preFormation = $passObj->getVaParticular( PassDef::VA_FORMATION );
		$changeHid = $preFormation[1];
		unset($preFormation[1]);
		$pass->setPassFormation($preFormation, array(0 => $changeHid));
		
	} 
	 
/* 	public function test_rankList_0()
	{
		$ret = PassLogic::getRankList( $this->uid );
		
		$this->assertTrue( count( $ret['top'] ) >= 1 );
	} */

/* 	public function test_rewardRank_0()
	{
		//用的时候需要改时间
		$data = new CData();
		$data->update('t_pass')->set( array( 'reward_time' => 0 ) )-> where( array( 'reward_time','>',0 ) )
		->query();
		
		PassLogic::rewardForRank();

	} */

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */