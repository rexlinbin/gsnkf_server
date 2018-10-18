<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IOneRecharge.class.php 248900 2016-06-30 02:11:06Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/IOneRecharge.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-06-30 02:11:06 +0000 (Thu, 30 Jun 2016) $
 * @version $Revision: 248900 $
 * @brief 单充回馈接口
 *
 **/
interface IOneRecharge
{
	/**
	 * @return
	 * <code>
	 * array
	 * [
	 * 		hadReward:array	当天已经领奖的数组
	 * 		[
	 * 			$rewardId => [
	 *                         0 => $select0,
	 *                         1 => $select1,
	 *                         2 => $select2,
	 *                         ...//每领取一次就多一条记录
	 *                       ]
	 * 		]
	 * 		toReward:array 当天充值达到领取条件但还没领取的奖励
	 * 		[
	 * 			$rewardId => $num
	 * 		]
	 * ]
	 * </code>
	 */
	function getInfo();
	/**
	 *
	 * @param $rewardId:int 从1开始
	 * @param $select:int 若奖励可全部领取，该值为0不用专门传入；若奖励N选1，则传入玩家选择的奖励在配置表中的位置，从1开始。
	 * @return 'ok':成功领取
	 */
	function gainReward($rewardId, $select=0);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */