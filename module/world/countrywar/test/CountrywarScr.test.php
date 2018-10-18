<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountrywarScr.test.php 212667 2015-11-26 07:04:49Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/test/CountrywarScr.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-11-26 07:04:49 +0000 (Thu, 26 Nov 2015) $
 * @version $Revision: 212667 $
 * @brief 
 *  
 **/
class CountrywarScr extends BaseScript
{
	private $uid ;
	private $pid ;
	private $uname ;
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) {
		// TODO Auto-generated method stub
		
		$console = new Console();
		$console->openCw();
		/* if( !isset( $arrOption[0] ) )
		{
			$this->createAndSetUser();
			$this->test_getCountryWarInfo();
			return;
		}
 		else
		{
			if( !isset( $arrOption[1] ) ||!isset( $arrOption[2] ) )
			{
				list($this->pid,$this->uid) = $this->getUserInfo();
			}
			else 
			{
				$this->pid = $arrOption[1];
				$this->uid = $arrOption[2] ;
			}
			$this->setUser($this->uid);
		} 
		
		$op = $arrOption[0];
		switch ( $op )
		{
			case 'info':
				$this->test_getCountryWarInfo();
				break;
			case 'team':
				$this->test_team();
				break;
			case 'sign':
				$this->createAndSetUser();
				$this->test_sign();
				break;
			case 'range':
				$this->test_range();
				break;
			case 'getloginInfo':
				$this->test_getLoginInfo();
		}
		 */
		
	}

	/*
	public function createAndSetUser()
	{
		$this->pid = 40000 + rand(0,9999);
		$utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid']; 
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
			
		$user = EnUser::getUserObj( $this->uid );
		$console = new Console();
		$console->level( 90 );
		$console->silver( 9999999 );
			
		$user->update();
		EnUser::release( $this->uid );
	}
	
 	public function setUser($uid )
	{
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
			
		$user = EnUser::getUserObj( $this->uid );
		$console = new Console();
		$console->level( 90 );
		$console->silver( 9999999 );
			
		$user->update();
		EnUser::release( $this->uid );
	}
	public function getUserInfo( $db = '' )
	{
		$data = new CData();
		if( !empty( $db ) )
		{
			$data->useDb($db);
		}
		$ret = $data ->select( array('pid','uid') )->from( 't_user' )
		->where(array( 'pid','>',0 ))
		->limit(0, 1)
		->query();
	
		return $ret[0];
	}
	public function test_getCountryWarInfo()
	{
		$cw = new CountryWarInner();
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	}
		
 	public function test_team()
	{
		CountryWarScrLogic::syncAllTeamFromPlat2Cross();
	} 
	 
      public function test_sign()
	{
		$cw = new CountryWarInner();
		$signInfo = $cw->signForOneCountry( 1 );
		var_dump($signInfo);
		
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	}   
 	
  	public function test_range()
	{
		CountryWarLogic::scrRangeRoom(false);
		$cw = new CountryWarInner();
		$info = $cw->getCoutrywarInfo();
		var_dump($info );
	} 
		 
 	public function test_getLoginInfo()
	{
		$cw = new CountryWarInner();
		$signInfo = $cw->signForOneCountry( 1 );
		var_dump($signInfo);
	
		$info = $cw->getLoginInfo();
		var_dump($info );
	}  */
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */