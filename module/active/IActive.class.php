<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IActive.class.php 224162 2016-01-20 08:31:37Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/IActive.class.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2016-01-20 08:31:37 +0000 (Wed, 20 Jan 2016) $
 * @version $Revision: 224162 $
 * @brief 
 *  
 **/
interface IActive
{
	/**
	 * 获取活跃度信息
	 *
	 * @return array 活跃度信息
	 * <code>
	 * {
	 * 		'point':int			总积分
	 * 		'va_active':
	 * 		{
	 * 			'step':int		配置表id
	 * 			'task'
	 * 			{
	 * 				$id => $num 任务id对应完成次数
	 * 			}
	 * 			'prize'
	 * 			{
	 * 				$id			领取过的奖励id
	 * 			}
	 * 			'taskReward'
	 * 			{
	 * 				$taskId			领取过任务奖励的任务id
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getActiveInfo();

	/**
	 * 领取每个任务对应的奖励
	 * @param int $taskId		任务id
	 * @return string 'ok'
	 */
	function getTaskPrize($taskId);
	
	/**
	 * 领取奖励
	 *
	 * @param int $prizeId		箱子id
	 * @return string $ret		结果
	 * 'ok' 					领取成功
	 * 'err'					领取失败
	*/
	function getPrize($prizeId);
	
	/**
	 * 奖励升级
	 * @return string $ret		结果
	 * 'ok' 					升级成功
	 * 'remainingReward'		还有奖励没领取完,升级失败,必须领取完奖励才让升级
	 * @throws string 'err'		达不到升级条件,抛fake							
	 */
	function upgrade();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */