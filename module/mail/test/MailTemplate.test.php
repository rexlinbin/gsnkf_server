<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MailTemplate.test.php 89793 2014-02-13 07:13:03Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mail/test/MailTemplate.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-02-13 07:13:03 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89793 $
 * @brief 
 *  
 **/
class MailTemplateTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;
	private $uidTwo;
	
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
		
		$pid = 50000 + rand(0,9999);
		$utid = 1;
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, $utid, $uname);
		$users = UserLogic::getUsers( $pid );
		$this->uidTwo = $users[0]['uid'];
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
	
	public function test_mailTemplate_0()
	{
		$console = new Console();
// 		$console->sendMineralMail($this->uid, $this->uidTwo);
// 		$console->sendArenaMail($this->uid, $this->uidTwo);
// 		$console->sendFriendMail($this->uid, $this->uidTwo);
// 		$console->sendFragseizeMail($this->uid, $this->uidTwo);
//		$console->sendRobForceMail( $this->uid , $this->uidTwo);
		
// 		$guildInfo = array(
// 			'guild_uid' => 1,
// 			'guild_name' => 'chunhua',
// 		);
// 		$kickerInfo = EnUser::getUserObj( $this->uid )->getTemplateUserInfo();
// 		MailTemplate::sendGuildResponse($this->uid, $guildInfo, true);
// 		MailTemplate::sendGuildResponse($this->uid, $guildInfo, false);
// 		MailTemplate::sendGuildKick( $this->uid , $guildInfo, $kickerInfo);

		$console->sendPlatformMail();
		$mail = new Mail();
		$mailList = $mail->getMailBoxList(0, 40);
		
		var_dump( $mailList );
		
// 		$mailList = $mail->getMineralMailList(0, 40);
// 		var_dump( $mailList );
		
		
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */