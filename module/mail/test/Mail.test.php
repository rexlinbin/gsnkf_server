<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mail.test.php 65748 2013-09-23 02:40:39Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mail/test/Mail.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-23 02:40:39 +0000 (Mon, 23 Sep 2013) $
 * @version $Revision: 65748 $
 * @brief 
 *  
 **/
class MailTset extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;
	private $receUid;
	
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
		
		$data = new CData();
		$ret = $data ->select( array( 'uid' ) )->from( 't_user' )->where( array( 'uid' , '>' , 0 ) )->limit(0, 2)->query();
		if ( empty( $ret ) )
		{
			echo 'no user for testing';
			return ;
		}
		$this->receUid = $ret[ 0 ]['uid'];
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
	
	public function test_mailSend_0()
	{
		$mail = new Mail();
		$mail->sendMail( $this->receUid , 'hello' , 'world');
	}
	
	public function test_mailGet_0()
	{	
		$mailtp = new MailTemplate();
		$mail = new Mail();
		//测试跑通
		$mail->getBattleMailList( 0 , 10, true );
		$mail->getMailBoxList(0, 10, true);
		$mail->getPlayMailList( 0 , 10, true );
		$mail->getSysItemMailList( 0, 10, true );
		$mail->getSysMailList( 0, 10, true );
		
		$mail->getBattleMailList( 0 , 10, false );
		$mail->getMailBoxList(0, 10, false);
		$mail->getPlayMailList( 0 , 10, false );
		$mail->getSysItemMailList( 0, 10, false );
		$mail->getSysMailList( 0, 10, false );
	
		
		//测试发送和接收
		$mail->sendMail(  $this->receUid , '江泽民' , '六四');

		$ret = $mail->getPlayMailList( 0 , 10, false );
		
		$mailtp->sendArenaAward($this->uid, 2, 24, 100, 100);
		$mailtp->sendArenaAward($this->uid, 2, 24, 100, 100);
		$mid1 = $mailtp->sendArenaAward($this->uid, 2, 24, 100, 100);
		
		$mailtp->sendCharge($this->uid, 20);
		$mailtp->sendCharge($this->uid, 20);
		$mid2 = $mailtp->sendCharge($this->uid, 20);
		
		$mailtp->sendMineralAttack($this->uid, array(), 1, true);
		$mailtp->sendMineralAttack($this->uid, array(), 1, true);
		$mid3 = $mailtp->sendMineralAttack($this->uid, array(), 1, true);
		
		$mid = $mailtp->sendFriend( 11, $this->receUid,$this->uid, 'nihao ..');
		
		$ret = $mail->getPlayMailList( $mid, 10, true );
		var_dump( '================' );
		var_dump( $ret );
		$ret = $mail->getSysMailList( $mid2, 10, true );
		var_dump( '================' );
		var_dump( $ret );
		$ret = $mail->getBattleMailList( $mid3, 10, true );
		var_dump( '================' );
		var_dump( $ret );
		
		
		$mail->setApplyMailAdded( $this->uid );
	}
	
	public function test_canSendFriendMail_0()
	{
		
		MailTemplate::sendFriend( 11 , $this->uid, $this->receUid, '擦, 加我！');

		$canOrNot = MailLogic::canApplyFriend($this->receUid, $this->uid);
		$this->assertTrue( $canOrNot == false );
		
		RPCContext::getInstance()->setSession('global.uid', $this->receUid);
		$mail = new Mail();
		$ret = $mail->getPlayMailList( 0 , 10);
		$mailInfo = $ret['list'][0];
		$this->assertTrue( $mailInfo[ MailDef::MAIL_SQL_TEMPLATE_ID] == MailTemplateID::FRIEND_APPLY );
	
		MailLogic::setApplyMailStatus($this->receUid, $mailInfo[ MailDef::MAIL_SQL_ID ], 1);
		$canOrNot = MailLogic::canApplyFriend($this->receUid, $this->uid);
		//$this->assertTrue( $canOrNot == true );
	}
	
	public function test_sendApplySeveralDays_0()
	{
		$console = new Console();
		$console->sendApplyDays($this->receUid, 5);
		$mail = new Mail();
		$ret = $mail->getMailBoxList(0, 10);
		foreach ( $ret[ 'list' ] as $onePiece )
		{
			Logger::debug('this array is %s',$onePiece );
			$this->assertTrue( $onePiece[ 'va_extra' ][ 'status' ] == 0 );
		}
		
		MailLogic::setApplyMailStatus($this->uid, $this->receUid, 1);
		
		$ret = $mail->getMailBoxList(0, 10);
		foreach ( $ret[ 'list' ] as $onePiece )
		{
			$this->assertTrue( $onePiece[ 'va_extra' ][ 'status' ] == 1 );
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */