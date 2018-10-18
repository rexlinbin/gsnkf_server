<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWorldCompete.class.php 241120 2016-05-05 07:35:33Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/IWorldCompete.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 07:35:33 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241120 $
 * @brief 
 *  
 **/
 
interface IWorldCompete
{
	/**
	 * 获得基本信息
	 * @return array
	 * { 
	 * 		ret										'ok'/'no'/'off' 为no时代表这个服不在任何分组内,没有以下字段,off没有这个功能
	 * 		open_time								只有为no时，才有这个字段，代表服务器活动能够开启的最近时间
	 * 		atk_num									挑战完成次数
	 * 		suc_num									挑战胜利次数
	 * 		buy_atk_num								挑战购买次数
	 * 		refresh_num								对手刷新次数
	 * 		worship_num								膜拜完成次数
	 * 	  	max_honor 								本次比武的最大荣誉
	 * 		cross_honor								累积比武的总荣誉
	 * 		begin_time								本次活动开始时间
	 * 		end_time								本次活动结束时间
	 * 		reward_end_time							发奖结束时间
	 * 		period_end_time							整个活动的结束时间
	 * 		prize									已领取的奖励
	 * 		{
	 * 			index => sucNum						index从0开始,sucNum是胜利次数	
	 * 		}
	 * 		rival									3个对手的信息
	 * 		{
	 * 			index => array
	 * 			{
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				fight_force
	 * 				dress
	 * 				status							status为0是失败,1是成功
	 * 				title			
	 * 			}
	 * 		}
	 * }
	 */
	function getWorldCompeteInfo();
	
	/**
	 * 挑战
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @param int $crazy							是否狂怒模式，1是0否，默认1
	 * @param int $skip								是否跳过战斗，1是0否，默认1
	 * @return array
	 * {
	 * 		ret										'ok'
	 * 		appraisal								战斗评价
	 * 		fightRet								战斗串，不是跳过战斗的情况下才有这个值
	 * 		fight_force								对方战斗力
	 * 		rival									3个对手的信息,3个对手都胜利的情况下返回
	 * 		{
	 * 			index => array
	 * 			{
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				fight_force
	 * 				dress
	 * 				status							status为0是失败,1是成功
	 * 				title
	 * 			}
	 * 		}
	 * }
	 */
	function attack($serverId, $pid, $crazy = 1, $skip = 1);
	
	/**
	 * 购买挑战次数
	 * 
	 * @param int $num
	 * @return string 'ok'
	 */
	function buyAtkNum($num);
	
	/**
	 * 刷新对手们
	 * 
	 * @return array
	 * {
	 * 		index => array
	 * 		{
	 * 			server_id
	 * 			server_name
	 * 			pid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			status							status为0是失败,1是成功
	 * 			title
	 * 		}		
	 * }
	 */
	function refreshRival();
	
	/**
	 * 领取每日奖励
	 *
	 * @param int $num 胜利次数
	 * @return string 'ok'
	 */
	function getPrize($num);
	
	/**
	 * 膜拜
	 * 
	 * @return string 'ok'
	 */
	function worship();
	
	/**
	 * 获得对手的阵容信息
	 *
	 * @param int $aServerId
	 * @param int $aPid
	 * @return @see User.getBattleDataOfUsers
	 */
	function getFighterDetail($aServerId, $aPid);
	
	/**
	 * 拉取排行榜信息
	 *
	 * @return array
	 * {
	 * 		inner => array
	 * 		{
	 * 			uid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			max_honor
	 * 			rank
	 * 		}
	 * 		cross => array
	 * 		{
	 * 			server_id
	 * 			server_name
	 * 			uid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			max_honor
	 * 			rank
	 * 		}
	 * 		my_inner_rank => int
	 * 		my_cross_rank => int
	 * }
	*/
	function getRankList();
	
	/**
	 * 拉取冠军信息
	 *
	 * @return array
	 * {
	 * 		cross => array
	 * 		{
	 * 			server_id
	 * 			server_name
	 * 			uid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			max_honor
	 * 			rank
	 * 			title
	 * 		}
	 * }
	 */
	function getChampion();
	
	/**
	 * 商店信息
	 *
	 * @return array
	 * <code>
	 * {
	 *     goodsId => array			商品id
	 *     {
	 *         'num'				购买次数 
	 * 		   'time'				购买时间
	 *     }
	 * }
	 * </code>
	 */
	function getShopInfo();
	
	/**
	 * 购买商品
	 *
	 * @param int $goodsId
	 * @param int $num
	 * @return string 'ok'
	 */
	function buyGoods($goodsId, $num);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */