<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Guildtask.test.php 115712 2014-06-19 07:40:13Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/test/Guildtask.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-19 07:40:13 +0000 (Thu, 19 Jun 2014) $
 * @version $Revision: 115712 $
 * @brief 
 *  
 **/
class GuildtaskTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

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
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
		RPCContext::getInstance()->unsetSession( GuildTaskDef::GUILD_TASK_SESSION );
		GuildTaskObj::release();
	}

	public function test_getTaskInfo_0()
	{
		$guildtask = new GuildTask();
		$ret = $guildtask->getTaskInfo();
		
		var_dump( $ret );
	}
	
	public function test_refTask_0()
	{
		$guildtask = new GuildTask();
		$ret1 = $guildtask->getTaskInfo();
		$ret2 = $guildtask->refTask();
		$ret3 = $guildtask->getTaskInfo();
		
		//这个有可能是一样的，概率问题
		$this->assertTrue( $ret1[GuildTaskDef::VA_GUILDTASK]['task'] != $ret3[GuildTaskDef::VA_GUILDTASK]['task'] );
	}
	
	public function test_accept_0()
	{
		$guildtask = new GuildTask();
		$ret = $guildtask->getTaskInfo();
		
		$guildtask->acceptTask(0, $ret[GuildTaskDef::VA_GUILDTASK]['task'][0]['id']);
		$ret = $guildtask->getTaskInfo();
		$this->assertTrue( $ret[GuildTaskDef::VA_GUILDTASK]['task'][0]['status'] == GuildTaskDef::ACCEPT );
		
		$guildtask->forgiveTask(0,$ret[GuildTaskDef::VA_GUILDTASK]['task'][0]['id']);
		$ret = $guildtask->getTaskInfo();
		
		$this->assertTrue( $ret[GuildTaskDef::VA_GUILDTASK]['task'][0]['status'] == GuildTaskDef::NOT_ACCEPT );
		$this->assertTrue( $ret[GuildTaskDef::FORGIVE_TIME] == Util::getTime() );
	}
	
	public function test_dotask_0()
	{
		$taskPos = 2;
		//要测不同的任务最好是让策划给个表啥的，不然随机会很蛋疼
		
		$guildtaskInst = GuildTaskObj::getInstance($this->uid);
		
		$taskarr = array(
				array('id' => 1000,'status' => 0,'num' =>0),
				array('id' => 3000,'status' => 0,'num' =>0),
				array('id' => 4000,'status' => 0,'num' =>0),
		);
		
		$guildtaskInst->setTask($taskarr);
		$guildtaskInst->update();
		
		
		$guildtask = new GuildTask();
		$ret = $guildtask->getTaskInfo();
		
		$guildtask->acceptTask($taskPos, $ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['id']);
		$ret = $guildtask->getTaskInfo();
		$this->assertTrue( $ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['status'] == GuildTaskDef::ACCEPT );
		
		$taskid = $ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['id'];
		
		$guildtaskConf = btstore_get()->GUILD_TASK;
		$tasktype = $guildtaskConf[$taskid][GuildTaskDef::BTS_TYPE];
		$guildtaskCond = $guildtaskConf[$taskid][GuildTaskDef::BTS_FINISH_COND];
		
		$bag = BagManager::getInstance()->getBag($this->uid);
		
		if ( $tasktype == GuildTaskType::MEND_CITY||$tasktype == GuildTaskType::RUIN_CITY )
		{
			$beforeTaskInfo = EnGuildTask::beforeTask($this->uid, $tasktype);
			var_dump($beforeTaskInfo);
		}
		
		
		if ( $taskid == 1000 )
		{
			$itemTmplArr = array(101101=>1);
		}
		else if ($taskid == 3000)
		{
			$itemTmplArr = array(50201=>1);
		}
		if ( !empty( $itemTmplArr ) )
		{
			$arrItemId = ItemManager::getInstance()->addItems($itemTmplArr);
			$bag->addItems($arrItemId);
			ItemManager::getInstance()->update();
			$bag->update();
			
			$guildtask->handIn($taskPos, $taskid, array( $arrItemId[0] => $guildtaskCond[2] ));
		}
		else
		{
			EnGuildTask::taskIt($this->uid, $tasktype, $guildtaskCond[1], $guildtaskCond[2]);
		}
		
// 		$needAddArm = array(
// 				1 => array(101101,101202,101301,101401,),
// 				2 => array(102101,102201,102301,102401,),
// 				3 => array(103101,103201,103312,103402,),
// 				4 => array(104101,104201,104303,104403,),
// 		);
		
// 		$needAddTrea = array(
// 				1 => array(501301,501401,501501,),
// 				2 => array(502301,502401,502501,),
// 		);

// 		$needProps = array(
// 				50001,50101,50201,50301,50401,
// 		);

// 		if ( $tasktype == GuildTaskType::HANDIN_ARM )
// 				{
// 					foreach ( $needAddArm[$guildtaskCond[0]] as $itemtmpl1 )
// 						{
// 						$arrItemTpl[$itemtmpl1] = 5;
// 					}
// 				}
// 				elseif ($tasktype == GuildTaskType::HANDIN_TREASURE)
// 				{
// 					foreach ( $needAddTrea[$guildtaskCond[0]] as $itemtmpl2 )
// 						{
// 						$arrItemTpl[$itemtmpl2] = 5;
// 					}
// 				}
// 				else
// 					{
// 					foreach ( $needProps as $itemtmpl3 )
// 						{
// 						$arrItemTpl[$itemtmpl3] = 5;
// 					}
// 				}
		
// 		if ()
// 		{
			
// 		}
		
		$ret = $guildtask->getTaskInfo();
		
		Logger::debug('tasked num:%d,guildcand num:%d',$ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['num'],$guildtaskCond[2]);
		$this->assertTrue($ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['num'] == $guildtaskCond[2]);
		$guildtask->doneTask($taskPos, $taskid);
		
		$ret = $guildtask->getTaskInfo();
		
		$this->assertTrue( $ret[GuildTaskDef::VA_GUILDTASK]['task'][$taskPos]['status'] == GuildTaskDef::NOT_ACCEPT );
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */