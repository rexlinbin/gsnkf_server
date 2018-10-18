<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IHappySign.class.php 232026 2016-03-10 08:34:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/IHappySign.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-10 08:34:26 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232026 $
 * @brief 
 *  
 **/
interface IHappySign
{
	/**
	 * 获取欢乐签到信息
	 * @return array
	 * <code>
	 * [
	 * 		loginDayNum:int 	活动期间内玩家登录的天数
	 * 		today:int 			今天是活动的第几天
	 *		hadSignIdArr => array( 1,3,4... ),已经领取了的奖励id数组
	 * ]
	 * </code>
	 * 活动过期后,返回arra为空
	 */
	public function getSignInfo();
	
	/**
	 * 领取欢乐签到奖励
	 * @param int $rewardId		奖励档位
	 * @param int $select	如果是可选奖励类型,则传选择的奖励物品在奖励数组中的顺序编号;如果是不可选类型则这个字段前端不用传，默认补0
	 * @return 'ok'
	 */
	public function gainSignReward($rewardId, $select = 0);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */