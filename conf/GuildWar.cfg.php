<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWar.cfg.php 160528 2015-03-09 01:47:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/GuildWar.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-09 01:47:39 +0000 (Mon, 09 Mar 2015) $
 * @version $Revision: 160528 $
 * @brief 
 *  
 **/

class GuildWarConf
{
	const PROCESS_TEAM_NUM 					= 5;					// 海选赛和竞技赛中进程数
	const MAX_JOIN_NUM 						= 16;					// 需要多少队伍进晋级赛
	const CAN_NOT_CHANGE_NUM				= 15;					// 最少的出战人数
	const ONE_TIME_PLAYER 					= 4;					// 一次有多少个人上场
	const OFFSET_ONE 						= 1;					// 传给战斗系统的偏移量1
	const OFFSET_TWO 						= 2;					// 传给战斗系统的偏移量2
	const MAX_ARENA_COUNT 					= 1;					// 最大擂台数
	const REWARD_WHOLEWORLD_LAST_TIME		= 86400;				// 全服奖励持续时间
	const CD_AFTER_SIGN_UP_TIME				= 300;					// 报完名以后多少秒才能查看战斗信息
	const ACCEPT_NO_DEAL_SUPPORT_USER 		= 10000;				// 可以接受的最多没发助威奖的人数，这个值搞这么大，主要不想影响下面的流程
	const RAND_SLEEP_WHEN_BEGIN_AUDITION    = FALSE;				// 多进程开始跑海选时候，每个进程是否在海选开始进行随机sleep，最大sleep时间为一轮海选时间20分钟
	
	//TODO 这几个玩意到底有用没 
	const BACK_GROUND_M 					= 77;					// 战斗背景
	const BACK_GROUND_S 					= 77;					// 战斗背景
	const MUSIC_ID_M 						= 9;					// 主背景音乐ID
	const MUSIC_ID_S 						= 9;					// 辅背景音乐ID
	
	const GUILD_WAR_RANK_16 				= 16;
	const GUILD_WAR_RANK_8 					= 8;
	const GUILD_WAR_RANK_4 					= 4;
	const GUILD_WAR_RANK_2 					= 2;
	const GUILD_WAR_RANK_1 					= 1;
	
	public static $ValidRank = array
	(
			self::GUILD_WAR_RANK_16,
			self::GUILD_WAR_RANK_8,
			self::GUILD_WAR_RANK_4,
			self::GUILD_WAR_RANK_2,
			self::GUILD_WAR_RANK_1,
	);
	
	public static $Rank2PrizeIndex = array
	(
			self::GUILD_WAR_RANK_16 => 0,
			self::GUILD_WAR_RANK_8 => 1,
			self::GUILD_WAR_RANK_4 => 2,
			self::GUILD_WAR_RANK_2 => 3,
			self::GUILD_WAR_RANK_1 => 4,
	);
	
	
	public static $next_rank = array(			// 每个排名的下一个排名是啥
			0 => 16,
			16 => 8,
			8 => 4,
			4 => 2,
			2 => 1,
	);
	
	public static $all_rank = array(			// 所有阶段对应的排名
			GuildWarRound::AUDITION => 16,
			GuildWarRound::ADVANCED_16 => 8,
			GuildWarRound::ADVANCED_8  => 4,
			GuildWarRound::ADVANCED_4  => 2,
			GuildWarRound::ADVANCED_2  => 1,
	);
	
	public static $round_rank = array(			// 每个阶段对应的最大排名
			GuildWarRound::ADVANCED_16 => 16,
			GuildWarRound::ADVANCED_8  => 8,
			GuildWarRound::ADVANCED_4  => 4,
			GuildWarRound::ADVANCED_2  => 2,
	);
	
	public static $step = array(				// 跳几个人获取战斗对象
			GuildWarRound::ADVANCED_16 => 2,
			GuildWarRound::ADVANCED_8  => 4,
			GuildWarRound::ADVANCED_4  => 8,
			GuildWarRound::ADVANCED_2  => 16,
	);
	
	const CHECK_TYPE_UPD_FMT					= 1;
	const CHECK_TYPE_CLEAR_UPD_CD				= 2;
	const CHECK_TYPE_BUY_MAX_WIN				= 3;
	const CHECK_TYPE_GET_MEMBER_LIST			= 4;
	const CHECK_TYPE_CHANGE_CANDIDATE			= 5;
	const CHECK_TYPE_GET_MY_TEAM_INFO			= 6;
	const CHECK_TYPE_GET_GUILD_WAR_INFO			= 7;
	const CHECK_TYPE_GET_HISTORY_CHEER_INFO		= 8;
	const CHECK_TYPE_CHEER						= 9;
	const CHECK_TYPE_GET_TEMPLE_INFO			= 10;
	const CHECK_TYPE_WORSHIP					= 11;
	const CHECK_TYPE_GET_HISTORY_FIGHT_INFO		= 12;
	const CHECK_TYPE_GET_REPLAY					= 13;
	const CHECK_TYPE_SEND_CHEER_REWARD			= 14;
	
	public static $checkType = array
	(
			'checkInSession' => array
			(
					self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_CLEAR_UPD_CD,
					self::CHECK_TYPE_BUY_MAX_WIN,
					self::CHECK_TYPE_GET_MEMBER_LIST,
					self::CHECK_TYPE_CHANGE_CANDIDATE,
					self::CHECK_TYPE_GET_MY_TEAM_INFO,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO,
					self::CHECK_TYPE_CHEER,
					self::CHECK_TYPE_GET_TEMPLE_INFO,
					self::CHECK_TYPE_WORSHIP,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO,
					self::CHECK_TYPE_GET_REPLAY,
					self::CHECK_TYPE_SEND_CHEER_REWARD,
			),
			'checkInTeam' => array
			(
					self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_CLEAR_UPD_CD,
					self::CHECK_TYPE_BUY_MAX_WIN,
					self::CHECK_TYPE_GET_MEMBER_LIST,
					self::CHECK_TYPE_CHANGE_CANDIDATE,
					self::CHECK_TYPE_GET_MY_TEAM_INFO,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO,
					self::CHECK_TYPE_CHEER,
					self::CHECK_TYPE_GET_TEMPLE_INFO,
					self::CHECK_TYPE_WORSHIP,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO,
					self::CHECK_TYPE_GET_REPLAY,
					self::CHECK_TYPE_SEND_CHEER_REWARD,
			),
			'checkInGuild' => array
			(
					self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_CLEAR_UPD_CD,
					self::CHECK_TYPE_BUY_MAX_WIN,
					self::CHECK_TYPE_GET_MEMBER_LIST,
					self::CHECK_TYPE_CHANGE_CANDIDATE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO,
			),
			'checkIsSignUp' => array
			(
					self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_CLEAR_UPD_CD,
					self::CHECK_TYPE_BUY_MAX_WIN,
					self::CHECK_TYPE_GET_MEMBER_LIST,
					self::CHECK_TYPE_CHANGE_CANDIDATE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO,
			),
			'checkCdAfterSignUp' => array
			(
					self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_CLEAR_UPD_CD,
					self::CHECK_TYPE_BUY_MAX_WIN,
					self::CHECK_TYPE_GET_MEMBER_LIST,
					self::CHECK_TYPE_CHANGE_CANDIDATE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO,
			),
			
			'checkInCandidates' => array
			(
					//self::CHECK_TYPE_UPD_FMT,
					self::CHECK_TYPE_BUY_MAX_WIN,
			),
			
			'checkIsArmed' => array
			(
					self::CHECK_TYPE_BUY_MAX_WIN,
			),
			
			'checkIsPresident' => array
			(
					self::CHECK_TYPE_CHANGE_CANDIDATE,
			),
	);
	
	public static $checkStage = array
	(
			'BetweenSignUp' => array	// 报名阶段
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => FALSE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => FALSE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => FALSE,
					self::CHECK_TYPE_GET_REPLAY => FALSE,
			),
			'BeforeAuditionStart' => array // 报名结束 - 海选赛开始 
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => FALSE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => FALSE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => FALSE,
					self::CHECK_TYPE_GET_REPLAY => FALSE,
			),
			'BetweenAudition' => array // 海选赛比赛期间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => FALSE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => FALSE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => FALSE,
			),
			'BeforeAuditionEnd' => array // 海选赛比赛完毕时间 - 海选赛正式结束时间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => FALSE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => FALSE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => FALSE,
			),
			'BeforeAdvancedStart' => array // 海选赛正式结束时间 - 晋级赛开始时间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => TRUE,		
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => TRUE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => TRUE,
					self::CHECK_TYPE_CHEER => TRUE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => TRUE,
			),
			'BetweenSubRound' => array	// 晋级赛小组比赛期间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => FALSE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => TRUE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => TRUE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => TRUE,
			),
			'BeforeNextSubRound' => array // 晋级赛小组比赛结结束 - 下个小组赛开始
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => FALSE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => TRUE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => TRUE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => TRUE,
			),
			'BeforeAdvancedEnd' => array // 本轮晋级赛比赛完毕 - 本轮晋级赛正式结束时间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => FALSE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => FALSE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => TRUE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => TRUE,
					self::CHECK_TYPE_CHEER => FALSE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => FALSE,
					self::CHECK_TYPE_WORSHIP => FALSE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => TRUE,
			),
			'BeforeNextAdvancedStart' => array // 本轮晋级赛正式结束时间 - 下轮晋级赛开始时间
			(
					self::CHECK_TYPE_BUY_MAX_WIN => TRUE,
					self::CHECK_TYPE_CHANGE_CANDIDATE => TRUE,
					self::CHECK_TYPE_GET_GUILD_WAR_INFO => TRUE,
					self::CHECK_TYPE_GET_HISTORY_CHEER_INFO => TRUE,
					self::CHECK_TYPE_CHEER => TRUE,
					self::CHECK_TYPE_GET_TEMPLE_INFO => TRUE,
					self::CHECK_TYPE_WORSHIP => TRUE,
					self::CHECK_TYPE_GET_HISTORY_FIGHT_INFO => TRUE,
					self::CHECK_TYPE_GET_REPLAY => TRUE,
			),
	);
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */