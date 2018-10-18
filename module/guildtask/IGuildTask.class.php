<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuildTask.class.php 117054 2014-06-24 10:49:04Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/IGuildTask.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-24 10:49:04 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 117054 $
 * @brief 
 *  
 **/
interface  IGuildTask
{
	/**
	 * 当前任务的信息
	 * @return
	 * 
	 * 'task_num' => int, 今天已经完成任务的数量
	 * 'forgive_time' => int,放弃任务的时间
	 * 'ref_num' => int, 刷新任务的次数
	 * 'task' => array
	 * 		(
	 * 			0 => array( 'id' => int, 'status' => int, 'num' => int, ),
	 * 			1...
	 * 			2...
	 * 		)
	 */
	function getTaskInfo();
	
	/**
	 * 刷新后的任务信息
	 * @return
	 * array
	 * (
	 * 		0 => array( 'id' => int, 'status' => int, 'num' => int, ),
	 * 		1
	 * 		2...
	 * )
	 */
	function refTask();
	
	/**
	 * 接一个任务
	 * @param int $pos 任务位置
	 * @param int $TTid 任务id
	 */
	function acceptTask($pos, $TTid);
	
	/**
	 * 放弃一个任务
	 * @param int $pos
	 * @param int $TTid
	 */
	function forgiveTask($pos, $TTid);
	
	/**
	 * 完成一个任务（领取已完成任务的奖励）
	 * @param int $pos
	 * @param int $TTid
	 * @param string $useGold 是否用金币强制完成1 为强制完成
	 * @return
	 * array
	 * (
	 * 		0 => array( 'id' => int, 'status' => int, 'num' => int, ),
	 * 		1
	 * 		2...
	 * )
	 */
	function doneTask($pos, $TTid, $useGold);
	
	/**
	 * 贡献物品
	 * @param int $pos
	 * @param int $TTid
	 * @param array $itemIdNumArr  要贡献物品的id组
	 */
	function handIn($pos, $TTid, $itemIdNumArr);

} 
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */