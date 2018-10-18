<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnGuildTask.class.php 145543 2014-12-11 10:36:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/EnGuildTask.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-11 10:36:30 +0000 (Thu, 11 Dec 2014) $
 * @version $Revision: 145543 $
 * @brief 
 *  
 **/
class EnGuildTask
{
	/**
	 * 完成公会任务
	 * @param int $uid
	 * @param int $taskType 类型：@see GuildTaskType 的注释
	 * @param int $detailId 此参数在不同的模块中意义不同 详见@see GuildTaskType
	 * @param int $num	做该任务的次数，一般为1次
	 */
	public static function taskIt($uid,$taskType,$detailId,$num)
	{
		if(!GuildTaskLogic::canGuildtask($uid))
		{
			return;
		}
		
		$ret = GuildTaskLogic::doTaskOnce($uid,$taskType,$detailId,$num);
		
		if ( $ret == 'doOnce' )
		{
			$bag = BagManager::getInstance()->getBag($uid);
			$bag->update();
			$user = EnUser::getUserObj($uid);
			$user->update();
			$guildInst = GuildTaskObj::getInstance($uid);
			$guildInst->update();
		}
	}
	
	/**
	 * 在做操作前添加（一般的模块不需要，有需要的会单独通知）
	 * @param int $uid
	 * @param int $taskType 详见 @see GuildTaskType
	 * @return 
	 * array(
	 * 			'can' => false,//是否可以执行操作
	 * 			'extra' =>array(),//各类型要返回的一些其他数据，没有即为空，@see below
	 * 			
	 * 
	 * 'level' 执行破坏城防和修复城防需要目标城池的最低等级
	 * 'num' 破坏或者修复的数值
	 * 			
	 * )
	 */
	public static function beforeTask($uid,$taskType)
	{
		$ret = array('can' => false,'extra' =>array('level' => 100,'num' => 0));
		
		//return $ret;
		
		$guildtaskInst = GuildTaskObj::getInstance($uid);
		$inDoingTaskId = $guildtaskInst->getIndoingTaskId();
		$guildtask = btstore_get()->GUILD_TASK;
		
		if (!isset( $guildtask[$inDoingTaskId][GuildTaskDef::BTS_TYPE] ))
		{
			return $ret;
		}
		if ($taskType != $guildtask[$inDoingTaskId][GuildTaskDef::BTS_TYPE])
		{
			return $ret;
		}
		$inDoingPos = $guildtaskInst->getIndoingTaskPos();
		$task = $guildtaskInst->getTaskDetail();
		if ( $task[$inDoingPos]['num'] >= $guildtask[$inDoingTaskId][GuildTaskDef::BTS_FINISH_COND][2] )
		{
			Logger::debug('already do num: %d >= finish need num: %d ',$task[$inDoingPos]['num']
			,$guildtask[$inDoingTaskId][GuildTaskDef::BTS_FINISH_COND][2] );
			return $ret;
		}
		switch ( $taskType )
		{
			case GuildTaskType::RUIN_CITY:
			case GuildTaskType::MEND_CITY:
				$ret['can'] = true;
				$ret['extra']['level'] = $guildtask[$inDoingTaskId][GuildTaskDef::BTS_FINISH_COND][1];
				
				//$guildtaskOne = btstore_get()->GUILD_TASK[$inDoingTaskId];
				$ret['extra']['num'] = $guildtask[$inDoingTaskId][GuildTaskDef::BTS_FINISH_COND][0];
				$ret['extra']['perExe'] = $guildtask[$inDoingTaskId][GuildTaskDef::BTS_NEED_EXE];
				break;
			default:
				Logger::warning('type:%d no need to call this', $taskType);
				break;
		}
		
		return $ret;
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
