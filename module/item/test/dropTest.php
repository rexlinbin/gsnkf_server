<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: dropTest.php 88233 2014-01-21 12:40:44Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/test/dropTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-01-21 12:40:44 +0000 (Tue, 21 Jan 2014) $
 * @version $Revision: 88233 $
 * @brief 
 *  
 **/
class DropTest extends PHPUnit_Framework_TestCase
{
	private $drop;

	/* (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	*/
	protected function setUp()
	{
		$this->drop = new Drop();
		parent::setUp ();
	}

	protected function tearDown()
	{
	}

	public function test_dropMixed()
	{
		$ret = $this->drop->dropMixed(101);
	}
	
	public function test_getDropInfo()
	{
		$dropId = 20103;
		$ret = $this->drop->getDropInfo($dropId);
		print_r($ret);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */