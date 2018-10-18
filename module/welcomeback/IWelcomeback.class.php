<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWelcomeback.class.php 259806 2016-08-31 10:44:13Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/IWelcomeback.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-31 10:44:13 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259806 $
 * @brief 老玩家回归
 *  
 **/
interface IWelcomeback
{
	/**
	 * @return array(
	 * 			'isOpen' => 0|1,
	 * 			'endTime' => 1412345678	
	 * )
	 */
	public function getOpen();
	/**
	 * @return array 
	 * <code>
	 * array
	 * [
	 * 		'endTime' => time,				活动结束时间，秒数
	 * 
	   		'day' => day					离线时间，天数，最大为策划配置的奖励天数
	   		
	   		'gift' => array(
				id => gainGift				1:未领取，2：已经领取
			), 
			
			'task' => array(
				id => array(
						finishedTimes, 		目前执行次数
						status				0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
				)
			), 
			
			'recharge' => array(
				id => array(
						hadRewardTimes,		已领奖次数
						toRewardTimes		待领奖次数
				)
			),
			
			'shop' => array(
				id => buyTimes				已购买次数
			)
	 * ]
	 * </code>
	 */
	public function getInfo();
	
	/**
	 * 领取奖励：回归礼包、回归任务、单笔充值
	 * @param int $taskId
	 * @param int $select 0:全领；1：领第一个物品；2：领第二个物品；以此类推
	 * @return string 'ok'领取成功
	 */
	public function gainReward($taskId, $select = 0);
	
	/**
	 * 限时商店购买
	 * @param int $taskId
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buy($taskId, $num = 1);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */