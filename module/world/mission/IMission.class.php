<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMission.class.php 214360 2015-12-07 09:09:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/IMission.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-07 09:09:52 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214360 $
 * @brief 
 *  
 **/

interface IMission 
{
	/**
	 * @return
	 * 
	 * 获取信息
	 * [
	 * 
	 *  donate_item_num => int, 本轮捐献的物品数量（这个也可以拿到任务进度里去吧）
	 *  spec_mission_fame => int, 本轮做任务获得的名望
	 *  dayreward_time => int, 每日奖励领取时间
	 *  teamId => int, 分组id <= 0 为未分组
	 *  rank => int,
	 *	missionInfo => [ 	任务进度
	 *  					missionId(int) =>[ num => int ], 
	 *  			   ],
	 *  
	 *  config => [	配置信息
	 *  				rankRewardArr => array((int,int),(int,int)...), 排名奖励前段展示用
	 *  				dayRewardArr => array((int,int),(int,int)...), 每日奖励前段展示用
	 *  				missionBackground => array( (int,int),(int, int) ),背景展示
	 *  		  ],
	 *  
	 * ]
	 * 
	 */
	function getMissionInfo();
	
	/**
	 * 登录时拉取
	 * 
	 */
	function getMissionInfoLogin();
	
	/**
	 * 贡献物品
	 * @param array $itemArr
	 * {
	 * 	itemid =>itemnum,
	 * }
	 * 
	 * @return array('res' => ok);
	 * 
	 */
	function doMissionItem($itemArr);
	
	/**
	 * 贡献金币
	 * @param int $goldNum
	 * 
	 */
	function doMissionGold( $goldNum );
	
	/**
	 * @return 
	 * [
	 * list => 
	 * 		   	1=>	[
	 * 					uname => string,
	 * 					fame => int,
	 * 					server_name => string,
	 * 					vip => int,
	 * 					level => int,
	 * 					dress => array(),
	 * 		   		],
	 * 					
	 * 			2=>	[
	 * 					uname => string,
	 * 					fame => int,
	 * 					server_name => string,
	 * 					vip => int,
	 * 					level => int,
	 * 					dress => array(),
	 * 		   		],
	 * 
	 * mine => [
	 * 				fame => int, 
	 * 				rank => int/string,     '200-300'/'300-400'/'400-500'/'500+'/-1
	 * 		   ],
	 * 
	 * ]
	 * 
	 */
	function getRankList();
	
	/**
	 *领取每日奖励
	 * 
	 */
	function receiveDayReward();
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */