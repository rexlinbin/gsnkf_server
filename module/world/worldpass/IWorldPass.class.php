<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWorldPass.class.php 179260 2015-06-16 03:20:54Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/IWorldPass.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-16 03:20:54 +0000 (Tue, 16 Jun 2015) $
 * @version $Revision: 179260 $
 * @brief 
 *  
 **/

/*******************************接口修改记录*******************************
 * 创建接口内容															20150521 14:21:00
 * getWorldPassInfo接口monster字段改为数组								20150521 19:00:00
 * getRankList中跨服排行增加server_name									20150522 10:05:00
 * getRankList中增加fight_force,dress									20150522 10:10:00
 * attack接口增加hp和hell_point字段										20150522 19:00:00
 * 修改getShopInfo接口，去掉refreshShopGoods接口							20150526 13:15:00
 * 修改了getWorldPassInfo中formation和choice，如果没有武将，则不用传对应的index	20150528 20:02:00
 * getWorldPassInfo增加两个字段，begin_time和end_time，代表当前活动的开始和结束时间 20150528 20:06:00
 * reset接口返回玩家信息													20150603 11:39:00
 * getRankList字段增加玩家自己的服内排名和跨服排名								20150611 15:17:00
 * getWorldPassInfo和reset增加reward_end_time和period_end_time			20150612 10:48:00
 * getWorldPassInfo增加open_time字段									20150616 11:15:00
 * 
 */
 
interface IWorldPass
{
	/**
	 * 获得基本信息
	 * @return array
	 * { 
	 * 		ret										'ok'/'no' 为no时代表这个服不在任何分组内,没有以下字段
	 * 		open_time								只有为no时，才有这个字段，代表服务器活动能够开启的最近时间
	 * 		passed_stage							当前通关的最大关卡0-6
	 * 		curr_point 								本次闯关的总积分
	 * 		hell_point								炼狱积分
	 * 		atk_num									攻击次数
	 * 		buy_atk_num								购买的攻击次数
	 * 		refresh_num								刷新备选武将列表的次数
	 * 		begin_time								本次活动开始时间
	 * 		end_time								本次活动结束时间
	 * 		reward_end_time							发奖结束时间
	 * 		period_end_time							一整个活动的结束时间
	 * 		monster									index取值1-6，代表第1关到第6关，每个关卡初始化的怪物，一旦初始化好，就不能改啦
	 * 		[
	 * 			index => armyId
	 * 		]
	 * 		choice => array							index取值0-4，代表5个备选武将格子,如果没有这个index或者这个index的值为0，代表没有武将
	 * 		[
	 * 			index => htid
	 * 		]
	 * 		formation => array						index取值0-5，代表6个位置,如果没有这个index或者这个index的值为0，代表没有武将
	 * 		[
	 * 			index => htid
	 * 		]
	 * 		point => array							index取值0-n，代表每次闯关的积分
	 * 		[
	 * 			index => point
	 * 		]
	 * }
	 */
	function getWorldPassInfo();
	
	/**
	 * 闯关
	 * 
	 * @param int $stage							当前要攻打的关卡
	 * @param array $arrFormation					攻打时候的阵型
	 * 
	 * @return array
	 * {
	 * 		ret										'ok'
	 * 		fightRet								战斗串
	 * 		appraise								评价
	 * 		damage									伤害
	 * 		hp										我方剩血
	 * 		point									积分
	 * 		hell_point								攻打关卡得到的炼狱积分
	 * 		choice => array							index取值0-4，代表5个备选武将格子，如果这个位置上没有武将，htid为0
	 * 		[
	 * 			index => htid
	 * 		]
	 * }
	 */
	function attack($stage, $arrFormation);
	
	/**
	 * 重新开始闯关
	 * 
	 * @return array
	 * { 
	 * 		ret										'ok'
	 * 		passed_stage							当前通关的最大关卡0-6
	 * 		curr_point 								本次闯关的总积分
	 * 		hell_point								炼狱积分
	 * 		atk_num									攻击次数
	 * 		buy_atk_num								购买的攻击次数
	 * 		refresh_num								刷新备选武将列表的次数
	 * 		begin_time								本次活动开始时间
	 * 		end_time								本次活动结束时间
	 * 		reward_end_time							发奖结束时间
	 * 		period_end_time							一整个活动的结束时间
	 * 		monster									index取值1-6，代表第1关到第6关，每个关卡初始化的怪物，一旦初始化好，就不能改啦
	 * 		[
	 * 			index => armyId
	 * 		]
	 * 		choice => array							index取值0-4，代表5个备选武将格子,如果没有这个index或者这个index的值为0，代表没有武将
	 * 		[
	 * 			index => htid
	 * 		]
	 * 		formation => array						index取值0-5，代表6个位置,如果没有这个index或者这个index的值为0，代表没有武将
	 * 		[
	 * 			index => htid
	 * 		]
	 * 		point => array							index取值0-n，代表每次闯关的积分
	 * 		[
	 * 			index => point
	 * 		]
	 * }
	 */
	function reset();
	
	/**
	 * 购买闯关次数
	 * 
	 * @return 'ok'
	 */
	function addAtkNum();
	
	/**
	 * 拉取同组所有服
	 *
	 * @return array
	 * [
	 * 		serverId => serverName
	 * ]
	 */
	function getMyTeamInfo();
	
	/**
	 * 拉取排行榜信息
	 * 
	 * @return array
	 * [
	 * 		inner => array
	 * 		[
	 * 			uid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			max_point
	 * 			rank
	 * 		]
	 * 		cross => array
	 * 		[
	 * 			server_id
	 * 			server_name
	 * 			uid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			max_point
	 * 			rank
	 * 		]
	 * 		my_inner_rank => int
	 * 		my_cross_rank => int
	 * ]
	 */
	function getRankList();
	
	/**
	 * 刷新武将列表
	 * 
	 * @return array
	 * [
	 * 		index => htid		
	 * ]
	 */
	function refreshHeros();
	
	/**
	 * 商店信息
	 *
	 * @return array
	 * <code>
	 * [
	 *     goodsId => array
	 *     {
	 *         'num'				购买次数 
	 * 		   'time'				购买时间
	 *     }
	 * ]
	 * </code>
	 */
	function getShopInfo();
	
	/**
	 * 购买物品
	 *
	 * @param int $goodsId
	 * @param int $num
	 * @return array
	 * <code>
	 * [
	 *     ret:string            		'ok'
	 *     drop					 		如果没有的话是空数组
	 * ]
	 * </code>
	 */
	function buyGoods($goodsId, $num);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */