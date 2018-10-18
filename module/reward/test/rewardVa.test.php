<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: rewardVa.test.php 62855 2013-09-04 04:12:17Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/rewardVa.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-04 04:12:17 +0000 (Wed, 04 Sep 2013) $
 * @version $Revision: 62855 $
 * @brief 
 *  
 **/
class RewardVa extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
	}

	public function test_getva()
	{
		$data = new CData();
		$ret = $data->select( array( 'rid','source','send_time','recv_time','va_reward' ) )
		->from( 't_reward' )
		->where( array( 'uid', '=', 22842 ) )
		->query();
		var_dump( $ret );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */