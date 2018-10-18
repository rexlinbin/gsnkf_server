<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuildWar.class.php 157386 2015-02-06 05:24:21Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/IGuildWar.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-06 05:24:21 +0000 (Fri, 06 Feb 2015) $
 * @version $Revision: 157386 $
 * @brief 
 *  
 **/

/**
 * 接口修改日志
 * 修改时间			修改人 			修改内容
 * 20150206 		mengbaoguo		getReplay增加left_user字段
 *
 */
 
interface IGuildWar
{
	/**
	 * 进入
	 * 
	 * @return 'ok':sting						进入成功
	 */
	function enter();
	
	/**
	 * 退出
	 * 
	 * @return 'ok':sting						退出成功
	 */
	function leave();
	
	/**
	 * 报名
	 *
	 * @return 'ok':sting						报名成功
	 * 		   'already':string					已经报名
	 */
	function signUp();
	
	/**
	 * 获取用户的跨服军团战信息
	 *
	 * @return array							跨服军团战信息
	 *{
	 *		ret:	 							no表示本服务器不在分组内|ok表示在一个分组内
	 *		session: 							当前是第几届，如果当前没开启新的一届，返回上一届
	 *		sign_time：							本军团的报名时间，未报名为 0
	 *		round								大轮
	 *		status								大轮的状态
	 *		sub_round							晋级赛中的小轮次:0,1,2,3,4
	 *		sub_status							小轮的状态
	 * 		cheer_guild_id: 					助威对象军团Id
	 * 		cheer_guild_server_id:				助威对象所在服务器Id
	 * 		cheer_round：						助威轮次
	 * 		buy_max_win_num：					购买连胜的次数
	 * 		buy_max_win_time：					购买连胜的时间
	 * 		worship_time：						膜拜时刻
	 * 		fight_force:						战斗力
	 * 		update_fmt_time：					更新战斗力时刻
	 * 		server_id:							本服务器id
	 * 		sign_up_count:						已经报名的军团个数
	 *}
	 */
	function getUserGuildWarInfo();
	
	/**
	 * 获取军团成员列表
	 *
	 * @return array
	 * [
	 * 	    {
	 *			uid:							用户Id
	 *			uname:							用户名称
	 *			level:							用户等级
	 *			fight_force:					用户战斗力
	 *			vip								用户vip等级
	 *			htid							用户htid
	 *			contr_num:						用户贡献值
	 *			member_type						成员类型
	 *			state							状态 0未出战|1已出战
	 *			dress=>array					时装信息
	 * 		}
	 * ]
	 */
	function getGuildWarMemberList();
	
	/**
	 * 更新用户阵型信息
	 *
	 * @return int								更新成功, 返回战斗力，方便前端重新排序
	 *         'fighting':string				更新失败，玩家已经上阵，不能更新
	 *         'cd':string						更新失败，玩家处在更新战斗数据cd中，不能更新
	 */
	function updateFormation();
	
	/**
	 * 使用金币清除更新cd时间
	 *
	 * @return 	int:							清除cd花的钱
	 * 			'lack':string					缺少金币
	 */
	function clearUpdFmtCdByGold();
		
	/**
	 * 设置上场人员和下场人员
	 * 
	 * @param int $type							0下场|1上场
	 * @param int $uid							上场或者下场的uid
	 *
	 * @return 'ok'
	 */
	function changeCandidate($type, $uid);
	
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
	 * 助威
	 *
	 * @param $guildId							助威对象的Id
	 * @param $serverId							助威对象的服务器Id
	 *
	 * @return 'ok'								助威成功
	 *         'directPromotion'				军团这一轮轮空，无需助威
	 */
	function cheer($guildId, $serverId);
	
	/**
	 * 获取晋级赛阶段军团信息列表
	 *
	 * @return array							跨服战信息
	 * [
	 * 	    {
	 * 			pos:							军团显示的位置顺序
	 * 			guild_id：						军团Id
	 * 			guild_name：						军团名
	 * 			guild_server_id：				军团所在的服务器Id
	 * 			guild_server_name：				军团所在的服务器名称
	 * 			sign_time：						报名时间
	 * 			final_rank:						最终名次
	 * 			fight_force:					战斗力
	 * 			guild_level:					军团等级
	 * 			guild_badge:					军团徽章
	 *     }
	 * ]
	 * </code>
	 */
	function getGuildWarInfo();
	
	/**
	 * 获取所有助威的历史数据
	 *
	 * @return array							自己所有的助威信息
	 * [
	 * 		round=>array 						第几轮
	 * 		{
	 * 			guildId:						军团Id
	 * 			guildName:						军团名字
	 * 			serverId:						服务器Id
	 * 			serverName						服务器名称
	 *			guildState:						0还未比赛|1比赛中|2晋级|3淘汰|
	 *			rewardState:					0没发助威奖|1已发助威奖
	 * 		}
	 * ]
	 */
	function getHistoryCheerInfo();
	
	/**
	 * 获取膜拜神殿信息
	 *
	 * @return array
	 * {
	 * 		session:							届数
	 * 		guild_id							军团Id
	 * 		guild_name							军团名称
	 * 		guild_server_id						服务器Id
	 * 		guild_server_name					服务器名称
	 * 		guild_badge							军团徽章Id
	 * 		president_uname						军团长名称
	 * 		president_htid						军团长主角形象
	 *      president_level						军团长等级
	 *      president_vip_level					军团长vip等级
	 *      president_fight_force				军团长战斗力
	 *      president_dress						军团长时装信息
	 * }
	 */
	function getTempleInfo();
	
	/**
	 * 膜拜
	 *
	 * @param $type      						膜拜种类 取值1,2,3
	 *
	 * @return ok								膜拜成功
	 */
	function worship($type);
	
	/**
	 * 根据主战报获取子战报详细信息
	 *
	 * @param array $arrReplayId
	 * @return array
	 * [
	 * 		replay_id => array
	 * 		{
	 * 			result									战斗结果：2胜利，0失败
	 * 			atk_server_id
	 * 			atk_guild_id
	 * 			def_server_id
	 * 			def_guild_id
	 * 			userList => array
	 * 			{
	 * 				uid => {name, fight_force, htid}
	 * 			}
	 * 			arrProcess => array
	 * 			[
	 * 				{
	 * 					result							战斗结果：2:胜，1：平， 0：败
	 * 					brid							战报Id
	 * 					atk_uid							攻方玩家uid
	 * 					def_uid							防守玩家uid
	 * 					atk_max_win						连胜次数，只有在此玩家下场时，才有这个字段
	 * 					def_max_win						连胜次数，只有在此玩家下场时，才有这个字段
	 * 				}
	 * 			]
	 * 		}
	 * ]
	 *
	 */
	public function getReplayDetail($arrReplayId);
	
	/**
	 * 查看战绩(获取自己军团跨服或者海选的所有战斗信息)
	 *
	 * @return array                        	战绩
	 * {
	 * 		self => array
	 * 		{
	 * 			guild_id						 军团Id
	 * 			guild_name						军团名称
	 * 			guild_server_id					服务器Id
	 * 			guild_server_name				服务器名称
	 * 			guild_badge						军团徽章Id
	 * 		}
	 * 
	 * 		audition => array 					海选战报,以数据作为轮数
	 * 		[
	 * 			{
	 * 				replay_id
	 * 				result						战斗结果：2胜利，0失败
	 * 				attacker => array			攻方信息，如果是自己，则为空数组
	 * 				{
	 *					guild_id				军团Id
	 *					guild_name				军团名称
	 *					guild_server_id			服务器Id
	 *					guild_server_name		服务器名称
	 *					guild_badge				军团徽章Id
	 *				}
	 * 				defender => array			防方信息，如果为自己，则为空数组
	 * 				{
	 *					guild_id				军团Id
	 *					guild_name				军团名称
	 *					guild_server_id			服务器Id
	 *					guild_server_name		服务器名称
	 *					guild_badge				军团徽章Id
	 *				}
	 * 			}
	 * 		]								
	 *
	 * 		finals => array 					晋级赛战报
	 * 		[
	 * 			round => array					轮次做key    例如：8强赛，4强赛，半决赛，决赛
	 * 			{
	 *				result						战斗结果：2胜利，0失败
	 * 				attacker => array
	 * 				{
	 * 					guild_id
	 * 					guild_name
	 * 					guild_server_id
	 * 					guild_server_name
	 * 					guild_badge	
	 * 				}
	 * 				defender => array
	 * 				{
	 * 					guild_id
	 * 					guild_name
	 * 					guild_server_id
	 * 					guild_server_name
	 * 					guild_badge				
	 * 				}
	 * 				sub_round => array
	 * 				[
	 * 					{
	 * 						replay_id
	 * 					}
	 * 				]
	 * 			}
	 * 		]									
	 * }
	*/
	function getHistoryFightInfo();
	
	/**
	 * 查看晋级赛之间任意战报
	 *
	 * @param $guildId01						军团01的Id
	 * @param $serverId01						军团01的服务器Id
	 * @param $guildId02						军团02的Id
	 * @param $serverId02						军团02的服务器Id
	 *
	 * @return array                        	战绩
	 * {	
	 * 		result								战斗结果：2胜利，0失败
	 *		attacker => array 					军团01的信息					
	 *		{
	 *			guild_id
	 *			guild_name
	 *			guild_server_id
	 *			guild_server_name
	 *			guild_badge						
	 *			member => array				
	 *			[
	 *				{
	 *					state					标记这个玩家是否能战斗,0无法战斗1可以战斗
	 *					htid
	 *					uname
	 *					fight_force
	 *				}
	 *			]
	 *		}									
	 *		defender:							同 attacker
	 *		sub_round => array
	 * 		[
	 * 			{
	 * 				replay_id
	 * 				arrProcess
	 * 			}
	 * 		]
	 * 		left_user => array
	 * 		[
	 * 			sub_round_index => array
	 * 			{
	 * 				{
	 * 					htid
	 * 					uname
	 * 					fight_force
	 * 				}
	 * 			}
	 * 		]			
	 * }
	 */
	function getReplay($guildId01, $serverId01, $guildId02, $serverId02);
	
	/**
	 * 购买连胜次数，只能在晋级赛购买
	 *
	 * @return int								最大连胜次数
	 */
	function buyMaxWinTimes();
	
	/**
	 * 1 跨服军团战状态推送接口
	 * push.guildwar.update
	 * array
	 * {
	 * 		round
	 * 		status
	 * 		sub_round
	 * 		sub_status
	 * }
	 */
	
	/**
	 * 2 奖励推送接口
	 * re.reward.newReward
	 */
	
	/**
	 * 3 邮件推送接口
	 * re.mail.newMail
	 */
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */