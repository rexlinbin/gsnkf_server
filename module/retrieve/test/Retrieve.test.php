<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Retrieve.test.php 146528 2014-12-16 12:32:32Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/test/Retrieve.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-16 12:32:32 +0000 (Tue, 16 Dec 2014) $
 * @version $Revision: 146528 $
 * @brief 
 *  
 **/
 
class RetrieveTest extends PHPUnit_Framework_TestCase
{
	private $uid = 0;
	protected function setUp()
	{
		parent::setUp ();
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
	
	public function test_1()
	{
		$ret = CompeteLogic::getCompeteTime();
		var_dump($ret);
		
		if (is_array($ret)) 
		{
			var_dump(strftime('%Y%m%d %H%M%S', $ret[0]));
			var_dump(strftime('%Y%m%d %H%M%S', $ret[1]));
		}
		
		$ret = CompeteLogic::getBeforeCompeteTime();
		var_dump($ret);
		
		if (is_array($ret))
		{
			var_dump(strftime('%Y%m%d %H%M%S', $ret[0]));
			var_dump(strftime('%Y%m%d %H%M%S', $ret[1]));
		}
	}
	
	public function test_2()
	{
		$ret = RetrieveLogic::getRetrieveInfo($this->uid);
		var_dump($ret);
	}
	
	public function test_3()
	{
		$ret = RetrieveLogic::retrieve($this->uid, RetrieveDef::BOSS, TRUE);
		var_dump($ret);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */