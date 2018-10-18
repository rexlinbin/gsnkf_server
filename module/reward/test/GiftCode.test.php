<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GiftCode.test.php 64109 2013-09-11 07:05:51Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/GiftCode.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-11 07:05:51 +0000 (Wed, 11 Sep 2013) $
 * @version $Revision: 64109 $
 * @brief 
 *  
 **/
class GiftcodeTest extends PHPUnit_Framework_TestCase
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
	
	public function test_giftByCode()
	{
		$ret = GiftCodeLogic::getGiftByCode( $this->uid , 'SGTEST2-0eebnc_layg8q2');
		var_dump( $ret );
	}
	
	public function test_fresher_0()
	{
		RPCContext::getInstance()->setSession('global.qid', ' ');
		$qid = RPCContext::getInstance()->getSession('global.qid');
		if ( $qid == null )
		{
			echo 'empty qid'.$qid;
		}
		
		$md5str = $qid . GameConf::LY_SERVER_ID . GameConf::LY_KEY;
		$sign = strtoupper(md5($md5str));
		echo 'code is:'.$sign;
		
		$ret = GiftCodeLogic::getGiftByCode($this->uid, $sign);
		var_dump( $ret );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */