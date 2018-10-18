<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IRoulette.class.php 175626 2015-05-29 08:08:35Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/IRoulette.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-29 08:08:35 +0000 (Fri, 29 May 2015) $
 * @version $Revision: 175626 $
 * @brief 
 *  
 **/
interface IRoulette
{
	/**
	 * 获取自己的信息
	 * 
	 * @return array
	 *         [
	 *             uid : int
	 *             today_free_num : int                     今天免费挖宝的次数
	 *             accum_gold_num : int                     活动期间金币挖宝次数
	 *             integeral : int                          积分
	 *             va_boxreward : array
	 *             					[
	 *             						array
	 *             						[
	 *             							'status' : int  箱子状态（1： 不可领取，2：可以领取但未领取，3：已经领取）
	 *             						] 
	 *             					]
	 *             isReceived : int      0表示未领取 1表示已经领取
	 *         ]
	 */
	public function getMyRouletteInfo();
	
	/**
	 * 抽奖
	 * 
	 * @param int num    抽奖次数
	 * @return array
	 *         [
	 *             array
	 *             [
	 *                 type : int    物品类型
	 *                 id   : int    物品id
	 *                 num  : int    数量
	 *                 point: int    指向第几组物品
	 *             ]
	 *         ]
	 */
	public function rollRoulette($num);
	
	/**
	 * 领取宝箱奖励
	 * @param int $num   第几个宝箱
	 * @return 'ok'
	 */
	public function receiveBoxReward($num);
	
	/**
	 * 积分排行榜
	 * @return array
	 * 			[
	 * 				rank : int
	 * 				list : array
	 * 						[
	 * 							index => array
	 * 									[
	 * 										uid:int
	 * 										name:string
	 * 										guild_name:string
	 * 										htid:int
	 * 										level:int
	 * 										dressInfo:array
	 * 										vip:int
	 * 										integeral:int
	 * 										rank:int
	 * 									]
	 * 						]
	 * 			]
	 */
	public function getRankList();
	
// 	/**
// 	 * 领取积分排行奖励
// 	 * @return 'ok'
// 	 */
// 	public function receiveRankReward();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */