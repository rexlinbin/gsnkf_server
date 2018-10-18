<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ScoreShopTest.php 166998 2015-04-11 10:39:09Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/scoreshop/test/ScoreShopTest.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-04-11 10:39:09 +0000 (Sat, 11 Apr 2015) $
 * @version $Revision: 166998 $
 * @brief 
 *  
 **/
class ScoreShopTest extends PHPUnit_Framework_TestCase
{
	public static $uid;
	public static $pid;
	private $uname;
	
	protected function setUp()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
	}
	
	protected function tearDown()
	{
		unset( $this->obj );
		$this->obj = NULL;
	}

    public function testInfo()      
    {
    	echo "Test Start.\n";
    	
    	RPCContext::getInstance()->setSession('global.uid', $this->uid);
    	$uid = RPCContext::getInstance()->getUid();
    	var_dump($uid);
    	
		$scoreShop = new ScoreShop();
		$shopInfo = $scoreShop->getShopInfo();
		var_dump($shopInfo);
		
		echo "Test End.\n";
		return $shopInfo;
    }
    
    public function testBuy()
    {
    	echo "Test Buy Start.\n";
    	
    	RPCContext::getInstance()->setSession('global.uid', $this->uid);
    	
    	$scoreShop = new ScoreShop();
    	$ret = $scoreShop->buy(1,1);
    	
    	var_dump($ret);
    	
    	echo "Test Buy End.\n";
    	
    	return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */