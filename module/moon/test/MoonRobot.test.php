<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonRobot.test.php 171289 2015-05-06 07:46:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/test/MoonRobot.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-05-06 07:46:39 +0000 (Wed, 06 May 2015) $
 * @version $Revision: 171289 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/test/UserClient.php";
class MyRobot extends UserClient
{
	protected $err = false;
	protected $errTryNum = 1;

	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		printf('pid:%d login ok\n', $pid);
		$this->setClass('moon');
	}
}

class MoonRobot extends BaseScript
{
	private $ipaddr = ScriptConf::PRIVATE_HOST;
	private $pid = 73381;

	protected function executeScript($arrOption)
	{
		$robot = new MyRobot($this->ipaddr, 7777, $this->pid);
		$ret = $robot->getMoonInfo();
		var_dump($ret);
		//$ret = $robot->attackBoss(25);
		//var_dump($ret);
		$ret = $robot->openBox(26, 4);
		var_dump($ret);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */