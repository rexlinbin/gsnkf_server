<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: INewServerActivity.class.php 242390 2016-05-12 09:40:56Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/INewServerActivity.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-12 09:40:56 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242390 $
 * @brief “开服7天乐”接口
 *  
 **/
interface INewServerActivity
{
	/**
	 * @param $fight 前端传来的战斗力,
	 * 目的是为了让战斗力数据与前端及时同步,把前端的战斗力计算交给后端处理,因为框架的战斗力计算是必须打副本的
	 * 
	 * 1.返回给前端小于等于当天的能看到的任务的信息数组('taskInfo'字段)
     * 2.返回给前端抢购商品的信息数组('purchase'字段)
     * 3.返回给前端任务更新截止时间戳 ('DEADLINE'字段)和 “开服7天乐”关闭时间戳('CLOSEDAY'字段)
	 * @return array
	 * [
	 * 	'taskInfo' => [
	 *  	$taskId => array[
	 *         	's' status缩写 => int (0未完成, 1完成, 2已领奖),
	 *         	'fn' finish_num缩写=> int 完成进度,
	 *  		]
	 *  	],
	 *  'purchase' => array[
	 *  		$day => array[
	 *  			'buyFlag' => int(用于区分当天的抢购商品是否购买了;表示 0未购买, 1已购买),
	 *  			'remainNum' => int(当天的抢购商品剩余数量),
	 *  		]
	 *  	],
	 *  'DEADLINE' => int 返回任务更新的截止时间戳
	 *  'CLOSEDAY'	=> int 返回“开服7天乐”的关闭时间戳
	 * ]
	 */
	function getInfo($fight = 0);
	
	/**
	 * 领取完成的任务奖励
	 * @param $taskId int 完成的的任务id , 任务id在策划给的open_server_reward表中
	 * @return string 'ok'
	*/
	function obtainReward($taskId);
	
	/**
	 * 可以购买当天以及之前天数的商品,每个商品在整个“开服7天乐”中同一个玩家只能购买一次
	 * @param $day int 天数
	 * @return array
	 * [
	 * 	'ret' => 'ok':购买成功  或者  'limit':商品被购买完,购买失败 ,
	 * 	'remainNum' => int(购买后抢购商品剩余数量),
	 * ]
	*/
	function buy($day);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */