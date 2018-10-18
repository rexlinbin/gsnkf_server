<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWar.test.php 152943 2015-01-16 04:05:50Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/test/GuildWar.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-16 04:05:50 +0000 (Fri, 16 Jan 2015) $
 * @version $Revision: 152943 $
 * @brief 
 *  
 **/
 
class GuildWarTest extends PHPUnit_Framework_TestCase
{
	private $uid = 0;
	protected function setUp()
	{
		parent::setUp();
		$this->uid = 21000;
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	protected static function getPrivateMethod($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */