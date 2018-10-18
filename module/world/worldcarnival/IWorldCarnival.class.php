<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWorldCarnival.class.php 198383 2015-09-14 08:00:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/IWorldCarnival.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-14 08:00:26 +0000 (Mon, 14 Sep 2015) $
 * @version $Revision: 198383 $
 * @brief 
 *  
 **/
 
/*******************************接口修改记录*********************************************
 * 创建接口内容																	20150828 12:00:00
 * 战报接口增加brid字段															20150828 19:00:00
 * getCarnivalInfo接口增加total_sub_round字段									20150831 17:00:00
 * 增加一个接口getFighterDetail，用于查看参赛者信息									20150906 19:00:00
 * 修改updateFmt返回值，如果非参赛者更新战斗信息，抛fake									20150907 12:00:00
 * getCarnivalInfo接口增加final_period字段										20150907 20:00:00
 * getCarnivalInfo接口增加time_config字段，去掉final_period等两个字段					20150908 15:00:00
 * getCarnivalInfo接口修改，增加next_fight_time，normal_period，final_period 字段	20150911 19:00:00
 * 推送增加next_fight_time字段													20150914 16:00:00
*/

interface IWorldCarnival
{
	/**
	 * 获得基本信息
	 * 
	 * @return array
	 * {
	 * 		ret								请求状态，取值范围： 'fighter'参数者/'watcher'围观者/'invalid'非法人群，***如果为'invalid'，则没有下面的字段***
	 * 		round							大轮次，取值范围：1 ：A组比赛  / 2 ：B组比赛  / 3 ： 决赛
	 * 		status							大轮次状态，取值范围：10 ：正在比赛  / 100 ： 比赛结束				
	 * 		sub_round						小轮次，取值范围：1-5
	 * 		sub_status						状态，取值范围：10 ：正在比赛/100 ：比赛结束
	 * 		next_fight_time					后端计算好的下次战斗的时间
	 * 		normal_period					正常小轮比赛间隔
	 * 		final_period					决赛前间隔
	 * 		fighters						参赛者基本信息
	 * 		{
	 * 			pos => array
	 * 				{
	 * 					rank
	 * 					server_id
	 * 					server_name
	 * 					pid
	 * 					uname
	 * 					htid
	 * 					level
	 * 					vip
	 * 					fight_force
	 * 					dress
	 * 				}
	 * 		}
	 * }
	 */
	function getCarnivalInfo();
	
	/**
	 * 更新战斗信息
	 * 
	 * @return 'ok'更新成功
	 */
	function updateFmt();
	
	/**
	 * 
	 * @param int $round				大轮次，取值范围：1 A组比赛/2 B组比赛/3 决赛
	 * @return array
	 * [
	 * 		subRound => array			小轮次作为key
	 * 		{
	 * 			attacker_pos			攻方位置,这里其实返回的不是真正意义上的攻方，而是位置在前面的一方
	 * 			defender_pos			守方位置,这里其实返回的不是真正意义上的攻方，而是位置在前面的一方
	 * 			result					结果：1 攻方胜利，0攻方失败
	 * 			brid					战报id
	 * 		}
	 * ]
	 */
	function getRecord($round);
	
	/**
	 * 获得参赛者的阵容信息
	 * 
	 * @param int $aServerId
	 * @param int $aPid
	 * @return @see User.getBattleDataOfUsers
	 */
	function getFighterDetail($aServerId, $aPid);
}

/**
 * 1 跨服嘉年华状态推送接口
 * push.worldcarnival.update
 * array
 * {
 * 		round
 * 		status
 * 		sub_round
 * 		sub_status
 * 		win_pos
 * 		next_fight_time
 * }
 */

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */