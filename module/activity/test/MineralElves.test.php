<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralElves.test.php 242295 2016-05-12 06:22:34Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/MineralElves.test.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-05-12 06:22:34 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242295 $
 * @brief 
 *  
 **/
class MineralElvesTest extends PHPUnit_Framework_TestCase
{
	private $domain_id;
    private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;
	private $inst;

	protected function setUp()
	{
		parent::setUp ();
		$ret=self::createUser();
		$this->uid=$ret['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		self::openMineralSwitch($this->uid);
		$this->inst=new MineralElves();
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
	/**
	*@group getMineralElves
	*/
	public function test_getMineralElves()
	{
		echo "start to test getMineralElves --------------------------------------/n";
		$mineralelvesinfo=$this->inst->getMineralElves();
		var_dump($mineralelvesinfo);
		echo "end test getMineralElves --------------------------------------/n";
	}
	/**
	 * @group getMineralElvesByDomainId
	 */
	public function test_getMineralElvesByDomainId()
	{
		echo "start to test getMineralElvesByDomainId --------------------------------------/n";
		$domain_id=10015;
		$info=$this->inst->getMineralElvesByDomainId($domain_id);
		var_dump($info);
		echo "end test getMineralElvesByDomainId --------------------------------------/n";
	}
	/**
	 * @group occupyMineralElves
	 */
	public function test_occupyMineralElves()
	{
		echo "start to test occupyMineralElves --------------------------------------/n";
		$domain_id=10015;
		$info=$this->inst->occupyMineralElves($domain_id);
		var_dump($info);
		echo "end test occupyMineralElves --------------------------------------/n";
	}
	
	public function test_getMineralElvesConf()
	{
		echo "start to test occupyMineralElves --------------------------------------/n";
		
		echo "end test occupyMineralElves --------------------------------------/n";
	}
	public static function openMineralSwitch($uid)
	{
		$console = new Console();
		$switchId = SwitchDef::MINERAL;
		$openConf = btstore_get()->SWITCH[$switchId];
		$needLv    =    $openConf['openLv'];
		$needBase  =    $openConf['openNeedBase'];
		if(!empty($needLv))
		{
			$console->level($needLv);
		}
		if(!empty($needBase))
		{
			$baseId    =    intval($needBase);
			$copyId = btstore_get()->BASE[$baseId]['copyid'];
			$copyId = intval($copyId);
			$console->passNCopies($copyId,BaseLevel::SIMPLE);
		}
		
	}
	private static function createUser()
	{
		$time= time();
		//创建用户
		$pid = $time;
		$str = strval($pid);
		$uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		Logger::trace('create user ret %s.',$ret);
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */