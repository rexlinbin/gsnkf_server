<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Sign.test.php 136898 2014-10-21 03:34:00Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/test/Sign.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-21 03:34:00 +0000 (Tue, 21 Oct 2014) $
 * @version $Revision: 136898 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/def/Sign.def.php');
require_once ('/home/pirate/rpcfw/conf/Sign.cfg.php');

class SignTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 60000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
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

	public function test_getSignInfo_0()
	{
		$sign = new Sign();
		
		$accInfo = $sign->getAccInfo( $this->uid );
		var_dump( $accInfo );
		
		$normalInfo = $sign->getNormalInfo();
		var_dump( $normalInfo );
	}
	
	public function test_gainReward_0()
	{
		Logger::debug('now begin test_gainReward_0');
		$sign = new Sign();
		$sign->getNormalInfo();
		$console = new Console();
		$console->signNor( 10 );
		$ret = $sign->getNormalInfo();
		var_dump( $ret );
		
		$sign->gainNormalSignReward( 7 );
		
		$sign->getAccInfo();
		$console->signAcc( 10 );
		$ret = $sign->getAccInfo();
		var_dump( $ret );
		
		$sign->gainAccSignReward( 1 );
	}
	
	public function test_gainNormalList_0()
	{
		$sign = new Sign();
		$ret = $sign->getNormalInfo();
		var_dump( $ret );
		
		$sign->gainNormalSignReward( 1 );
		$ret = $sign->getNormalInfo();
		var_dump( $ret );
		$console = new Console();
		for ( $i= 1; $i <= NormalsignLogic::getMaxNormalDays(); $i++ )
		{
			$console->signNor( $i );
			$ret = $sign->getNormalInfo();
			var_dump( " step  $i before" );
			var_dump( $ret ) ;
			if ( $i + 1 >=8  )
			{
				$sign->gainNormalSignReward( 7 );
			}
			else 
			{
				$sign->gainNormalSignReward( $i +1 );
			}
			
			var_dump( " step  $i after" );
			$ret = $sign->getNormalInfo();
			var_dump( $ret ) ;
		}
		
	}
	
	public function test_gainErrStep_0()
	{
		$sign = new Sign();
		$ret = $sign->getNormalInfo();
		var_dump( '===========================1' );
		var_dump( $ret );
		try {$sign->gainNormalSignReward( 7 );}
		catch (Exception $e)
		{
			var_dump( '===========================2' );
		}
		$ret = $sign->getNormalInfo();
		var_dump( $ret );
	}

	public function test_monthsigninfo_0()
	{
		$sign = new Sign();
		$ret = $sign->getMonthSignInfo();
		var_dump( $ret );
	}
	
	public function test_monthreward_0()
	{
		$sign = new Sign();
		$ret = $sign->getMonthSignInfo();
		
		$sign->gainMonthSignReward(1);
		
	}
	
	
	public function test_getMonConfId_0()
	{
		echo " \n";
		
		for ( $i = 1; $i <= 12; $i++ )
		{
			$timeStr = '2014';
			if( $i < 10 )
			{
				$timeStr .= '0';
			}
			$timeStr .= $i;
			$timeStr .= '12202020';
			$timeStamp = strtotime($timeStr);
			$confId = MonthSignLogic::getSignConfId( $timeStamp );
			echo "$timeStamp  and $timeStr ";
			echo "$confId \n";
		}
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */