<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivity.class.php 243505 2016-05-19 02:51:51Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivity.class.php $
 * @author $Author: MingTian $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-19 02:51:51 +0000 (Thu, 19 May 2016) $
 * @version $Revision: 243505 $
 * @brief “开服7天乐”入口类
 *  
 **/
class NewServerActivity implements INewServerActivity
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (!NewServerActivityUtil::isOpen()) 
		{
			throw new FakeException('new server activity is not open');
		}
	}
	
	public function getInfo($fight = 0)
	{
		Logger::trace("NewServerActivity::getInfo begin, uid:%d fightPower：%d", $this->uid, $fight);
		$ret =  NewServerActivityLogic::getInfo($this->uid, $fight);
		Logger::trace("NewServerActivity::getInfo end, uid:%d fightPower：%d", $this->uid, $fight);
		return $ret;
	}
	
	public function obtainReward($taskId)
	{
		Logger::trace("NewServerActivity::obtainReward begin, uid:%d taskId:%d", $this->uid, $taskId);
		$ret =  NewServerActivityLogic::obtainReward($this->uid, $taskId);
		Logger::trace("NewServerActivity::obtainReward end, uid:%d taskId:%d", $this->uid, $taskId);
		return $ret;
	}

	public function buy($day)
	{
		Logger::trace("NewServerActivity::buy begin, uid:%d dayOfGoods:%d", $this->uid, $day);
		$ret =  NewServerActivityLogic::buy($this->uid, $day);
		Logger::trace("NewServerActivity::buy end, uid:%d dayOfGoods:%d", $this->uid, $day);
		return $ret;
	}
	
	// 用于转请求到其他玩家线程执行,比如A加B为好友,B接收申请,则要更新A的好友任务进度,则B玩家通过这个函数把线程转到A玩家自己的更新任务函数来操作
	public function updateTypeByOtherUser($uid, $taskType, $finishNum, $finishType)
	{
		NewServerActivityManager::getInstance($uid)->updateTask($taskType, $finishNum, $finishType, true);
		Logger::trace("NewServerActivity::updateTypeByOtherUser end, uid:%d, taskType:%d, finishNum:%d, finishType:%s", $uid, $taskType, $finishNum, $finishType);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */