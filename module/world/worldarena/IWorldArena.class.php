<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWorldArena.class.php 241167 2016-05-05 12:18:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/IWorldArena.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-05 12:18:17 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241167 $
 * @brief 
 *  
 **/

/*******************************接口修改记录*********************************************
 * 创建接口内容															20150630 19:00:00
 * 修改hp_percent注释，以10000为基数的值									20150702 14:00:00
 * getRecordList接口attacker_pos改为attacker_rank
 * getRecordList接口defender_pos改为defender_rank						20150702 16:35:00
 * 对手信息中增加self字段，用于标识是自己还是对手									20150707 18:57:00
 * getWorldArenaInfo增加pid											20150709 15:02:00
 * getRecordList增加攻方和守方的htid										20150709 17:12:00
 * attack接口增加了主动攻击别人输了的奖励										20150710 16:21:00
 * getInfo添加接口cd_duration_before_end，attack_cd用以处理cd				20151110 11:53:00
 */
 
interface IWorldArena
{
	/**
	 * 拉取基础信息
	 * 
	 * @return array
	 * {
	 * 		ret								请求状态，取值范围： 'ok'
	 * 		stage							所处阶段，取值范围：'before_signup','signup','range_room','attack','reward'
	 * 		team_id							分组id，没分组则为0
	 * 		room_id							房间id，没分房则为0
	 * 		pid								返给前端自己的Pid
	 * 		signup_time						报名时间，没报名则为0
	 * 		period_bgn_time					周期开始时间
	 * 		period_end_time					周期结束时间
	 * 		signup_bgn_time					报名开始时间
	 * 		signup_end_time					报名结束时间
	 * 		attack_bgn_time					攻打开始时间
	 * 		attack_end_time					攻打结束时间
	 * 		cd_duration_before_end			攻打结束前有cd的持续时间，目前是10分钟
	 * 		extra							扩展信息，可以取如下值,处于不同阶段时候，这个字段的key不同
	 * 		{
	 * 			[stage为before_signup时候取如下值:]
	 * 			空
	 * 			
	 * 			[stage为signup时候取如下值:]
	 * 			update_fmt_time				更新战斗信息时间
	 * 
	 * 			[stage为range_room时候取如下值:]
	 * 			空
	 * 			
	 * 			[stage为attack时候取如下值:]
	 * 			atk_num						当前剩余的攻击次数 
	 * 			buy_atk_num					已经购买的攻击次数
	 * 			silver_reset_num			银币重置次数
	 * 			gold_reset_num				金币重置次数
	 * 			kill_num					玩家的击杀总数
	 * 			cur_conti_num				玩家当前的连杀数
	 * 			max_conti_num				玩家最大的连杀数
	 * 			last_attack_time			玩家上次主动攻打的时间
	 * 			player						玩家列表，包括对手和自己，按照pos排序
	 * 			[	
	 * 				pos => array
	 * 				{
	 * 					server_id
	 * 					server_name
	 * 					pid
	 * 					uname
	 * 					htid
	 * 					level
	 * 					vip
	 * 					title
	 * 					fight_force
	 * 					dress
	 * 					hp_percent			以10000作为基地
	 * 					protect_time
	 * 					self				如果是自己为1，别人为0
	 * 				}
	 * 			]
	 * 
	 * 			[stage为reward时候取如下值:]
	 * 			空
	 * 		}
	 * }
	 */
	function getWorldArenaInfo();
	
	/**
	 * 玩家报名
	 * 
	 * @return	int							返回玩家报名时间
	 */
	function signUp();
	
	/**
	 * 更新战斗信息
	 * 
	 * @return	int							返回玩家更新战斗力的时间
	 */
	function updateFmt();
	
	/**
	 * 攻击某个排名的玩家
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @param int $skip
	 * @return array
	 * {
	 * 		ret								请求状态，取值范围： 'ok'正常/'out_range'对手和自己的相对排名变化超出范围/'protect'对方在保护时间内
	 * 
	 * 		以下字段和getWorldArenaInfo中返回的相同
	 * 		atk_num							当前剩余的攻击次数 
	 * 		buy_atk_num						已经购买的攻击次数
	 * 		silver_reset_num				银币重置次数
	 * 		gold_reset_num					金币重置次数
	 * 		kill_num						玩家的击杀总数
	 * 		cur_conti_num					玩家当前的连杀数
	 * 		max_conti_num					玩家最大的连杀数
	 * 		last_attack_time				玩家上次主动攻打的时间
	 * 		player							玩家列表，包括对手和自己，按照pos排序
	 * 		[	
	 * 			pos => array
	 * 			{
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				title
	 * 				fight_force
	 * 				dress
	 * 				hp_percent				以10000作为基地
	 * 				protect_time
	 * 				self					如果是自己为1，别人为0
	 * 			}
	 * 		]
	 * 		
	 * 		以下字段只有在ret为ok的时候才有的字段
	 * 		appraisal						战斗评价
	 * 		fightRet						战斗串，不是跳过战斗的情况下才有这个值
	 * 		reward							各种奖励
	 * 		{
	 * 			lose_reward					输了的时候的奖励
	 * 
	 * 			win_reward					打赢对手的奖励，普通奖励
	 * 			conti_reward				打赢对手的奖励，连杀的奖励
	 * 			terminal_conti_reward		打赢对手的奖励，终结连杀奖励
	 * 		}
	 * 		terminal_conti_num				如果胜利的话，而且终结了对方的连胜，这个值是终结的对方的连胜值						
	 * }
	 */
	function attack($serverId, $pid, $skip = 1);
	
	/**
	 * 购买攻击次数
	 * 
	 * @param int $num
	 * @return num
	 */
	function buyAtkNum($num);
	
	/**
	 * 重置，包含更新战斗信息，回满血
	 * 
	 * @param string $type 					取值如下：'silver'银币重置/'gold'金币重置
	 * @return 'ok'
	 */
	function reset($type);
	
	/**
	 * 获得战报列表
	 * 
	 * @return array
	 * {
	 * 		my => array
	 * 		[
	 * 			{
	 * 				attacker_server_id		攻方服务器id
	 * 				attacker_server_name	攻方服名字
	 * 				attacker_pid			攻方pid
	 * 				attacker_uname			攻方名字
	 * 				attacker_htid			攻方htid
	 * 				attacker_rank			攻方名次
	 * 				attacker_conti          攻方连胜次数
	 * 				attacker_terminal_conti 攻方终结对方连胜次数
	 * 				defender_server_id		守方服务器id
	 * 				defender_server_name	守方服名字
	 * 				defender_pid			守方pid
	 * 				defender_uname			守方名字
	 * 				defender_htid			守方htid
	 * 				defender_rank			守方名次
	 * 				defender_conti          守方连胜次数
	 * 				defender_terminal_conti 守方终结对方连胜次数
	 * 				attack_time				攻击时间
	 * 				result					结果，1代表攻方胜，0代表守方胜
	 * 				brid					战报id
	 * 			}
	 * 		]
	 * 		conti => array
	 * 		[
	 * 			{
	 * 				结构同上
	 * 			}
	 * 		]
	 * }
	 */
	function getRecordList();
	
	/**
	 * 获得排行
	 * 
	 * @return array
	 * {
	 * 		pos_rank => array				对决排行
	 * 		[
	 * 			{
	 * 				rank
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				title
	 * 				fight_force
	 * 				dress
	 * 			}
	 * 		]
	 * 		kill_rank => array				击杀排行
	 * 		[
	 * 			{
	 * 				rank
	 * 				kill_num				击杀数
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				title
	 * 				fight_force
	 * 				dress
	 * 			}
	 * 		]
	 * 		conti_rank => array				连杀排行
	 * 		[
	 * 			{
	 * 				rank
	 * 				max_conti_num			最大连杀数					
	 * 				server_id
	 * 				server_name
	 * 				pid
	 * 				uname
	 * 				htid
	 * 				level
	 * 				vip
	 * 				title
	 * 				fight_force
	 * 				dress
	 * 			}
	 * 		]
	 * }
	 */
	function getRankList();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */