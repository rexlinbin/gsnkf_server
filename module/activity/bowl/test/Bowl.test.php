<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Bowl.test.php 150494 2015-01-06 10:16:23Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/test/Bowl.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-06 10:16:23 +0000 (Tue, 06 Jan 2015) $
 * @version $Revision: 150494 $
 * @brief 
 *  
 **/
 
class BowlTest extends PHPUnit_Framework_TestCase
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
	
	public function test_readBowlCSV()
	{
		$csvFile = '/home/pirate/static/config/treasurebowl.csv';
		$file = fopen($csvFile, 'r');
		if (FALSE == $file)
		{
			echo $argv[1] . "{$csvFile} open failed! exit!\n";
			exit;
		}

		$arrCsv = array();
		fgetcsv($file);
		fgetcsv($file);
		while (TRUE)
		{
			$data = fgetcsv($file);
			if (empty($data))
				break;
			$arrCsv[] = $data;
		}
		
		$ret = EnBowl::readBowlCSV($arrCsv);
		var_dump($ret);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */