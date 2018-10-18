<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BossTest.php 89717 2014-02-13 02:37:09Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/test/BossTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-02-13 02:37:09 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89717 $
 * @brief 
 *  
 **/

class BossTest extends PHPUnit_Framework_TestCase
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
		while ( $user->getLevel() < 35 && UserConf::MAX_LEVEL > 35 )
		{
			$user->addExp( 10000 );
		}
		
		EnUser::release( $this->uid );
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
		EnUser::release();
		Atker::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

// 	public function test_enterBoss_0()
// 	{
// 		var_dump( 'test=======test_enterBoss_0'."\n" );
// 		$ret = BossLogic::enterBoss( $this->uid,1 );
// 		var_dump( $ret );
// 	}
	
	/**	
	public function test_enterBoss_1()
	{
		var_dump( 'test=======test_enterBoss_1'."\n" );
		self::openBoss( $this->uid );
		$ret = BossLogic::enterBoss( $this->uid, 1 );
		var_dump( $ret );
	}
	
	public function test_inspireBySilver_0()
	{
		var_dump( 'test=======test_inspireBySilver_0'."\n" );
		self::openBoss( $this->uid );
		$ret = BossLogic::enterBoss( $this->uid, 1 );
		BossLogic::inspireBySilver( $this->uid );
		BossLogic::leaveBoss(1);
		
		$ret = BossLogic::enterBoss( $this->uid, 1 );
		var_dump( $ret );
	}
	
	public function test_inspireByGold_0()
	{
		var_dump( 'test=======test_inspireByGold_0'."\n" );
		self::openBoss( $this->uid );
		$ret = BossLogic::enterBoss( $this->uid, 1 );
		BossLogic::inspireByGold( $this->uid );
		BossLogic::leaveBoss(1);
		
		$ret = BossLogic::enterBoss( $this->uid, 1 );
		var_dump( $ret );
	}
	

	

	
	
	
	
	
// 	public function test_over_0()
// 	{
// 		self::openBoss( $this->uid );
// 		self::setBossSick();
// 		BossLogic::enterBoss( $this->uid, 1 );
// 		$bossdead = false;
// 		while ( !$bossdead )
// 		{
// 			//该用例需要把判定cd的注释掉
// 			BossLogic::attack($this->uid);
// 			$bossInfo = BossDAO::getBoss( 1 );
// 			if ( $bossInfo[BossDef::BOSS_HP] == 0 )
// 			{
// 				$bossdead = true;
// 			}
// 			$bosshp = $bossInfo[BossDef::BOSS_HP];
// 			echo "loop bosshp is: $bosshp \n";
// 		}
		
// 		$ret = BossLogic::over( $this->uid );
		
// 	}
	
	public function test_over_0()
	{
		self::openBoss( $this->uid );
		BossLogic::enterBoss( $this->uid , 1);
		$ret = BossLogic::over( $this->uid );
		var_dump( $ret );
	}

	*/
	
// 	public function test_getRankList_0()
// 	{
// 		var_dump( 'test=======test_getRankList_0'."\n" );
	
// 		self::openBoss( $this->uid );
	
// 		RPCContext::getInstance()->unsetSession( 'global.uid' );
// 		RPCContext::getInstance()->unsetSession(FormationDef::SESSION_KEY_FORMATION);
// 		EnUser::release( $this->uid );
// 		Atker::release();
	
// 		$data = new CData();
// 		$ret = $data->select( array( 'uid' ) )-> from( 't_user' )->where( array('uid','>',0) )->limit( 0 , 20)
// 		->query();
// 		foreach ( $ret as $key => $info)
// 		{
// 			self::atkForOne( $info['uid'] );
// 		}
// 		$ret = BossLogic::getAtkerRank( 1 );
	
// 		var_dump( "ranklist is: $ret  " );
// 		var_dump( $ret );
	
// 		$startTime = Util::getTime()-5;
// 		$endTime = Util::getTime() +5;
	
// 		//测试发奖
// 		BossLogic::reward( 1 , 1, $startTime, $endTime);
// 	}
	
	

// 	public function test_atk_0()
// 	{
// 		var_dump( 'test=======test_atk_0'."\n" );
// 		self::openBoss( $this->uid );
// 		BossLogic::enterBoss( $this->uid, 1 );
// 		BossLogic::attack( $this->uid );
	
// 		$atkInst = Atker::getInstance( $this->uid , 1);
// 		$ret = $atkInst->getAtkerInfo();
	
// 		var_dump( $ret );
// 	}
	
// 	public function test_superHero_0()
// 	{
// 		$ret = BossUtil::getSuperHero( 1 );
// 		var_dump( $ret );
// 	}

// 	public function test_rank_1()
// 	{
// 		$ret = BossLogic::getAtkerRank(1 );
// 		var_dump( $ret );
// 	}
	
	
// 	public function test_revive_0()
// 	{
// 		var_dump( 'test=======test_subCd_0'."\n" );
// 		$user = EnUser::getUserObj( $this->uid );
// 		$user->setVip( 10 );
// 		$user->update();
		
// 		self::openBoss( $this->uid );
// 		BossLogic::enterBoss( $this->uid, 1 );
// 		BossLogic::attack( $this->uid );
	
// 		$bossmaxHp = BossUtil::getBossMaxHp( 1 , 1);
// 		BossDAO::updateBoss( array( 'hp' => $bossmaxHp ) , array( array( 'boss_id', '=',1 ) ));
	
// 		BossLogic::revive( $this->uid );
// 		$atkerInst = Atker::getInstance( $this->uid , 1);
// 		$ret = $atkerInst->getAtkerInfo();
	
// 		var_dump( $ret );
// 	}
	
	public function test_unsupporttypebug_0()
	{
		BossLogic::over( $this->uid );
	}
	
	function setBossSick()
	{
		BossDAO::updateBoss( array( 'hp' => 100) ,array( array( 'boss_id','=',1 ) ));
	}
	
	function atkForOne( $uid )
	{
		RPCContext::getInstance()->setSession('global.uid', $uid);
		
		self::fullHp( $uid );
		BossLogic::enterBoss( $uid , 1);
		BossLogic::attack($uid);
		
		EnUser::release( $uid );
		Atker::release();
		RPCContext::getInstance()->unsetSession( 'global.uid' );
		RPCContext::getInstance()->unsetSession(FormationDef::SESSION_KEY_FORMATION);
	}
	
	public function fullHp( $uid )
	{
		$enterInfo = BossLogic::enterBoss( $uid , 1);
		if ( $enterInfo == 'bossDead' )
		{
			$bossmaxHp = BossUtil::getBossMaxHp( 1 , 1);
			BossDAO::updateBoss( array( 'hp' => $bossmaxHp ) , array(  array( 'boss_id', '=',1 ) ));
		}
	}
	
	function openBoss( $uid )
	{
		self::fullHp( $uid );
		if (!BossUtil::isBossTime( 1 ))
		{
			$startTime = BossUtil::getBossStartTime( 1 );
			echo "start time 3: $startTime \n";
			$curTime = Util::getTime();
			$offset = $curTime - $startTime-60;
			BossConf::$bossOffset = $offset;
			//BossDAO::updateBoss( array(BossDef::START_TIME => $curTime - 60, ) , array( array( BossDef::BOSS_ID, '=', 1  )));
		}
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */