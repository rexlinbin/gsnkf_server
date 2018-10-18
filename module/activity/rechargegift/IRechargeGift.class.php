<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IRechargeGift.class.php 207293 2015-11-04 11:23:51Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/IRechargeGift.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-04 11:23:51 +0000 (Wed, 04 Nov 2015) $
 * @version $Revision: 207293 $
 * @brief 
 *  
 **/
interface IRechargeGift
{
	/**
	 * 获取已经领过奖的id
	 * @return array[ 
	 * 			'acc_gold' => int 活动期间内的累计充值金币数量,
	 * 			'hadRewardArr' => array(1, 2, ...) 已经领取过奖励的奖励id数组 
	 * ]
	 */
	public function getInfo();
	
	/**
	 * 领取奖励
	 * @param int $rewardId		奖励档位
	 * @param int $select	如果是可选奖励类型,则传选择的奖励物品在奖励数组中的顺序编号;如果是不可选类型则这个字段前端不用传，默认补0
	 * @return 'ok'
	 * notice:目前策划对应可选类型的需求是单选,即只是N选1的情况,所以$select是int而不是array,如果以后改成多选那么就改成array或者对应的改配置表格式
	 */
	public function obtainReward($rewardId, $select=0);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */