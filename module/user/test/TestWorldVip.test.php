<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TestWorldVip.test.php 190099 2015-08-11 06:12:55Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/test/TestWorldVip.test.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-08-11 06:12:55 +0000 (Tue, 11 Aug 2015) $
 * @version $Revision: 190099 $
 * @brief 
 *  
 **/

class TestWorldVip extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if( count( $arrOption ) != 3 )
		{
			throw new FakeException( 'para pid parent|child vip' );
		}
		
		
		$pid = intval( $arrOption[0] );
		if ( $pid == 0 )
		{
			$pid = self::getNextPid();
		}
		
		$utid = 1;
		$uname = self::getUname();
		
		
		$treateAsChild = $arrOption[1];
		$needCheckVipBefore = 0;

		Logger::debug(" argsi: %s %s  ", $treateAsChild, $pid);
		if ( $treateAsChild == 'child' )
		{
			$needCheckVipBefore = intval( $arrOption[2] );
		}
		$needCheckVipAfter = intval( $arrOption[2] );
		
		$users = UserLogic::getUsers($pid);
		if( !empty( $users ) )
		{
			printf( 'user exist' );
			return;
		}
		else 
		{
			self::checkVip($pid, $needCheckVipBefore);
			self::createUser($pid, $utid, $uname);
		}
		$usersAfter = UserLogic::getUsers($pid);
		RPCContext::getInstance()->setSession('global.uid', $usersAfter[0]['uid']);
		
		if( $treateAsChild == 'parent' )
		{
			$console = new Console();
			$sumGold = User4BBpayDao::getSumGoldByUid($usersAfter[0]['uid']);
			foreach (btstore_get()->VIP as $vipInfo)
			{
				if( $needCheckVipAfter == $vipInfo['vipLevel'] )
				{
					$needGold = $vipInfo['totalRecharge']-$sumGold;
					$needGold = $needGold<0? 0:$needGold;
					break;
				}
			}
			$console->addGoldOrder($needGold);
		}

		self::checkVip($pid, $needCheckVipAfter);
		
		$user = new User();
		$ret = $user->login( $pid );
	    
	    var_dump( $ret );
	    
		echo "pid:$pid \n";
	}
	
	function checkVip( $pid, $vip )
	{
		$maxVip = self::getMaxVip($pid);
		if( $maxVip != $vip )
		{
			throw new FakeException( 'max vip is: %s, not vip: %s', $maxVip, $vip );
		}
	}
	
	function getNextPid()
	{
		$data = new CData();
		$ret = $data->select(array('pid'))->from('t_user')
		->where('uid','>',0)->orderBy( 'pid' , false)->limit(0 , 1)
		->query();
		if( empty( $ret ) )
		{
			$pid = 40000;
		}
		else
		{
			$pid = $ret[0]['pid']+1;
		}
		
		return $pid;
	}
	
	
	function getMaxVip( $pid )
	{
		$vip = UserWorldDao::getCreateVip($pid);
		
		return $vip;
	}
	
	function getUname()
	{
		$data = new CData();
		$ret = $data->select(array('uid'))->from('t_user')
		->where('uid','>',0)->orderBy( 'uid' , false)->limit(0 , 1)
		->query();
		if( empty( $ret ) )
		{
			$uname = 'w0';
		}
		else
		{
			$uname = 'w'.($ret[0]['uid']+1);
		}
		
		return $uname;
	}
	
	function createUser( $pid,$utid,$uname )
	{
		UserLogic::createUser($pid,$utid,$uname );
	}
		
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */