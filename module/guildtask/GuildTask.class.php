<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTask.class.php 141750 2014-11-24 09:36:07Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/GuildTask.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-11-24 09:36:07 +0000 (Mon, 24 Nov 2014) $
 * @version $Revision: 141750 $
 * @brief 
 *  
 **/
class GuildTask implements IGuildTask
{
	private $uid = null;
	
	function __construct()
	{
		//现在只支持玩家自己操作，如果任务有被动完成的，记得把这个改掉
		$this->uid = RPCContext::getInstance()->getUid();
		if ( empty( $this->uid ) )
		{
			throw new FakeException( 'uid not in session, means not online' );
		}
		
		if(!GuildTaskLogic::canGuildtask($this->uid))
		{
			throw new FakeException( 'userlv or guildhalllv not satisfy the cond');
		}
		
	}
	/* (non-PHPdoc)
	 * @see IGuildTask::getTaskInfo()
	 */
	public function getTaskInfo() 
	{
		$ret = GuildTaskLogic::getTaskInfo( $this->uid );
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IGuildTask::refTask()
	 */
	public function refTask() 
	{
		$ret = GuildTaskLogic::refTask($this->uid);
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IGuildTask::acceptTask()
	 */
	public function acceptTask($pos, $TTid) 
	{
		if( $pos < 0|| $TTid <0 )
		{
			throw new FakeException( 'invalid args' );
		}
		GuildTaskLogic::acceptTask($this->uid, $pos, $TTid);
		return 'ok';
	}

	/* (non-PHPdoc)
	 * @see IGuildTask::forgiveTask()
	 */
	public function forgiveTask($pos, $TTid) 
	{
		if( $pos < 0|| $TTid <0 )
		{
			throw new FakeException( 'invalid args' );
		}
		GuildTaskLogic::forgiveTask($this->uid, $pos, $TTid);
		return 'ok';
	}

	/* (non-PHPdoc)
	 * @see IGuildTask::doneTask()
	 */
	public function doneTask($pos, $TTid, $useGold = 0) 
	{
		if ( $pos < 0 || $TTid < 0||($useGold != 0 && $useGold != 1) )
		{
			throw new FakeException( 'invalid args' );
		}
		$ret = GuildTaskLogic::doneTask($this->uid, $pos, $TTid, $useGold);
		EnActive::addTask( ActiveDef::GUILDTASK, 1 );
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IGuildTask::handIn()
	 */
	public function handIn($pos, $TTid,$itemIdNumArr) 
	{
		if( $pos < 0|| $TTid <0||empty( $itemIdNumArr ) )
		{
			throw new FakeException( 'invalid args' );
		}
		GuildTaskLogic::handIn($this->uid,$pos,$TTid,$itemIdNumArr);
		return 'ok';
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */